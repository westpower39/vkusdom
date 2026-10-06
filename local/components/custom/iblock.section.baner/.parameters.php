<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

/** @var array $arCurrentValues */

use Bitrix\Main\Loader;

if(!Loader::includeModule("iblock"))
{
	return;
}

$IblockTypes = CIBlockParameters::GetIBlockTypes();

$Iblock=array();
$IblockFilter = [
	'ACTIVE' => 'Y',
];
if (!empty($arCurrentValues['IBLOCK_TYPE'])){
	$IblockFilter['TYPE'] = $arCurrentValues['IBLOCK_TYPE'];
}
$Query = CIBlock::GetList(Array("sort" => "asc"), $IblockFilter);
while($Answer=$Query->Fetch()){
	$Iblock[$Answer["ID"]] = "[".$Answer["ID"]."] ".$Answer["NAME"];
}

$arComponentParameters = array(
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"NAME" => "Тип инфоблока",
			"TYPE" => "LIST",
			"VALUES" => $IblockTypes,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"NAME" => "Инфоблок",
			"TYPE" => "LIST",
			"VALUES" => $Iblock,
			"REFRESH" => "Y",
		),
		"SECIONS_ID" => array(
			"NAME" => "ID Раздела",
			"TYPE" => "STRING",
			"MULTIPLE" => "Y",
			"VALUES" => array(),
		),

	),
);