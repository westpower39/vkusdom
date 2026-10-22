<?

use Bitrix\Main\EventManager;

define("BASKET_MIN_PRICE",5000);
define("BASKET_MAX_WEIGHT",50000);

include(__DIR__."/libs/autoload.php");
include(__DIR__."/event_handler/autoload.php");


$EventManager = EventManager::getInstance();

$EventManager->addEventHandler('main', 'OnAfterUserUpdate', array("PhpInterface\EventHandler\Main","OnAfterUserUpdate"));


?>