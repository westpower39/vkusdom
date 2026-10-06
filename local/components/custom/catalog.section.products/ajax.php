<?php

const STOP_STATISTICS = true;
const NO_KEEP_STATISTIC = 'Y';
const NO_AGENT_STATISTIC = 'Y';
const DisableEventsCheck = true;
const BX_SECURITY_SHOW_MESSAGE = true;
const NOT_CHECK_PERMISSIONS = true;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$Request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$Signer = new \Bitrix\Main\Security\Sign\Signer;
try
{
	$SignedParams = $Request->get('signedParams') ?: '';
	$Params = $Signer->unsign($SignedParams, 'catalog.section.products');
	$Params = unserialize(base64_decode($Params), ['allowed_classes' => false]);
}catch (\Bitrix\Main\Security\Sign\BadSignatureException $e){
	die();
}

$Id = $Request->get('id');
if (empty($Id))
{
	return;
}

global $APPLICATION;

$Params["ACTIVE_ITEM_ID"] = $Id;
$Params["AJAX"] = "Y";

$APPLICATION->IncludeComponent(
	'custom:catalog.section.products',
	'',
	$Params
);
