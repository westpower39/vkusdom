<?
define('STOP_STATISTICS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Authentication\ApplicationPasswordTable;

global $USER;

$NUser = new CUser;

$Result = array("STATUS" => false,"MESSAGE" => "");

$PhoneNormal = isset($_POST["PHONE"]) ?  preg_replace("/\D+/i","",$_POST["PHONE"]) : "";

$Step = isset($_POST["STEP"]) ?  intval($_POST["STEP"]) : -1;

$Code = isset($_POST["CODE"]) && is_array($_POST["CODE"]) ? implode("",$_POST["CODE"]) : "";
$IAgree = isset($_POST["I_AGREE"]) && $_POST["I_AGREE"] == "Y";

if(!$USER->IsAuthorized()){

	if(strlen($PhoneNormal) == 11 && $IAgree){
		
		$UserId = 0;
		$Query = \Bitrix\Main\UserPhoneAuthTable::getList(array(
			"select" => array("USER_ID","PHONE_NUMBER"),
			"filter" => array("PHONE_NUMBER" => "+".$PhoneNormal)
		));
		if($Answer = $Query->Fetch()){
			$UserId = $Answer["USER_ID"];
		} else {
			$Password = ApplicationPasswordTable::generatePassword();
			
			$Fields = Array(
	            "LOGIN" => $PhoneNormal,
	            "PHONE_NUMBER" => $PhoneNormal,
	            "ACTIVE" => "N",
	            "GROUP_ID" => array(2,3,4,6),
	            "PASSWORD" => $Password,
	            "CONFIRM_PASSWORD" => $Password,
	        );
	        if($UserId = $NUser->Add($Fields)){
				
			} else {
				$Result["MESSAGE"] = $NUser->LAST_ERROR;
			}
		}
		
		if($UserId >0){
			
			if($Step == 1){
				
				$Query = Bitrix\Main\UserTable::getList(array(
					"select" => array("ID","ACTIVE","UF_PHONE_AUTH_CODE"),
					"filter" => array(
						"ID" => $UserId,
					),
					"limit" => 1,
				));
				if($Answer = $Query->Fetch()){
					
					if($Answer["UF_PHONE_AUTH_CODE"] == $Code){
						$Result["STATUS"] = true;
						
						$Fields = array();
						
						if($Answer["ACTIVE"] == "N"){
							$Fields["ACTIVE"] = "Y";
						}
						
						$NUser->Update($Answer["ID"], $Fields);
						
						$USER->Authorize($Answer["ID"]);
					} else {
						$Result["MESSAGE"] = "Неверный код.";
					}
					
				}
				
			} else {
				
			    $PhoneAuthCode = 1234;
			    
			    $NUser->Update($UserId, array(
					"UF_PHONE_AUTH_CODE" => $PhoneAuthCode
				));
				
				$Result["STATUS"] = true;
				
			}
			
		}
		
		
		
	} else {
		
		if(!$IAgree){
			$Result["MESSAGE"] = "Подтвердите согласие на обработку персональных данных.";
		} else {
			$Result["MESSAGE"] = "Некорректный формат телефона.";
		}
		
		
	}
}


echo json_encode($Result);

?>