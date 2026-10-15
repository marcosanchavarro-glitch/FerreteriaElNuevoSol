<?php

namespace App\Services\Storefront;

use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderNotifications;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Checkout
{
    public function __construct(private Cart $cart, private Session $session) {}

    public function fingerprint(array $summary): string
    {
        return hash('sha256', serialize($summary));
    }

    public function review(): array
    {
        $summary = $this->cart->summary();
        if (! $summary['items']) {
            throw ValidationException::withMessages(['cart' => 'Tu carrito está vacío.']);
        }
        $token = Str::random(64);
        $this->session->put('checkout', ['token' => $token, 'fingerprint' => $this->fingerprint($summary)]);

        return $summary + ['token' => $token];
    }

    public function place(array $contact, string $token, ?User $user): Order
    {
        $key = hash('sha256', $token);
        $completed = $this->session->get('checkout_completed', []);
        if (isset($completed[$key])) {
            return Order::where('checkout_key', $key)->where('user_id', $user?->id)->firstOrFail();
        }
        if (! hash_equals($this->session->get('checkout.token', ''), $token)) {
            throw ValidationException::withMessages(['cart' => 'Volvé a revisar el pedido antes de confirmar.']);
        }
        $existing = Order::where('checkout_key', $key)->where('user_id', $user?->id)->first();
        $order = $existing ?? DB::transaction(function () use ($contact, $key, $user): Order {
            // First SQLite write serializes checkouts before reading current catalog data.
            $order = new Order;
            $order->checkout_key = $key;
            $order->fill(['order_number' => 'ENS-'.Str::ulid(), 'user_id' => $user?->id,
                'customer_first_name' => $contact['first_name'], 'customer_last_name' => $contact['last_name'],
                'customer_phone' => $contact['phone'], 'customer_email' => $contact['email'] ?? null,
                'status' => 'received', 'fulfillment_method' => 'pickup', 'subtotal' => 0,
                'confirmed_total' => 0, 'has_items_to_confirm' => false]);
            $order->save();
            $summary = $this->cart->summary();
            if (! $summary['items'] || ! hash_equals($this->session->get('checkout.fingerprint', ''), $this->fingerprint($summary))) {
                throw ValidationException::withMessages(['cart' => 'El carrito o los precios cambiaron. Revisá el resumen actualizado y confirmá nuevamente.']);
            }
            foreach ($summary['items'] as $item) {
                unset($item['image'], $item['slug']);
                $order->items()->create($item);
            }
            $order->update(['subtotal' => $summary['total'], 'confirmed_total' => $summary['total'], 'has_items_to_confirm' => $summary['pending'] > 0]);
            $order->statusHistory()->create(['previous_status' => null, 'new_status' => 'received', 'changed_by' => $user?->id]);
            app(OrderNotifications::class)->record($order);

            return $order;
        }, 3);
        $completed[$key] = $order->order_number;
        $this->session->put('checkout_completed', array_slice($completed, -20, null, true));
        $this->session->forget(['cart', 'checkout']);

        return $order;
    }
}
