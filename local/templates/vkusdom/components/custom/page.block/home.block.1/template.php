<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<div class="section-main-contacts">
	<div class="section-main-contacts__inner">
		<div class="section-main-contacts__left">
			<div class="section-main-contacts__img">
				<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/map.php"), false);?>
			</div>
			<div class="section-main-contacts__items">
				<div class="section-main-contacts__item">
					<div class="section-main-contacts__item-name">
						Адрес:
					</div>
					<div class="section-main-contacts__item-value">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/address.php"), false);?>
					</div>
				</div>
				<div class="section-main-contacts__item">
					<div class="section-main-contacts__item-name">
						График работы:
					</div>
					<div class="section-main-contacts__item-value">
						<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/work_schedule.php"), false);?>
					</div>
				</div>
				<div class="section-main-contacts__item-columns">
					<div class="section-main-contacts__item">
						<div class="section-main-contacts__item-name">
							Телефон:
						</div>
						<div class="section-main-contacts__item-value">
							<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/phone.php"), false);?>
						</div>
					</div>
					<div class="section-main-contacts__item">
						<div class="section-main-contacts__item-name">
							E-mail:
						</div>
						<div class="section-main-contacts__item-value">
							<?$APPLICATION->IncludeComponent("bitrix:main.include", "", array("AREA_FILE_SHOW" => "file", "PATH" => "/include/email.php"), false);?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="section-main-contacts__right">
			<div class="section-main-contacts__intro">
				<div class="section-main-contacts__intro-svg">
					<svg width="32" height="32">
						<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#leafe-white-mini-icon">
						</use>
					</svg>
				</div>
				<div class="section-main-contacts__intro-text">
					Во «Вкусдом» приятно заглянуть за ужином, купить продукты на неделю или найти что‑то особенное к семейному столу.
				</div>
			</div>
			<div class="section-main-contacts__text">
				<div class="section-main-contacts__text-name">
					Мы тщательно подбираем ассортимент, чтобы рядом с привычными товарами всегда были свежие новинки, качественные продукты и выгодные предложения.
				</div>
				<div class="section-main-contacts__text-value">
					Мы верим, что хороший магазин — это не только широкий выбор, но и комфорт. Удобная навигация, свежая продукция, внимательный сервис и регулярные акции помогают делать покупки легко, быстро и с удовольствием — каждый день.
				</div>
			</div>
		</div>
	</div>
</div>