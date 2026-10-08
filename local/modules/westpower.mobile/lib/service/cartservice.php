<?php

namespace Westpower\Mobile\Service;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Discount;
use Bitrix\Sale\DiscountCouponsManager;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Auth\TokenService;
use Westpower\Mobile\Dictionary\ErrorCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Catalog\ProductCardBuilder;
use Westpower\Mobile\Service\Catalog\ProductRepository;

/**
 * Cart of the session buyer (FUSER) through the standard sale API: basket, refresh, discounts, coupons.
 * Coupons are kept with the app session because the standard coupon storage is the PHP session.
 */
final class CartService
{
	public function __construct()
	{
		Loader::includeModule('sale');
		Loader::includeModule('catalog');
		Loader::includeModule('currency');
	}

	public function get(AuthContext $context, array $extraWarnings = []): array
	{
		$basket = $this->load($context);

		$before = [];
		foreach ($this->items($basket) as $item)
		{
			$before[$item->getProductId()] = ['price' => (float)$item->getPrice(), 'canBuy' => $item->canBuy()];
		}

		$refresh = $basket->refresh();
		if ($refresh->isSuccess())
		{
			$basket->save();
		}

		$coupons = $this->applyCoupons($context);
		$prices = $this->discountPrices($basket, $context);
		$items = $this->items($basket);
		$cards = (new ProductCardBuilder())->build(array_map(static fn (BasketItem $item) => $item->getProductId(), $items), $context);

		$cartItems = [];
		$sumCurrent = 0.0;
		$sumBase = 0.0;
		$quantity = 0.0;
		$hasUnavailable = false;
		$currency = CurrencyManager::getBaseCurrency();

		foreach ($items as $item)
		{
			$productId = (int)$item->getProductId();
			$code = $item->getBasketCode();
			$price = (float)($prices[$code]['PRICE'] ?? $item->getPrice());
			$basePrice = (float)($prices[$code]['BASE_PRICE'] ?? $item->getBasePrice());
			$itemQuantity = (float)$item->getQuantity();
			$currency = (string)$item->getCurrency() ?: $currency;

			$warnings = $extraWarnings[$productId] ?? [];
			if (isset($before[$productId]) && abs($before[$productId]['price'] - (float)$item->getPrice()) >= 0.01)
			{
				$warnings[] = [
					'code' => 'price-changed',
					'message' => sprintf('Цена изменилась: было %s ₽, стало %s ₽', $this->money($before[$productId]['price']), $this->money((float)$item->getPrice())),
				];
			}

			$card = $cards[$productId] ?? null;
			$isAvailable = $item->canBuy() && ($card['stock']['isAvailable'] ?? false);
			if (!$isAvailable)
			{
				$hasUnavailable = true;
				if (!in_array('out-of-stock', array_column($warnings, 'code'), true))
				{
					$warnings[] = ['code' => 'out-of-stock', 'message' => 'Товар закончился'];
				}
			}

			$cartItems[] = [
				'product' => $card ?? $this->fallbackCard($item),
				'quantity' => $itemQuantity,
				'total' => Formatter::price($price * $itemQuantity, $basePrice * $itemQuantity, $currency),
				'warnings' => $warnings,
			];

			$sumCurrent += $price * $itemQuantity;
			$sumBase += $basePrice * $itemQuantity;
			$quantity += $itemQuantity;
		}

		$reasons = [];
		if (empty($cartItems))
		{
			$reasons[] = ['code' => 'empty-cart', 'message' => 'Корзина пуста'];
		}
		if ($hasUnavailable)
		{
			$reasons[] = ['code' => 'has-unavailable-items', 'message' => 'Удалите из корзины товары, которых нет в наличии'];
		}

		$weight = (int)round((float)$basket->getWeight());

		return [
			'items' => $cartItems,
			'promoCodes' => $coupons,
			'summary' => [
				'itemsCount' => count($cartItems),
				'quantity' => $quantity,
				'weight' => $weight > 0 ? $weight : null,
				'total' => Formatter::price($sumCurrent, $sumBase, $currency),
			],
			'checkout' => [
				'isAvailable' => empty($reasons),
				'reasons' => $reasons,
			],
		];
	}

	public function setQuantity(AuthContext $context, int $productId, $quantity): array
	{
		if (!is_numeric($quantity) || (float)$quantity < 0)
		{
			throw ApiException::validation('Количество должно быть числом не меньше 0', 'quantity');
		}
		$quantity = (float)$quantity;
		if ($quantity <= 0)
		{
			return $this->remove($context, $productId);
		}

		(new ProductRepository())->getActiveRef($productId);
		$info = (new ProductCardBuilder())->stockInfo([$productId])[$productId] ?? null;
		if ($info === null)
		{
			throw ApiException::notFound('Товар не найден');
		}

		$ratio = $info['ratio'] > 0 ? $info['ratio'] : 1.0;
		$steps = $quantity / $ratio;
		if (abs($steps - round($steps)) > 0.0001)
		{
			throw ApiException::validation(sprintf('Количество должно быть кратно %s', $this->number($ratio)), 'quantity');
		}

		$warnings = [];
		if ($info['traced'] && !$info['canBuyZero'] && $quantity > $info['quantity'])
		{
			$quantity = floor($info['quantity'] / $ratio + 0.0001) * $ratio;
			if ($quantity <= 0)
			{
				throw new ApiException(ErrorCode::VALIDATION_ERROR, 'Товар закончился', ['field' => 'quantity']);
			}
			$warnings[] = [
				'code' => 'quantity-reduced',
				'message' => sprintf('В наличии только %s %s', $this->number($quantity), $info['measure']),
			];
		}

		$basket = $this->load($context);
		$item = $basket->getExistsItem('catalog', $productId);
		if ($item)
		{
			$result = $item->setField('QUANTITY', $quantity);
		}
		else
		{
			$item = $basket->createItem('catalog', $productId);
			$result = $item->setFields([
				'QUANTITY' => $quantity,
				'CURRENCY' => CurrencyManager::getBaseCurrency(),
				'LID' => \Westpower\Mobile\Config::siteId(),
				'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\Basket::getDefaultProviderName(),
			]);
		}
		if (!$result->isSuccess())
		{
			throw ApiException::validation(implode('; ', $result->getErrorMessages()), 'quantity');
		}

		$this->save($basket);

		return $this->get($context, $warnings ? [$productId => $warnings] : []);
	}

	public function remove(AuthContext $context, int $productId): array
	{
		$basket = $this->load($context);
		foreach ($this->items($basket) as $item)
		{
			if ((int)$item->getProductId() === $productId)
			{
				$item->delete();
			}
		}
		$this->save($basket);

		return $this->get($context);
	}

	public function clear(AuthContext $context): array
	{
		$basket = $this->load($context);
		foreach ($this->items($basket) as $item)
		{
			$item->delete();
		}
		$this->save($basket);

		return $this->get($context);
	}

	public function addPromoCode(AuthContext $context, string $code): array
	{
		$code = trim($code);
		if ($code === '')
		{
			throw ApiException::validation('Введите промокод', 'code');
		}

		$this->initCoupons($context, []);
		$data = DiscountCouponsManager::getData($code, true);
		$status = is_array($data) ? (int)($data['STATUS'] ?? DiscountCouponsManager::STATUS_NOT_FOUND) : DiscountCouponsManager::STATUS_NOT_FOUND;
		if (in_array($status, [DiscountCouponsManager::STATUS_NOT_FOUND, DiscountCouponsManager::STATUS_FREEZE], true))
		{
			throw new ApiException(ErrorCode::PROMO_CODE_INVALID, 'Промокод не найден или срок его действия истёк', ['field' => 'code']);
		}

		$codes = TokenService::getCoupons((int)$context->sessionId);
		$codes[] = $code;
		TokenService::setCoupons((int)$context->sessionId, $codes);

		return $this->get($context);
	}

	public function removePromoCode(AuthContext $context, string $code): array
	{
		$codes = array_values(array_filter(
			TokenService::getCoupons((int)$context->sessionId),
			static fn ($stored) => mb_strtolower($stored) !== mb_strtolower($code)
		));
		TokenService::setCoupons((int)$context->sessionId, $codes);

		return $this->get($context);
	}

	/**
	 * Coupon codes of the session registered in the standard coupon manager (needed before discounts are calculated).
	 */
	public function applyCoupons(AuthContext $context): array
	{
		$codes = $context->sessionId !== null ? TokenService::getCoupons($context->sessionId) : [];
		$this->initCoupons($context, $codes);

		$result = [];
		$statuses = DiscountCouponsManager::get(true, [], true, true);
		foreach ($codes as $code)
		{
			$data = $statuses[$code] ?? null;
			$applied = is_array($data) && (int)($data['STATUS'] ?? 0) === DiscountCouponsManager::STATUS_APPLYED;
			$result[] = [
				'code' => $code,
				'status' => $applied ? 'applied' : 'not-applied',
				'message' => $applied ? null : 'Условия промокода не выполнены',
			];
		}

		return $result;
	}

	private function initCoupons(AuthContext $context, array $codes): void
	{
		DiscountCouponsManager::init(DiscountCouponsManager::MODE_CLIENT, ['userId' => (int)$context->userId], true);
		foreach ($codes as $code)
		{
			DiscountCouponsManager::add($code);
		}
	}

	private function discountPrices(Basket $basket, AuthContext $context): array
	{
		if ($basket->isEmpty())
		{
			return [];
		}

		$discount = Discount::buildFromBasket($basket, new Discount\Context\Fuser((int)$context->fuserId));
		if ($discount === null || !$discount->calculate()->isSuccess())
		{
			return [];
		}
		$result = $discount->getApplyResult(true);

		return $result['PRICES']['BASKET'] ?? [];
	}

	private function load(AuthContext $context): Basket
	{
		if ($context->fuserId === null)
		{
			throw ApiException::unauthorized('Для работы с корзиной нужен токен (POST /auth/guest)');
		}

		return Basket::loadItemsForFUser($context->fuserId, \Westpower\Mobile\Config::siteId());
	}

	/**
	 * @return BasketItem[] items in the cart (deferred items excluded)
	 */
	private function items(Basket $basket): array
	{
		$items = [];
		foreach ($basket as $item)
		{
			if (!$item->isDelay())
			{
				$items[] = $item;
			}
		}

		return $items;
	}

	private function save(Basket $basket): void
	{
		$result = $basket->save();
		if (!$result->isSuccess())
		{
			throw ApiException::validation(implode('; ', $result->getErrorMessages()), 'quantity');
		}
	}

	/**
	 * Card for a product that is no longer in the catalog.
	 */
	private function fallbackCard(BasketItem $item): array
	{
		return [
			'id' => (int)$item->getProductId(),
			'name' => (string)$item->getField('NAME'),
			'image' => null,
			'price' => Formatter::price((float)$item->getPrice(), (float)$item->getBasePrice(), (string)$item->getCurrency()),
			'unit' => ['name' => (string)$item->getField('MEASURE_NAME') ?: 'шт', 'step' => 1.0],
			'weight' => null,
			'stock' => ['isAvailable' => false, 'quantity' => 0.0],
			'labels' => [],
			'brand' => null,
			'isFavorite' => false,
			'cartQuantity' => (float)$item->getQuantity(),
		];
	}

	private function money(float $value): string
	{
		return rtrim(rtrim(number_format($value, 2, ',', ' '), '0'), ',');
	}

	private function number(float $value): string
	{
		return rtrim(rtrim(number_format($value, 3, ',', ''), '0'), ',');
	}
}
