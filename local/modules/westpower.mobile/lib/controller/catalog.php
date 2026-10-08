<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Catalog\CollectionService;
use Westpower\Mobile\Service\Catalog\ProductDetailService;
use Westpower\Mobile\Service\Catalog\ProductListService;
use Westpower\Mobile\Service\Catalog\ProductScope;
use Westpower\Mobile\Service\Catalog\SectionService;

/**
 * Catalog: sections, collections, product lists, filters, product detail, search.
 */
final class Catalog extends Base
{
	/** GET /api/v1/catalog/sections */
	public function sectionsAction()
	{
		return $this->respond(fn () => (new SectionService())->tree());
	}

	/** GET /api/v1/catalog/sections/{id} */
	public function sectionAction(int $id)
	{
		return $this->respond(fn () => (new SectionService())->detail($id));
	}

	/** GET /api/v1/catalog/sections/{id}/products */
	public function sectionProductsAction(int $id)
	{
		return $this->respond(function () use ($id) {
			(new SectionService())->get($id);

			return (new ProductListService())->list(ProductScope::section($id), $this->listQuery(), $this->context());
		});
	}

	/** GET /api/v1/catalog/sections/{id}/filters */
	public function sectionFiltersAction(int $id)
	{
		return $this->respond(function () use ($id) {
			(new SectionService())->get($id);

			return (new ProductListService())->filters(ProductScope::section($id), $this->listQuery());
		});
	}

	/** GET /api/v1/catalog/collections/{code}/products */
	public function collectionProductsAction(string $code)
	{
		return $this->respond(fn () => (new CollectionService())->products($code, $this->listQuery(), $this->context()));
	}

	/** GET /api/v1/catalog/collections/{code}/filters */
	public function collectionFiltersAction(string $code)
	{
		return $this->respond(fn () => (new CollectionService())->filters($code, $this->listQuery()));
	}

	/** GET /api/v1/catalog/collections/{code}/sections */
	public function collectionSectionsAction(string $code)
	{
		return $this->respond(fn () => (new CollectionService())->sections($code));
	}

	/** GET /api/v1/catalog/products?query= — search by name (TZ 4.1) */
	public function productsAction()
	{
		return $this->respond(fn () => (new ProductListService())->list($this->searchScope(), $this->listQuery(), $this->context()));
	}

	/** GET /api/v1/catalog/products/filters?query= */
	public function productsFiltersAction()
	{
		return $this->respond(fn () => (new ProductListService())->filters($this->searchScope(), $this->listQuery()));
	}

	/** GET /api/v1/catalog/products/{id} */
	public function productAction(int $id)
	{
		return $this->respond(fn () => (new ProductDetailService())->detail($id, $this->context()));
	}

	private function searchScope(): ProductScope
	{
		$query = trim((string)$this->query('query'));
		if (mb_strlen($query) < 2)
		{
			throw ApiException::validation('Введите не меньше 2 символов', 'query');
		}

		return ProductScope::search($query);
	}
}
