<?php

namespace Westpower\Mobile\Service\Catalog;

use Bitrix\Iblock\PropertyTable;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Dictionary\PropertyCode;
use Westpower\Mobile\Exception\ApiException;
use Westpower\Mobile\Service\Formatter;

/**
 * Product detail = ProductCard + images, description, article, breadcrumbs, nutrition, characteristics.
 */
final class ProductDetailService
{
	public function detail(int $id, AuthContext $context): array
	{
		$cards = (new ProductCardBuilder())->build([$id], $context, true);
		if (!isset($cards[$id]))
		{
			throw ApiException::notFound('Товар не найден');
		}
		$repository = new ProductRepository();
		$iblockId = $repository->iblockId();

		$element = \CIBlockElement::GetList(
			[],
			['IBLOCK_ID' => $iblockId, 'ID' => $id],
			false,
			false,
			['ID', 'IBLOCK_ID', 'DETAIL_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'IBLOCK_SECTION_ID']
		)->Fetch();

		$codes = array_merge(
			[PropertyCode::MORE_PHOTO, PropertyCode::ARTICLE, PropertyCode::DESCRIPTION, PropertyCode::CALORIES,
				PropertyCode::PROTEINS, PropertyCode::FATS, PropertyCode::CARBOHYDRATES,
				PropertyCode::STORAGE_TEMPERATURE_MIN, PropertyCode::STORAGE_TEMPERATURE_MAX],
			array_values(PropertyCode::DETAIL_PROPERTIES)
		);
		$properties = [$id => []];
		\CIBlockElement::GetPropertyValuesArray($properties, $iblockId, ['ID' => $id], ['CODE' => $codes]);
		$values = $properties[$id];

		$images = [];
		foreach ([$element['DETAIL_PICTURE'] ?: $element['PREVIEW_PICTURE']] as $fileId)
		{
			if ($url = Formatter::image($fileId))
			{
				$images[] = $url;
			}
		}
		foreach ((array)($values[PropertyCode::MORE_PHOTO]['VALUE'] ?? []) as $fileId)
		{
			if (($url = Formatter::image($fileId)) && !in_array($url, $images, true))
			{
				$images[] = $url;
			}
		}
		$card = $cards[$id];
		if ($card['image'] !== null && !in_array($card['image'], $images, true))
		{
			array_unshift($images, $card['image']);
		}

		$description = trim((string)$element['DETAIL_TEXT']);
		if ($description === '')
		{
			$description = (string)$this->value($values[PropertyCode::DESCRIPTION] ?? null);
		}

		$breadcrumbs = (int)$element['IBLOCK_SECTION_ID'] > 0
			? (new SectionService())->breadcrumbs((int)$element['IBLOCK_SECTION_ID'])
			: [];

		\CIBlockElement::CounterInc($id);

		return $card + [
			'images' => $images,
			'description' => $description !== '' ? $description : null,
			'article' => $this->value($values[PropertyCode::ARTICLE] ?? null),
			'breadcrumbs' => $breadcrumbs,
			'nutrition' => $this->nutrition($values),
			'properties' => $this->properties($values),
		];
	}

	private function nutrition(array $values): ?array
	{
		$nutrition = [
			'calories' => $this->number($values[PropertyCode::CALORIES] ?? null),
			'proteins' => $this->number($values[PropertyCode::PROTEINS] ?? null),
			'fats' => $this->number($values[PropertyCode::FATS] ?? null),
			'carbohydrates' => $this->number($values[PropertyCode::CARBOHYDRATES] ?? null),
		];

		// 1C sends zeros when nutrition is not filled: all-zero values mean "no data".
		return count(array_filter($nutrition, static fn ($value) => $value !== null && $value > 0)) > 0 ? $nutrition : null;
	}

	private function properties(array $values): array
	{
		$items = [];
		foreach (PropertyCode::DETAIL_PROPERTIES as $code => $propertyCode)
		{
			$value = $this->value($values[$propertyCode] ?? null);
			if ($value !== null)
			{
				$items[] = ['code' => $code, 'name' => $values[$propertyCode]['NAME'], 'value' => $value];
			}

			if ($code === 'shelf-life')
			{
				$temperature = $this->temperature($values);
				if ($temperature !== null)
				{
					$items[] = ['code' => 'storage-temperature', 'name' => 'Температура хранения', 'value' => $temperature];
				}
			}
		}

		return $items;
	}

	private function temperature(array $values): ?string
	{
		$min = $this->number($values[PropertyCode::STORAGE_TEMPERATURE_MIN] ?? null);
		$max = $this->number($values[PropertyCode::STORAGE_TEMPERATURE_MAX] ?? null);
		if ($min === null && $max === null)
		{
			return null;
		}
		if ($min !== null && $max !== null)
		{
			return sprintf('от %s до %s °C', $this->formatNumber($min), $this->formatNumber($max));
		}

		return $min !== null ? sprintf('от %s °C', $this->formatNumber($min)) : sprintf('до %s °C', $this->formatNumber($max));
	}

	/**
	 * Display text of a property value (lists — value names, multiple values joined).
	 */
	private function value(?array $property): ?string
	{
		if ($property === null || $property['VALUE'] === false || $property['VALUE'] === '' || $property['VALUE'] === [] || $property['VALUE'] === null)
		{
			return null;
		}

		$values = $property['PROPERTY_TYPE'] === PropertyTable::TYPE_LIST
			? (array)$property['VALUE']
			: (isset($property['VALUE']['TEXT']) ? [$property['VALUE']] : (array)$property['VALUE']);

		$texts = array_filter(array_map([Formatter::class, 'text'], $values));

		return !empty($texts) ? implode(', ', $texts) : null;
	}

	private function number(?array $property): ?float
	{
		$value = $property['VALUE'] ?? null;
		if (is_array($value))
		{
			$value = reset($value);
		}
		$value = str_replace(',', '.', (string)$value);

		return is_numeric($value) ? (float)$value : null;
	}

	private function formatNumber(float $value): string
	{
		return rtrim(rtrim(number_format($value, 1, ',', ''), '0'), ',');
	}
}
