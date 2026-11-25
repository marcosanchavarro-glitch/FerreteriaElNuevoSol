<?php

namespace App\Services\Storefront;

use App\Models\Order;
use App\Services\Content\SiteSettings;
use App\Support\ProductDisplay;

class WhatsAppOrder
{
    public function message(Order $order): string
    {
        $pending = $order->items->where('requires_confirmation', true)->count();
        $allPending = $pending > 0 && $pending === $order->items->count();
        $lines = [$allPending ? 'Hola, quisiera cotizar los siguientes productos.' : 'Hola, quisiera consultar este pedido.', 'Referencia: #'.$order->order_number, '', 'Cliente: '.$order->customer_first_name.' '.$order->customer_last_name, 'Teléfono: '.$order->customer_phone, '', 'Productos:'];
        foreach ($order->items as $item) {
            $unit = in_array($item->sale_unit->value, ['unit', 'other'], true) ? '' : ' '.ProductDisplay::label($item->sale_unit);
            $lines[] = '• '.ProductDisplay::quantity($item->quantity).$unit.' × '.$item->product_name.($item->variant_name ? ' · '.$item->variant_name : '').($item->requires_confirmation ? ($allPending ? '' : ' — Precio a cotizar') : ' — '.ProductDisplay::money($item->subtotal).' · precio unitario: '.ProductDisplay::money($item->unit_price));
        }
        $lines[] = '';
        $lines[] = $allPending ? 'Importe total: a cotizar por la ferretería.' : ($pending > 0 ? 'Subtotal de artículos con precio: ' : 'Total del pedido: ').ProductDisplay::money($order->confirmed_total);
        if ($pending > 0 && ! $allPending) {
            $lines[] = 'El importe final incluye los artículos que faltan cotizar.';
        }
        $lines[] = 'Retiro en local.';
        $lines[] = 'Solicitud registrada en la web. La ferretería debe confirmar precio y disponibilidad.';

        return implode("\n", $lines);
    }

    public function url(Order $order): ?string
    {
        if (config('demo.enabled')) {
            return null;
        }
        $number = app(SiteSettings::class)->get('whatsapp_number');
        if (! preg_match('/^[1-9][0-9]{7,14}$/D', $number)) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($this->message($order));
    }
}
