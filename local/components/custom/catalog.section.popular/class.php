<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class CCustomCatalogSectionPopular extends CBitrixComponent{
	public function onPrepareComponentParams($arParams){
		return $arParams;
	}
	public function executeComponent(){
		
		Loader::includeModule("iblock");
		
		if($this->startResultCache()) {
		
			if($this->arParams["IBLOCK_ID"] > 0){
				
				$Query = CIBlockSection::GetList(
					array('SORT' => 'asc'),
					array(
						'IBLOCK_ID' => $this->arParams["IBLOCK_ID"],
						"ACTIVE" => "Y",
						"UF_POPULAR" => true
					)
				);
				while ($Answer = $Query->GetNext()){
					$this->arResult["ITEMS"][] = $Answer;
				}
			}
			
			$this->includeComponentTemplate();
		}
	
		
	}
}

?>