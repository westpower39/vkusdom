<?php

namespace Westpower\Mobile\Dictionary;

/**
 * Fixed collection codes known to the mobile app.
 */
final class CollectionCode
{
	public const TYPE_PRODUCTS = 'products';
	public const TYPE_SECTIONS = 'sections';

	public const SOURCE_SECTIONS = 'sections';     // products of configured sections (with subsections)
	public const SOURCE_LABEL = 'label';           // products marked with a label
	public const SOURCE_PROMOTIONS = 'promotions'; // products of active promotions + "action" label
	public const SOURCE_POPULAR = 'popular';       // sections with the "popular" checkbox
	public const SOURCE_CHILDREN = 'children';     // child sections of configured sections

	/**
	 * code => [type, source, option prefix, label code]
	 */
	public const ALL = [
		'own-production' => [self::TYPE_PRODUCTS, self::SOURCE_SECTIONS, 'collection_own_production', null],
		'prepared' => [self::TYPE_PRODUCTS, self::SOURCE_SECTIONS, 'collection_prepared', null],
		'chemicals' => [self::TYPE_PRODUCTS, self::SOURCE_SECTIONS, 'collection_chemicals', null],
		'hits' => [self::TYPE_PRODUCTS, self::SOURCE_LABEL, 'collection_hits', LabelCode::HIT],
		'new' => [self::TYPE_PRODUCTS, self::SOURCE_LABEL, 'collection_new', LabelCode::NEW],
		'healthy' => [self::TYPE_PRODUCTS, self::SOURCE_LABEL, 'collection_healthy', LabelCode::HEALTHY],
		'promo' => [self::TYPE_PRODUCTS, self::SOURCE_PROMOTIONS, 'collection_promo', LabelCode::ACTION],
		'popular' => [self::TYPE_SECTIONS, self::SOURCE_POPULAR, 'collection_popular', null],
		'bread' => [self::TYPE_SECTIONS, self::SOURCE_CHILDREN, 'collection_bread', null],
		'semi-finished' => [self::TYPE_SECTIONS, self::SOURCE_CHILDREN, 'collection_semi_finished', null],
	];
}
