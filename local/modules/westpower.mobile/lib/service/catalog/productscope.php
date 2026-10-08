<?php

namespace Westpower\Mobile\Service\Catalog;

/**
 * A set of products: a section, a collection or a search. Holds the extra CIBlockElement filter
 * and the section whose smart-filter properties define the filters (0 — iblock level).
 */
final class ProductScope
{
	public array $filter;
	public int $filterSectionId;
	public bool $isEmpty;

	public function __construct(array $filter, int $filterSectionId = 0, bool $isEmpty = false)
	{
		$this->filter = $filter;
		$this->filterSectionId = $filterSectionId;
		$this->isEmpty = $isEmpty;
	}

	public static function empty(): self
	{
		return new self([], 0, true);
	}

	public static function section(int $sectionId): self
	{
		return new self(['SECTION_ID' => $sectionId, 'INCLUDE_SUBSECTIONS' => 'Y'], $sectionId);
	}

	public static function search(string $query): self
	{
		return new self(['%NAME' => $query]);
	}
}
