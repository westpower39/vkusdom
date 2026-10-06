<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<div class="section-orders-phone">
	<div class="section-orders-phone__desc">
		<div class="section-orders-phone__desc-name">
			Заказы в твоем телефоне
		</div>
		<div class="section-orders-phone__desc-text">
			Скачайте приложение. Копите бонусы, получайте персональные предложения и отслеживайте заказы.
		</div>
		<div class="section-orders-phone__desc-qr">
			<img src="<?=SITE_TEMPLATE_PATH?>/img/QR-Code.png">
		</div>
	</div>
	<div class="section-orders-phone__stores">
		<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/download_app.php"), false);?>
	</div>
</div>