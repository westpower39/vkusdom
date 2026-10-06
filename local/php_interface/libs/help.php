<?
namespace PhpInterface\Libs;

use \Bitrix\Main\Loader;

class Help {
	
	public static function Declension($Number = 1, $Titles = array()) {
	    $Cases = [2, 0, 1, 1, 1, 2];
	    return $Titles[($Number % 100 > 4 && $Number % 100 < 20) ? 2 : $Cases[min($Number % 10, 5)]];
	}
	
	public static function GetHighloadBlock($Id = 0){
		$Result = null;
		if($Id > 0){
			Loader::IncludeModule("highloadblock");
			$Query = \Bitrix\Highloadblock\HighloadBlockTable::getById($Id);
			if($Answer = $Query->fetch()){
				$Result = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($Answer)->getDataClass();
			}
		}
		return $Result;
	}
	
}



?>