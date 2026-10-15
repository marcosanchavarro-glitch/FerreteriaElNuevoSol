<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Storefront\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Cart $cart): View
    {
        return view('storefront.cart', $cart->summary(false));
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $data = $request->validate(['product_id' => ['required', 'integer', 'min:1'], 'product_variant_id' => ['nullable', 'integer', 'min:1'], 'quantity' => ['required']]);
        $cart->put((int) $data['product_id'], isset($data['product_variant_id']) ? (int) $data['product_variant_id'] : null, $data['quantity'], true);

        return to_route('cart.index')->with('status', 'Producto agregado al carrito.');
    }

    public function update(Request $request, string $key, Cart $cart): RedirectResponse
    {
        $line = $cart->lines()[$key] ?? abort(404);
        $request->validate(['quantity' => ['required']]);
        $cart->put($line['product_id'], $line['product_variant_id'], $request->input('quantity'), false);

        return to_route('cart.index')->with('status', 'Cantidad actualizada.');
    }

    public function destroy(string $key, Cart $cart): RedirectResponse
    {
        $cart->remove($key);

        return to_route('cart.index')->with('status', 'Producto eliminado.');
    }
}
