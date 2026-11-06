<?php

namespace App\Services\Storefront;

use App\Models\Order;
use App\Services\Content\SiteSettings;
use App\Support\ProductDisplay;

class WhatsAppOrder
{
    public function message(Order $order): string
    {
        $lines = ['Hola, quiero realizar el pedido #'.$order->order_number.'.', 'Cliente: '.$order->customer_first_name.' '.$order->customer_last_name, 'Teléfono: '.$order->customer_phone, '', 'Productos:'];
        foreach ($order->items as $item) {
            $lines[] = '• '.rtrim(rtrim($item->quantity, '0'), '.').' '.ProductDisplay::label($item->sale_unit).' x '.$item->product_name.($item->variant_name ? ' · '.$item->variant_name : '').' — '.ProductDisplay::money($item->subtotal).($item->requires_confirmation ? ' (sujeto a consulta)' : ' · precio unitario: '.ProductDisplay::money($item->unit_price));
        }
        $lines[] = '';
        $lines[] = 'Total confirmado: '.ProductDisplay::money($order->confirmed_total);
        $lines[] = 'Productos a confirmar: '.$order->items->where('requires_confirmation', true)->count();
        $lines[] = 'Retiro en local.';
        $lines[] = 'Solicitud registrada en la web; pendiente de atención y confirmación de la ferretería.';

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
