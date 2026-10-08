<?php

namespace Westpower\Mobile\Service;

use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Order;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Config;

/**
 * Product ratings (TZ 4.9). The list of purchased products comes from the user's orders (standard sale data);
 * rating storage is implemented by the site (awaiting) — until then "rating" is null and saving is unavailable.
 */
final class RatingService
{
	public function list(AuthContext $context, int $page, int $limit): array
	{
		Loader::includeModule('sale');

		$orderIds = [];
		$rows = Order::getList([
			'filter' => ['=USER_ID' => $context->userId, '=LID' => Config::siteId(), '=CANCELED' => 'N', '=PAYED' => 'Y'],
			'select' => ['ID'],
		]);
		while ($row = $rows->fetch())
		{
			$orderIds[] = (int)$row['ID'];
		}

		$products = [];
		if (!empty($orderIds))
		{
			$items = BasketTable::getList([
				'filter' => ['@ORDER_ID' => $orderIds],
				'select' => ['PRODUCT_ID', 'NAME'],
				'order' => ['ID' => 'DESC'],
			]);
			while ($item = $items->fetch())
			{
				$products[(int)$item['PRODUCT_ID']] ??= (string)$item['NAME'];
			}
		}

		$total = count($products);
		$pageProducts = array_slice($products, ($page - 1) * $limit, $limit, true);

		$images = [];
		if (!empty($pageProducts))
		{
			$result = \CIBlockElement::GetList([], ['ID' => array_keys($pageProducts)], false, false, ['ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']);
			while ($row = $result->Fetch())
			{
				$images[(int)$row['ID']] = Formatter::image($row['PREVIEW_PICTURE'] ?: $row['DETAIL_PICTURE']);
			}
		}

		$list = [];
		foreach ($pageProducts as $productId => $name)
		{
			$list[] = [
				'product' => ['id' => $productId, 'name' => $name, 'image' => $images[$productId] ?? null],
				'rating' => null,
			];
		}

		return ['items' => $list, 'pagination' => Pagination::make($page, $limit, $total)];
	}
}
