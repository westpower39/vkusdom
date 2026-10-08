<?php

use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;

defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true || die();

/** @global CMain $APPLICATION */
global $APPLICATION;

$moduleId = 'westpower.mobile';
if ($APPLICATION->GetGroupRight($moduleId) < 'W')
{
	return;
}
Loader::includeModule($moduleId);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$sectionsHint = 'символьные коды разделов каталога через запятую';
$collectionOptions = [
	'Главный экран приложения (GET /home)',
	['home_blocks', 'Блоки по порядку, по одному в строке: banners, promotions, brands, collection:<код подборки> (own-production, prepared, chemicals, hits, new, healthy, promo, popular, bread, semi-finished). Пустые блоки не показываются', '', ['textarea', 12, 40]],
	'Подборки товаров по разделам',
	['collection_own_production_sections', 'Наше производство — ' . $sectionsHint, '', ['text', 60]],
	['collection_own_production_name', 'Наше производство — название', '', ['text', 60]],
	['collection_prepared_sections', 'Готовая продукция — ' . $sectionsHint, '', ['text', 60]],
	['collection_prepared_name', 'Готовая продукция — название', '', ['text', 60]],
	['collection_chemicals_sections', 'Бытовая химия — ' . $sectionsHint, '', ['text', 60]],
	['collection_chemicals_name', 'Бытовая химия — название', '', ['text', 60]],
	'Подборки товаров по меткам (свойство LABELS: hit, new, healthy) и акциям',
	['collection_hits_name', 'Хиты продаж — название', '', ['text', 60]],
	['collection_new_name', 'Новинки — название', '', ['text', 60]],
	['collection_healthy_name', 'ЗОЖ — название', '', ['text', 60]],
	['collection_promo_name', 'Акции — название', '', ['text', 60]],
	'Подборки разделов (плитки)',
	['collection_popular_name', 'Популярные категории (галочка UF_POPULAR у раздела) — название', '', ['text', 60]],
	['collection_bread_sections', 'Хлеб и выпечка — родительские разделы, ' . $sectionsHint, '', ['text', 60]],
	['collection_bread_name', 'Хлеб и выпечка — название', '', ['text', 60]],
	['collection_semi_finished_sections', 'Полуфабрикаты — родительские разделы, ' . $sectionsHint, '', ['text', 60]],
	['collection_semi_finished_name', 'Полуфабрикаты — название', '', ['text', 60]],
];

$tabs = [
	[
		'DIV' => 'general',
		'TAB' => 'Общие',
		'TITLE' => 'API мобильного приложения',
		'OPTIONS' => [
			['public_url', 'Базовый URL для ссылок (пусто — текущий домен)', '', ['text', 60]],
			['debug_errors', 'Отладка: показывать текст исключения в ответе INTERNAL_ERROR (только для тестового стенда)', '', ['checkbox']],
			['hide_without_price', 'Скрывать в списках товары без розничной цены', '', ['checkbox']],
			['filter_max_products', 'Максимум товаров при расчёте фильтров', '', ['text', 10]],
			['pages', 'Страницы «Клиентской службы»: код|название|адрес, по одной в строке', '', ['textarea', 6, 80]],
		],
	],
	[
		'DIV' => 'collections',
		'TAB' => 'Подборки',
		'TITLE' => 'Блоки главной и каталога',
		'OPTIONS' => $collectionOptions,
	],
	[
		'DIV' => 'auth',
		'TAB' => 'Авторизация',
		'TITLE' => 'Токены приложения и вход по телефону',
		'OPTIONS' => [
			['access_token_ttl', 'Срок жизни access-токена, сек', '', ['text', 10]],
			['refresh_token_ttl', 'Срок жизни refresh-токена, сек (30 дней = 2592000)', '', ['text', 10]],
			['verification_ttl', 'Срок действия подтверждения телефона, сек', '', ['text', 10]],
			'Заглушка до подключения SMS/звонков на сайте',
			['auth_test_mode', 'Тестовый режим (SMS не отправляются, код фиксированный, звонок подтверждается сразу)', '', ['checkbox']],
			['auth_test_code', 'Тестовый код', '', ['text', 10]],
			['auth_test_call_phone', 'Тестовый номер для звонка', '', ['text', 20]],
		],
	],
	[
		'DIV' => 'orders',
		'TAB' => 'Заказы',
		'TITLE' => 'Статусы заказа в приложении (ТЗ 8.1)',
		'OPTIONS' => [
			['order_status_map', 'Статус Битрикса = статус приложения (accepted, paid, assembling, ready, delivering, completed), по одному в строке', '', ['textarea', 8, 40]],
		],
	],
];

$tabControl = new CAdminTabControl('tabControl', $tabs);

if ($request->isPost() && check_bitrix_sessid() && ($request->getPost('save') !== null || $request->getPost('apply') !== null))
{
	foreach ($tabs as $tab)
	{
		__AdmSettingsSaveOptions($moduleId, $tab['OPTIONS']);
	}

	LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($moduleId) . '&lang=' . LANGUAGE_ID . '&' . $tabControl->ActiveTabParam());
}

$tabControl->Begin();
?>
<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialcharsbx($moduleId) ?>&amp;lang=<?= LANGUAGE_ID ?>">
	<?= bitrix_sessid_post() ?>
	<?php
	foreach ($tabs as $tab)
	{
		$tabControl->BeginNextTab();
		__AdmSettingsDrawList($moduleId, $tab['OPTIONS']);
	}
	$tabControl->Buttons();
	?>
	<input type="submit" name="save" value="Сохранить" class="adm-btn-save">
	<input type="submit" name="apply" value="Применить">
	<?php $tabControl->End(); ?>
</form>
<?php
echo BeginNote();
echo 'API: <code>/api/v1/</code>. Документация: <code>/local/modules/westpower.mobile/docs/openapi.yaml</code>.<br>'
	. 'Инфоблоки, свойства, HL-блоки и типы плательщиков ищутся по символьным кодам (см. lib/dictionary).';
echo EndNote();
