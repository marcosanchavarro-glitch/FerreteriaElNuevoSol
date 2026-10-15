<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderNotifications;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderOperationsPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing') || ! str_ends_with(str_replace('\\', '/', DB::connection()->getDatabaseName()), '/storefront-preview.sqlite')) {
            throw new \RuntimeException('Solo se permite en storefront-preview.sqlite local/testing.');
        }
        $this->call(CheckoutPreviewSeeder::class);
        foreach (['cliente.pedidos@example.test' => ['Cliente', 'customer'], 'vendedor.pedidos@example.test' => ['Marcos', 'seller'], 'cliente.ajeno@example.test' => ['Otro cliente', 'customer']] as $email => [$name, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            if (! $user->exists) {
                $user->fill(['first_name' => $name, 'last_name' => 'DEMO', 'phone' => '1155550000', 'password' => 'SoloDesarrollo!2026']);
                $user->forceFill(['role' => $role, 'active' => true])->save();
            }
        }
        DB::transaction(function (): void {
            $other = User::where('email', 'cliente.ajeno@example.test')->firstOrFail();
            $order = Order::firstOrCreate(['order_number' => 'DEMO-PEDIDO-AJENO'], ['user_id' => $other->id, 'customer_first_name' => 'Otro cliente', 'customer_last_name' => 'DEMO', 'customer_phone' => '1155550000', 'status' => 'received', 'subtotal' => 0, 'confirmed_total' => 0, 'has_items_to_confirm' => false, 'fulfillment_method' => 'pickup']);
            if ($order->wasRecentlyCreated) {
                $order->statusHistory()->create(['previous_status' => null, 'new_status' => 'received', 'changed_by' => $other->id]);
                app(OrderNotifications::class)->record($order);
            }
            $this->command?->info('Pedido ajeno de prueba: '.$order->id.'; notificación: '.$other->notifications()->value('notifications.id'));
        });
    }
}
