<?php

namespace Westpower\Mobile\Controller;

use Westpower\Mobile\Service\CartService;

/**
 * Cart of the session buyer (guest or user).
 */
final class Cart extends Base
{
	/** GET /api/v1/cart */
	public function cartAction()
	{
		return $this->respond(fn () => (new CartService())->get($this->sessionContext()));
	}

	/** DELETE /api/v1/cart */
	public function clearAction()
	{
		return $this->respond(fn () => (new CartService())->clear($this->sessionContext()));
	}

	/** PUT /api/v1/cart/items/{productId} {quantity} */
	public function setItemAction(int $productId)
	{
		return $this->respond(fn () => (new CartService())->setQuantity($this->sessionContext(), $productId, $this->body()['quantity'] ?? null));
	}

	/** DELETE /api/v1/cart/items/{productId} */
	public function deleteItemAction(int $productId)
	{
		return $this->respond(fn () => (new CartService())->remove($this->sessionContext(), $productId));
	}

	/** POST /api/v1/cart/promo-codes {code} */
	public function addPromoCodeAction()
	{
		return $this->respond(fn () => (new CartService())->addPromoCode($this->sessionContext(), (string)($this->body()['code'] ?? '')));
	}

	/** DELETE /api/v1/cart/promo-codes/{code} */
	public function deletePromoCodeAction(string $code)
	{
		return $this->respond(fn () => (new CartService())->removePromoCode($this->sessionContext(), urldecode($code)));
	}
}
