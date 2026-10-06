<?
namespace PhpInterface\Libs;

use Bitrix\Main\Loader;

class MethodObtaining {
	
	private static $UserData = array();
	
	private static $IsInit = false;
	
	private static $DefaultStoreId = 0;
	private static $DefaultTypeId = 0;
	private static $Stores = array();
	
	private static $Types = array();
	
	
	private static function Init(){
		
		if(!self::$IsInit){
			self::$IsInit = true;
			
			Loader::includeModule("catalog");
			
			$Query =\Bitrix\Catalog\StoreTable::getList(array(
				"select" => array("*"),
				"filter" => array(
					"ISSUING_CENTER" => "Y",
					"ACTIVE" => "Y"
				)
			));
			while($Answer = $Query->Fetch()){
				self::$Stores[] = $Answer;
			}
			
			$Query =\Bitrix\Catalog\StoreTable::getList(array(
				"select" => array("ID"),
				"filter" => array(
					"ISSUING_CENTER" => "Y",
					"ACTIVE" => "Y",
					'=IS_DEFAULT' => 'Y'
				)
			));
			if($Answer = $Query->Fetch()){
				self::$DefaultStoreId = $Answer["ID"];
			}
			
			if(self::$DefaultStoreId == 0 && !empty(self::$Stores) ){
				self::$DefaultStoreId = self::$Stores[0]["ID"];
			}
			
			
			self::InitTypes();
		}
		
	}
	
	public static function GetStores(){
		self::Init();
		return self::$Stores;
	}
	
	public static function GetUserData(){
		global $USER;
		
		self::Init();
		
		if(empty(self::$UserData)){
			
			if($USER->IsAuthorized()){
			
				$Query = \Bitrix\Main\UserTable::getList(array(
					"select" => array("ID","UF_METHOD_OBTAINING_TYPE","UF_METHOD_OBTAINING_STORE_ID","UF_METHOD_OBTAINING_ADDRESS","UF_METHOD_OBTAINING_LAT","UF_METHOD_OBTAINING_LON"),
					"filter" => array(
						"ID" => $USER->GetID()
					)
				));
				if($Answer = $Query->Fetch()){
					self::$UserData = array(
						"TYPE_ID" => $Answer["UF_METHOD_OBTAINING_TYPE"],
						"TYPE" => array(),
						"STORE_ID" => $Answer["UF_METHOD_OBTAINING_STORE_ID"],
						"STORE" => array(),
						"ADDRESS" => $Answer["UF_METHOD_OBTAINING_ADDRESS"],
						"LAT" => $Answer["UF_METHOD_OBTAINING_LAT"],
						"LON" => $Answer["UF_METHOD_OBTAINING_LON"],
					);
				}
				
			} else {
				self::$UserData = array(
					"TYPE_ID" => intval( isset($_COOKIE["METHOD_OBTAINING_TYPE"]) ? $_COOKIE["METHOD_OBTAINING_TYPE"] : 0 ),
					"TYPE" => array(),
					"STORE_ID" => intval( isset($_COOKIE["METHOD_OBTAINING_TYPE"]) ? $_COOKIE["METHOD_OBTAINING_TYPE"] : 0 ),
					"STORE" => array(),
					"ADDRESS" => htmlspecialchars( isset($_COOKIE["METHOD_OBTAINING_ADDRESS"]) ? $_COOKIE["METHOD_OBTAINING_ADDRESS"] : 0 ),
					"LAT" => floatval( isset($_COOKIE["METHOD_OBTAINING_LAT"]) ? $_COOKIE["METHOD_OBTAINING_LAT"] : 0 ),
					"LON" => floatval( isset($_COOKIE["METHOD_OBTAINING_LON"]) ? $_COOKIE["METHOD_OBTAINING_LON"] : 0 ),
				);	
			}
			
			if(empty(self::$UserData) || self::$UserData["TYPE_ID"] == 0){
				self::$UserData = self::GetUserDataDefaul();
			}
			
			if(self::$UserData["TYPE_ID"] > 0){
				foreach(self::$Types as $key => $value){
					if($value["ID"] == self::$UserData["TYPE_ID"]){
						self::$UserData["TYPE"] = $value;
						break;
					}
				}
			}
			
			if(self::$UserData["STORE_ID"] > 0){
				foreach(self::$Stores as $key => $value){
					if($value["ID"] == self::$UserData["STORE_ID"]){
						self::$UserData["STORE"] = $value;
						break;
					}
				}
			}
			
		}
		
		
		
		return self::$UserData;
	}
	
	private static function GetUserDataDefaul(){
		$Result = array(
			"TYPE_ID" => self::$DefaultTypeId,
			"TYPE" => array(),
			"STORE_ID" => self::$DefaultStoreId,
			"STORE" => array(),
			"ADDRESS" => "",
			"LAT" => 0,
			"LON" => 0,
		);
		
		return $Result;
	}
	
	private static function InitTypes(){
		
		$Hb5 = \PhpInterface\Libs\Help::GetHighloadBlock(5);
			
		$Query = $Hb5::getList(array(
			"select" => array("*")
		));
	
		while($Answer = $Query->Fetch()){
			
			$Answer["COMPARISON"] = array(
				"PERSON_TYPE_TO_DELIVERY" => array(),
			);
			
			foreach($Answer["UF_PERSON_TYPE_IDS"] as $key => $value){
				
				if(!empty($value) && !empty($Answer["UF_DELIVERY_IDS"][$key])){
					
					if(!isset($Answer["COMPARISON"]["PERSON_TYPE_TO_DELIVERY"][$value])){
						$Answer["COMPARISON"]["PERSON_TYPE_TO_DELIVERY"][$value] = array();
					}
					$Answer["COMPARISON"]["PERSON_TYPE_TO_DELIVERY"][$value] = $Answer["UF_DELIVERY_IDS"][$key];
				}
				
			}
			
			$Answer["DELIVERY_TIMES"] = self::GetDeliveryTimes($Answer["ID"]);
			
			
			$Answer["DELIVERY_DATES"] = self::GetDeliveryDates($Answer["UF_DAYS_COUNT"]);
			
			self::$Types[] = $Answer;
		}
		
		if(self::$DefaultTypeId == 0 && !empty(self::$Types) ){
			self::$DefaultTypeId = self::$Types[0]["ID"];
		}
		
	}
	
	private static function GetDeliveryDates($DaysCount = 0){
		$Result = array();
		
		if($DaysCount > 0){
			$CTime = strtotime(date("d.m.Y"));
		
			for($i =0; $i < $DaysCount;$i++){
				$Result[] = date("d.m.Y",$CTime);
				$CTime +=86400;
			}
		}
		
		
		
		
		
		return $Result;
	}
	
	public static function GetDeliveryTimes($Id = 0){
		$Result = array();
		
		if($Id > 0){
			$Hb6 = \PhpInterface\Libs\Help::GetHighloadBlock(6);
		
			$Query = $Hb6::getList(array(
				"select" => array("*"),
				"filter" => array(
					"UF_METHOD_OBTAINING_ID" => $Id
				),
				"order" => array("UF_SORT" => "ASC")
			));
			while($Answer = $Query->Fetch()){
				$Result[] = $Answer;
			}
		}
		
		
		
		return $Result;
	}
	
}



?>