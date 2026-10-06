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
$this->setFrameMode(true);
?>
<?if(!empty($arResult["ITEMS"])):?>
<div class="section-popular">
	<div class="section-title">
		Популярные категории
		<svg width="24" height="24">
			<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#arrow-big-right-icon">
			</use>
		</svg>
	</div>
	<div class="slider-inner">
		<div class="swiper section-popular-slider">
			<div class="swiper-wrapper">
				<?foreach($arResult["ITEMS"] as $key => $value):?>
					<?
						$Img = "";
						
						if(!empty($value["PICTURE"])){
							$ResizeImage = CFile::ResizeImageGet(CFile::GetFileArray($value["PICTURE"]), array('width'=>150, 'height'=>150), BX_RESIZE_IMAGE_PROPORTIONAL, true); 
							$Img = $ResizeImage["src"];
						}
						
					?>
				<div class="swiper-slide">
					<a class="section-popular-slider__slide" href="<?=$value["SECTION_PAGE_URL"]?>">
						<div class="section-popular-slider__title">
							<svg width="48" height="48">
								<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#leafe-green-icon">
								</use>
							</svg><?=$value["NAME"]?>
						</div>
						<?if($Img != ""):?>
						<img src="<?=$Img?>" alt="">
						<?endif;?>
					</a>
				</div>
				<?endforeach;?>
			</div>
		</div>
		<div class="section-popular-slider-pagination slider-pagination">
		</div>
		<div class="section-popular-slider-next slider-next">
			<svg width="6" height="10">
				<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#arrow-right-icon">
				</use>
			</svg>
		</div>
		<div class="section-popular-slider-prev slider-prev">
			<svg width="6" height="10">
				<use xlink:href="<?=SITE_TEMPLATE_PATH?>/img/sprite.svg#arrow-left-icon">
				</use>
			</svg>
		</div>
	</div>
</div>
<?endif;?>