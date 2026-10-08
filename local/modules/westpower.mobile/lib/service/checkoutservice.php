<?php

namespace Westpower\Mobile\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\OrderPropsVariantTable;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Internals\UserPropsTable;
use Bitrix\Sale\Internals\UserPropsValueTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Shipment;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Auth\PhoneAuthService;
use Westpower\Mobile\Auth\TokenService;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Exception\ApiException;

/**
 * Checkout through the standard sale API: the order is assembled from the session cart with the delivery
 * service of the chosen obtaining method (site HL MethodObtaining). Delivery price, payment restrictions,
 * slots rules and order numbering are the site's responsibility — the API only uses them.
 */
final class CheckoutService
{
	// Pay system type (IS_CASH) "acquiring operation": paid online in the app
	private const PAY_SYSTEM_TYPE_ACQUIRING = 'A';

	public function __construct()
	{
		Loader::includeModule('sale');
		Loader::includeModule('catalog');
	}

	public function options(AuthContext $context): array
	{
		$methods = [];
		foreach (SiteData::methods() as $method)
		{
			$methods[] = ['code' => $method['code'], 'name' => $method['name']];
		}

		return [
			'methods' => $methods,
			'pickupPoints' => array_values(SiteData::pickupPoints()),
			'replacementOptions' => $this->replacementOptions(),
			'orderProfiles' => $context->isUser() ? (new OrderProfileService())->list($context)['items'] : [],
		];
	}

	/**
	 * Slots for the next days of the obtaining method.
	 * Availability (limit of orders per slot, time already passed) is awaiting the site slot service:
	 * until then every slot is returned as available.
	 */
	public function slots(string $methodCode): array
	{
		$method = SiteData::method($methodCode);
		if ($method === null)
		{
			throw ApiException::validation('Неизвестный способ получения', 'method');
		}

		$slots = SiteData::slots($method['id']);
		$days = [];
		$start = strtotime('today');
		for ($day = 0; $day < max(1, $method['daysCount']); $day++)
		{
			$timestamp = $start + $day * 86400;
			$items = [];
			foreach ($slots as $slot)
			{
				[$from, $to] = array_pad(array_map('trim', preg_split('/\s*[-–—]\s*/u', $slot['name'])), 2, null);
				$items[] = [
					'id' => $slot['id'],
					'name' => $from && $to ? $from . '–' . $to : $slot['name'],
					'from' => $from,
					'to' => $to,
					'isAvailable' => true,
				];
			}
			$days[] = ['date' => date('Y-m-d', $timestamp), 'slots' => $items];
		}

		return ['items' => $days];
	}

	public function calculation(AuthContext $context, array $input): array
	{
		[$order, $shipment] = $this->build($context, $input);

		$calculation = $shipment->calculateDelivery();
		$quote = $this->deliveryQuote($calculation, $order);

		$payment = $order->getPaymentCollection()->createItem();
		$payment->setField('SUM', $order->getPrice());
		$paymentMethods = [];
		$innerId = (int)PaySystem\Manager::getInnerPaySystemId();
		foreach (PaySystem\Manager::getListWithRestrictions($payment) as $paySystem)
		{
			if ((int)$paySystem['ID'] === $innerId)
			{
				continue;
			}
			$paymentMethods[] = [
				'id' => (int)$paySystem['ID'],
				'name' => (string)$paySystem['NAME'],
				'isOnline' => self::isOnlinePaySystem((int)$paySystem['ID']),
			];
		}

		return [
			'delivery' => $quote,
			'paymentMethods' => $paymentMethods,
			'summary' => $this->summary($order),
		];
	}

	public function create(AuthContext $context, array $input): array
	{
		[$order, $shipment] = $this->build($context, $input);

		$paySystemId = (int)($input['paymentMethodId'] ?? 0);
		if ($paySystemId <= 0)
		{
			throw ApiException::validation('Выберите способ оплаты', 'paymentMethodId');
		}

		$calculation = $shipment->calculateDelivery();
		if (!$calculation->isSuccess())
		{
			throw ApiException::validation(implode('; ', $calculation->getErrorMessages()) ?: 'Доставка по этому адресу недоступна', 'address');
		}

		$payment = $order->getPaymentCollection()->createItem();
		$payment->setField('SUM', $order->getPrice());
		$allowed = array_map('intval', array_column(PaySystem\Manager::getListWithRestrictions($payment), 'ID'));
		if (!in_array($paySystemId, $allowed, true))
		{
			throw ApiException::validation('Этот способ оплаты недоступен', 'paymentMethodId');
		}
		$payment->delete();

		$payment = $order->getPaymentCollection()->createItem(PaySystem\Manager::getObjectById($paySystemId));
		$payment->setField('SUM', $order->getPrice());
		$payment->setField('CURRENCY', $order->getCurrency());

		$result = $order->save();
		if (!$result->isSuccess())
		{
			throw ApiException::validation(implode('; ', $result->getErrorMessages()), 'order');
		}

		TokenService::setCoupons((int)$context->sessionId, []);

		return (new OrderService())->toArray(Order::load($order->getId()));
	}

	/**
	 * Order (not saved) with the session cart, person type, delivery and properties.
	 *
	 * @return array{0: Order, 1: Shipment}
	 */
	private function build(AuthContext $context, array $input): array
	{
		if ($context->fuserId === null)
		{
			throw \Westpower\Mobile\Exception\ApiException::unauthorized('Для оформления нужен токен');
		}

		$methodCode = (string)($input['method'] ?? '');
		$method = SiteData::method($methodCode);
		if ($method === null)
		{
			throw ApiException::validation('Выберите способ получения', 'method');
		}

		$profile = $this->profile($context, $input['orderProfileId'] ?? null);
		$personTypeId = $profile ? (int)$profile['PERSON_TYPE_ID'] : EntityResolver::personTypeId(EntityCode::PERSON_TYPE_INDIVIDUAL);
		$deliveryId = $method['deliveries'][$personTypeId] ?? null;
		if ($deliveryId === null)
		{
			throw ApiException::unavailable('Способ получения не настроен для этого типа покупателя');
		}

		$basket = Basket::loadItemsForFUser($context->fuserId, Config::siteId())->getOrderableItems();
		if ($basket->isEmpty())
		{
			throw ApiException::validation('Корзина пуста', 'cart');
		}

		(new CartService())->applyCoupons($context);

		$order = Order::create(Config::siteId(), $context->userId);
		$order->setPersonTypeId($personTypeId);
		$order->setBasket($basket);

		$shipment = $order->getShipmentCollection()->createItem(Delivery\Services\Manager::getObjectById($deliveryId));
		$shipmentItems = $shipment->getShipmentItemCollection();
		foreach ($order->getBasket() as $basketItem)
		{
			$shipmentItems->createItem($basketItem)->setQuantity($basketItem->getQuantity());
		}

		if ($methodCode === 'pickup')
		{
			$pointId = (int)($input['pickupPointId'] ?? 0);
			$points = SiteData::pickupPoints();
			if (!isset($points[$pointId]))
			{
				throw ApiException::validation('Выберите точку самовывоза', 'pickupPointId');
			}
			// Kept by sale only if the store is attached to the pickup delivery service (site settings).
			$shipment->setStoreId($pointId);
		}
		else
		{
			$address = $input['address'] ?? null;
			if (!is_array($address) || trim((string)($address['text'] ?? '')) === '')
			{
				throw ApiException::validation('Укажите адрес доставки', 'address');
			}
			$values = [
				PropertyCode::ORDER_DELIVERY_ADDRESS => trim((string)$address['text']),
				PropertyCode::ORDER_DELIVERY_LATITUDE => $address['latitude'] ?? null,
				PropertyCode::ORDER_DELIVERY_LONGITUDE => $address['longitude'] ?? null,
				PropertyCode::ORDER_DELIVERY_APARTMENT => $address['apartment'] ?? null,
				PropertyCode::ORDER_DELIVERY_ENTRANCE => $address['entrance'] ?? null,
				PropertyCode::ORDER_DELIVERY_FLOOR => $address['floor'] ?? null,
				PropertyCode::ORDER_DELIVERY_HAS_ELEVATOR => $this->flag($address['hasElevator'] ?? null),
				PropertyCode::ORDER_DELIVERY_HAS_FREIGHT_ELEVATOR => $this->flag($address['hasFreightElevator'] ?? null),
			];
			foreach ($values as $code => $value)
			{
				if ($value !== null && $value !== '')
				{
					$this->setProperty($order, $code, $value);
				}
			}
		}

		if (!empty($input['slot']) && is_array($input['slot']))
		{
			$slotValue = $this->slotValue($method['id'], (string)($input['slot']['date'] ?? ''), (int)($input['slot']['id'] ?? 0));
			$this->setProperty($order, PropertyCode::ORDER_DELIVERY_TIME, $slotValue);
		}

		if (!empty($input['replacement']))
		{
			$this->setProperty($order, PropertyCode::ORDER_REPLACEMENT, [(string)$input['replacement']]);
		}

		$this->fillContacts($order, $context, $profile);

		$comment = trim((string)($input['comment'] ?? ''));
		if ($comment !== '')
		{
			$order->setField('USER_DESCRIPTION', $comment);
		}

		$order->doFinalAction(true);

		return [$order, $shipment];
	}

	/**
	 * DeliveryQuote from the delivery service result. Details come from the delivery module (integration contract).
	 */
	private function deliveryQuote(Delivery\CalculationResult $result, Order $order): array
	{
		$data = (array)$result->getData();
		$currency = (string)$order->getCurrency();

		if (!$result->isSuccess())
		{
			return [
				'isAvailable' => false,
				'reason' => ['code' => 'delivery-unavailable', 'message' => implode('; ', $result->getErrorMessages()) ?: 'Доставка недоступна'],
				'price' => Formatter::price(0.0, null, $currency),
				'isFree' => false,
				'freeDelivery' => null,
				'components' => [],
				'details' => ['distance' => null, 'weight' => (int)round((float)$order->getBasket()->getWeight()), 'transport' => null, 'weather' => null],
			];
		}

		$price = (float)$result->getDeliveryPrice();
		$before = isset($data['priceBeforeDiscount']) ? (float)$data['priceBeforeDiscount'] : null;

		$freeDelivery = null;
		if (!empty($data['freeDelivery']['isEnabled']) && isset($data['freeDelivery']['threshold']))
		{
			$threshold = (float)$data['freeDelivery']['threshold'];
			$freeDelivery = [
				'threshold' => Formatter::money($threshold),
				'amountLeft' => Formatter::money(max(0.0, $threshold - (float)$order->getBasket()->getPrice())),
			];
		}

		$components = [];
		foreach ((array)($data['components'] ?? []) as $component)
		{
			$components[] = [
				'code' => (string)($component['code'] ?? ''),
				'name' => (string)($component['name'] ?? ''),
				'amount' => Formatter::money((float)($component['amount'] ?? 0)),
				'isCoveredByFree' => (bool)($component['isCoveredByFree'] ?? false),
			];
		}

		return [
			'isAvailable' => true,
			'reason' => null,
			'price' => Formatter::price($price, $before, $currency),
			'isFree' => $price < 0.01,
			'freeDelivery' => $freeDelivery,
			'components' => $components,
			'details' => [
				'distance' => isset($data['distance']) ? (int)$data['distance'] : null,
				'weight' => isset($data['weight']) ? (int)$data['weight'] : (int)round((float)$order->getBasket()->getWeight()),
				'transport' => isset($data['transport']['code']) ? ['code' => (string)$data['transport']['code'], 'name' => (string)($data['transport']['name'] ?? '')] : null,
				'weather' => isset($data['weather']['code']) ? [
					'code' => (string)$data['weather']['code'],
					'name' => (string)($data['weather']['name'] ?? ''),
					'coefficient' => (float)($data['weather']['coefficient'] ?? 1),
				] : null,
			],
		];
	}

	private function summary(Order $order): array
	{
		$basket = $order->getBasket();
		$currency = (string)$order->getCurrency();
		$delivery = (float)$order->getDeliveryPrice();

		return [
			'itemsTotal' => Formatter::price((float)$basket->getPrice(), (float)$basket->getBasePrice(), $currency),
			'delivery' => Formatter::price($delivery, null, $currency),
			'total' => Formatter::price((float)$order->getPrice(), null, $currency),
		];
	}

	private function replacementOptions(): array
	{
		$property = OrderPropsTable::getList([
			'filter' => ['=CODE' => PropertyCode::ORDER_REPLACEMENT, '=ACTIVE' => 'Y'],
			'select' => ['ID'],
			'limit' => 1,
		])->fetch();
		if (!$property)
		{
			return [];
		}

		$options = [];
		$rows = OrderPropsVariantTable::getList([
			'filter' => ['=ORDER_PROPS_ID' => $property['ID']],
			'select' => ['VALUE', 'NAME'],
			'order' => ['SORT' => 'ASC'],
		]);
		while ($row = $rows->fetch())
		{
			$options[] = ['code' => (string)$row['VALUE'], 'name' => (string)$row['NAME']];
		}

		return $options;
	}

	private function profile(AuthContext $context, $profileId): ?array
	{
		if ($profileId === null || $profileId === '')
		{
			return null;
		}
		if (!$context->isUser())
		{
			throw ApiException::unauthorized();
		}

		$profile = UserPropsTable::getList([
			'filter' => ['=ID' => (int)$profileId, '=USER_ID' => $context->userId],
			'select' => ['ID', 'PERSON_TYPE_ID', 'NAME'],
		])->fetch();
		if (!$profile)
		{
			throw ApiException::validation('Профиль заказа не найден', 'orderProfileId');
		}

		return $profile;
	}

	private function fillContacts(Order $order, AuthContext $context, ?array $profile): void
	{
		$properties = $order->getPropertyCollection();

		if ($profile)
		{
			$values = UserPropsValueTable::getList([
				'filter' => ['=USER_PROPS_ID' => $profile['ID']],
				'select' => ['ORDER_PROPS_ID', 'VALUE'],
			]);
			while ($value = $values->fetch())
			{
				$item = $properties->getItemByOrderPropertyId((int)$value['ORDER_PROPS_ID']);
				if ($item && ($item->getValue() === null || $item->getValue() === ''))
				{
					$item->setValue($value['VALUE']);
				}
			}
		}

		if ($context->isUser())
		{
			$user = UserTable::getList(['filter' => ['=ID' => $context->userId], 'select' => ['NAME', 'LAST_NAME', 'EMAIL']])->fetch();
			$this->setPropertyIfEmpty($order, PropertyCode::ORDER_FIO, trim(($user['NAME'] ?? '') . ' ' . ($user['LAST_NAME'] ?? '')));
			$this->setPropertyIfEmpty($order, PropertyCode::ORDER_EMAIL, (string)($user['EMAIL'] ?? ''));
			$this->setPropertyIfEmpty($order, PropertyCode::ORDER_PHONE, (string)(new PhoneAuthService())->phone((int)$context->userId));
		}
	}

	/**
	 * Order properties are site settings: a property that does not exist is skipped.
	 */
	private function setProperty(Order $order, string $code, $value): void
	{
		foreach ($order->getPropertyCollection() as $property)
		{
			if ($property->getField('CODE') === $code)
			{
				$property->setValue($value);

				return;
			}
		}
	}

	private function setPropertyIfEmpty(Order $order, string $code, string $value): void
	{
		if ($value === '')
		{
			return;
		}
		foreach ($order->getPropertyCollection() as $property)
		{
			if ($property->getField('CODE') === $code && ($property->getValue() === null || $property->getValue() === ''))
			{
				$property->setValue($value);
			}
		}
	}

	/**
	 * Slot value saved to the order property. The format is a stub until the site defines it (awaiting the site).
	 */
	private function slotValue(int $methodId, string $date, int $slotId): string
	{
		$timestamp = strtotime($date);
		if (!$timestamp || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
		{
			throw ApiException::validation('Дата слота в формате ГГГГ-ММ-ДД', 'slot.date');
		}
		foreach (SiteData::slots($methodId) as $slot)
		{
			if ($slot['id'] === $slotId)
			{
				return date('d.m.Y', $timestamp) . ' ' . $slot['name'];
			}
		}

		throw ApiException::validation('Слот не найден', 'slot.id');
	}

	private function flag($value): ?string
	{
		if ($value === null)
		{
			return null;
		}

		return $value ? 'Y' : 'N';
	}

	/**
	 * Online = the pay system type is "acquiring operation" (standard pay system field IS_CASH = A).
	 */
	public static function isOnlinePaySystem(int $paySystemId): bool
	{
		$row = PaySystemActionTable::getList(['filter' => ['=ID' => $paySystemId], 'select' => ['IS_CASH']])->fetch();

		return $row && $row['IS_CASH'] === self::PAY_SYSTEM_TYPE_ACQUIRING;
	}
}
