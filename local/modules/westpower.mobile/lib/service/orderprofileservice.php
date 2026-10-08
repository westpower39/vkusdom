<?php

namespace Westpower\Mobile\Service;

use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Internals\UserPropsTable;
use Bitrix\Sale\Internals\UserPropsValueTable;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Dictionary\EntityCode;
use Westpower\Mobile\Exception\ApiException;

/**
 * Order profiles (TZ 4.7: individual / legal entity) — standard sale buyer profiles.
 * Fields are the order properties of the person type marked "use in profile".
 */
final class OrderProfileService
{
	public function __construct()
	{
		Loader::includeModule('sale');
	}

	public function list(AuthContext $context): array
	{
		$items = [];
		$rows = UserPropsTable::getList([
			'filter' => ['=USER_ID' => $context->userId],
			'select' => ['ID'],
			'order' => ['DATE_UPDATE' => 'DESC'],
		]);
		while ($row = $rows->fetch())
		{
			$items[] = $this->get($context, (int)$row['ID']);
		}

		return ['items' => $items];
	}

	/**
	 * Fields of a profile for the person type (form schema).
	 */
	public function fields(string $personTypeCode): array
	{
		$personTypeId = $this->personTypeId($personTypeCode);
		$items = [];
		foreach ($this->properties($personTypeId) as $property)
		{
			$items[] = [
				'code' => (string)$property['CODE'],
				'name' => (string)$property['NAME'],
				'type' => $this->fieldType($property),
				'isRequired' => $property['REQUIRED'] === 'Y',
			];
		}

		return ['items' => $items];
	}

	public function get(AuthContext $context, int $id): array
	{
		$profile = $this->load($context, $id);
		$properties = $this->properties((int)$profile['PERSON_TYPE_ID']);

		$values = [];
		$rows = UserPropsValueTable::getList([
			'filter' => ['=USER_PROPS_ID' => $id],
			'select' => ['ORDER_PROPS_ID', 'VALUE'],
		]);
		while ($row = $rows->fetch())
		{
			$values[(int)$row['ORDER_PROPS_ID']] = $row['VALUE'];
		}

		$fields = [];
		foreach ($properties as $propertyId => $property)
		{
			$fields[] = [
				'code' => (string)$property['CODE'],
				'name' => (string)$property['NAME'],
				'value' => isset($values[$propertyId]) && $values[$propertyId] !== '' ? (string)$values[$propertyId] : null,
			];
		}

		$personType = PersonTypeTable::getList(['filter' => ['=ID' => $profile['PERSON_TYPE_ID']], 'select' => ['CODE', 'NAME']])->fetch();

		return [
			'id' => (int)$profile['ID'],
			'name' => (string)$profile['NAME'],
			'personType' => [
				'code' => (string)($personType['CODE'] ?? ''),
				'name' => (string)($personType['NAME'] ?? ''),
			],
			'fields' => $fields,
		];
	}

	public function save(AuthContext $context, ?int $id, array $input): array
	{
		if ($id !== null)
		{
			$profile = $this->load($context, $id);
			$personTypeId = (int)$profile['PERSON_TYPE_ID'];
		}
		else
		{
			$personTypeId = $this->personTypeId((string)($input['personType'] ?? ''));
		}

		$name = trim((string)($input['name'] ?? ''));
		if ($name === '')
		{
			throw ApiException::validation('Укажите название профиля', 'name');
		}

		$fields = is_array($input['fields'] ?? null) ? $input['fields'] : [];
		$values = [];
		foreach ($this->properties($personTypeId) as $propertyId => $property)
		{
			$value = $fields[$property['CODE']] ?? null;
			if ($property['REQUIRED'] === 'Y' && ($value === null || trim((string)$value) === ''))
			{
				throw ApiException::validation(sprintf('Заполните поле «%s»', $property['NAME']), 'fields.' . $property['CODE']);
			}
			$values[$propertyId] = $value !== null ? trim((string)$value) : '';
		}

		$errors = [];
		$profileId = \CSaleOrderUserProps::DoSaveUserProfile($context->userId, $id ?? 0, $name, $personTypeId, $values, $errors);
		if (!$profileId || !empty($errors))
		{
			$messages = array_map(static fn ($error) => is_array($error) ? ($error['TEXT'] ?? '') : (string)$error, $errors);
			throw ApiException::validation(implode('; ', array_filter($messages)) ?: 'Не удалось сохранить профиль', 'fields');
		}

		return $this->get($context, (int)$profileId);
	}

	public function delete(AuthContext $context, int $id): void
	{
		$this->load($context, $id);
		\CSaleOrderUserProps::Delete($id);
	}

	private function load(AuthContext $context, int $id): array
	{
		$profile = UserPropsTable::getList([
			'filter' => ['=ID' => $id, '=USER_ID' => $context->userId],
			'select' => ['ID', 'NAME', 'PERSON_TYPE_ID'],
		])->fetch();
		if (!$profile)
		{
			throw ApiException::notFound('Профиль не найден');
		}

		return $profile;
	}

	/**
	 * Order properties of the person type used in buyer profiles, keyed by property ID.
	 */
	private function properties(int $personTypeId): array
	{
		$properties = [];
		$rows = OrderPropsTable::getList([
			'filter' => ['=PERSON_TYPE_ID' => $personTypeId, '=ACTIVE' => 'Y', '=USER_PROPS' => 'Y', '=UTIL' => 'N'],
			'select' => ['ID', 'CODE', 'NAME', 'TYPE', 'REQUIRED', 'IS_EMAIL', 'IS_PHONE', 'SORT'],
			'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
		]);
		while ($row = $rows->fetch())
		{
			if ((string)$row['CODE'] !== '')
			{
				$properties[(int)$row['ID']] = $row;
			}
		}

		return $properties;
	}

	private function personTypeId(string $code): int
	{
		if (!in_array($code, [EntityCode::PERSON_TYPE_INDIVIDUAL, EntityCode::PERSON_TYPE_LEGAL], true))
		{
			throw ApiException::validation('Тип покупателя должен быть individual или legal', 'personType');
		}

		return EntityResolver::personTypeId($code);
	}

	private function fieldType(array $property): string
	{
		if ($property['IS_EMAIL'] === 'Y')
		{
			return 'email';
		}
		if ($property['IS_PHONE'] === 'Y')
		{
			return 'phone';
		}

		return $property['TYPE'] === 'NUMBER' ? 'number' : 'string';
	}
}
