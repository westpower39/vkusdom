<?
define('STOP_STATISTICS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Authentication\ApplicationPasswordTable;

global $USER;

$NUser = new CUser;

$Result = array("STATUS" => false,"MESSAGE" => "","CODE" => "");

$PhoneNormal = isset($_POST["PHONE"]) ?  preg_replace("/\D+/i","",$_POST["PHONE"]) : "";

$Step = isset($_POST["STEP"]) ?  intval($_POST["STEP"]) : -1;

$Code = isset($_POST["CODE"]) && is_array($_POST["CODE"]) ? implode("",$_POST["CODE"]) : htmlspecialchars($_POST["CODE"]);
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
	            "PERSONAL_PHONE" => $_POST["PHONE"],
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
				
				/*
			    $PhoneAuthCode = rand(1111,9999);
			    $ANNResult = PhpInterface\Libs\ApiNewtelNet::CallPasswordIdStartWaitingModeBusy($PhoneNormal,$PhoneAuthCode);
			    */
			    
			    
			    /*
			    postman
			    
			    PUT
			    https://vkusdom.westpower.ru/callback/api_new_tel_net.php
			    
			    RAW:
			    {"callId":"987bb0e2-a06d-43b5-b5c6-95bc6759a9f6","clientNumber":"79527979093","confirmationNumber":"78007773740","isMnp":false,"operatorName":"\u041a\u0430\u043b\u0438\u043d\u0438\u043d\u0433\u0440\u0430\u0434\u0441\u043a\u0430\u044f \u043e\u0431\u043b.","operatorNameMnp":null,"regionName":"\u041e\u041e\u041e \u04222 \u041c\u041e\u0411\u0410\u0419\u041b","qrCodeUri":"https:\/\/api.new-tel.net\/files\/qr-code\/call-password-id\/987bb0e2-a06d-43b5-b5c6-95bc6759a9f6.png","userData":"1234","mode":"busy","callDuration":0,"talkDuration":0}
			    
			    $PhoneAuthCode = 1234;
			    
				$ANNResult = array(
					"status" => "success",
					"data" => array(
						"callDetails" => array(
							"callId" => "987bb0e2-a06d-43b5-b5c6-95bc6759a9f6",
							"confirmationNumber" => "78007773740"
						)
					)
				);
			    */
			    $PhoneAuthCode = 1234;
			    
				$ANNResult = array(
					"status" => "success",
					"data" => array(
						"callDetails" => array(
							"callId" => "987bb0e2-a06d-43b5-b5c6-95bc6759a9f6",
							"confirmationNumber" => "78007773740"
						)
					)
				);
				
				if(!empty($ANNResult) && $ANNResult["status"] == "success"){
					
					$NUser->Update($UserId, array(
						"UF_PHONE_AUTH_CODE" => $PhoneAuthCode
					));
					
					$ConfirmationNumber = $ANNResult["data"]["callDetails"]["confirmationNumber"];
					$ConfirmationNumberFormat = "+".substr($ConfirmationNumber,0,1)."(".substr($ConfirmationNumber,1,3).") ".substr($ConfirmationNumber,4,3)."-".substr($ConfirmationNumber,7,2)."-".substr($ConfirmationNumber,9,2);
					
					$Result["CODE"] = $ANNResult["data"]["callDetails"]["callId"];
					
					$Result["MESSAGE"] = "<a href=\"tel:".$ConfirmationNumber."\">".$ConfirmationNumberFormat."</a>";
					
					$Result["STATUS"] = true;
					
				} else {
					$Result["MESSAGE"] = "Проблема с оператором. Обратитесь к администратору сайта.";
				}
			    
			    
				
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