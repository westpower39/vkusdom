<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true){
	die();
}


$arParams["ELEMENT_SORT_FIELD"] = "";
$arParams["ELEMENT_SORT_ORDER"] = "";

$SortId = intval(isset($_GET["sort"]) ? $_GET["sort"] : 0);
$Sort = array();
$Sorts = array(
	array(
		"FIELD" => "NAME",
		"ORDER" => "ASC",
		"NAME" => "По алфавиту"
	),
	array(
		"FIELD" => "CATALOG_PRICE_2",
		"ORDER" => "ASC",
		"NAME" => "Сначала дешевле"
	),
	array(
		"FIELD" => "CATALOG_PRICE_2",
		"ORDER" => "DESC",
		"NAME" => "Сначала дороже"
	)
);

if(!isset($Sorts[$SortId])){
	$SortId = 0;
}

$Sort = $Sorts[$SortId];

$arParams["ELEMENT_SORT_FIELD"] = $Sort["FIELD"];
$arParams["ELEMENT_SORT_ORDER"] = $Sort["ORDER"];

?>
<div class="catalog__buttons-filter">
	<button class="btn btn_mobile-filter">
		<svg width="16" height="16">
			<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#mobile-filter-icon">
			</use>
		</svg>Все фильтры
	</button>
	<div class="catalog__sort-select">
		<form id="FormSort">
			<select name="sort">
				<?foreach($Sorts as $key => $value):?>
				<option <?=$key==$SortId ? "selected=\"\"" : ""?>  value="<?=$key?>"><?=$value["NAME"]?></option>
				<?endforeach;?>
			</select>
		</form>
	</div>
</div>