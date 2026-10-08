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

		// Auth
		$routes->post('auth/guest', [Controller\Auth::class, 'guest']);
		$routes->post('auth/verifications', [Controller\Auth::class, 'createVerification']);
		$routes->get('auth/verifications/{id}', [Controller\Auth::class, 'verification'])->where('id', '[A-Za-z0-9_.\-]+');
		$routes->post('auth/login', [Controller\Auth::class, 'login']);
		$routes->post('auth/refresh', [Controller\Auth::class, 'refresh']);
		$routes->post('auth/logout', [Controller\Auth::class, 'logout']);

		// Must stay last: JSON 404 for unknown API paths instead of the site 404 page.
		$routes->get('{path}', [Controller\System::class, 'notFound'])->where('path', '.*');
	});
};
