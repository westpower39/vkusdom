<?

namespace PhpInterface\EventHandler;

class Main {
	public static function OnAfterUserUpdate(&$arFields) {
		
		if($arFields["RESULT"]){
			
			\Bitrix\Main\UserPhoneAuthTable::update(
				$arFields["ID"],
				array(
					"PHONE_NUMBER" => \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($arFields["PERSONAL_PHONE"])
				)
			);
			
		}
	}
}

?>
