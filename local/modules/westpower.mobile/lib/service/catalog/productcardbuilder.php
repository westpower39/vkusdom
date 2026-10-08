<?php

namespace Westpower\Mobile\Service\Catalog;

use Bitrix\Catalog\MeasureRatioTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\BasketTable;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\LabelCode;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Service\Content\BrandService;
use Westpower\Mobile\Service\Content\PromotionService;
use Westpower\Mobile\Service\EntityResolver;
use Westpower\Mobile\Service\FavoriteService;
use Westpower\Mobile\Service\Formatter;

/**
 * Builds the ProductCard contract object — the same card in every product list.
 */
final class ProductCardBuilder
{
	private const OKEI_KILOGRAM = '166';

	private static ?array $measures = null;

	private int $iblockId;

	public function __construct()
	{
		Loader::includeModule('iblock');
		Loader::includeModule('catalog');
		Loader::includeModule('sale');
		$this->iblockId = EntityResolver::catalogIblockId();
	}

	/**
	 * Cards keyed by product ID, in the order of $ids. Unknown products are skipped.
	 */
	public function build(array $ids, AuthContext $context, bool $onlyActive = false): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids))
		{
			return [];
		}

		$elements = $this->elements($ids, $onlyActive);
		if (empty($elements))
		{
			return [];
		}
		$ids = array_keys($elements);

		$properties = array_fill_keys($ids, []);
		\CIBlockElement::GetPropertyValuesArray(
			$properties,
			$this->iblockId,
			['ID' => $ids],
			['CODE' => [PropertyCode::LABELS, PropertyCode::BRAND, PropertyCode::WEIGHT]]
		);

		$stock = $this->stockInfo($ids);
		$prices = $this->prices($ids, $context);
		$promotionProducts = array_flip((new PromotionService())->activeProductIds());
		$favorites = array_flip((new FavoriteService())->safeProductIds($context));
		$cartQuantities = $this->cartQuantities($context);
		$brands = new BrandService();

		$cards = [];
		foreach ($elements as $id => $element)
		{
			$info = $stock[$id] ?? $this->emptyStock();
			$price = $prices[$id] ?? null;
			$labels = $this->labels($properties[$id][PropertyCode::LABELS] ?? null);
			if (isset($promotionProducts[$id]) && !in_array(LabelCode::ACTION, array_column($labels, 'code'), true))
			{
				$labels[] = ['code' => LabelCode::ACTION, 'name' => LabelCode::ACTION_NAME];
			}

			$weight = $info['weight'] ?? (int)($properties[$id][PropertyCode::WEIGHT]['VALUE'] ?? 0);

			$cards[$id] = [
				'id' => $id,
				'name' => $element['NAME'],
				'image' => Formatter::image($element['PREVIEW_PICTURE'] ?: $element['DETAIL_PICTURE']),
				'price' => $price ?? Formatter::price(0.0),
				'unit' => ['name' => $info['measure'], 'step' => $info['ratio']],
				'weight' => $weight > 0 ? $weight : null,
				'stock' => [
					'isAvailable' => $element['ACTIVE'] === 'Y' && $info['available'] && $price !== null && $price['current'] > 0,
					'quantity' => $info['quantity'],
				],
				'labels' => $labels,
				'brand' => $brands->refByXmlId($this->scalarXmlId($properties[$id][PropertyCode::BRAND] ?? null)),
				'isFavorite' => isset($favorites[$id]),
				'cartQuantity' => (float)($cartQuantities[$id] ?? 0),
			];
		}

		return $cards;
	}

	/**
	 * Stock, measure and weight data used by cards and the cart.
	 *
	 * @return array<int, array{available: bool, quantity: float, canBuyZero: bool, traced: bool, measure: string, isWeighted: bool, ratio: float, weight: ?int}>
	 */
	public function stockInfo(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids))
		{
			return [];
		}

		$measures = $this->measures();
		$ratios = [];
		$ratioRows = MeasureRatioTable::getList([
			'filter' => ['@PRODUCT_ID' => $ids, '=IS_DEFAULT' => 'Y'],
			'select' => ['PRODUCT_ID', 'RATIO'],
		]);
		while ($row = $ratioRows->fetch())
		{
			$ratios[(int)$row['PRODUCT_ID']] = (float)$row['RATIO'];
		}

		$info = [];
		$rows = ProductTable::getList([
			'filter' => ['@ID' => $ids],
			'select' => ['ID', 'QUANTITY', 'AVAILABLE', 'WEIGHT', 'MEASURE', 'CAN_BUY_ZERO', 'QUANTITY_TRACE'],
		]);
		while ($row = $rows->fetch())
		{
			$id = (int)$row['ID'];
			$measure = $measures[(int)$row['MEASURE']] ?? $measures[0] ?? ['name' => 'шт', 'code' => ''];
			$info[$id] = [
				'available' => $row['AVAILABLE'] === 'Y',
				'quantity' => (float)$row['QUANTITY'],
				'canBuyZero' => $row['CAN_BUY_ZERO'] === 'Y',
				'traced' => $row['QUANTITY_TRACE'] === 'Y',
				'measure' => $measure['name'],
				'isWeighted' => $measure['code'] === self::OKEI_KILOGRAM,
				'ratio' => $ratios[$id] ?? 1.0,
				'weight' => (int)$row['WEIGHT'] > 0 ? (int)$row['WEIGHT'] : null,
			];
		}

		return $info;
	}

	private function elements(array $ids, bool $onlyActive): array
	{
		$filter = ['IBLOCK_ID' => $this->iblockId, 'ID' => $ids];
		if ($onlyActive)
		{
			$filter += ['ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'];
		}

		$rows = [];
		$result = \CIBlockElement::GetList([], $filter, false, false, ['ID', 'IBLOCK_ID', 'NAME', 'ACTIVE', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']);
		while ($row = $result->Fetch())
		{
			$rows[(int)$row['ID']] = $row;
		}

		$ordered = [];
		foreach ($ids as $id)
		{
			if (isset($rows[$id]))
			{
				$ordered[$id] = $rows[$id];
			}
		}

		return $ordered;
	}

	/**
	 * Optimal prices of the configured price type with discounts for one unit.
	 */
	private function prices(array $ids, AuthContext $context): array
	{
		$priceTypeId = EntityResolver::priceTypeId();
		$rows = [];
		$result = PriceTable::getList([
			'filter' => ['@PRODUCT_ID' => $ids, '=CATALOG_GROUP_ID' => $priceTypeId],
			'select' => ['ID', 'PRODUCT_ID', 'PRICE', 'CURRENCY', 'CATALOG_GROUP_ID', 'QUANTITY_FROM', 'QUANTITY_TO'],
			'order' => ['QUANTITY_FROM' => 'ASC'],
		]);
		while ($row = $result->fetch())
		{
			$productId = (int)$row['PRODUCT_ID'];
			if (!isset($rows[$productId]))
			{
				$rows[$productId] = $row;
			}
		}

		$groups = $context->userGroups();
		$prices = [];
		foreach ($rows as $productId => $row)
		{
			$base = (float)$row['PRICE'];
			$current = $base;
			$currency = (string)$row['CURRENCY'];

			$optimal = \CCatalogProduct::GetOptimalPrice($productId, 1, $groups, 'N', [$row], Config::siteId(), []);
			if (is_array($optimal) && isset($optimal['RESULT_PRICE']))
			{
				$current = (float)$optimal['RESULT_PRICE']['DISCOUNT_PRICE'];
				$base = (float)$optimal['RESULT_PRICE']['BASE_PRICE'];
				$currency = (string)$optimal['RESULT_PRICE']['CURRENCY'];
			}

			$prices[$productId] = Formatter::price($current, $base, $currency);
		}

		return $prices;
	}

	private function cartQuantities(AuthContext $context): array
	{
		if ($context->fuserId === null)
		{
			return [];
		}

		$quantities = [];
		$rows = BasketTable::getList([
			'filter' => ['=FUSER_ID' => $context->fuserId, '=ORDER_ID' => null, '=LID' => Config::siteId(), '=DELAY' => 'N'],
			'select' => ['PRODUCT_ID', 'QUANTITY'],
		]);
		while ($row = $rows->fetch())
		{
			$quantities[(int)$row['PRODUCT_ID']] = ($quantities[(int)$row['PRODUCT_ID']] ?? 0) + (float)$row['QUANTITY'];
		}

		return $quantities;
	}

	private function labels(?array $property): array
	{
		if ($property === null)
		{
			return [];
		}

		$xmlIds = array_values((array)($property['VALUE_XML_ID'] ?? []));
		$names = array_values((array)($property['VALUE'] ?? []));
		$labels = [];
		foreach ($xmlIds as $index => $xmlId)
		{
			if ((string)$xmlId !== '')
			{
				$labels[] = ['code' => (string)$xmlId, 'name' => (string)($names[$index] ?? $xmlId)];
			}
		}

		return $labels;
	}

	private function scalarXmlId(?array $property): ?string
	{
		if ($property === null)
		{
			return null;
		}
		$value = $property['VALUE_XML_ID'] ?? null;
		if (is_array($value))
		{
			$value = reset($value);
		}

		return $value !== null && $value !== false ? (string)$value : null;
	}

	/**
	 * Measures by ID (+ key 0 for the default measure): {name, code}.
	 */
	private function measures(): array
	{
		if (self::$measures === null)
		{
			self::$measures = [];
			// CCatalogMeasure fills localized symbols of standard measures (empty in the table itself).
			$rows = \CCatalogMeasure::getList([], [], false, false, ['ID', 'CODE', 'SYMBOL', 'SYMBOL_RUS', 'IS_DEFAULT']);
			while ($row = $rows->Fetch())
			{
				$measure = [
					'name' => (string)($row['SYMBOL_RUS'] ?: $row['SYMBOL']),
					'code' => (string)$row['CODE'],
				];
				self::$measures[(int)$row['ID']] = $measure;
				if ($row['IS_DEFAULT'] === 'Y')
				{
					self::$measures[0] = $measure;
				}
			}
		}

		return self::$measures;
	}

	private function emptyStock(): array
	{
		$default = $this->measures()[0] ?? ['name' => 'шт', 'code' => ''];

		return [
			'available' => false,
			'quantity' => 0.0,
			'canBuyZero' => false,
			'traced' => true,
			'measure' => $default['name'],
			'isWeighted' => $default['code'] === self::OKEI_KILOGRAM,
			'ratio' => 1.0,
			'weight' => null,
		];
	}
}
