<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Routing\RoutingConfigurator;
use Westpower\Mobile\Controller;

/**
 * Mobile API routes. Registered in bitrix/.settings.php: 'routing' => ['value' => ['config' => ['api.php']]].
 * Requests reach this file through /bitrix/routing_index.php (see .htaccess).
 * Documentation: local/modules/westpower.mobile/docs/openapi.yaml
 */
return function (RoutingConfigurator $routes) {
	// Routing serves the whole site, so never fail here if the module is not installed.
	if (!Loader::includeModule('westpower.mobile'))
	{
		return;
	}

	$routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {
		$id = '\d+';
		$code = '[a-z0-9-]+';

		$routes->get('ping', [Controller\System::class, 'ping']);

		// Content
		$routes->get('banners', [Controller\Content::class, 'banners']);
		$routes->get('promotions', [Controller\Content::class, 'promotions']);
		$routes->get('promotions/{id}', [Controller\Content::class, 'promotion'])->where('id', $id);
		$routes->get('brands', [Controller\Content::class, 'brands']);
		$routes->get('pages', [Controller\Content::class, 'pages']);

		// Catalog
		$routes->get('catalog/sections', [Controller\Catalog::class, 'sections']);
		$routes->get('catalog/sections/{id}', [Controller\Catalog::class, 'section'])->where('id', $id);
		$routes->get('catalog/sections/{id}/products', [Controller\Catalog::class, 'sectionProducts'])->where('id', $id);
		$routes->get('catalog/sections/{id}/filters', [Controller\Catalog::class, 'sectionFilters'])->where('id', $id);
		$routes->get('catalog/collections/{code}/products', [Controller\Catalog::class, 'collectionProducts'])->where('code', $code);
		$routes->get('catalog/collections/{code}/filters', [Controller\Catalog::class, 'collectionFilters'])->where('code', $code);
		$routes->get('catalog/collections/{code}/sections', [Controller\Catalog::class, 'collectionSections'])->where('code', $code);
		$routes->get('catalog/products', [Controller\Catalog::class, 'products']);
		$routes->get('catalog/products/filters', [Controller\Catalog::class, 'productsFilters']);
		$routes->get('catalog/products/{id}', [Controller\Catalog::class, 'product'])->where('id', $id);

		// Auth
		$routes->post('auth/guest', [Controller\Auth::class, 'guest']);
		$routes->post('auth/verifications', [Controller\Auth::class, 'createVerification']);
		$routes->get('auth/verifications/{id}', [Controller\Auth::class, 'verification'])->where('id', '[A-Za-z0-9_.\-]+');
		$routes->post('auth/login', [Controller\Auth::class, 'login']);
		$routes->post('auth/refresh', [Controller\Auth::class, 'refresh']);
		$routes->post('auth/logout', [Controller\Auth::class, 'logout']);

		// Cart
		$routes->get('cart', [Controller\Cart::class, 'cart']);
		$routes->delete('cart', [Controller\Cart::class, 'clear']);
		$routes->put('cart/items/{productId}', [Controller\Cart::class, 'setItem'])->where('productId', $id);
		$routes->delete('cart/items/{productId}', [Controller\Cart::class, 'deleteItem'])->where('productId', $id);
		$routes->post('cart/promo-codes', [Controller\Cart::class, 'addPromoCode']);
		$routes->delete('cart/promo-codes/{code}', [Controller\Cart::class, 'deletePromoCode'])->where('code', '[^/]+');

		// Favorites
		$routes->get('favorites', [Controller\Favorite::class, 'favorites']);
		$routes->put('favorites/{productId}', [Controller\Favorite::class, 'add'])->where('productId', $id);
		$routes->delete('favorites/{productId}', [Controller\Favorite::class, 'delete'])->where('productId', $id);

		// Checkout and orders
		$routes->get('checkout/options', [Controller\Checkout::class, 'options']);
		$routes->get('checkout/slots', [Controller\Checkout::class, 'slots']);
		$routes->post('checkout/calculation', [Controller\Checkout::class, 'calculation']);
		$routes->post('orders', [Controller\Checkout::class, 'createOrder']);
		$routes->get('orders', [Controller\Checkout::class, 'orders']);
		$routes->get('orders/{id}', [Controller\Checkout::class, 'order'])->where('id', $id);
		$routes->post('orders/{id}/repeat', [Controller\Checkout::class, 'repeatOrder'])->where('id', $id);
		$routes->post('orders/{id}/payment', [Controller\Checkout::class, 'orderPayment'])->where('id', $id);

		// Personal account
		$routes->get('profile', [Controller\Profile::class, 'profile']);
		$routes->put('profile', [Controller\Profile::class, 'updateProfile']);
		$routes->put('profile/notifications', [Controller\Profile::class, 'updateNotifications']);
		$routes->get('order-profiles', [Controller\Profile::class, 'orderProfiles']);
		$routes->get('order-profiles/fields', [Controller\Profile::class, 'orderProfileFields']);
		$routes->post('order-profiles', [Controller\Profile::class, 'createOrderProfile']);
		$routes->put('order-profiles/{id}', [Controller\Profile::class, 'updateOrderProfile'])->where('id', $id);
		$routes->delete('order-profiles/{id}', [Controller\Profile::class, 'deleteOrderProfile'])->where('id', $id);
		$routes->get('ratings/products', [Controller\Profile::class, 'ratings']);
		$routes->put('ratings/products/{productId}', [Controller\Profile::class, 'setRating'])->where('productId', $id);
		$routes->post('feedback', [Controller\Profile::class, 'feedback']);
		$routes->put('devices/{token}', [Controller\Profile::class, 'registerDevice'])->where('token', '[A-Za-z0-9:_\-]+');
		$routes->delete('devices/{token}', [Controller\Profile::class, 'unregisterDevice'])->where('token', '[A-Za-z0-9:_\-]+');

		// Must stay last: JSON 404 for unknown API paths instead of the site 404 page.
		$routes->get('{path}', [Controller\System::class, 'notFound'])->where('path', '.*');
	});
};
