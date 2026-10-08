<?php

namespace Westpower\Mobile\Service\Catalog;

use Bitrix\Main\Loader;
use Bitrix\Main\UserFieldTable;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\EntityResolver;
use Westpower\Mobile\Service\Formatter;

/**
 * Catalog sections: tree from the iblock root, section detail, popular and child sections.
 * Sections listed in "catalog_excluded_sections" (with their subtrees) are hidden from the app.
 */
final class SectionService
{
	private const SELECT = ['ID', 'IBLOCK_ID', 'NAME', 'PICTURE', 'DETAIL_PICTURE', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'SORT'];

	private int $iblockId;
	private ?array $excludedRanges = null;

	public function __construct()
	{
		Loader::includeModule('iblock');
		$this->iblockId = EntityResolver::catalogIblockId();
	}

	/**
	 * Two-level tree for the catalog screen.
	 */
	public function tree(): array
	{
		$rows = $this->fetch(['<=DEPTH_LEVEL' => 2]);

		$items = [];
		foreach ($rows as $row)
		{
			if ((int)$row['DEPTH_LEVEL'] === 1)
			{
				$items[(int)$row['ID']] = $this->item($row) + ['children' => []];
			}
		}
		foreach ($rows as $row)
		{
			$parentId = (int)$row['IBLOCK_SECTION_ID'];
			if ((int)$row['DEPTH_LEVEL'] === 2 && isset($items[$parentId]))
			{
				$items[$parentId]['children'][] = $this->item($row);
			}
		}

		return ['items' => array_values($items)];
	}

	public function detail(int $id): array
	{
		$section = $this->get($id);

		$children = $this->fetch([
			'>LEFT_MARGIN' => $section['LEFT_MARGIN'],
			'<RIGHT_MARGIN' => $section['RIGHT_MARGIN'],
			'=DEPTH_LEVEL' => (int)$section['DEPTH_LEVEL'] + 1,
		]);

		return [
			'id' => (int)$section['ID'],
			'name' => $section['NAME'],
			'image' => Formatter::image($section['PICTURE'] ?: $section['DETAIL_PICTURE']),
			'breadcrumbs' => $this->breadcrumbs($id),
			'children' => array_map([$this, 'item'], $children),
			'productsCount' => $this->productsCount($id),
		];
	}

	/**
	 * Visible section row or 404.
	 */
	public function get(int $id): array
	{
		$rows = $this->fetch(['=ID' => $id]);
		if (empty($rows))
		{
			throw ApiException::notFound('Раздел не найден');
		}

		return $rows[0];
	}

	public function breadcrumbs(int $id): array
	{
		$breadcrumbs = [];
		$chain = \CIBlockSection::GetNavChain($this->iblockId, $id, ['ID', 'NAME']);
		while ($link = $chain->Fetch())
		{
			$breadcrumbs[] = ['id' => (int)$link['ID'], 'name' => $link['NAME']];
		}

		return $breadcrumbs;
	}

	/**
	 * Sections with the "popular category" checkbox (TZ: popular categories tiles).
	 */
	public function popular(): array
	{
		$exists = UserFieldTable::getList([
			'filter' => ['=ENTITY_ID' => 'IBLOCK_' . $this->iblockId . '_SECTION', '=FIELD_NAME' => EntityCode::SECTION_UF_POPULAR],
			'select' => ['ID'],
		])->fetch();
		if (!$exists)
		{
			return [];
		}

		return array_map([$this, 'item'], $this->fetch([EntityCode::SECTION_UF_POPULAR => 1], ['SORT' => 'ASC', 'NAME' => 'ASC']));
	}

	/**
	 * Direct children of the given sections.
	 */
	public function children(array $parentIds): array
	{
		$items = [];
		foreach ($parentIds as $parentId)
		{
			foreach ($this->fetch(['=IBLOCK_SECTION_ID' => (int)$parentId]) as $row)
			{
				$items[] = $this->item($row);
			}
		}

		return $items;
	}

	public function productsCount(int $sectionId): int
	{
		return (int)\CIBlockElement::GetList([], [
			'IBLOCK_ID' => $this->iblockId,
			'SECTION_ID' => $sectionId,
			'INCLUDE_SUBSECTIONS' => 'Y',
			'ACTIVE' => 'Y',
			'ACTIVE_DATE' => 'Y',
		], []);
	}

	/**
	 * Section IDs of the catalog by codes (visible ones only).
	 *
	 * @return int[]
	 */
	public function idsByCodes(array $codes): array
	{
		$ids = [];
		foreach (EntityResolver::sectionIds($this->iblockId, $codes) as $id)
		{
			if (!empty($this->fetch(['=ID' => $id])))
			{
				$ids[] = $id;
			}
		}

		return $ids;
	}

	public function item(array $row): array
	{
		return [
			'id' => (int)$row['ID'],
			'name' => $row['NAME'],
			'image' => Formatter::image($row['PICTURE'] ?: $row['DETAIL_PICTURE']),
		];
	}

	/**
	 * Active, globally active, not excluded sections.
	 */
	private function fetch(array $filter, array $order = ['LEFT_MARGIN' => 'ASC']): array
	{
		$filter = array_merge([
			'IBLOCK_ID' => $this->iblockId,
			'ACTIVE' => 'Y',
			'GLOBAL_ACTIVE' => 'Y',
		], $filter);

		$select = self::SELECT;
		if (array_key_exists(EntityCode::SECTION_UF_POPULAR, $filter))
		{
			$select[] = EntityCode::SECTION_UF_POPULAR;
		}

		$rows = [];
		$result = \CIBlockSection::GetList($order, $filter, false, $select);
		while ($row = $result->Fetch())
		{
			if (!$this->isExcluded($row))
			{
				$rows[] = $row;
			}
		}

		if ($order === ['LEFT_MARGIN' => 'ASC'])
		{
			// Keep tree order, but sort siblings by SORT, then NAME.
			usort($rows, static function (array $a, array $b): int {
				if ($a['IBLOCK_SECTION_ID'] === $b['IBLOCK_SECTION_ID'])
				{
					return [(int)$a['SORT'], $a['NAME']] <=> [(int)$b['SORT'], $b['NAME']];
				}

				return (int)$a['LEFT_MARGIN'] <=> (int)$b['LEFT_MARGIN'];
			});
		}

		return $rows;
	}

	private function isExcluded(array $row): bool
	{
		if ($this->excludedRanges === null)
		{
			$this->excludedRanges = [];
			$codes = Config::getCodes('catalog_excluded_sections');
			if (!empty($codes))
			{
				$result = \CIBlockSection::GetList([], ['IBLOCK_ID' => $this->iblockId, 'CODE' => $codes], false, ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN']);
				while ($excluded = $result->Fetch())
				{
					$this->excludedRanges[] = [(int)$excluded['LEFT_MARGIN'], (int)$excluded['RIGHT_MARGIN']];
				}
			}
		}

		foreach ($this->excludedRanges as [$left, $right])
		{
			if ((int)$row['LEFT_MARGIN'] >= $left && (int)$row['RIGHT_MARGIN'] <= $right)
			{
				return true;
			}
		}

		return false;
	}
}
