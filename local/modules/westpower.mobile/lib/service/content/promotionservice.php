<?php

namespace Westpower\Mobile\Service\Content;

use Bitrix\Main\Loader;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Catalog\ProductCardBuilder;
use Westpower\Mobile\Service\EntityResolver;
use Westpower\Mobile\Service\Formatter;

/**
 * Promotions iblock: small banners, promotion detail with products, products of active promotions.
 */
final class PromotionService
{
	private static ?array $activeProductIds = null;

	public function list(): array
	{
		$items = [];
		foreach ($this->fetch([]) as $row)
		{
			$items[] = [
				'id' => (int)$row['ID'],
				'name' => $row['NAME'],
				'image' => Formatter::image($row['PREVIEW_PICTURE'] ?: $row['DETAIL_PICTURE']),
			];
		}

		return ['items' => $items];
	}

	public function detail(int $id, AuthContext $context): array
	{
		$rows = $this->fetch(['ID' => $id]);
		if (empty($rows))
		{
			throw ApiException::notFound('Акция не найдена');
		}
		$row = $rows[0];

		$productIds = $this->productIds([$id]);
		$cards = (new ProductCardBuilder())->build($productIds, $context, true);
		$description = trim((string)($row['DETAIL_TEXT'] ?: $row['PREVIEW_TEXT']));

		return [
			'id' => (int)$row['ID'],
			'name' => $row['NAME'],
			'image' => Formatter::image($row['DETAIL_PICTURE'] ?: $row['PREVIEW_PICTURE']),
			'description' => $description !== '' ? $description : null,
			'activeFrom' => Formatter::dateTime($row['ACTIVE_FROM']),
			'activeTo' => Formatter::dateTime($row['ACTIVE_TO']),
			'products' => array_values($cards),
		];
	}

	/**
	 * Ref objects {id, name} of active promotions by IDs.
	 */
	public function refs(array $ids): array
	{
		$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
		if (empty($ids))
		{
			return [];
		}

		$refs = [];
		foreach ($this->fetch(['ID' => $ids]) as $row)
		{
			$refs[(int)$row['ID']] = ['id' => (int)$row['ID'], 'name' => $row['NAME']];
		}

		return $refs;
	}

	/**
	 * Products of all active promotions (the "promo" collection and the automatic "action" label).
	 *
	 * @return int[]
	 */
	public function activeProductIds(): array
	{
		if (self::$activeProductIds === null)
		{
			try
			{
				$promotionIds = array_map(static fn ($row) => (int)$row['ID'], $this->fetch([]));
				self::$activeProductIds = $this->productIds($promotionIds);
			}
			catch (ApiException $e)
			{
				self::$activeProductIds = [];
			}
		}

		return self::$activeProductIds;
	}

	private function productIds(array $promotionIds): array
	{
		if (empty($promotionIds))
		{
			return [];
		}

		$properties = array_fill_keys($promotionIds, []);
		\CIBlockElement::GetPropertyValuesArray(
			$properties,
			EntityResolver::iblockId(EntityCode::IBLOCK_PROMOTIONS),
			['ID' => $promotionIds],
			['CODE' => [PropertyCode::PROMOTION_PRODUCTS]]
		);

		$ids = [];
		foreach ($promotionIds as $promotionId)
		{
			foreach ((array)($properties[$promotionId][PropertyCode::PROMOTION_PRODUCTS]['VALUE'] ?? []) as $productId)
			{
				if ((int)$productId > 0)
				{
					$ids[] = (int)$productId;
				}
			}
		}

		return array_values(array_unique($ids));
	}

	private function fetch(array $filter): array
	{
		Loader::includeModule('iblock');
		$rows = [];
		$result = \CIBlockElement::GetList(
			['SORT' => 'ASC', 'ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'],
			array_merge(['IBLOCK_ID' => EntityResolver::iblockId(EntityCode::IBLOCK_PROMOTIONS), 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'], $filter),
			false,
			false,
			['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'ACTIVE_FROM', 'ACTIVE_TO']
		);
		while ($row = $result->Fetch())
		{
			$rows[] = $row;
		}

		return $rows;
	}
}
