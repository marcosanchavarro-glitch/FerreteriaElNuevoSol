<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class RenderDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('demo.enabled')) {
            throw new LogicException('Este seeder requiere DEMO_MODE=true.');
        }
        DB::transaction(function (): void {
            if (Setting::where('key', 'demo.initialized')->exists()) {
                return;
            }
            if (User::exists() || Product::exists() || Category::exists() || Setting::exists()) {
                throw new LogicException('La demo requiere una base nueva y exclusiva. No se modificaron datos existentes.');
            }
            $emails = [];
            foreach (['admin' => UserRole::Admin, 'seller' => UserRole::Seller, 'customer' => UserRole::Customer] as $key => $role) {
                $email = mb_strtolower((string) config('demo.users.'.$key.'.email'));
                $password = (string) config('demo.users.'.$key.'.password');
                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || in_array($email, $emails, true) || strlen($password) < 16 || strlen($password) > 72) {
                    throw new LogicException('Configurá tres emails distintos y contraseñas secretas de entre 16 y 72 caracteres para la demo.');
                }
                $emails[] = $email;
                $user = new User;
                $user->forceFill(['first_name' => ucfirst($key), 'last_name' => 'Demo', 'email' => $email, 'password' => $password, 'role' => $role, 'active' => true])->save();
            }
            $category = Category::create(['name' => 'Herramientas de prueba', 'slug' => 'herramientas-demo', 'active' => true, 'featured' => true]);
            $defaults = ['product_type' => 'simple', 'sale_unit' => 'unit', 'purchase_mode' => 'calculated', 'status' => 'published', 'featured' => true, 'description' => 'Artículo ficticio para probar la tienda. No está a la venta.'];
            foreach ([
                ['name' => 'Martillo · Demo', 'slug' => 'martillo-demo', 'price' => 125000, 'stock' => '20.000'],
                ['name' => 'Cable por metro · Demo', 'slug' => 'cable-demo', 'sale_unit' => 'meter', 'price' => 10001, 'stock' => '25.500'],
                ['name' => 'Corte a medida · Demo', 'slug' => 'corte-demo', 'purchase_mode' => 'inquiry'],
                ['name' => 'Pintura · Demo', 'slug' => 'pintura-demo', 'product_type' => 'variable'],
            ] as $attributes) {
                $product = Product::create($attributes + $defaults);
                $product->categories()->attach($category);
                if ($product->slug === 'pintura-demo') {
                    foreach (['1 L' => 150000, '4 L' => 500000] as $size => $price) {
                        ProductVariant::create(['product_id' => $product->id, 'name' => $size, 'value' => $size, 'price' => $price, 'stock' => '10.000', 'active' => true]);
                    }
                }
            }
            $this->call(HomepageSeeder::class);
            Setting::create(['key' => 'demo.initialized', 'value' => '1']);
        });
    }
}
