<?
namespace PhpInterface\Libs;

use \Bitrix\Main\Loader;

class Help {
	
	private static $IsInit = false;
	private static $HighloadBlock = array();
	private static $DefaultProductWeight = -1;
	
	private static function Init(){
		if(!self::$IsInit){
			self::$IsInit = true;	
			Loader::IncludeModule("highloadblock");
		}
	}
	
	public static function DefaultProductWeight(){
		self::Init();
		
		if(self::$DefaultProductWeight == -1){
			$Hb10 = self::GetHighloadBlock(10);
		
			$Query = $Hb10::getList(array(
				"select" => array("*"),
				"limit" => 1
			));
			if($Answer = $Query->Fetch()){
				self::$DefaultProductWeight = floatval($Answer["UF_WEIGHT"]);
			}
		}
		
		return self::$DefaultProductWeight;
	}
	
	public static function Declension($Number = 1, $Titles = array()) {
	    $Cases = [2, 0, 1, 1, 1, 2];
	    return $Titles[($Number % 100 > 4 && $Number % 100 < 20) ? 2 : $Cases[min($Number % 10, 5)]];
	}
	
	public static function GetHighloadBlock($Id = 0){
		$Result = null;
		if($Id > 0){
			
			if(isset(self::$HighloadBlock[$Id])){
				$Result = self::$HighloadBlock[$Id];
			} else {
				
				self::Init();
				
				$Query = \Bitrix\Highloadblock\HighloadBlockTable::getById($Id);
				if($Answer = $Query->fetch()){
					$Result = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($Answer)->getDataClass();
					self::$HighloadBlock[$Id] = $Result;
				}
				
				
			}
			
			
		}
		return $Result;
	}
	
}



?>