<?php

namespace Westpower\Mobile\Exception;

use Westpower\Mobile\Dictionary\ErrorCode;

/**
 * Business error that is returned to the client as {code, message, customData}.
 * The message is user-facing Russian text.
 */
class ApiException extends \RuntimeException
{
	private string $errorCode;
	private ?array $customData;

	public function __construct(string $errorCode, string $message, ?array $customData = null)
	{
		parent::__construct($message);
		$this->errorCode = $errorCode;
		$this->customData = $customData;
	}

	public function getErrorCode(): string
	{
		return $this->errorCode;
	}

	public function getCustomData(): ?array
	{
		return $this->customData;
	}

	public static function notFound(string $message): self
	{
		return new self(ErrorCode::NOT_FOUND, $message);
	}

	public static function validation(string $message, string $field): self
	{
		return new self(ErrorCode::VALIDATION_ERROR, $message, ['field' => $field]);
	}

	public static function unauthorized(string $message = 'Требуется авторизация'): self
	{
		return new self(ErrorCode::UNAUTHORIZED, $message);
	}

	public static function forbidden(string $message): self
	{
		return new self(ErrorCode::FORBIDDEN, $message);
	}

	public static function unavailable(string $message): self
	{
		return new self(ErrorCode::SERVICE_UNAVAILABLE, $message);
	}
}
