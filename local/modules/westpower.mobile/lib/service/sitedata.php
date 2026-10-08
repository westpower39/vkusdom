<?php

namespace Westpower\Mobile\Service;

use Bitrix\Catalog\StoreTable;
use Bitrix\Main\Loader;
use Westpower\Mobile\Dictionary\EntityCode;

/**
 * Read access to the site's own checkout data: obtaining methods (HL MethodObtaining),
 * delivery time slots (HL DeliveryTime) and pickup points (stores marked as issuing centers).
 */
final class SiteData
{
	private static ?array $methods = null;

	/**
	 * code => {id, code, name, daysCount, deliveries: [personTypeId => deliveryId]}
	 */
	public static function methods(): array
	{
		if (self::$methods === null)
		{
			self::$methods = [];
			$dataClass = EntityResolver::hlDataClass(EntityCode::HL_METHOD_OBTAINING);
			$rows = $dataClass::getList(['select' => ['ID', 'UF_CODE', 'UF_NAME', 'UF_DAYS_COUNT', 'UF_PERSON_TYPE_IDS', 'UF_DELIVERY_IDS'], 'order' => ['ID' => 'ASC']]);
			while ($row = $rows->fetch())
			{
				$deliveries = [];
				foreach (array_values((array)$row['UF_PERSON_TYPE_IDS']) as $index => $personTypeId)
				{
					$deliveryId = (int)(array_values((array)$row['UF_DELIVERY_IDS'])[$index] ?? 0);
					if ((int)$personTypeId > 0 && $deliveryId > 0)
					{
						$deliveries[(int)$personTypeId] = $deliveryId;
					}
				}

				$code = mb_strtolower((string)$row['UF_CODE']);
				self::$methods[$code] = [
					'id' => (int)$row['ID'],
					'code' => $code,
					'name' => (string)$row['UF_NAME'],
					'daysCount' => (int)$row['UF_DAYS_COUNT'],
					'deliveries' => $deliveries,
				];
			}
		}

		return self::$methods;
	}

	public static function method(string $code): ?array
	{
		return self::methods()[$code] ?? null;
	}

	public static function methodByDeliveryId(int $deliveryId): ?array
	{
		foreach (self::methods() as $method)
		{
			if (in_array($deliveryId, $method['deliveries'], true))
			{
				return $method;
			}
		}

		return null;
	}

	/**
	 * Slots of an obtaining method: [{id, name}] sorted.
	 */
	public static function slots(int $methodId): array
	{
		$dataClass = EntityResolver::hlDataClass(EntityCode::HL_DELIVERY_TIME);
		$slots = [];
		$rows = $dataClass::getList([
			'filter' => ['=UF_METHOD_OBTAINING_ID' => $methodId],
			'select' => ['ID', 'UF_NAME'],
			'order' => ['UF_SORT' => 'ASC', 'ID' => 'ASC'],
		]);
		while ($row = $rows->fetch())
		{
			$slots[] = ['id' => (int)$row['ID'], 'name' => trim((string)$row['UF_NAME'])];
		}

		return $slots;
	}

	/**
	 * Pickup points: active stores marked as issuing centers.
	 */
	public static function pickupPoints(): array
	{
		Loader::includeModule('catalog');
		$items = [];
		$rows = StoreTable::getList([
			'filter' => ['=ACTIVE' => 'Y', '=ISSUING_CENTER' => 'Y'],
			'select' => ['ID', 'TITLE', 'ADDRESS', 'SCHEDULE', 'PHONE', 'GPS_N', 'GPS_S', 'IMAGE_ID', 'SORT'],
			'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
		]);
		while ($row = $rows->fetch())
		{
			$items[(int)$row['ID']] = [
				'id' => (int)$row['ID'],
				'name' => (string)$row['TITLE'],
				'address' => (string)$row['ADDRESS'],
				'schedule' => (string)$row['SCHEDULE'] !== '' ? (string)$row['SCHEDULE'] : null,
				'phone' => (string)$row['PHONE'] !== '' ? (string)$row['PHONE'] : null,
				'latitude' => is_numeric($row['GPS_N']) ? (float)$row['GPS_N'] : null,
				'longitude' => is_numeric($row['GPS_S']) ? (float)$row['GPS_S'] : null,
				'image' => Formatter::image($row['IMAGE_ID']),
			];
		}

		return $items;
	}
}
