<?php

namespace Westpower\Mobile\Service;

use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Catalog\ProductCardBuilder;
use Westpower\Mobile\Service\Catalog\ProductRepository;

/**
 * Favorites of a user, stored in the site's "Favorit" highload block (shared with the site).
 */
final class FavoriteService
{
	private static array $cache = [];

	/**
	 * @return int[] product IDs, most recent first
	 */
	public function productIds(int $userId): array
	{
		if (!isset(self::$cache[$userId]))
		{
			$dataClass = EntityResolver::hlDataClass(EntityCode::HL_FAVORITES);
			$ids = [];
			$rows = $dataClass::getList([
				'filter' => ['=UF_USER_ID' => $userId],
				'select' => ['UF_PRODUCT_ID'],
				'order' => ['ID' => 'DESC'],
			]);
			while ($row = $rows->fetch())
			{
				$ids[] = (int)$row['UF_PRODUCT_ID'];
			}
			self::$cache[$userId] = array_values(array_unique($ids));
		}

		return self::$cache[$userId];
	}

	public function list(AuthContext $context, int $page, int $limit): array
	{
		$ids = (new ProductRepository())->filterActive($this->productIds((int)$context->userId));
		$total = count($ids);
		$pageIds = array_slice($ids, ($page - 1) * $limit, $limit);

		return [
			'items' => array_values((new ProductCardBuilder())->build($pageIds, $context)),
			'pagination' => Pagination::make($page, $limit, $total),
		];
	}

	public function add(AuthContext $context, int $productId): array
	{
		$product = (new ProductRepository())->getActiveRef($productId);
		if (!in_array($productId, $this->productIds((int)$context->userId), true))
		{
			$dataClass = EntityResolver::hlDataClass(EntityCode::HL_FAVORITES);
			$result = $dataClass::add(['UF_USER_ID' => $context->userId, 'UF_PRODUCT_ID' => $productId]);
			if (!$result->isSuccess())
			{
				throw new \RuntimeException(implode('; ', $result->getErrorMessages()));
			}
			unset(self::$cache[$context->userId]);
		}

		return $this->state($context, $product, true);
	}

	public function remove(AuthContext $context, int $productId): array
	{
		$product = (new ProductRepository())->getRef($productId);
		$dataClass = EntityResolver::hlDataClass(EntityCode::HL_FAVORITES);
		$rows = $dataClass::getList([
			'filter' => ['=UF_USER_ID' => $context->userId, '=UF_PRODUCT_ID' => $productId],
			'select' => ['ID'],
		]);
		while ($row = $rows->fetch())
		{
			$dataClass::delete($row['ID']);
		}
		unset(self::$cache[$context->userId]);

		return $this->state($context, $product, false);
	}

	private function state(AuthContext $context, array $product, bool $isFavorite): array
	{
		return [
			'product' => $product,
			'isFavorite' => $isFavorite,
			'favoritesCount' => count((new ProductRepository())->filterActive($this->productIds((int)$context->userId))),
		];
	}

	/**
	 * Favorites without throwing when the HL block is missing (used by product cards).
	 */
	public function safeProductIds(AuthContext $context): array
	{
		if (!$context->isUser())
		{
			return [];
		}

		try
		{
			return $this->productIds($context->userId);
		}
		catch (ApiException $e)
		{
			return [];
		}
	}
}
