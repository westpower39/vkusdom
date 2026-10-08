<?php

namespace Westpower\Mobile\Service;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\OrderStatus;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Exception\ApiException;

/**
 * Order history of the user (TZ 4.7): list, detail, repeat, online payment link.
 */
final class OrderService
{
	public function __construct()
	{
		Loader::includeModule('sale');
		Loader::includeModule('catalog');
	}

	public function list(AuthContext $context, int $page, int $limit): array
	{
		$filter = ['=USER_ID' => $context->userId, '=LID' => Config::siteId()];
		$total = (int)Order::getList(['filter' => $filter, 'select' => ['CNT'], 'runtime' => [
			new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(*)'),
		]])->fetch()['CNT'];

		$items = [];
		$rows = Order::getList([
			'filter' => $filter,
			'select' => ['ID'],
			'order' => ['ID' => 'DESC'],
			'limit' => $limit,
			'offset' => ($page - 1) * $limit,
		]);
		while ($row = $rows->fetch())
		{
			$order = Order::load($row['ID']);
			if ($order)
			{
				$items[] = $this->toArray($order);
			}
		}

		return ['items' => $items, 'pagination' => Pagination::make($page, $limit, $total)];
	}

	public function get(AuthContext $context, int $id): array
	{
		return $this->toArray($this->load($context, $id));
	}

	/**
	 * Adds the order items to the cart (TZ 4.7 "повторный заказ") and returns the cart.
	 */
	public function repeat(AuthContext $context, int $id): array
	{
		$order = $this->load($context, $id);
		$cart = new CartService();
		$quantities = [];
		foreach ((new CartService())->get($context)['items'] as $item)
		{
			$quantities[$item['product']['id']] = $item['quantity'];
		}

		$warnings = [];
		foreach ($order->getBasket() as $basketItem)
		{
			$productId = (int)$basketItem->getProductId();
			try
			{
				$cart->setQuantity($context, $productId, ($quantities[$productId] ?? 0) + (float)$basketItem->getQuantity());
			}
			catch (ApiException $e)
			{
				$warnings[$productId][] = ['code' => 'not-added', 'message' => 'Не удалось добавить: ' . $e->getMessage()];
			}
		}

		return $cart->get($context, $warnings);
	}

	/**
	 * Payment URL of the online pay system of the order (standard initiatePay of the pay system handler).
	 */
	public function payment(AuthContext $context, int $id): array
	{
		$order = $this->load($context, $id);
		if ($order->isCanceled() || $order->isPaid())
		{
			throw ApiException::validation('Заказ не требует оплаты', 'id');
		}

		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->isPaid() || !CheckoutService::isOnlinePaySystem((int)$payment->getPaymentSystemId()))
			{
				continue;
			}

			$service = PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
			$result = $service ? $service->initiatePay($payment, null, PaySystem\BaseServiceHandler::STRING) : null;
			$url = $result && $result->isSuccess() ? (string)$result->getPaymentUrl() : '';
			if ($url === '')
			{
				AddMessage2Log('initiatePay failed for order ' . $id . ': ' . ($result ? implode('; ', $result->getErrorMessages()) : 'no service'), Config::MODULE_ID);
				throw ApiException::unavailable('Не удалось получить ссылку на оплату, попробуйте позже');
			}

			return ['url' => $url];
		}

		throw ApiException::validation('У заказа нет онлайн-оплаты', 'id');
	}

	public function toArray(Order $order): array
	{
		$currency = (string)$order->getCurrency();
		$basket = $order->getBasket();

		$images = [];
		$productIds = [];
		foreach ($basket as $item)
		{
			$productIds[] = (int)$item->getProductId();
		}
		if (!empty($productIds))
		{
			$result = \CIBlockElement::GetList([], ['ID' => $productIds], false, false, ['ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']);
			while ($row = $result->Fetch())
			{
				$images[(int)$row['ID']] = Formatter::image($row['PREVIEW_PICTURE'] ?: $row['DETAIL_PICTURE']);
			}
		}

		$items = [];
		$quantity = 0.0;
		foreach ($basket as $item)
		{
			$itemQuantity = (float)$item->getQuantity();
			$quantity += $itemQuantity;
			$items[] = [
				'product' => [
					'id' => (int)$item->getProductId(),
					'name' => (string)$item->getField('NAME'),
					'image' => $images[(int)$item->getProductId()] ?? null,
				],
				'quantity' => $itemQuantity,
				'total' => Formatter::price((float)$item->getPrice() * $itemQuantity, (float)$item->getBasePrice() * $itemQuantity, $currency),
			];
		}

		$method = null;
		$pickupPoint = null;
		foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
		{
			$siteMethod = SiteData::methodByDeliveryId((int)$shipment->getDeliveryId());
			if ($siteMethod)
			{
				$method = ['code' => $siteMethod['code'], 'name' => $siteMethod['name']];
			}
			$storeId = (int)$shipment->getStoreId();
			if ($storeId > 0)
			{
				$store = StoreTable::getList(['filter' => ['=ID' => $storeId], 'select' => ['ID', 'TITLE', 'ADDRESS']])->fetch();
				if ($store)
				{
					$pickupPoint = ['id' => (int)$store['ID'], 'name' => (string)$store['TITLE'], 'address' => (string)$store['ADDRESS']];
				}
			}
		}

		$paymentMethod = null;
		$isOnline = false;
		foreach ($order->getPaymentCollection() as $payment)
		{
			$paymentMethod = ['id' => (int)$payment->getPaymentSystemId(), 'name' => (string)$payment->getPaymentSystemName()];
			$isOnline = CheckoutService::isOnlinePaySystem((int)$payment->getPaymentSystemId());
		}

		$properties = [];
		foreach ($order->getPropertyCollection() as $property)
		{
			$properties[(string)$property->getField('CODE')] = $property->getValue();
		}
		$address = trim((string)($properties[PropertyCode::ORDER_DELIVERY_ADDRESS] ?? ''));
		$deliveryTime = trim((string)($properties[PropertyCode::ORDER_DELIVERY_TIME] ?? ''));

		$weight = (int)round((float)$basket->getWeight());
		$status = $this->status($order);

		return [
			'id' => (int)$order->getId(),
			'number' => (string)$order->getField('ACCOUNT_NUMBER'),
			'createdAt' => Formatter::dateTime($order->getDateInsert()),
			'status' => ['code' => $status, 'name' => OrderStatus::NAMES[$status] ?? $status],
			'method' => $method,
			'pickupPoint' => $pickupPoint,
			'address' => $address !== '' ? ['text' => $address] : null,
			'deliveryTime' => $deliveryTime !== '' ? $deliveryTime : null,
			'items' => $items,
			'summary' => [
				'itemsCount' => count($items),
				'quantity' => $quantity,
				'weight' => $weight > 0 ? $weight : null,
				'itemsTotal' => Formatter::price((float)$basket->getPrice(), (float)$basket->getBasePrice(), $currency),
				'delivery' => Formatter::price((float)$order->getDeliveryPrice(), null, $currency),
				'total' => Formatter::price((float)$order->getPrice(), null, $currency),
			],
			'payment' => [
				'method' => $paymentMethod,
				'isPaid' => $order->isPaid(),
				'isOnline' => $isOnline,
			],
			'canPay' => $isOnline && !$order->isPaid() && !$order->isCanceled(),
		];
	}

	private function status(Order $order): string
	{
		if ($order->isCanceled())
		{
			return OrderStatus::CANCELLED;
		}

		return Config::orderStatusMap()[(string)$order->getField('STATUS_ID')] ?? OrderStatus::ACCEPTED;
	}

	private function load(AuthContext $context, int $id): Order
	{
		$order = Order::load($id);
		if (!$order || (int)$order->getUserId() !== (int)$context->userId)
		{
			throw ApiException::notFound('Заказ не найден');
		}

		return $order;
	}
}
