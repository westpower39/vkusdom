<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

class CCustomPageBlock extends CBitrixComponent{
	public function onPrepareComponentParams($arParams){
		return $arParams;
	}
	public function executeComponent(){
		$this->includeComponentTemplate();
	}
}

?>