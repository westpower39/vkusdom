<?php

/**
 * Only changeable settings of the API layer live here. Entities (iblocks, properties, HL blocks,
 * person types, price types) are addressed by symbolic codes from lib/dictionary, never by ID.
 * Business rules (delivery, slots, auth providers, push sending) belong to the site, not to this module.
 */
$westpower_mobile_default_option = [
	// General
	'public_url' => '',
	// Show exception details in INTERNAL_ERROR responses (test stand only)
	'debug_errors' => 'N',

	// Catalog
	'catalog_excluded_sections' => '',
	'filter_max_products' => '5000',

	// Home screen layout (GET /home): one block per line, in display order.
	// Types: banners, promotions, brands, collection:<code>
	'home_blocks' => "banners\npromotions\ncollection:own-production\ncollection:popular\ncollection:hits\ncollection:prepared\ncollection:new\ncollection:healthy\ncollection:bread\ncollection:semi-finished\nbrands\ncollection:chemicals",

	// Collections: comma-separated section codes of the catalog iblock
	'collection_own_production_sections' => '',
	'collection_own_production_name' => 'Наше производство',
	'collection_prepared_sections' => '',
	'collection_prepared_name' => 'Готовая продукция',
	'collection_chemicals_sections' => '',
	'collection_chemicals_name' => 'Бытовая химия',
	'collection_bread_sections' => '',
	'collection_bread_name' => 'Хлеб и выпечка',
	'collection_semi_finished_sections' => '',
	'collection_semi_finished_name' => 'Полуфабрикаты',
	'collection_hits_name' => 'Хиты продаж',
	'collection_new_name' => 'Новинки',
	'collection_healthy_name' => 'Здоровый образ жизни',
	'collection_promo_name' => 'Акции',
	'collection_popular_name' => 'Популярные категории',

	// App tokens (TZ 8.5)
	'access_token_ttl' => '900',
	'refresh_token_ttl' => '2592000',
	'verification_ttl' => '600',

	// Stub until the site implements SMS/call providers: no SMS is sent, the code is fixed,
	// a call verification is confirmed immediately.
	'auth_test_mode' => 'Y',
	'auth_test_code' => '1234',
	'auth_test_call_phone' => '+70000000000',

	// Orders: Bitrix status ID => app status (TZ 8.1)
	'order_status_map' => "N=accepted\nP=paid\nDA=assembling\nDS=delivering\nF=completed",

	// "Client service" pages for webview: code|name|url
	'pages' => "legal|Правовая информация|/pravovaya-informatsiya/\noffer|Договор-оферта|/dogovor-oferta/\nconsent|Согласие на обработку персональных данных|/soglasie-na-obrabotku-personalnykh-dannykh/",
];
