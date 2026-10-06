<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class CCustomCatalogSectionProducts extends CBitrixComponent{
	public function onPrepareComponentParams($arParams){
		
		$arParams["AJAX"] = !empty($arParams["AJAX"]) && $arParams["AJAX"] == "Y" ? "Y" : "N";
		$arParams["IBLOCK_ID"] = intval(!empty($arParams["IBLOCK_ID"]) ? $arParams["IBLOCK_ID"] : 0);
		$arParams["SECTION_ID"] = intval(!empty($arParams["SECTION_ID"]) ? $arParams["SECTION_ID"] : 0);
		
		return $arParams;
	}
	public function executeComponent(){
		
		Loader::includeModule("iblock");
		
		if($this->arParams["IBLOCK_ID"] > 0 && $this->arParams["SECTION_ID"] > 0){
			
			$Query = CIBlockSection::GetByID($this->arParams["SECTION_ID"]);
			if ($Answer = $Query->GetNext()){
				$Answer["ITEMS"] = array();
				$Answer["ACTIVE_ITEM_ID"] = 0;
				$this->arResult = $Answer;
			}
			
			//echo "<pre>";
			//print_r($this->arParams);
			//die();
			
			if(!empty($this->arResult)){
				$Query = CIBlockSection::GetList(
					array('SORT' => 'asc'),
					array(
						'IBLOCK_ID' => $this->arResult['IBLOCK_ID'],
						'>LEFT_MARGIN' => $this->arResult['LEFT_MARGIN'],
						'<RIGHT_MARGIN' => $this->arResult['RIGHT_MARGIN'],
						'=DEPTH_LEVEL' => $this->arResult['DEPTH_LEVEL']+1
					)
				);
				while ($Answer = $Query->GetNext()){
					$this->arResult["ITEMS"][] = $Answer;
					
					if(!empty($this->arParams["ACTIVE_ITEM_ID"])){
						
						if($this->arParams["ACTIVE_ITEM_ID"] == $Answer["ID"]){
							$this->arResult["ACTIVE_ITEM_ID"] = $Answer["ID"];
						}
					
					} elseif($this->arResult["ACTIVE_ITEM_ID"] == 0){
						$this->arResult["ACTIVE_ITEM_ID"] = $Answer["ID"];
					}
					
				}
				
				if($this->arResult["ACTIVE_ITEM_ID"] == 0){
					$this->arResult["ACTIVE_ITEM_ID"] = $this->arResult["ID"];
				}
				
				
				
			}
		}
	
		$this->includeComponentTemplate();
	}
}

?>