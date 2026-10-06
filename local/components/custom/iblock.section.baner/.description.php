<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => "Баннеры разделов инфоблока",
	"DESCRIPTION" => "Баннеры разделов инфоблока",
	"ICON" => "",
	"COMPLEX" => "Y",
	"PATH" => array(
		"ID" => "custom",
		"NAME" => "Свое",
		"CHILD" => array(
			"ID" => "iblock",
			"NAME" => "Инфоблоки",
		),
	),
);
?>