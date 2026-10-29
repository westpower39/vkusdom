<?
namespace Sale\Handlers\Delivery;

use \Bitrix\Main\Loader,
	\Bitrix\Sale\Delivery\Services\Base,
	\Bitrix\Sale\Delivery\CalculationResult;

class VkusdomHandler extends Base{
    
    protected static $isCalculatePriceImmediately = true;
	protected  static $whetherAdminExtraServicesShow = true;
    
    public function __construct(array $initParams){
		parent::__construct($initParams);

		//Default value
		if(!isset($this->config["MAIN"]["0"]))
			$this->config["MAIN"]["0"] = "0";
	}
    
    public static function getClassTitle(){
        return "Vkusdom";
    }
        
    public static function getClassDescription(){
        return "Vkusdom служба доставки";
    }
        
    protected function calculateConcrete(\Bitrix\Sale\Shipment $Shipment){
 		
 		$Order = $Shipment->getOrder();
 		$OBasket = $Order->getBasket();
 		
 		$TotalWeight = 0;
 		
 		$DefaultProductWeight = \PhpInterface\Libs\Help::DefaultProductWeight();
 		
 		foreach($OBasket as $Item){
			
			
			$ProductQuantity = $Item->getField("QUANTITY");
			$ProductWeight = $Item->getField("WEIGHT");
			
			if($ProductWeight > 0){
				
			} else {
				$ProductWeight = $DefaultProductWeight;
			}
			
			$TotalWeight += ( $ProductWeight*$ProductQuantity);
			
		}
		
		if($TotalWeight > 0){
			$TotalWeight /= 1000;
		}
		
        $Price = 0;
        
        $MOUserData = \PhpInterface\Libs\MethodObtaining::GetUserData();
        
       
        if(!empty($MOUserData["ZONE_ID"])){
			
			$Zone = array();
			
			$Hb9 = \PhpInterface\Libs\Help::GetHighloadBlock(9);
			$Query = $Hb9::getList(array("select" => array("*"),"filter" => array("ID" => $MOUserData["ZONE_ID"])));
			if($Answer = $Query->Fetch()){
				$Zone = $Answer;	
			}
			
			if(!empty($Zone)){
				
				if($TotalWeight >= $Zone["UF_LIGHT_TRANSPORT_WEIGHT_FROM"] && $TotalWeight < $Zone["UF_LIGHT_TRANSPORT_WEIGHT_TO"]){
					$Price = $Zone["UF_LIGHT_TRANSPORT_PRICE"];
				} elseif($TotalWeight >= $Zone["UF_MEDIUM_TRANSPORT_WEIGHT_FROM"] && $TotalWeight < $Zone["UF_MEDIUM_TRANSPORT_WEIGHT_TO"]){
					$Price = $Zone["UF_MEDIUM_TRANSPORT_PRICE"];
				} elseif($TotalWeight >= $Zone["UF_HEAVY_TRANSPORT_WEIGHT_FROM"] && $TotalWeight < $Zone["UF_HEAVY_TRANSPORT_WEIGHT_TO"]){
					$Price = $Zone["UF_HEAVY_TRANSPORT_PRICE"];
				}
				
			}
		}
        
        
        
        $Result = new CalculationResult();
        $Result->setDeliveryPrice(roundEx(floatval($Price),SALE_VALUE_PRECISION));
        
        return $Result;
    }
    
   
    protected function getConfigStructure(){
        return array(
            "MAIN" => array(
                "TITLE" => 'Настройка обработчика',
                "DESCRIPTION" => 'Настройка обработчика',
                "ITEMS" => array(
                )
            )
        );
    }
        
    public function isCalculatePriceImmediately(){
		return self::$isCalculatePriceImmediately;
	}

	public static function whetherAdminExtraServicesShow(){
		return self::$whetherAdminExtraServicesShow;
	}
}
?>