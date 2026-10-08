<?php

namespace Westpower\Mobile\Service\Catalog;

use Bitrix\Main\Loader;
use Westpower\Mobile\Config;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\EntityResolver;

/**
 * Low-level queries to products of the catalog iblock.
 */
final class ProductRepository
{
	private int $iblockId;

	public function __construct()
	{
		Loader::includeModule('iblock');
		Loader::includeModule('catalog');
		$this->iblockId = EntityResolver::catalogIblockId();
	}

	public function iblockId(): int
	{
		return $this->iblockId;
	}

	/**
	 * Filter of products visible in the app.
	 */
	public function baseFilter(): array
	{
		return [
			'IBLOCK_ID' => $this->iblockId,
			'ACTIVE' => 'Y',
			'ACTIVE_DATE' => 'Y',
		];
	}

	/**
	 * Filter of products shown in lists (sections, collections, search, filters):
	 * visible products, optionally only those with the retail price.
	 */
	public function listFilter(): array
	{
		$filter = $this->baseFilter();
		if (Config::getBool('hide_without_price'))
		{
			$filter['>CATALOG_PRICE_' . EntityResolver::priceTypeId()] = 0;
		}

		return $filter;
	}

	public function count(array $filter): int
	{
		return (int)\CIBlockElement::GetList([], $filter, []);
	}

	/**
	 * @return int[]
	 */
	public function ids(array $filter, array $order = ['ID' => 'ASC'], ?int $page = null, ?int $limit = null): array
	{
		$navigation = $limit !== null ? ['nPageSize' => $limit, 'iNumPage' => $page ?? 1, 'checkOutOfRange' => true] : false;
		$ids = [];
		$result = \CIBlockElement::GetList($order, $filter, false, $navigation, ['ID']);
		while ($row = $result->Fetch())
		{
			$ids[] = (int)$row['ID'];
		}

		return $ids;
	}

	/**
	 * Keeps only visible products, preserving the order of $ids.
	 *
	 * @return int[]
	 */
	public function filterActive(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids))
		{
			return [];
		}

		$active = array_flip($this->ids($this->baseFilter() + ['ID' => $ids]));

		return array_values(array_filter($ids, static fn (int $id) => isset($active[$id])));
	}

	public function getActiveRef(int $id): array
	{
		$rows = $this->refs($this->baseFilter() + ['ID' => $id]);
		if (empty($rows))
		{
			throw ApiException::notFound('Товар не найден');
		}

		return $rows[$id];
	}

	public function getRef(int $id): array
	{
		$rows = $this->refs(['IBLOCK_ID' => $this->iblockId, 'ID' => $id]);
		if (empty($rows))
		{
			throw ApiException::notFound('Товар не найден');
		}

		return $rows[$id];
	}

	private function refs(array $filter): array
	{
		$refs = [];
		$result = \CIBlockElement::GetList([], $filter, false, false, ['ID', 'NAME']);
		while ($row = $result->Fetch())
		{
			$refs[(int)$row['ID']] = ['id' => (int)$row['ID'], 'name' => $row['NAME']];
		}

		return $refs;
	}
}
