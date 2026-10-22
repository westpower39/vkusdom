<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CCustomFavoritUser extends CBitrixComponent{
	public function onPrepareComponentParams($arParams){
		return $arParams;
	}
	public function executeComponent(){
		global $USER;
		
		$this->arResult["ITEMS"] = array();
		
		if($USER->IsAuthorized()){
	
			$UserId = $USER->GetID();
			
			$Hb4 = PhpInterface\Libs\Help::GetHighloadBlock(4);
		
			$Query = $Hb4::getList(array(
				"select" => array("UF_PRODUCT_ID"),
				"filter" => array("UF_USER_ID" =>$UserId)
			));
			while($Answer = $Query->Fetch()){
				$this->arResult["ITEMS"][] = $Answer["UF_PRODUCT_ID"];
			}
			
		}
		
		$this->includeComponentTemplate();
	}
}

?>