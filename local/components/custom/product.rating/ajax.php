<?
define('STOP_STATISTICS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
$APPLICATION->IncludeComponent(
	"custom:product.rating",
	"",
	Array(
		"AJAX" => "Y"
	)	
);
?>