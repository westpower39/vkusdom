<?php

namespace Westpower\Mobile\Auth;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Internals\FuserTable;
use Westpower\Mobile\Config;

/**
 * Sale buyers (FUSER): guest buyers for app sessions and the buyer of a user.
 */
final class FuserService
{
	public static function createGuest(): int
	{
		Loader::includeModule('sale');
		$result = FuserTable::add([
			'DATE_INSERT' => new DateTime(),
			'DATE_UPDATE' => new DateTime(),
			'CODE' => md5(random_bytes(16)),
		]);
		if (!$result->isSuccess())
		{
			throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
		}

		return (int)$result->getId();
	}

	public static function forUser(int $userId): int
	{
		Loader::includeModule('sale');
		$row = FuserTable::getList([
			'filter' => ['=USER_ID' => $userId],
			'select' => ['ID'],
			'order' => ['ID' => 'DESC'],
			'limit' => 1,
		])->fetch();
		if ($row)
		{
			return (int)$row['ID'];
		}

		$result = FuserTable::add([
			'DATE_INSERT' => new DateTime(),
			'DATE_UPDATE' => new DateTime(),
			'USER_ID' => $userId,
			'CODE' => md5(random_bytes(16)),
		]);

		return (int)$result->getId();
	}

	/**
	 * Moves the guest cart to the user on login.
	 *
	 * TZ 4.3 requires the guest cart to REPLACE the user's cart; that rule is a site feature (awaiting Вова,
	 * see PLAN.md "ждём от сайта"). Until it exists the standard Bitrix transfer (merge) is used.
	 */
	public static function transferCart(int $guestFuserId, int $userFuserId): void
	{
		if ($guestFuserId === $userFuserId || empty(self::cartItemIds($guestFuserId)))
		{
			return;
		}

		\CSaleBasket::TransferBasket($guestFuserId, $userFuserId);
	}

	private static function cartItemIds(int $fuserId): array
	{
		$ids = [];
		$rows = BasketTable::getList([
			'filter' => ['=FUSER_ID' => $fuserId, '=ORDER_ID' => null, '=LID' => Config::siteId()],
			'select' => ['ID'],
		]);
		while ($row = $rows->fetch())
		{
			$ids[] = (int)$row['ID'];
		}

		return $ids;
	}
}
