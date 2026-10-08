<?php

namespace Westpower\Mobile\Dictionary;

/**
 * Iblock and order property codes used by the API.
 */
final class PropertyCode
{
	// Catalog iblock
	public const LABELS = 'LABELS';
	public const BRAND = 'BREND';
	public const WEIGHT = 'VES_G';
	public const MORE_PHOTO = 'MORE_PHOTO';
	public const ARTICLE = 'CML2_ARTICLE';
	public const DESCRIPTION = 'OPISANIE';
	public const CALORIES = 'KKAL';
	public const PROTEINS = 'BELKI';
	public const FATS = 'ZHIRY';
	public const CARBOHYDRATES = 'UGLEVODY';
	public const STORAGE_TEMPERATURE_MIN = 'TEMPERATURA_KHRANENIYA_MIN_C';
	public const STORAGE_TEMPERATURE_MAX = 'TEMPERATURA_KHRANENIYA_MAKS_C';

	/**
	 * Product detail "characteristics" block: API code => iblock property code, in display order.
	 */
	public const DETAIL_PROPERTIES = [
		'composition' => 'SOSTAV',
		'main-ingredients' => 'OSNOVNYE_INGREDIENTY',
		'allergens' => 'ALLERGENY',
		'may-contain' => 'MOZHET_SODERZHAT',
		'storage-conditions' => 'USLOVIYA_KHRANENIYA',
		'shelf-life' => 'SROK_KHRANENIYA',
		'country' => 'STRANA',
		'manufacturer' => 'PROIZVODITEL',
	];

	// Banners iblock
	public const BANNER_PROMOTION = 'PROMOTION';

	// Promotions iblock
	public const PROMOTION_PRODUCTS = 'PRODUCTS';

	// Order properties
	public const ORDER_FIO = 'FIO';
	public const ORDER_EMAIL = 'EMAIL';
	public const ORDER_PHONE = 'PHONE';
	public const ORDER_DELIVERY_TIME = 'DELIVERY_TIME';
	public const ORDER_REPLACEMENT = 'PRODUCTS_NOT_AVAILABLE';
	public const ORDER_DELIVERY_ADDRESS = 'DELIVERY_ADDRESS';
	public const ORDER_DELIVERY_LATITUDE = 'DELIVERY_LATITUDE';
	public const ORDER_DELIVERY_LONGITUDE = 'DELIVERY_LONGITUDE';
	public const ORDER_DELIVERY_APARTMENT = 'DELIVERY_APARTMENT';
	public const ORDER_DELIVERY_ENTRANCE = 'DELIVERY_ENTRANCE';
	public const ORDER_DELIVERY_FLOOR = 'DELIVERY_FLOOR';
	public const ORDER_DELIVERY_HAS_ELEVATOR = 'DELIVERY_HAS_ELEVATOR';
	public const ORDER_DELIVERY_HAS_FREIGHT_ELEVATOR = 'DELIVERY_HAS_FREIGHT_ELEVATOR';
}
