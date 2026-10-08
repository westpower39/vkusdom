<?php

namespace Westpower\Mobile\Service;

use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\CollectionCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Catalog\CollectionService;
use Westpower\Mobile\Service\Content\BannerService;
use Westpower\Mobile\Service\Content\BrandService;
use Westpower\Mobile\Service\Content\PromotionService;

/**
 * Home screen layout (server-driven): ordered blocks from module settings ("home_blocks").
 * The app renders blocks by type; blocks without content are not returned.
 */
final class HomeService
{
	public const TYPE_BANNERS = 'banners';
	public const TYPE_PROMOTIONS = 'promotions';
	public const TYPE_BRANDS = 'brands';
	public const TYPE_PRODUCTS = 'products';
	public const TYPE_SECTIONS = 'sections';

	private const COLLECTION_PREFIX = 'collection:';

	public function layout(): array
	{
		$collections = new CollectionService();
		$items = [];

		foreach (Config::getLines('home_blocks') as $line)
		{
			try
			{
				$block = $this->block($line, $collections);
			}
			catch (ApiException $e)
			{
				// Block source is not configured on the site yet — skip the block.
				$block = null;
			}

			if ($block !== null)
			{
				$items[] = $block;
			}
		}

		return ['items' => $items];
	}

	private function block(string $line, CollectionService $collections): ?array
	{
		if (strpos($line, self::COLLECTION_PREFIX) === 0)
		{
			$code = trim(substr($line, strlen(self::COLLECTION_PREFIX)));
			if (!isset(CollectionCode::ALL[$code]) || !$collections->hasContent($code))
			{
				return null;
			}

			$type = CollectionCode::ALL[$code][0] === CollectionCode::TYPE_SECTIONS ? self::TYPE_SECTIONS : self::TYPE_PRODUCTS;

			return ['type' => $type, 'collection' => $collections->meta($code)];
		}

		switch ($line)
		{
			case self::TYPE_BANNERS:
				$hasContent = !empty((new BannerService())->list()['items']);
				break;
			case self::TYPE_PROMOTIONS:
				$hasContent = !empty((new PromotionService())->list()['items']);
				break;
			case self::TYPE_BRANDS:
				$hasContent = !empty((new BrandService())->list()['items']);
				break;
			default:
				return null;
		}

		return $hasContent ? ['type' => $line, 'collection' => null] : null;
	}
}
