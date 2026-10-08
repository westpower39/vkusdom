<?php

namespace Westpower\Mobile\Controller;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Response;
use Westpower\Mobile\Auth\AuthContext;
use Westpower\Mobile\Auth\TokenService;
use Westpower\Mobile\Config;
use Westpower\Mobile\Dictionary\ErrorCode;
use Westpower\Mobile\Exception\ApiException;

/**
 * Base controller of the mobile API.
 *
 * Response envelope is the standard AjaxJson: {"status": "success|error", "data": ..., "errors": [...]}.
 * The HTTP status is derived from the first error code. HTTP methods are restricted by routes.
 * Identity comes from "Authorization: Bearer <accessToken>" (no Bitrix session, no CSRF token).
 */
abstract class Base extends Controller
{
	protected const NOT_IMPLEMENTED_MESSAGE = 'Функция ожидает реализации на сайте';

	protected function getDefaultPreFilters(): array
	{
		return [];
	}

	protected function getDefaultPostFilters(): array
	{
		return [];
	}

	public function finalizeResponse(Response $response): void
	{
		$errors = $this->getErrors();
		if (!empty($errors) && $response instanceof HttpResponse)
		{
			$response->setStatus(ErrorCode::getHttpStatus($errors[0]->getCode()));
		}
	}

	/**
	 * Runs an action body: ApiException becomes an API error, any other error is logged and hidden.
	 */
	protected function respond(callable $handler)
	{
		try
		{
			return $handler();
		}
		catch (ApiException $e)
		{
			return $this->addApiError($e->getErrorCode(), $e->getMessage(), $e->getCustomData());
		}
		catch (\Throwable $e)
		{
			Application::getInstance()->getExceptionHandler()->writeToLog($e);

			$details = Config::getBool('debug_errors') ? [
				'exception' => get_class($e),
				'message' => $e->getMessage(),
				'file' => str_replace($_SERVER['DOCUMENT_ROOT'] ?? '', '', $e->getFile()) . ':' . $e->getLine(),
				'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 8),
			] : null;

			return $this->addApiError(ErrorCode::INTERNAL_ERROR, 'Внутренняя ошибка сервера', $details);
		}
	}

	protected function addApiError(string $code, string $message, ?array $customData = null)
	{
		$this->addError(new Error($message, $code, $customData));

		return null;
	}

	/**
	 * Current identity; anonymous when no token is sent.
	 */
	protected function context(): AuthContext
	{
		return TokenService::current();
	}

	/**
	 * Guest or user session is required (cart, checkout).
	 */
	protected function sessionContext(): AuthContext
	{
		$context = $this->context();
		if (!$context->hasSession())
		{
			throw ApiException::unauthorized('Нужен токен: получите гостевой через POST /auth/guest');
		}

		return $context;
	}

	/**
	 * Logged-in user is required (favorites, orders, profile).
	 */
	protected function userContext(): AuthContext
	{
		$context = $this->context();
		if (!$context->isUser())
		{
			throw ApiException::unauthorized('Войдите, чтобы продолжить');
		}

		return $context;
	}

	/**
	 * JSON request body.
	 */
	protected function body(): array
	{
		$raw = (string)\Bitrix\Main\HttpRequest::getInput();
		if (trim($raw) === '')
		{
			return [];
		}

		$data = json_decode($raw, true);
		if (!is_array($data))
		{
			throw ApiException::validation('Тело запроса должно быть JSON-объектом', 'body');
		}

		return $data;
	}

	protected function query(string $name)
	{
		return $this->getRequest()->getQuery($name);
	}

	/**
	 * page, limit, sort, filter from the query string.
	 */
	protected function listQuery(): array
	{
		return [
			'page' => $this->query('page'),
			'limit' => $this->query('limit'),
			'sort' => $this->query('sort'),
			'filter' => $this->query('filter'),
		];
	}

	protected function notImplemented()
	{
		return $this->addApiError(ErrorCode::SERVICE_UNAVAILABLE, self::NOT_IMPLEMENTED_MESSAGE);
	}
}
