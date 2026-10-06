<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Page\Asset;

global $USER;

$AssetInstance = Asset::getInstance();

$GLOBALS["SITE_SETTINGS"] = array(
	"IS_AUTH" => false,
	"IS_MAIN_EMPTY" => false,
	"IS_CATALOG" => false,
	"IS_PRODUCT" => false,
	"IS_ORDER"  => false,
);


if($_SERVER["SCRIPT_NAME"] == "/index.php"){
	$GLOBALS["SITE_SETTINGS"]["IS_MAIN_EMPTY"] = true;
} elseif(defined("NEED_AUTH") && !$USER->IsAuthorized()){
	$GLOBALS["SITE_SETTINGS"]["IS_AUTH"] = true;
} elseif(preg_match("/^\/catalog\//i",$_SERVER["REQUEST_URI"])){
	$GLOBALS["SITE_SETTINGS"]["IS_CATALOG"] = true;
} elseif(preg_match("/^\/product\//i",$_SERVER["REQUEST_URI"])){
	$GLOBALS["SITE_SETTINGS"]["IS_PRODUCT"] = true;
}  elseif(preg_match("/^\/order\//i",$_SERVER["REQUEST_URI"])){
	$GLOBALS["SITE_SETTINGS"]["IS_ORDER"] = true;
}

?>
<!DOCTYPE html>
<html xml:lang="<?=LANGUAGE_ID?>" lang="<?=LANGUAGE_ID?>">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geologica:wght,CRSV@100..900,0&amp;family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&amp;display=swap" rel="stylesheet">
	<title><?$APPLICATION->ShowTitle()?></title>
	<?
		$AssetInstance->addCss(SITE_TEMPLATE_PATH."/libs/css/normalize.css");
		$AssetInstance->addCss(SITE_TEMPLATE_PATH."/libs/css/fancybox.css");
		$AssetInstance->addCss(SITE_TEMPLATE_PATH."/libs/css/nouislider.css");
		$AssetInstance->addCss(SITE_TEMPLATE_PATH."/libs/css/swiper-bundle.min.css");
		$AssetInstance->addCss(SITE_TEMPLATE_PATH."/libs/css/slimselect.css");
		$AssetInstance->addCss(SITE_TEMPLATE_PATH."/css/all.css");
			
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/jquery.min.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/inputmask.min.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/nouislider.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/lightTabs.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/fancybox.umd.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/swiper-bundle.min.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/slimselect.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/libs/js/jquery.cookie.js");
		
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/js/main.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/js/basket.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/js/favorit.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/js/use_cookie.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/js/method_obtaining.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/js/slider.js");
		$AssetInstance->addJs(SITE_TEMPLATE_PATH."/js/help.js");
		
		
		
		$APPLICATION->ShowHead(); 
	?>
</head>
<body>
	<div><? $APPLICATION->ShowPanel(); ?></div>
	<div class="page-flex">
	<?if(!$GLOBALS["SITE_SETTINGS"]["IS_AUTH"]):?>
    	<div class="bg-overlay"></div>
    	<header class="header">
			<div class="container">
				<div class="header__inner">
					<div class="header-logo">
						<a href="/">
							<img class="header-logo_desktop" src="<?=SITE_TEMPLATE_PATH?>/img/logo.svg" alt=""><img class="header-logo_mobile" src="<?=SITE_TEMPLATE_PATH?>/img/logo-mobile.svg" alt="">
						</a>
					</div>
					<?$APPLICATION->IncludeComponent(
						"custom:catalog.search.title",
						"",
						Array(
							
						)
					);?>
					<?$APPLICATION->IncludeComponent(
						"bitrix:menu",
						"v3",
						Array(
							"ALLOW_MULTI_SELECT" => "N",
							"CHILD_MENU_TYPE" => "",
							"DELAY" => "N",
							"MAX_LEVEL" => "2",
							"MENU_CACHE_GET_VARS" => array(""),
							"MENU_CACHE_TIME" => "3600",
							"MENU_CACHE_TYPE" => "A",
							"MENU_CACHE_USE_GROUPS" => "N",
							"ROOT_MENU_TYPE" => "catalog",
							"USE_EXT" => "Y"
						)
					);?>
					<button data-method-obtaining="" class="btn header-green-button">
						<svg width="24" height="24">
							<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#mappin-icon">
							</use>
						</svg>
						<span>Укажите способ получения</span>
					</button>
					<div class="header-links">
						<a class="header-links__link" data-favorit="totalCount" href="/personal/favorit/">
							<svg width="24" height="24">
								<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#favorite-icon"></use>
							</svg>
							<span class="header-links__link-count">0</span>
						</a>
						<a class="header-links__link" data-basket="totalCount" href="/basket/">
							<svg width="24" height="24">
								<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#cart-icon"></use>
							</svg>
							<span class="header-links__link-count">0</span>
						</a>
						<a class="header-links__link" href="<?=$USER->IsAuthorized() ? "/personal/" : "/auth/"?>">
							<svg width="24" height="24">
								<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#user-icon"></use>
							</svg>
						</a>
					</div>
					<div class="hamburger">
						<div class="hamburger__button">
							<span></span>
							<span></span>
							<span></span>
						</div>
					</div>
				</div>
				<?$APPLICATION->IncludeComponent(
					"bitrix:menu",
					"v2",
					Array(
						"ALLOW_MULTI_SELECT" => "N",
						"CHILD_MENU_TYPE" => "left",
						"DELAY" => "N",
						"MAX_LEVEL" => "1",
						"MENU_CACHE_GET_VARS" => array(""),
						"MENU_CACHE_TIME" => "3600",
						"MENU_CACHE_TYPE" => "A",
						"MENU_CACHE_USE_GROUPS" => "N",
						"ROOT_MENU_TYPE" => "top",
						"USE_EXT" => "Y"
					)
				);?>
				<div class="header__two-buttons">
					<button data-method-obtaining="delivery" class="btn">
						<svg width="24" height="24">
							<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#delivery-icon">
							</use>
						</svg>Доставка
					</button>
					<button data-method-obtaining="selfPickup" class="btn">
						<svg width="24" height="24">
							<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#self-pickup-icon">
							</use>
						</svg>Самовывоз
					</button>
				</div>
			</div>
		</header>
		<div class="popup-menu">
			<div class="popup-menu__bonuses">
				<div class="popup-menu__bonuses-title">
					Доступно бонусов
				</div>
				<div class="popup-menu__bonuses-count">
					<svg width="32" height="32">
						<use xlink:href="./img/sprite.svg#bonuses-icon">
						</use>
					</svg>500
				</div>
			</div>
			<a class="btn" href="">
				<svg width="24" height="24">
					<use xlink:href="./img/sprite.svg#menu-icon">
					</use>
				</svg>Каталог
			</a>
			<div class="popup-menu__nav">
				<ul>
					<li>
						<a href="">
							<svg width="24" height="24">
								<use xlink:href="./img/sprite.svg#nav-icon01">
								</use>
							</svg>Акции
						</a>
					</li>
					<li>
						<a href="">
							<svg width="24" height="24">
								<use xlink:href="./img/sprite.svg#nav-icon02">
								</use>
							</svg>Хиты продаж
						</a>
					</li>
					<li>
						<a href="">
							<svg width="24" height="24">
								<use xlink:href="./img/sprite.svg#nav-icon03">
								</use>
							</svg>Новинки
						</a>
					</li>
				</ul>
			</div>
			<div class="popup-menu__nav">
				<ul>
					<li>
						<a href="">
							Бонусы и акции
						</a>
					</li>
					<li>
						<a href="">
							Избранное
						</a>
					</li>
					<li>
						<a href="">
							Доставка и самовывоз
						</a>
					</li>
					<li>
						<a href="">
							Магазины
						</a>
					</li>
				</ul>
			</div>
			<div class="popup-menu__personal">
				<div class="popup-menu__personal-title">
					Войти в личный кабинет
				</div>
				<div class="popup-menu__personal-text">
					Для отслеживания своих заказов, бонусных баллов и скидок
				</div>
				<button class="btn">
					Войти
				</button>
			</div>
			<div class="popup-menu__nav">
				<ul>
					<li>
						<a href="">
							<svg width="24" height="24">
								<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#nav-icon04">
								</use>
							</svg>Мой профиль
						</a>
					</li>
					<li>
						<a href="">
							<svg width="24" height="24">
								<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#nav-icon05">
								</use>
							</svg>Заказы
						</a>
					</li>
					<li>
						<a href="">
							<svg width="24" height="24">
								<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#nav-icon06">
								</use>
							</svg>Мои бонусы
						</a>
					</li>
				</ul>
			</div>
		</div>
		<main class="main">
        	<div class="container">
        	<?if($GLOBALS["SITE_SETTINGS"]["IS_ORDER"]):?>
        		 <?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "", array(
						"START_FROM" => "0",
						"PATH" => "",
						"SITE_ID" => "-"
					),
					false,
					Array('HIDE_ICONS' => 'Y')
				);?>
				<div class="order-page">
					<div class="section-title">
						<svg width="24" height="24"><use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#arrow-big-left-icon"></use></svg>
						<?$APPLICATION->ShowTitle()?>
					</div>
        	<?elseif($GLOBALS["SITE_SETTINGS"]["IS_CATALOG"]):?>
        	<div class="catalog">
            	<div class="catalog__inner">
            		<div class="catalog-menu"><?$APPLICATION->ShowViewContent('CATALOG_MENU');?></div>
            		<div class="catalog__content">
            			 <?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "", array(
								"START_FROM" => "0",
								"PATH" => "",
								"SITE_ID" => "-"
							),
							false,
							Array('HIDE_ICONS' => 'Y')
						);?>
						<div class="title-flex">
	                  		<div class="section-title">
							 	<svg width="24" height="24"><use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#arrow-big-left-icon"></use></svg>
							 	<?$APPLICATION->ShowTitle()?>
							 </div>
                 			 <?$APPLICATION->ShowViewContent('TITLE_AFTER');?>
                		</div>
						
        	<?elseif($GLOBALS["SITE_SETTINGS"]["IS_PRODUCT"]):?>
        	<div class="catalog">
            	<?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "", array(
						"START_FROM" => "0",
						"PATH" => "",
						"SITE_ID" => "-"
					),
					false,
					Array('HIDE_ICONS' => 'Y')
				);?>
          	</div>
        	<?elseif(!$GLOBALS["SITE_SETTINGS"]["IS_MAIN_EMPTY"]):?>
        		<?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "", array(
						"START_FROM" => "0",
						"PATH" => "",
						"SITE_ID" => "-"
					),
					false,
					Array('HIDE_ICONS' => 'Y')
				);?>
				<div class="text-page">
					<div class="section-title">
						<svg width="24" height="24"><use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#arrow-big-left-icon"></use></svg>
						<?$APPLICATION->ShowTitle()?>
					</div>
				
			<?endif;?>
	<?endif;?>