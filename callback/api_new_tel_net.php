<?

define('STOP_STATISTICS', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$Put = file_get_contents('php://input');
/*
$Put = '{"callId":"987bb0e2-a06d-43b5-b5c6-95bc6759a9f6","clientNumber":"79527979093","confirmationNumber":"78007773740","isMnp":false,"operatorName":"\u041a\u0430\u043b\u0438\u043d\u0438\u043d\u0433\u0440\u0430\u0434\u0441\u043a\u0430\u044f \u043e\u0431\u043b.","operatorNameMnp":null,"regionName":"\u041e\u041e\u041e \u04222 \u041c\u041e\u0411\u0410\u0419\u041b","qrCodeUri":"https:\/\/api.new-tel.net\/files\/qr-code\/call-password-id\/987bb0e2-a06d-43b5-b5c6-95bc6759a9f6.png","userData":"1234","mode":"busy","callDuration":0,"talkDuration":0}';
*/
$ArPut = json_decode($Put,true);

$UserData = "";
$ClientNumber = 0;
$UserId = 0;
$CallId = "";

if(!empty($ArPut["userData"])){
	$UserData = htmlspecialchars($ArPut["userData"]);
}

if(!empty($ArPut["callId"])){
	$CallId = htmlspecialchars($ArPut["callId"]);
}

if(!empty($ArPut["clientNumber"])){
	$ClientNumber = intval($ArPut["clientNumber"]);
}

if($UserData != "" && $ClientNumber > 0){
	
	$Query = \Bitrix\Main\UserPhoneAuthTable::getList(array(
		"select" => array("USER_ID","PHONE_NUMBER"),
		"filter" => array("PHONE_NUMBER" => "+".$ClientNumber)
	));
	
	if($Answer = $Query->Fetch()){
		$UserId = $Answer["USER_ID"];
	}
	
	if($UserId > 0){
		$Query = Bitrix\Main\UserTable::getList(array(
			"select" => array("ID","UF_PHONE_AUTH_CODE"),
			"filter" => array(
				"ID" => $UserId,
			),
			"limit" => 1,
		));
		if($Answer = $Query->Fetch()){
			
			if(!empty($Answer["UF_PHONE_AUTH_CODE"]) && $Answer["UF_PHONE_AUTH_CODE"] == $UserData){
				$NUser = new CUser;
				$NUser->Update($Answer["ID"], array(
					"UF_PHONE_AUTH_CODE" => $CallId
				));
			}
			
		}
	}
}

/*
Array
(
    [callId] => 987bb0e2-a06d-43b5-b5c6-95bc6759a9f6
    [clientNumber] => 79527979093
    [confirmationNumber] => 78007773740
    [isMnp] => 
    [operatorName] => Калининградская обл.
    [operatorNameMnp] => 
    [regionName] => ООО Т2 МОБАЙЛ
    [qrCodeUri] => https://api.new-tel.net/files/qr-code/call-password-id/987bb0e2-a06d-43b5-b5c6-95bc6759a9f6.png
    [userData] => 1234
    [mode] => busy
    [callDuration] => 0
    [talkDuration] => 0
)
$Data = array(
	"GET" => $_GET,
	"POST" => $_POST,
	"PUT" => 
);


file_put_contents(__DIR__."/api_new_tel_net.log",json_encode($Data)."\r\n\r\n",FILE_APPEND);
*/
?>