<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class CCustomIblockSectionBaner extends CBitrixComponent{
	public function onPrepareComponentParams($arParams){
		
		$Result["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
		$Result["IBLOCK_TYPE"] = htmlspecialchars($arParams["IBLOCK_TYPE"]);
		$Result["SECIONS_ID"] = array();
		
		
		
		if(is_array($arParams["SECIONS_ID"])){
			
			foreach($arParams["SECIONS_ID"] as $key => $value){
				if(!empty($value)){
					$Result["SECIONS_ID"][] = intval($value);
				}
				
			}
			
		}
	
		return $Result;
	}
	public function executeComponent(){
		
		Loader::includeModule("iblock");
		
		if($this->startResultCache()){
			
			if($this->arParams["IBLOCK_ID"] > 0 && !empty($this->arParams["SECIONS_ID"])){
				
				$this->arResult["ITEMS"] = array();
				
				$Query = CIBlockSection::GetList(
					array('SORT' => 'asc'),
					array(
						'ID' => $this->arParams["SECIONS_ID"],
						'IBLOCK_ID' => $this->arParams["IBLOCK_ID"],
						'GLOBAL_ACTIVE'=>'Y',
						'ACTIVE'=>'Y'
					)
				);
				while ($Answer = $Query->GetNext()){
					$Answer["ITEMS"] = array();
					$this->arResult["ITEMS"][] = $Answer;
				}
				
			
				foreach($this->arResult["ITEMS"] as $key =>$value){
					
					$Query = CIBlockSection::GetList(
						array('SORT' => 'asc'),
						array(
							'IBLOCK_ID' => $value['IBLOCK_ID'],
							'>LEFT_MARGIN' => $value['LEFT_MARGIN'],
							'<RIGHT_MARGIN' => $value['RIGHT_MARGIN'],
							'=DEPTH_LEVEL' => $value['DEPTH_LEVEL']+1,
							'GLOBAL_ACTIVE'=>'Y',
							'ACTIVE'=>'Y'
						),
						false,
						array("*"),
						array(
							"nPageSize" => 4
						)
					);
					while ($Answer = $Query->GetNext()){
						$this->arResult["ITEMS"]["$key"]["ITEMS"][] = $Answer;
					}
				}
				
			}
			
			$this->includeComponentTemplate();
		}
	}
}

?>