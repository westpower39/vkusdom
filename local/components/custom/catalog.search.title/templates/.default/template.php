<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);?>
<?php
$INPUT_ID = trim($arParams['~INPUT_ID']);
if ($INPUT_ID == '')
{
	$INPUT_ID = 'CatalogSearchTitleInput';
}
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams['~CONTAINER_ID']);
if ($CONTAINER_ID == '')
{
	$CONTAINER_ID = 'CatalogSearchTitle';
}
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);

if ($arParams['SHOW_INPUT'] !== 'N'):

$RESULT_ID = trim($arParams['~RESULT_ID']);
if ($RESULT_ID == '')
{
	$RESULT_ID = 'CatalogSearchTitleResult';
}

?>
<div id="<?php echo $CONTAINER_ID?>" class="header-search">
	<form action="<?php echo $arResult['FORM_ACTION']?>" >
		<div class="header-search-form">
			<button class="btn header-search__catalog-button">
				<svg width="24" height="24">
					<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#menu-icon">
					</use>
				</svg>
				<span>
					Каталог
				</span>
			</button>
			<input id="<?php echo $INPUT_ID?>" class="header-search__input" type="text" name="q" placeholder="Поиск по сайту">
			<button class="header-search__clear">
				<svg width="32" height="32">
					<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#close-icon">
					</use>
				</svg>
			</button>
			<button name="s" class="header-search__button" type="submit">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<g clip-path="url(#clip0_3001_30449)">
						<path d="M10.5 18C14.6421 18 18 14.6421 18 10.5C18 6.35786 14.6421 3 10.5 3C6.35786 3 3 6.35786 3 10.5C3 14.6421 6.35786 18 10.5 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /><path d="M15.8047 15.8027L21.0012 20.9993" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
					</g>
					<defs>
						<clipPath id="clip0_3001_30449">
							<rect width="24" height="24" fill="white" />
						</clipPath>
					</defs>
				</svg>
			</button>
		</div>
		<div class="title-search-result" id="<?=$RESULT_ID?>"></div>
		<script>
			BX.ready(function(){
				new CustomCatalogSearchTitle({
					'AJAX_PAGE' : '/ajax/catalog_search_title.php',
					'CONTAINER_ID': '<?php echo $CONTAINER_ID?>',
					'INPUT_ID': '<?php echo $INPUT_ID?>',
					'RESULT_ID': '<?php echo $RESULT_ID?>',
					'MIN_QUERY_LEN': 2
				});
			});
		</script>
	</form>
</div>
<?php endif?>