<?php

namespace Westpower\Mobile\Service\Content;

use Bitrix\Main\Loader;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\EntityResolver;
use Westpower\Mobile\Service\Formatter;

/**
 * Brands iblock. A brand is linked to products by XML_ID: brand element XML_ID = XML_ID of the BREND list value.
 */
final class BrandService
{
	private static ?array $byXmlId = null;

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

	/**
	 * Brand Ref by XML_ID of the product BREND value; null when the brand is not in the brands iblock.
	 */
	public function refByXmlId(?string $xmlId): ?array
	{
		if ($xmlId === null || $xmlId === '')
		{
			return null;
		}

		if (self::$byXmlId === null)
		{
			self::$byXmlId = [];
			try
			{
				foreach ($this->fetch([]) as $row)
				{
					if ((string)$row['XML_ID'] !== '')
					{
						self::$byXmlId[$row['XML_ID']] = ['id' => (int)$row['ID'], 'name' => $row['NAME']];
					}
				}
			}
			catch (ApiException $e)
			{
				// brands iblock is not configured: products have no brand
			}
		}

		return self::$byXmlId[$xmlId] ?? null;
	}

	private function fetch(array $filter): array
	{
		Loader::includeModule('iblock');
		$rows = [];
		$result = \CIBlockElement::GetList(
			['SORT' => 'ASC', 'NAME' => 'ASC'],
			array_merge(['IBLOCK_ID' => EntityResolver::iblockId(EntityCode::IBLOCK_BRANDS), 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'], $filter),
			false,
			false,
			['ID', 'NAME', 'XML_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
		);
		while ($row = $result->Fetch())
		{
			$rows[] = $row;
		}

		return $rows;
	}
}
