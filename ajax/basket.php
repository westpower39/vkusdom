<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Context,
	Bitrix\Main\Loader;

Loader::includeModule("sale");
Loader::includeModule("iblock");

$Result = array(
	"ADD" => array(
		"IDS" => array(),
		"ITEMS" => array()
	),
);

$Id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$Action = isset($_POST["action"]) ? htmlspecialchars($_POST["action"]) : "";
$Quantity= isset($_POST["quantity"]) ? intval($_POST["quantity"]) : 0;



if($Action == "add"){
	
	if($Id > 0){
		$Basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
	
		$Query = Bitrix\Sale\Internals\BasketTable::getList(array(
		    "select" => array("ID","PRODUCT_ID","DELAY"),
		    'filter' => array(
		        'FUSER_ID' => Bitrix\Sale\Fuser::getId(), 
		        'ORDER_ID' => null,
		        'LID' => SITE_ID,
		        'PRODUCT_ID' => $Id,
		    )
		));
		if($Answer = $Query->Fetch()){
			
			$BasketItem = $Basket->getItemById($Answer["ID"]);
			
			if($Quantity == 0){
				$BasketItem->delete();
			} else {
				$BasketItem->setField("QUANTITY",$Quantity);
			}
			
			$Basket->save();
			
		} else {
		
			$BasketItem = $Basket->createItem('catalog', $Id);
		    $BasketItem->setFields(array(
		        'QUANTITY' => $Quantity,
		        'CURRENCY' => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
		        'LID' => Bitrix\Main\Context::getCurrent()->getSite(),
		        'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
		        "DELAY" => $Action == "delay" ? "Y" : "N"
		    ));
			$Basket->save();
		}
	}
	
} elseif($Action == "clear"){
	$Basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
	
	$Query = Bitrix\Sale\Internals\BasketTable::getList(array(
	    "select" => array("ID"),
	    'filter' => array(
	        'FUSER_ID' => Bitrix\Sale\Fuser::getId(), 
	        'ORDER_ID' => null,
	        'LID' => SITE_ID,
	    )
	));
	while($Answer = $Query->Fetch()){
		$BasketItem = $Basket->getItemById($Answer["ID"]);
		$BasketItem->delete();
	}
	$Basket->save();
}


$Query = Bitrix\Sale\Internals\BasketTable::getList(array(
    "select" => array("ID","PRODUCT_ID","QUANTITY"),
    'filter' => array(
        'FUSER_ID' => Bitrix\Sale\Fuser::getId(), 
        'ORDER_ID' => null,
        'LID' => SITE_ID,
        "DELAY" => "N"
    )
));
while($Answer = $Query->Fetch()){
	$Result["ADD"]["IDS"][] = $Answer["PRODUCT_ID"];
	$Result["ADD"]["ITEMS"][] = $Answer;
}

echo json_encode($Result);
?>