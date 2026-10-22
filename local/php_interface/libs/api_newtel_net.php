<?
namespace PhpInterface\Libs;

class ApiNewtelNet {
	
	private static $AuthKey = "99f8072319e20d5de20ab50842333b1d4a8765f4660790de";
	private static $SignKey = "ffbcdfa2cfd4c89adaa4dec8716474f45649a0baff3219f5";
	
	private static $CallbackLink = "https://vkusdom.westpower.ru/callback/api_new_tel_net.php";
	private static $Timeout = 300;
	
	/*
	Array
	(
	    [status] => success
	    [data] => Array
	        (
	            [result] => success
	            [callDetails] => Array
	                (
	                    [callId] => 478564f9-fa26-4c81-9d22-de0ed4044aa9
	                    [callbackLink] => https://vkusdom.westpower.ru/callback/api_new_tel_net.php
	                    [clientNumber] => 79527979093
	                    [confirmationNumber] => 78007773740
	                    [isMnp] => 
	                    [operatorName] => Калининградская обл.
	                    [operatorNameMnp] => 
	                    [regionName] => ООО Т2 МОБАЙЛ
	                    [qrCodeUri] => https://api.new-tel.net/files/qr-code/call-password-id/478564f9-fa26-4c81-9d22-de0ed4044aa9.png
	                    [userData] => 1234
	                )

	        )

	)
	*/
	
	public static function CallPasswordIdStartWaitingModeBusy($ClientNumber = "",$UserData = ""){
		$Result = array();
		$ArData = array();
	
		
		if($ClientNumber != "" && $UserData!= ""){
			$ArData = array(
				'callbackLink' => self::$CallbackLink,
			    'clientNumber' => $ClientNumber,
			    'timeout' => self::$Timeout,
			    'userData' => $UserData,
			);
			
		}
		
		if(!empty($ArData)){
			
			$Response = self::Send("call-password-id/start-waiting-mode-busy",$ArData);
			
			
			$Result = json_decode($Response,true);
		}
		
		return $Result;
	}
	
	private static function GetToken($authKey, $signKey, $methodName, $params, $time){
		 return $authKey . $time . hash('sha256',$methodName . "\n" . $time . "\n" . $authKey . "\n" . $params . "\n" . $signKey);
	}
	
	private static function Send($Url = "",$Data = array()){
		$Result = "";
		$Time  = time();
		
		$StrData = json_encode($Data);
		
		$Token = self::GetToken(self::$AuthKey,self::$SignKey,$Url,$StrData,$Time);
		
		if($Ci = curl_init()){
			
			curl_setopt($Ci, CURLINFO_HEADER_OUT, true);

			curl_setopt($Ci, CURLOPT_HEADER, true);
			curl_setopt($Ci, CURLOPT_HTTPHEADER, [
			    'authorization: Bearer ' . $Token,
			    'content-type: application/json',
			]);
			curl_setopt($Ci, CURLOPT_POST, true);
			curl_setopt($Ci, CURLOPT_POSTFIELDS, $StrData);
			curl_setopt($Ci, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($Ci, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($Ci, CURLOPT_URL,
			    'https://api.new-tel.net/'.$Url,
			);

			$Response = curl_exec($Ci);
			
			$HeaderSize = curl_getinfo($Ci, CURLINFO_HEADER_SIZE);

			$Header = substr($Response, 0, $HeaderSize);
			$Body = substr($Response, $HeaderSize);
			
			$Result = $Body;
			
			curl_close($Ci);
		}
		
		return $Result;
	}	
}
?>