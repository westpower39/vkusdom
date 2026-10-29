<?php

namespace Westpower\Mobile\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Order;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Exception\ApiException;

/**
 * Product ratings (TZ 4.9), stored in the site highload blocks, as the site "product.rating" component does:
 * ProductRating — a rating per purchased basket line, ProductRatingTotal — average and count per product.
 * The API rates a product: the rating goes to the latest unrated purchase of it, or replaces the latest rating.
 */
final class RatingService
{
	private const MIN_RATING = 1;
	private const MAX_RATING = 5;
	private const ANONYMOUS_NAME = 'Анонимный пользователь';

	public function __construct()
	{
		Loader::includeModule('sale');
	}

	public function list(AuthContext $context, int $page, int $limit): array
	{
		$products = [];
		foreach ($this->purchases((int)$context->userId) as $line)
		{
			$products[$line['PRODUCT_ID']] ??= $line['NAME'];
		}

		$total = count($products);
		$pageProducts = array_slice($products, ($page - 1) * $limit, $limit, true);
		$ratings = $this->userRatings((int)$context->userId, array_keys($pageProducts));

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
				'rating' => $ratings[$productId] ?? null,
			];
		}

		return ['items' => $list, 'pagination' => Pagination::make($page, $limit, $total)];
	}

	public function set(AuthContext $context, int $productId, $rating): void
	{
		if (!is_int($rating) || $rating < self::MIN_RATING || $rating > self::MAX_RATING)
		{
			throw ApiException::validation('Оценка — целое число от 1 до 5', 'rating');
		}

		$userId = (int)$context->userId;
		$lines = array_filter($this->purchases($userId), fn ($line) => $line['PRODUCT_ID'] === $productId);
		if (empty($lines))
		{
			throw ApiException::forbidden('Оценить можно только купленный товар');
		}

		$dataClass = EntityResolver::hlDataClass(EntityCode::HL_PRODUCT_RATING);
		$rated = [];
		$rows = $dataClass::getList([
			'filter' => ['=UF_USER_ID' => $userId, '=UF_PRODUCT_ID' => $productId],
			'select' => ['ID', 'UF_BASKET_ID'],
			'order' => ['ID' => 'DESC'],
		]);
		while ($row = $rows->fetch())
		{
			$rated[(int)$row['UF_BASKET_ID']] ??= (int)$row['ID'];
		}

		$unrated = array_values(array_filter($lines, fn ($line) => !isset($rated[$line['ID']])));
		if (!empty($unrated))
		{
			$result = $dataClass::add([
				'UF_RATING' => $rating,
				'UF_PRODUCT_ID' => $productId,
				'UF_BASKET_ID' => $unrated[0]['ID'],
				'UF_ORDER_ID' => $unrated[0]['ORDER_ID'],
				'UF_USER_ID' => $userId,
				'UF_USER_FIO' => $this->userName($userId),
				'UF_DATE' => date('d.m.Y'),
			]);
		}
		else
		{
			$result = $dataClass::update(max($rated), ['UF_RATING' => $rating, 'UF_DATE' => date('d.m.Y')]);
		}
		if (!$result->isSuccess())
		{
			throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
		}

		$this->updateTotal($productId);
	}

	/**
	 * Basket lines of the user's not cancelled orders, newest first.
	 *
	 * @return array<int, array{ID: int, ORDER_ID: int, PRODUCT_ID: int, NAME: string}>
	 */
	private function purchases(int $userId): array
	{
		$orderIds = [];
		$rows = Order::getList([
			'filter' => ['=USER_ID' => $userId, '=LID' => Config::siteId(), '=CANCELED' => 'N'],
			'select' => ['ID'],
		]);
		while ($row = $rows->fetch())
		{
			$orderIds[] = (int)$row['ID'];
		}
		if (empty($orderIds))
		{
			return [];
		}

		$lines = [];
		$items = BasketTable::getList([
			'filter' => ['@ORDER_ID' => $orderIds],
			'select' => ['ID', 'ORDER_ID', 'PRODUCT_ID', 'NAME'],
			'order' => ['ID' => 'DESC'],
		]);
		while ($item = $items->fetch())
		{
			$lines[] = [
				'ID' => (int)$item['ID'],
				'ORDER_ID' => (int)$item['ORDER_ID'],
				'PRODUCT_ID' => (int)$item['PRODUCT_ID'],
				'NAME' => (string)$item['NAME'],
			];
		}

		return $lines;
	}

	/**
	 * Latest rating of the user per product.
	 *
	 * @param int[] $productIds
	 * @return array<int, int>
	 */
	private function userRatings(int $userId, array $productIds): array
	{
		if (empty($productIds))
		{
			return [];
		}

		$dataClass = EntityResolver::hlDataClass(EntityCode::HL_PRODUCT_RATING);
		$ratings = [];
		$rows = $dataClass::getList([
			'filter' => ['=UF_USER_ID' => $userId, '@UF_PRODUCT_ID' => $productIds],
			'select' => ['UF_PRODUCT_ID', 'UF_RATING'],
			'order' => ['ID' => 'DESC'],
		]);
		while ($row = $rows->fetch())
		{
			$ratings[(int)$row['UF_PRODUCT_ID']] ??= (int)$row['UF_RATING'];
		}

		return $ratings;
	}

	/**
	 * Recalculates the product average the same way as the site component.
	 */
	private function updateTotal(int $productId): void
	{
		$ratingClass = EntityResolver::hlDataClass(EntityCode::HL_PRODUCT_RATING);
		$stats = $ratingClass::getList([
			'filter' => ['=UF_PRODUCT_ID' => $productId],
			'select' => ['CNT', 'TOTAL'],
			'runtime' => [
				new ExpressionField('CNT', 'COUNT(%s)', 'ID'),
				new ExpressionField('TOTAL', 'SUM(%s)', 'UF_RATING'),
			],
		])->fetch();
		$count = (int)($stats['CNT'] ?? 0);
		if ($count === 0)
		{
			return;
		}
		$fields = ['UF_RATING' => round((float)$stats['TOTAL'] / $count, 1), 'UF_COUNT' => $count];

		$totalClass = EntityResolver::hlDataClass(EntityCode::HL_PRODUCT_RATING_TOTAL);
		$row = $totalClass::getList(['filter' => ['=UF_PRODUCT_ID' => $productId], 'select' => ['ID']])->fetch();
		$result = $row
			? $totalClass::update($row['ID'], $fields)
			: $totalClass::add(['UF_PRODUCT_ID' => $productId] + $fields);
		if (!$result->isSuccess())
		{
			throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
		}
	}

	private function userName(int $userId): string
	{
		$user = UserTable::getList(['filter' => ['=ID' => $userId], 'select' => ['NAME', 'LAST_NAME', 'SECOND_NAME']])->fetch();

		return ($user ? ProfileService::fullName($user) : null) ?? self::ANONYMOUS_NAME;
	}
}
