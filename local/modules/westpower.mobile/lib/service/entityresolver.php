<?php

namespace Westpower\Mobile\Service;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Exception\ApiException;

/**
 * Resolves site entities by symbolic codes. Missing configuration results in SERVICE_UNAVAILABLE
 * with a message that names the missing code.
 */
final class EntityResolver
{
	private static array $iblocks = [];
	private static array $personTypes = [];
	private static array $hlClasses = [];
	private static ?int $priceTypeId = null;

	public static function iblockId(string $code): int
	{
		if (!isset(self::$iblocks[$code]))
		{
			Loader::includeModule('iblock');
			$row = IblockTable::getList([
				'filter' => ['=CODE' => $code],
				'select' => ['ID'],
				'cache' => ['ttl' => 3600],
			])->fetch();
			if (!$row)
			{
				throw ApiException::unavailable(sprintf('Не найден инфоблок с символьным кодом «%s»', $code));
			}
			self::$iblocks[$code] = (int)$row['ID'];
		}

		return self::$iblocks[$code];
	}

	public static function catalogIblockId(): int
	{
		return self::iblockId(EntityCode::IBLOCK_CATALOG);
	}

	public static function priceTypeId(): int
	{
		if (self::$priceTypeId === null)
		{
			Loader::includeModule('catalog');
			$row = \Bitrix\Catalog\GroupTable::getList([
				'filter' => ['=NAME' => EntityCode::PRICE_TYPE],
				'select' => ['ID'],
				'cache' => ['ttl' => 3600],
			])->fetch();
			if (!$row)
			{
				throw ApiException::unavailable(sprintf('Не найден тип цены «%s»', EntityCode::PRICE_TYPE));
			}
			self::$priceTypeId = (int)$row['ID'];
		}

		return self::$priceTypeId;
	}

	public static function personTypeId(string $code): int
	{
		if (!isset(self::$personTypes[$code]))
		{
			Loader::includeModule('sale');
			$row = \Bitrix\Sale\Internals\PersonTypeTable::getList([
				'filter' => ['=CODE' => $code, '=ACTIVE' => 'Y', '=LID' => Config::siteId()],
				'select' => ['ID'],
			])->fetch();
			if (!$row)
			{
				throw ApiException::unavailable(sprintf('Не найден тип плательщика с кодом «%s»', $code));
			}
			self::$personTypes[$code] = (int)$row['ID'];
		}

		return self::$personTypes[$code];
	}

	/**
	 * Person type code by ID (reverse lookup), null for unknown types.
	 */
	public static function personTypeCode(int $personTypeId): ?string
	{
		foreach ([EntityCode::PERSON_TYPE_INDIVIDUAL, EntityCode::PERSON_TYPE_LEGAL] as $code)
		{
			try
			{
				if (self::personTypeId($code) === $personTypeId)
				{
					return $code;
				}
			}
			catch (ApiException $e)
			{
				// not configured — skip
			}
		}

		return null;
	}

	/**
	 * Data class of a highload block found by entity name.
	 *
	 * @return string|\Bitrix\Main\ORM\Data\DataManager
	 */
	public static function hlDataClass(string $name): string
	{
		if (!isset(self::$hlClasses[$name]))
		{
			Loader::includeModule('highloadblock');
			$block = HighloadBlockTable::getList(['filter' => ['=NAME' => $name]])->fetch();
			if (!$block)
			{
				throw ApiException::unavailable(sprintf('Не найден highload-блок «%s»', $name));
			}
			self::$hlClasses[$name] = HighloadBlockTable::compileEntity($block)->getDataClass();
		}

		return self::$hlClasses[$name];
	}

	/**
	 * Section IDs of an iblock by section codes (unknown codes are skipped).
	 *
	 * @return int[]
	 */
	public static function sectionIds(int $iblockId, array $codes): array
	{
		if (empty($codes))
		{
			return [];
		}

		Loader::includeModule('iblock');
		$ids = [];
		$rows = \Bitrix\Iblock\SectionTable::getList([
			'filter' => ['=IBLOCK_ID' => $iblockId, '@CODE' => $codes],
			'select' => ['ID', 'CODE'],
		]);
		$byCode = [];
		while ($row = $rows->fetch())
		{
			$byCode[$row['CODE']] = (int)$row['ID'];
		}
		foreach ($codes as $code)
		{
			if (isset($byCode[$code]))
			{
				$ids[] = $byCode[$code];
			}
		}

		return $ids;
	}
}
