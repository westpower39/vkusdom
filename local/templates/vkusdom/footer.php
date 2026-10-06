<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();?>
	<?if(!$GLOBALS["SITE_SETTINGS"]["IS_AUTH"]):?>
		<?if($GLOBALS["SITE_SETTINGS"]["IS_ORDER"]):?>
		</div>
		<?elseif($GLOBALS["SITE_SETTINGS"]["IS_CATALOG"]):?>
					</div>
            	</div>
            </div>
		<?elseif(!$GLOBALS["SITE_SETTINGS"]["IS_MAIN_EMPTY"]):?>
			</div>
		<?endif;?>
		<?$APPLICATION->ShowViewContent('MAIN_CONTAINER_AFTER');?>
		</div>
	</main>
<footer class="footer">
	<div class="container">
		<div class="footer__top">
			<div class="footer__inner">
				<div class="footer__inner-left">
					<div class="footer-logo">
						<img src="<?=SITE_TEMPLATE_PATH?>/img/logo.svg" alt="">
					</div>
					<div class="footer-menu">
						<?$APPLICATION->IncludeComponent(
							"bitrix:menu",
							"v0",
							Array(
								"ALLOW_MULTI_SELECT" => "N",
								"CHILD_MENU_TYPE" => "left",
								"DELAY" => "N",
								"MAX_LEVEL" => "1",
								"MENU_CACHE_GET_VARS" => array(""),
								"MENU_CACHE_TIME" => "3600",
								"MENU_CACHE_TYPE" => "A",
								"MENU_CACHE_USE_GROUPS" => "N",
								"ROOT_MENU_TYPE" => "bottom0",
								"USE_EXT" => "N"
							)
						);?>
					</div>
					<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/download_app.php"), false);?>
				</div>
				<div class="footer__inner-right">
					<div class="footer-subscribe">
						<button class="footer-subscribe__button">
							Будьте в курсе выгодных предложений
						</button>
						<div class="footer-subscribe__text">
							Подпишитесь на рассылку и первыми узнавайте об акциях, скидках и новинках.
						</div>
					</div>
					<div class="footer-contacts">
						<div class="footer-contact">
							<div class="footer-contact__name">
								Телефон:
							</div>
							<div class="footer-contact__value">
								<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/phone.php"), false);?>
							</div>
						</div>
						<div class="footer-contact">
							<div class="footer-contact__name">
								E-mail:
							</div>
							<div class="footer-contact__value">
								<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/email.php"), false);?>
							</div>
						</div>
						<div class="footer-contact">
							<div class="footer-contact__name">
								Адрес:
							</div>
							<div class="footer-contact__value">
								<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/address.php"), false);?>
							</div>
						</div>
						<div class="footer-contact">
							<div class="footer-contact__name">
								График работы:
							</div>
							<div class="footer-contact__value">
								<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/work_schedule.php"), false);?>
							</div>
						</div>
					</div>
					<div class="socials">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/socials.php"), false);?>
					</div>
				</div>
			</div>
		</div>
		<div class="footer__bottom">
			<div class="footer-copyright">
				<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/copyright.php"), false);?>
			</div>
			<div class="footer-conf">
				<?$APPLICATION->IncludeComponent(
					"bitrix:menu",
					"v1",
					Array(
						"ALLOW_MULTI_SELECT" => "N",
						"CHILD_MENU_TYPE" => "left",
						"DELAY" => "N",
						"MAX_LEVEL" => "1",
						"MENU_CACHE_GET_VARS" => array(""),
						"MENU_CACHE_TIME" => "3600",
						"MENU_CACHE_TYPE" => "A",
						"MENU_CACHE_USE_GROUPS" => "N",
						"ROOT_MENU_TYPE" => "bottom1",
						"USE_EXT" => "N"
					)
				);?>
			</div>
		</div>
	</div>
</footer>

<div class="mobile-fixed-block">
	<a class="mobile-fixed-block__link" href="">
		<svg width="24" height="24">
			<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#search-grey-icon">
			</use>
		</svg>
	</a>
	<a class="mobile-fixed-block__link" data-basket="totalCount" href="/basket/">
		<div class="mobile-fixed-block__link-count">
			0
		</div>
		<svg width="24" height="24">
			<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#basket-icon">
			</use>
		</svg>
	</a>
	<a class="mobile-fixed-block__link" href="/">
		<svg width="32" height="32">
			<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#logo-min">
			</use>
		</svg>
	</a>
	<a class="mobile-fixed-block__link" href="">
		<svg width="24" height="24">
			<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#action-icon">
			</use>
		</svg>
	</a>
	<a href="<?=$USER->IsAuthorized() ? "/personal/" : "/auth/"?>" class="mobile-fixed-block__link mobile-fixed-block__lk">
		<svg width="24" height="24">
			<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#personal-icon">
			</use>
		</svg>
	</a>
</div>
	<?endif;?>
</div>
</body>
</html>