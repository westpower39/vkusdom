<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Config;
use Westpower\Mobile\Service\Content\BannerService;
use Westpower\Mobile\Service\Content\BrandService;
use Westpower\Mobile\Service\Content\PromotionService;
use Westpower\Mobile\Service\Formatter;
use Westpower\Mobile\Service\HomeService;

/**
 * Content: home screen layout, banners, promotions, brands, "client service" pages.
 */
final class Content extends Base
{
	/** GET /api/v1/home */
	public function homeAction()
	{
		return $this->respond(fn () => (new HomeService())->layout());
	}

	/** GET /api/v1/banners */
	public function bannersAction()
	{
		return $this->respond(fn () => (new BannerService())->list());
	}

	/** GET /api/v1/promotions */
	public function promotionsAction()
	{
		return $this->respond(fn () => (new PromotionService())->list());
	}

	/** GET /api/v1/promotions/{id} */
	public function promotionAction(int $id)
	{
		return $this->respond(fn () => (new PromotionService())->detail($id, $this->context()));
	}

	/** GET /api/v1/brands */
	public function brandsAction()
	{
		return $this->respond(fn () => (new BrandService())->list());
	}

	/** GET /api/v1/pages */
	public function pagesAction()
	{
		return $this->respond(function () {
			$items = [];
			foreach (Config::getLines('pages') as $line)
			{
				[$code, $name, $url] = array_pad(array_map('trim', explode('|', $line, 3)), 3, '');
				if ($code !== '' && $url !== '')
				{
					$items[] = ['code' => $code, 'name' => $name, 'url' => Formatter::absoluteUrl($url)];
				}
			}

			return ['items' => $items];
		});
	}
}
