<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Main\Loader;
Loader::includeModule("sale");

global $USER;

$Result = array(
	"ID" => 0
);

$Id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$Action = isset($_POST["action"]) ? htmlspecialchars($_POST["action"]) : "";

if($USER->IsAuthorized()){
	
	if($Action == "add"){
		$Fields = array(
			"NAME" => "Профиль",
			"USER_ID" => $USER->GetID(),
			"PERSON_TYPE_ID" => $Id
		);
		$Result["ID"] = CSaleOrderUserProps::Add($Fields);
		
	}
	
}

echo json_encode($Result);
?>