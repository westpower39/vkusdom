<?php

namespace Westpower\Mobile\Model;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * App session: guest (USER_ID is null) or user. Tokens are stored as SHA-256 hashes.
 */
class SessionTable extends DataManager
{
	public static function getTableName(): string
	{
		return 'westpower_mobile_session';
	}

	public static function getMap(): array
	{
		return [
			(new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
			(new IntegerField('USER_ID'))->configureNullable(),
			(new IntegerField('FUSER_ID'))->configureRequired(),
			(new StringField('ACCESS_HASH'))->configureSize(64)->configureRequired(),
			(new DatetimeField('ACCESS_EXPIRES_AT'))->configureRequired(),
			(new StringField('REFRESH_HASH'))->configureSize(64)->configureRequired(),
			(new DatetimeField('REFRESH_EXPIRES_AT'))->configureRequired(),
			(new TextField('COUPONS'))->configureNullable(),
			(new StringField('USER_AGENT'))->configureSize(255)->configureNullable(),
			(new DatetimeField('CREATED_AT'))->configureRequired(),
			(new DatetimeField('LAST_USED_AT'))->configureRequired(),
		];
	}
}
