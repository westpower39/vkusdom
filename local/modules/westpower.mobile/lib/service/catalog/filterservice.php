<?php

namespace Westpower\Mobile\Service\Catalog;

use Bitrix\Catalog\PriceTable;
use Bitrix\Iblock\PropertyTable;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\SortCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\EntityResolver;

/**
 * Dynamic filters (TZ 4.1): price + catalog properties marked "show in smart filter" for the section.
 * Counts are calculated directly over the products of the scope (no facet index yet — part B).
 */
final class FilterService
{
	public const PRICE = 'price';
	private const TYPE_RANGE = 'range';
	private const TYPE_MULTISELECT = 'multiselect';

	private ProductRepository $repository;
	private array $definitions = [];
	private array $data = [];

	public function __construct()
	{
		$this->repository = new ProductRepository();
	}

	/**
	 * Filters and sorts for the scope, counts take the applied filters into account.
	 */
	public function filters(ProductScope $scope, array $applied): array
	{
		$sorts = [];
		foreach (SortCode::NAMES as $code => $name)
		{
			$sorts[] = ['code' => $code, 'name' => $name];
		}
		if ($scope->isEmpty)
		{
			return ['filters' => [], 'sorts' => $sorts];
		}

		$definitions = $this->definitions($scope);
		$data = $this->data($scope);

		$filters = [];
		foreach ($definitions as $code => $definition)
		{
			if ($definition['type'] === self::TYPE_RANGE)
			{
				$values = array_filter(array_map(static fn ($product) => $product[$code] ?? null, $data['products']), 'is_numeric');
				if (empty($values))
				{
					continue;
				}
				$filters[] = [
					'code' => $code,
					'name' => $definition['name'],
					'type' => self::TYPE_RANGE,
					'min' => (float)floor(min($values)),
					'max' => (float)ceil(max($values)),
				];
				continue;
			}

			$counts = [];
			foreach ($data['products'] as $product)
			{
				if (!$this->matches($product, $applied, $definitions, $code))
				{
					continue;
				}
				foreach ((array)($product[$code] ?? []) as $value)
				{
					$counts[$value] = ($counts[$value] ?? 0) + 1;
				}
			}

			$values = [];
			foreach ($data['names'][$code] ?? [] as $value => $name)
			{
				$values[] = ['value' => (string)$value, 'name' => (string)$name, 'count' => $counts[$value] ?? 0];
			}
			if (empty($values))
			{
				continue;
			}
			usort($values, static fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

			$filters[] = [
				'code' => $code,
				'name' => $definition['name'],
				'type' => self::TYPE_MULTISELECT,
				'values' => $values,
			];
		}

		return ['filters' => $filters, 'sorts' => $sorts];
	}

	/**
	 * Validates the "filter" query parameter against the scope filters.
	 */
	public function parse($raw, ProductScope $scope): array
	{
		if ($raw === null || $raw === '' || $raw === [])
		{
			return [];
		}
		if (!is_array($raw))
		{
			throw ApiException::validation('Параметр filter должен быть объектом', 'filter');
		}

		$definitions = $this->definitions($scope);
		$applied = [];
		foreach ($raw as $code => $value)
		{
			$field = sprintf('filter[%s]', $code);
			if (!isset($definitions[$code]))
			{
				throw ApiException::validation(sprintf('Неизвестный фильтр «%s»', $code), $field);
			}

			if ($definitions[$code]['type'] === self::TYPE_RANGE)
			{
				$range = [];
				foreach (['min', 'max'] as $bound)
				{
					if (isset($value[$bound]) && $value[$bound] !== '')
					{
						if (!is_numeric($value[$bound]))
						{
							throw ApiException::validation('Границы диапазона должны быть числами', $field . '[' . $bound . ']');
						}
						$range[$bound] = (float)$value[$bound];
					}
				}
				if (!empty($range))
				{
					$applied[$code] = $range;
				}
				continue;
			}

			$values = array_values(array_filter(array_map('strval', (array)$value), 'strlen'));
			if (!empty($values))
			{
				$applied[$code] = $values;
			}
		}

		return $applied;
	}

	/**
	 * IDs of scope products that pass the applied filters.
	 *
	 * @return int[]
	 */
	public function matchingIds(ProductScope $scope, array $applied): array
	{
		$definitions = $this->definitions($scope);
		$ids = [];
		foreach ($this->data($scope)['products'] as $id => $product)
		{
			if ($this->matches($product, $applied, $definitions))
			{
				$ids[] = $id;
			}
		}

		return $ids;
	}

	private function matches(array $product, array $applied, array $definitions, ?string $skipCode = null): bool
	{
		foreach ($applied as $code => $value)
		{
			if ($code === $skipCode)
			{
				continue;
			}

			if ($definitions[$code]['type'] === self::TYPE_RANGE)
			{
				$productValue = $product[$code] ?? null;
				if (!is_numeric($productValue))
				{
					return false;
				}
				if ((isset($value['min']) && $productValue < $value['min']) || (isset($value['max']) && $productValue > $value['max']))
				{
					return false;
				}
				continue;
			}

			if (empty(array_intersect((array)($product[$code] ?? []), $value)))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * code => {name, type, propertyCode, propertyType, propertyId}
	 */
	private function definitions(ProductScope $scope): array
	{
		$key = $scope->filterSectionId;
		if (isset($this->definitions[$key]))
		{
			return $this->definitions[$key];
		}

		$definitions = [self::PRICE => ['name' => 'Цена', 'type' => self::TYPE_RANGE, 'propertyType' => null]];

		$propertyIds = [];
		foreach (\CIBlockSectionPropertyLink::GetArray($this->repository->iblockId(), $scope->filterSectionId) as $propertyId => $link)
		{
			if (($link['SMART_FILTER'] ?? 'N') === 'Y')
			{
				$propertyIds[] = (int)$propertyId;
			}
		}

		if (!empty($propertyIds))
		{
			$rows = PropertyTable::getList([
				'filter' => ['@ID' => $propertyIds, '=ACTIVE' => 'Y'],
				'select' => ['ID', 'CODE', 'NAME', 'PROPERTY_TYPE', 'USER_TYPE', 'SORT'],
				'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
			]);
			while ($row = $rows->fetch())
			{
				$type = $row['PROPERTY_TYPE'];
				$supported = in_array($type, [PropertyTable::TYPE_LIST, PropertyTable::TYPE_ELEMENT, PropertyTable::TYPE_NUMBER], true)
					|| ($type === PropertyTable::TYPE_STRING && (string)$row['USER_TYPE'] === '');
				if (!$supported || (string)$row['CODE'] === '')
				{
					continue;
				}

				$code = strtolower(str_replace('_', '-', $row['CODE']));
				$definitions[$code] = [
					'name' => $row['NAME'],
					'type' => $type === PropertyTable::TYPE_NUMBER ? self::TYPE_RANGE : self::TYPE_MULTISELECT,
					'propertyCode' => $row['CODE'],
					'propertyType' => $type,
					'propertyId' => (int)$row['ID'],
				];
			}
		}

		return $this->definitions[$key] = $definitions;
	}

	/**
	 * Values of filter fields for every product of the scope + names of multiselect values.
	 *
	 * @return array{products: array<int, array>, names: array<string, array>}
	 */
	private function data(ProductScope $scope): array
	{
		$key = md5(serialize([$scope->filter, $scope->filterSectionId]));
		if (isset($this->data[$key]))
		{
			return $this->data[$key];
		}

		$definitions = $this->definitions($scope);
		$ids = array_slice(
			$this->repository->ids($this->repository->listFilter() + $scope->filter),
			0,
			max(100, Config::getInt('filter_max_products'))
		);

		$products = array_fill_keys($ids, []);
		$names = [];
		if (empty($ids))
		{
			return $this->data[$key] = ['products' => [], 'names' => []];
		}

		$prices = PriceTable::getList([
			'filter' => ['@PRODUCT_ID' => $ids, '=CATALOG_GROUP_ID' => EntityResolver::priceTypeId()],
			'select' => ['PRODUCT_ID', 'PRICE'],
		]);
		while ($row = $prices->fetch())
		{
			$products[(int)$row['PRODUCT_ID']][self::PRICE] = (float)$row['PRICE'];
		}

		$propertyIds = array_values(array_filter(array_column($definitions, 'propertyId')));
		if (!empty($propertyIds))
		{
			$values = array_fill_keys($ids, []);
			\CIBlockElement::GetPropertyValuesArray($values, $this->repository->iblockId(), ['ID' => $ids], ['ID' => $propertyIds]);

			$elementIds = [];
			foreach ($ids as $id)
			{
				foreach ($definitions as $code => $definition)
				{
					if (empty($definition['propertyCode']))
					{
						continue;
					}
					$property = $values[$id][$definition['propertyCode']] ?? null;
					if ($property === null || $property['VALUE'] === false || $property['VALUE'] === '' || $property['VALUE'] === [])
					{
						continue;
					}

					switch ($definition['propertyType'])
					{
						case PropertyTable::TYPE_NUMBER:
							$number = is_array($property['VALUE']) ? reset($property['VALUE']) : $property['VALUE'];
							if (is_numeric($number))
							{
								$products[$id][$code] = (float)$number;
							}
							break;

						case PropertyTable::TYPE_LIST:
							$xmlIds = array_values((array)$property['VALUE_XML_ID']);
							$enumIds = array_values((array)$property['VALUE_ENUM_ID']);
							$texts = array_values((array)$property['VALUE']);
							foreach ($texts as $index => $text)
							{
								$value = (string)($xmlIds[$index] ?? '') !== '' ? (string)$xmlIds[$index] : (string)($enumIds[$index] ?? $text);
								$products[$id][$code][] = $value;
								$names[$code][$value] = $text;
							}
							break;

						case PropertyTable::TYPE_ELEMENT:
							foreach ((array)$property['VALUE'] as $elementId)
							{
								$products[$id][$code][] = (string)(int)$elementId;
								$elementIds[(int)$elementId][] = $code;
							}
							break;

						default:
							foreach ((array)$property['VALUE'] as $text)
							{
								$text = trim((string)$text);
								if ($text !== '')
								{
									$products[$id][$code][] = $text;
									$names[$code][$text] = $text;
								}
							}
					}
				}
			}

			if (!empty($elementIds))
			{
				$result = \CIBlockElement::GetList([], ['ID' => array_keys($elementIds)], false, false, ['ID', 'NAME']);
				while ($row = $result->Fetch())
				{
					foreach (array_unique($elementIds[(int)$row['ID']]) as $code)
					{
						$names[$code][(string)(int)$row['ID']] = $row['NAME'];
					}
				}
			}
		}

		return $this->data[$key] = ['products' => $products, 'names' => $names];
	}
}
