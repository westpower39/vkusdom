<?php

namespace Westpower\Mobile\Dictionary;

/**
 * Order statuses shown in the app (TZ 8.1). Mapping from Bitrix status IDs is configured in module options.
 */
final class OrderStatus
{
	public const ACCEPTED = 'accepted';
	public const PAID = 'paid';
	public const ASSEMBLING = 'assembling';
	public const READY = 'ready';
	public const DELIVERING = 'delivering';
	public const COMPLETED = 'completed';
	public const CANCELLED = 'cancelled';

	public const NAMES = [
		self::ACCEPTED => 'Принят',
		self::PAID => 'Оплачен',
		self::ASSEMBLING => 'Сборка',
		self::READY => 'Готов к выдаче',
		self::DELIVERING => 'Доставка',
		self::COMPLETED => 'Выполнен',
		self::CANCELLED => 'Отменён',
	];
}
