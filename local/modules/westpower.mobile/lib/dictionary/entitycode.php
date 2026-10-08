<?php

namespace Westpower\Mobile\Dictionary;

/**
 * Symbolic codes of site entities used by the API. IDs are never stored: everything
 * is resolved by these codes at runtime.
 */
final class EntityCode
{
	// Iblock CODE values
	public const IBLOCK_CATALOG = 'catalog';
	public const IBLOCK_BANNERS = 'main_slider';
	public const IBLOCK_PROMOTIONS = 'promotions';
	public const IBLOCK_BRANDS = 'brands';

	// Catalog price type (b_catalog_group.NAME)
	public const PRICE_TYPE = 'Розничная';

	// Person type CODE values
	public const PERSON_TYPE_INDIVIDUAL = 'individual';
	public const PERSON_TYPE_LEGAL = 'legal';

	// Highload block entity names
	public const HL_FAVORITES = 'Favorit';
	public const HL_METHOD_OBTAINING = 'MethodObtaining';
	public const HL_DELIVERY_TIME = 'DeliveryTime';

	// Catalog section user field: "popular category" checkbox
	public const SECTION_UF_POPULAR = 'UF_POPULAR';

	// Pay system handlers that are never paid online (ACTION_FILE prefixes): internal account, invoices
	public const OFFLINE_PAY_HANDLER_PREFIXES = ['inner', 'bill'];
}
