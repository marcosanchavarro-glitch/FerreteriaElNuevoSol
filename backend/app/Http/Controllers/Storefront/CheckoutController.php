<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Storefront\Checkout;
use App\Services\Storefront\WhatsAppOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Checkout $checkout): View|RedirectResponse
    {
        try {
            return view('storefront.checkout', $checkout->review());
        } catch (ValidationException $exception) {
            return to_route('cart.index')->withErrors($exception->errors());
        }
    }

    public function store(Request $request, Checkout $checkout): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,15}\D*$)\+?[0-9 ()-]{6,30}$/D'],
            'email' => ['nullable', 'email', 'max:254'],
            'token' => ['required', 'string', 'size:64'],
        ], ['required' => 'Completá el campo :attribute.', 'phone.regex' => 'Ingresá un teléfono válido.'],
            ['first_name' => 'nombre', 'last_name' => 'apellido', 'phone' => 'teléfono']);
        try {
            $order = $checkout->place($data, $data['token'], $request->user());
        } catch (ValidationException $exception) {
            return to_route('checkout.create')->withErrors($exception->errors())->withInput($request->only('first_name', 'last_name', 'phone', 'email'));
        }

        return to_route('checkout.confirmation', $order->order_number);
    }

    public function confirmation(Request $request, string $number, WhatsAppOrder $whatsapp): View
    {
        abort_unless(in_array($number, $request->session()->get('checkout_completed', []), true), 404);
        $order = Order::with('items')->where('order_number', $number)->firstOrFail();
        abort_unless($order->user_id === null || $order->user_id === $request->user()?->id, 404);

        return view('storefront.confirmation', ['order' => $order, 'whatsappUrl' => $whatsapp->url($order), 'message' => $whatsapp->message($order)]);
    }
}
