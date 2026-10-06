<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");


global $USER;




$Result = array(
	"ADD" => array(
		"IDS" => array(),
	),
);

$UserId = 0;

$Id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$Action = isset($_POST["action"]) ? htmlspecialchars($_POST["action"]) : "";


if($USER->IsAuthorized()){
	
	$UserId = $USER->GetID();
	
	$Hb1 = PhpInterface\Libs\Help::GetHighloadBlock(4);
	
	if($Action == "add" && $Id > 0){
		
		$Query = $Hb1::getList(array(
			"select" => array("*"),
			"filter" => array("UF_USER_ID" =>$UserId,"UF_PRODUCT_ID" => $Id)
		));
		if($Answer = $Query->Fetch()){
			
			$Hb1::delete($Answer["ID"]);
			
		} else {
			$Hb1::add(array(
				"UF_USER_ID" => $UserId,
				"UF_PRODUCT_ID" => $Id
			));
		}
		
	}
	
	$Query = $Hb1::getList(array(
		"select" => array("*"),
		"filter" => array("UF_USER_ID" =>$UserId)
	));
	while($Answer = $Query->Fetch()){
		
		$Result["ADD"]["IDS"][] = $Answer["UF_PRODUCT_ID"];
	}
	
	
}

echo json_encode($Result);
?>