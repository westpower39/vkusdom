<?php

namespace Westpower\Mobile\Service\Content;

use Bitrix\Main\Loader;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Service\EntityResolver;
use Westpower\Mobile\Service\Formatter;

/**
 * Big banners (home slider iblock). The app uses the mobile slide (preview picture, detail picture as fallback).
 */
final class BannerService
{
	public function list(): array
	{
		Loader::includeModule('iblock');
		$iblockId = EntityResolver::iblockId(EntityCode::IBLOCK_BANNERS);

		$rows = [];
		$result = \CIBlockElement::GetList(
			['SORT' => 'ASC', 'ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'],
			['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
			false,
			false,
			['ID', 'IBLOCK_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
		);
		while ($row = $result->Fetch())
		{
			$rows[(int)$row['ID']] = $row;
		}
		if (empty($rows))
		{
			return ['items' => []];
		}

		$properties = array_fill_keys(array_keys($rows), []);
		\CIBlockElement::GetPropertyValuesArray($properties, $iblockId, ['ID' => array_keys($rows)], ['CODE' => [PropertyCode::BANNER_PROMOTION]]);

		$promotionIds = [];
		foreach ($properties as $values)
		{
			$promotionId = (int)($values[PropertyCode::BANNER_PROMOTION]['VALUE'] ?? 0);
			if ($promotionId > 0)
			{
				$promotionIds[] = $promotionId;
			}
		}
		$promotions = (new PromotionService())->refs($promotionIds);

		$items = [];
		foreach ($rows as $id => $row)
		{
			$promotionId = (int)($properties[$id][PropertyCode::BANNER_PROMOTION]['VALUE'] ?? 0);
			$items[] = [
				'id' => $id,
				'image' => Formatter::image($row['PREVIEW_PICTURE'] ?: $row['DETAIL_PICTURE']),
				'promotion' => $promotions[$promotionId] ?? null,
			];
		}

		return ['items' => $items];
	}
}
