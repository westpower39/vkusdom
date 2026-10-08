<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
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
		$this->InstallDB();
	}

	public function DoUninstall()
	{
		// App sessions table is kept on uninstall: dropping it would log out every app user.
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	/**
	 * The only own table of the module: app sessions with access/refresh token hashes (TZ 8.5).
	 */
	public function InstallDB()
	{
		Loader::includeModule($this->MODULE_ID);

		$connection = Application::getConnection();
		$entity = \Westpower\Mobile\Model\SessionTable::getEntity();
		$table = \Westpower\Mobile\Model\SessionTable::getTableName();

		if (!$connection->isTableExists($table))
		{
			$entity->createDbTable();
			$connection->createIndex($table, 'ix_wpm_session_access', ['ACCESS_HASH']);
			$connection->createIndex($table, 'ix_wpm_session_refresh', ['REFRESH_HASH']);
			$connection->createIndex($table, 'ix_wpm_session_user', ['USER_ID']);
		}

		return true;
	}
}
