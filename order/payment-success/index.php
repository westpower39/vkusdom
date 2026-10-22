<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Заказ успешно оплачен");
?>
<div class="order-page__success">
              <div class="order-page__success-img"><img src="<?=SITE_TEMPLATE_PATH?>/img/order-pay-success.png" alt=""></div>
              <div class="order-page__success-title">Заказ успешно оплачен!</div>
              <div class="order-page__success-text">Отслеживать его статус вы можете вличном кабинете</div>
            </div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>