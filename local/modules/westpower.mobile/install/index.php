<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class westpower_mobile extends CModule
{
	public $MODULE_ID = 'westpower.mobile';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $PARTNER_NAME;
	public $PARTNER_URI;

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION = $arModuleVersion['VERSION'];
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
		$this->MODULE_NAME = Loc::getMessage('WESTPOWER_MOBILE_MODULE_NAME');
		$this->MODULE_DESCRIPTION = Loc::getMessage('WESTPOWER_MOBILE_MODULE_DESCRIPTION');
		$this->PARTNER_NAME = Loc::getMessage('WESTPOWER_MOBILE_PARTNER_NAME');
		$this->PARTNER_URI = 'https://westpower.ru';
	}

	public function DoInstall()
	{
		ModuleManager::registerModule($this->MODULE_ID);
	}

	public function DoUninstall()
	{
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

}
