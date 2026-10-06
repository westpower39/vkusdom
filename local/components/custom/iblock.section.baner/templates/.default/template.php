<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true){
	die();
}
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);?>
<?if(!empty($arResult["ITEMS"])):?>
<div class="section-bakery">
	<div class="section-bakery__columns">
		<?foreach($arResult["ITEMS"] as $key => $value):?>
			<?
				$Img ="";
				
				if(!empty($value["PICTURE"])){
					$ResizeImage = CFile::ResizeImageGet(CFile::GetFileArray($value["PICTURE"]), array('width'=>300, 'height'=>300), BX_RESIZE_IMAGE_PROPORTIONAL, true);
				}
				
			?>
		<div class="section-bakery__column section-bakery__column<?=($key+1)%2 ? "01" : "02"?>">
			<a class="section-title section-title_light" href="<?=$value["DETAIL_PAGE_URL"]?>">
				<?=$value["NAME"]?>
				<svg width="24" height="24">
					<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#arrow-big-right-white-icon">
					</use>
				</svg>
			</a>
			<div class="section-bakery__items">
				<?foreach($value["ITEMS"] as $Item):?>
				<a class="section-bakery__item" href="<?=$Item["DETAIL_PAGE_URL"]?>">
					<div class="section-bakery__item-name">
						<svg width="22" height="21">
							<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#title-icon">
							</use>
						</svg><?=$Item["NAME"]?>
					</div>
					<div class="section-bakery__item-photo">
						<?if($Img != ""):?>
						<img src="<?=$Img?>" alt="">
						<?endif;?>
					</div>
				</a>
				<?endforeach;?>
			</div>
		</div>
		<?endforeach;?>
	</div>
</div>
<?endif;?>