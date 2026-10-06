<?
define("NEED_AUTH", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");


global $USER;

if($USER->IsAuthorized()){
	$Url = "/";
	
	if (is_string($_REQUEST["backurl"]) && mb_strpos($_REQUEST["backurl"], "/") === 0){
		$Url = $_REQUEST["backurl"];
		
	}
	LocalRedirect($Url);
}



$APPLICATION->SetTitle("Авторизация и регистрация");
?>
<p>Вы зарегистрированы и успешно авторизовались.</p>

<p><a href="<?=SITE_DIR?>">Вернуться на главную страницу</a></p>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>