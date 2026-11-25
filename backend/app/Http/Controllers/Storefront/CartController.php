<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, Cart $cart): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return $this->panel($cart);
        }

        return view('storefront.cart', $cart->summary(false));
    }

    public function store(Request $request, Cart $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'min:1'], 'product_variant_id' => ['nullable', 'integer', 'min:1'], 'quantity' => ['required']]);
        $cart->put((int) $data['product_id'], isset($data['product_variant_id']) ? (int) $data['product_variant_id'] : null, $data['quantity'], true);

        if ($request->expectsJson()) {
            return $this->panel($cart, 'Producto agregado al carrito.');
        }

        return to_route('cart.index')->with('status', 'Producto agregado al carrito.');
    }

    public function update(Request $request, string $key, Cart $cart): RedirectResponse|JsonResponse
    {
        $line = $cart->lines()[$key] ?? abort(404);
        $request->validate(['quantity' => ['required']]);
        $cart->put($line['product_id'], $line['product_variant_id'], $request->input('quantity'), false);

        if ($request->expectsJson()) {
            return $this->panel($cart, 'Cantidad actualizada.');
        }

        return to_route('cart.index')->with('status', 'Cantidad actualizada.');
    }

    public function destroy(Request $request, string $key, Cart $cart): RedirectResponse|JsonResponse
    {
        $cart->remove($key);

        if ($request->expectsJson()) {
            return $this->panel($cart, 'Producto eliminado.');
        }

        return to_route('cart.index')->with('status', 'Producto eliminado.');
    }

    private function panel(Cart $cart, string $message = ''): JsonResponse
    {
        return response()->json([
            'html' => view('storefront.cart-content', $cart->summary(false) + ['quantityPrefix' => 'drawer-'])->render(),
            'count' => count($cart->lines()),
            'message' => $message,
        ])->header('Cache-Control', 'no-store');
    }
}
