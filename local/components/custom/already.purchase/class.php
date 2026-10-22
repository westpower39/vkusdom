<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class CCustomAlreadyPurchase extends CBitrixComponent{
	public function onPrepareComponentParams($arParams){
		return $arParams;
	}
	public function executeComponent(){
		global $USER;
		
		Loader::includeModule("sale");
		
		$this->arResult["ITEMS"] = array();
		
		if($USER->IsAuthorized()){
			
			$UserId = $USER->GetID();
			
			$OrdersId = array();
			
			$Query = \Bitrix\Sale\OrderTable::getList([
			    'select' => ["ID"],
			    'filter' => ['USER_ID' => $UserId],
			]);
			while ($Answer = $Query->fetch()) {
				$OrdersId[] = $Answer["ID"];
			}
			
			if(!empty($OrdersId)){
				$Query = Bitrix\Sale\Internals\BasketTable::getList([
				    'select' => [
				    	'PRODUCT_ID',
				    	'CNT' => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)'),
				    ],
				    'filter' => ['ORDER_ID' => $OrdersId],
				    'group' => ['PRODUCT_ID'],
				]);

				while ($Answer = $Query->fetch()) {
					$this->arResult["ITEMS"][] = $Answer["PRODUCT_ID"];
				}
			}
		}
		
		$this->includeComponentTemplate();
	}
}

?>