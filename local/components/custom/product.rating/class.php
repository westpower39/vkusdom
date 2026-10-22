<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class CCustomProductRating extends CBitrixComponent{
	public function onPrepareComponentParams($arParams){
		return $arParams;
	}
	public function executeComponent(){
		global $USER;
		
		Loader::includeModule("sale");
		Loader::includeModule("iblock");
		
		if($USER->IsAuthorized()){
			
			$UserId = $USER->GetID();
			
			
			
			if($this->arParams["AJAX"] == "Y"){
				
				
				$this->Set($UserId);
				
				
			} else {
				
				$this->Get($UserId);
				
			}
		}
		
		if($this->arParams["AJAX"] == "Y"){
			die();
		} else {
			$this->includeComponentTemplate();
		}
	}
	
	private function Set($UserId){
		global $USER;
		
		$OrderId = intval($_POST["ORDER_ID"]);
		$BasketId = intval($_POST["BASKET_ID"]);
		$ProductId = intval($_POST["PRODUCT_ID"]);
		$Rating = intval($_POST["RATING"]);
		
		$TotalCount =0;
		$TotalRating =0;
		
		
		$Flag = false;
		
		if($OrderId > 0 && $BasketId > 0 && $ProductId > 0 && $Rating > 0){
			
			$Query = Bitrix\Sale\Internals\BasketTable::getList([
			    'select' => [
			    	"ID"
			    ],
			    'filter' => [
			    	'ORDER_ID' => $OrderId,
			    	"ID" => $BasketId,
			    	"PRODUCT_ID" => $ProductId
			    ],
			]);
			
			if($Answer = $Query->Fetch()){
				$Flag = true;
			}
		}
		
		if($Flag){
			
			
			
			$Hb7 = PhpInterface\Libs\Help::GetHighloadBlock(7);
			
			$Flag = true;
			
			$Query = $Hb7::getList(array(
				"select" => array("ID"),
				"filter" => array(
					"UF_BASKET_ID" => $BasketId,
					"UF_ORDER_ID" => $OrderId,
					"UF_PRODUCT_ID" => $ProductId
				)
			));
			
			if($Answer = $Query->Fetch()){
				$Flag = false;
			}
			
			if($Flag){
				
				$FullName = $USER->GetFullName();
				
				if($FullName == ""){
					$FullName = "Анонимный пользователь";
				}
				
				$Hb7::add(array(
					"UF_RATING" => $Rating,
					"UF_PRODUCT_ID" => $ProductId,
					"UF_BASKET_ID" => $BasketId,
					"UF_ORDER_ID" => $OrderId,
					"UF_USER_ID" => $UserId,
					"UF_USER_FIO" =>$FullName,
					"UF_DATE" => date("d.m.Y")
				));
				
				
				$Query = $Hb7::getList(array(
					"select" => array("COUNT"),
					"filter" => array(
						"UF_PRODUCT_ID" => $ProductId
					),
					'runtime' => array(
				        new \Bitrix\Main\ORM\Fields\ExpressionField('COUNT',  'COUNT(ID)' )
				    ),
				));
				if($Answer = $Query->Fetch()){
					$TotalCount = $Answer["COUNT"];
				}
				
				$Query = $Hb7::getList(array(
					"select" => array("SUM"),
					"filter" => array(
						"UF_PRODUCT_ID" => $ProductId
					),
					'runtime' => array(
				        new \Bitrix\Main\ORM\Fields\ExpressionField('SUM',  'SUM(UF_RATING)' )
				    ),
				));
				if($Answer = $Query->Fetch()){
					$TotalRating = $Answer["SUM"];
				}
				
				
				
				$Hb8 = PhpInterface\Libs\Help::GetHighloadBlock(8);
				
				$Query = $Hb8::getList(array(
					"select" => array("ID"),
					"filter" => array(
						"UF_PRODUCT_ID" => $ProductId
					)
				));
				if($Answer = $Query->Fetch()){
					
					$Hb8::update($Answer["ID"],array(
						"UF_RATING" => round($TotalRating/$TotalCount,1),
						"UF_COUNT" => $TotalCount
					));

					
				} else {
					$Hb8::add(array(
						"UF_PRODUCT_ID" => $ProductId,
						"UF_RATING" => round($TotalRating/$TotalCount,1),
						"UF_COUNT" => $TotalCount
					));
				}
			}
			
		}
		
	}
	
	private function Get($UserId){
		
		$this->arResult["ITEMS"] = array();
		$this->arResult["ITEMS_IMG"] = array();
		
		$OrdersId = array();
		$ProductsId = array();
		
		$Query = \Bitrix\Sale\OrderTable::getList([
		    'select' => ["ID","STATUS_ID","PAYED"],
		    'filter' => ['USER_ID' => $UserId],
		]);
		while ($Answer = $Query->fetch()) {
			$OrdersId[] = $Answer["ID"];
			$Orders[$Answer["ID"]] = $Answer;
		}
		
		
		
		if(!empty($OrdersId)){
			
			$IgnoreBasketId = array();
			
			$Query = \Bitrix\Sale\Internals\StatusLangTable::getList([
			    'select' => ['STATUS_ID',"NAME"],
			    'filter' => [
			        'STATUS.TYPE' => 'O', // 'O' - заказ, 'D' - доставка
			        'LID'=>LANGUAGE_ID
			    ],
			]);
			while ($Answer = $Query->fetch()) {
				$Status[$Answer["STATUS_ID"]] = $Answer["NAME"];
			}
			
			$Hb7 = PhpInterface\Libs\Help::GetHighloadBlock(7);
			
			$Query = $Hb7::getList(array(
				"select" => array("UF_BASKET_ID"),
				"filter" => array(
					"UF_USER_ID" => $UserId,
				)
			));
			while ($Answer = $Query->fetch()) {
				$IgnoreBasketId[] = $Answer["UF_BASKET_ID"];
			}
			
			$Query = Bitrix\Sale\Internals\BasketTable::getList([
			    'select' => [
			    	"ID",
			    	'ORDER_ID',
			    	"PRODUCT_ID",
			    	"NAME",
			    	"DETAIL_PAGE_URL",
			    	"DATE_INSERT"
			    ],
			    'filter' => [
			    	'ORDER_ID' => $OrdersId,
			    	"!ID" => $IgnoreBasketId
			    ],
			]);

			while ($Answer = $Query->fetch()) {
				
				$Answer["ORDER_STATUS_ID"] = $Orders[$Answer["ORDER_ID"]]["STATUS_ID"];
				$Answer["ORDER_PAYED"] = $Orders[$Answer["ORDER_ID"]]["PAYED"];
				$Answer["ORDER_STATUS_NAME"] = $Status[$Answer["ORDER_STATUS_ID"]];
				
				
				$this->arResult["ITEMS"][] = $Answer;
				$ProductsId[] = $Answer["PRODUCT_ID"];
				
			}
			
			if(!empty($ProductsId)){
				$Query = \Bitrix\Iblock\ElementTable::getList(array(
					"select" => array('ID',"DETAIL_PICTURE","PREVIEW_PICTURE"),
					"filter" => array(
						"ID" => $ProductsId
					)
				));
				while ($Answer = $Query->fetch()) {
					
					if($Answer["PREVIEW_PICTURE"] != ""){
						$this->arResult["ITEMS_IMG"][$Answer["ID"]] = CFile::GetPath($Answer["PREVIEW_PICTURE"]);
					} else if($Answer["DETAIL_PICTURE"] != ""){
						$this->arResult["ITEMS_IMG"][$Answer["ID"]] = CFile::GetPath($Answer["DETAIL_PICTURE"]);
					}
				}
			}
		}
	}
	
}

?>