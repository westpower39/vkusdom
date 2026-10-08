<?php

namespace Westpower\Mobile\Service\Catalog;

use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\CollectionCode;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Content\PromotionService;

/**
 * Collections: named sets of products or sections with content managed in the admin
 * (module options: section codes; product labels; promotions; section checkboxes).
 */
final class CollectionService
{
	private SectionService $sections;

	public function __construct()
	{
		$this->sections = new SectionService();
	}

	public function products(string $code, array $query, AuthContext $context): array
	{
		$this->assertType($code, CollectionCode::TYPE_PRODUCTS);

		return ['collection' => $this->meta($code)] + (new ProductListService())->list($this->scope($code), $query, $context);
	}

	public function filters(string $code, array $query): array
	{
		$this->assertType($code, CollectionCode::TYPE_PRODUCTS);

		return (new ProductListService())->filters($this->scope($code), $query);
	}

	public function sections(string $code): array
	{
		[, $source, $prefix] = $this->assertType($code, CollectionCode::TYPE_SECTIONS);

		if ($source === CollectionCode::SOURCE_POPULAR)
		{
			$items = $this->sections->popular();
		}
		else
		{
			$items = $this->sections->children($this->sections->idsByCodes(Config::getCodes($prefix . '_sections')));
		}

		return ['collection' => $this->meta($code), 'items' => $items];
	}

	/**
	 * Whether the collection has anything to show (used to hide empty home blocks).
	 */
	public function hasContent(string $code): bool
	{
		[$type] = $this->config($code);
		if ($type === CollectionCode::TYPE_SECTIONS)
		{
			return !empty($this->sections($code)['items']);
		}

		$scope = $this->scope($code);
		if ($scope->isEmpty)
		{
			return false;
		}
		$repository = new ProductRepository();

		return $repository->count($repository->listFilter() + $scope->filter) > 0;
	}

	public function meta(string $code): array
	{
		[, $source, $prefix] = $this->config($code);

		$section = null;
		if (in_array($source, [CollectionCode::SOURCE_SECTIONS, CollectionCode::SOURCE_CHILDREN], true))
		{
			$ids = $this->sections->idsByCodes(Config::getCodes($prefix . '_sections'));
			if (count($ids) === 1)
			{
				$row = $this->sections->get($ids[0]);
				$section = ['id' => (int)$row['ID'], 'name' => $row['NAME']];
			}
		}

		return [
			'code' => $code,
			'name' => Config::get($prefix . '_name'),
			'section' => $section,
		];
	}

	private function scope(string $code): ProductScope
	{
		[, $source, $prefix, $label] = $this->config($code);
		$repository = new ProductRepository();

		switch ($source)
		{
			case CollectionCode::SOURCE_SECTIONS:
				$ids = $this->sections->idsByCodes(Config::getCodes($prefix . '_sections'));
				if (empty($ids))
				{
					return ProductScope::empty();
				}

				return new ProductScope(['SECTION_ID' => $ids, 'INCLUDE_SUBSECTIONS' => 'Y'], count($ids) === 1 ? $ids[0] : 0);

			case CollectionCode::SOURCE_LABEL:
				$enumId = $this->labelEnumId($repository->iblockId(), $label);

				return $enumId ? new ProductScope(['PROPERTY_' . PropertyCode::LABELS => $enumId]) : ProductScope::empty();

			case CollectionCode::SOURCE_PROMOTIONS:
				$ids = (new PromotionService())->activeProductIds();
				$enumId = $this->labelEnumId($repository->iblockId(), $label);
				if (!empty($ids) && $enumId)
				{
					return new ProductScope([['LOGIC' => 'OR', ['ID' => $ids], ['PROPERTY_' . PropertyCode::LABELS => $enumId]]]);
				}
				if (!empty($ids))
				{
					return new ProductScope(['ID' => $ids]);
				}

				return $enumId ? new ProductScope(['PROPERTY_' . PropertyCode::LABELS => $enumId]) : ProductScope::empty();
		}

		return ProductScope::empty();
	}

	private function labelEnumId(int $iblockId, ?string $xmlId): ?int
	{
		if ($xmlId === null)
		{
			return null;
		}

		$row = \CIBlockPropertyEnum::GetList([], [
			'IBLOCK_ID' => $iblockId,
			'CODE' => PropertyCode::LABELS,
			'XML_ID' => $xmlId,
		])->Fetch();

		return $row ? (int)$row['ID'] : null;
	}

	private function config(string $code): array
	{
		if (!isset(CollectionCode::ALL[$code]))
		{
			throw ApiException::notFound('Подборка не найдена');
		}

		return CollectionCode::ALL[$code];
	}

	private function assertType(string $code, string $type): array
	{
		$config = $this->config($code);
		if ($config[0] !== $type)
		{
			throw ApiException::notFound($type === CollectionCode::TYPE_PRODUCTS
				? 'Это подборка разделов, используйте …/sections'
				: 'Это подборка товаров, используйте …/products');
		}

		return $config;
	}
}
