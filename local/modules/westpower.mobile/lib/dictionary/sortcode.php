<?php

namespace Westpower\Mobile\Dictionary;

/**
 * Product list sort options. "discount" and "rating" will be added when the data appears (part B).
 */
final class SortCode
{
	public const POPULAR = 'popular';
	public const PRICE_ASC = 'price-asc';
	public const PRICE_DESC = 'price-desc';

	public const NAMES = [
		self::POPULAR => 'Популярные',
		self::PRICE_ASC => 'Сначала дешевле',
		self::PRICE_DESC => 'Сначала дороже',
	];
}
