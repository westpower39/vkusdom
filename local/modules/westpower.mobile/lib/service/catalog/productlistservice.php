<?php

namespace Westpower\Mobile\Service\Catalog;

use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Dictionary\SortCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\EntityResolver;
use Westpower\Mobile\Service\Pagination;

/**
 * Product lists of sections, collections and search: pagination, sorting, filters.
 */
final class ProductListService
{
	private ProductRepository $repository;
	private FilterService $filters;

	public function __construct()
	{
		$this->repository = new ProductRepository();
		$this->filters = new FilterService();
	}

	/**
	 * @param array $query page, limit, sort, filter
	 */
	public function list(ProductScope $scope, array $query, AuthContext $context, int $defaultLimit = 20): array
	{
		[$page, $limit] = Pagination::parse($query['page'] ?? null, $query['limit'] ?? null, $defaultLimit);
		$order = $this->order($query['sort'] ?? null);
		$applied = $this->filters->parse($query['filter'] ?? null, $scope);

		if ($scope->isEmpty)
		{
			return ['items' => [], 'pagination' => Pagination::make($page, $limit, 0)];
		}

		$filter = $this->repository->baseFilter() + $scope->filter;
		if (!empty($applied))
		{
			$ids = $this->filters->matchingIds($scope, $applied);
			$filter['ID'] = !empty($ids) ? $ids : [0];
		}

		$total = $this->repository->count($filter);
		$ids = $total > 0 ? $this->repository->ids($filter, $order, $page, $limit) : [];

		return [
			'items' => array_values((new ProductCardBuilder())->build($ids, $context)),
			'pagination' => Pagination::make($page, $limit, $total),
		];
	}

	public function filters(ProductScope $scope, array $query): array
	{
		return $this->filters->filters($scope, $this->filters->parse($query['filter'] ?? null, $scope));
	}

	private function order($sort): array
	{
		$sort = $sort === null || $sort === '' ? SortCode::POPULAR : (string)$sort;
		$priceField = 'CATALOG_PRICE_' . EntityResolver::priceTypeId();

		switch ($sort)
		{
			case SortCode::POPULAR:
				return ['SHOW_COUNTER' => 'DESC', 'SORT' => 'ASC', 'ID' => 'DESC'];
			case SortCode::PRICE_ASC:
				return [$priceField => 'ASC', 'ID' => 'ASC'];
			case SortCode::PRICE_DESC:
				return [$priceField => 'DESC', 'ID' => 'ASC'];
		}

		throw ApiException::validation(
			sprintf('Параметр sort должен быть одним из: %s', implode(', ', array_keys(SortCode::NAMES))),
			'sort'
		);
	}
}
