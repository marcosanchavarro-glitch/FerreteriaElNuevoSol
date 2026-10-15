<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\PurchaseMode;
use App\Enums\SaleUnit;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('DevelopmentSeeder is allowed only in local/testing.');
        }
        DB::transaction(function () {
            foreach ([['admin', UserRole::Admin], ['vendedor', UserRole::Seller], ['cliente1', UserRole::Customer], ['cliente2', UserRole::Customer]] as [$name,$role]) {
                if (! User::where('email', $name.'@example.test')->exists()) {
                    $user = new User;
                    $user->forceFill(['first_name' => ucfirst($name), 'last_name' => 'Demostración', 'email' => $name.'@example.test', 'password' => 'SoloDesarrollo!2026', 'role' => $role, 'active' => true]);
                    $user->save();
                }
            }
            $tools = Category::firstOrCreate(['slug' => 'herramientas'], ['name' => 'Herramientas', 'active' => true]);
            $electric = Category::firstOrCreate(['slug' => 'herramientas-electricas'], ['name' => 'Herramientas eléctricas', 'parent_id' => $tools->id, 'active' => true]);
            $drills = Category::firstOrCreate(['slug' => 'taladros'], ['name' => 'Taladros', 'parent_id' => $electric->id, 'active' => true]);
            $paints = Category::firstOrCreate(['slug' => 'pinturas'], ['name' => 'Pinturas', 'active' => true]);
            $defaults = ['product_type' => ProductType::Simple, 'sale_unit' => SaleUnit::Unit, 'purchase_mode' => PurchaseMode::Calculated, 'status' => ProductStatus::Draft, 'featured' => false];
            $drill = Product::firstOrCreate(['slug' => 'taladro-demo'], array_merge($defaults, ['name' => 'Taladro — DEMO', 'sku' => 'DEMO-TAL', 'price' => 10000000, 'stock' => '15']));
            $cable = Product::firstOrCreate(['slug' => 'cable-demo'], array_merge($defaults, ['name' => 'Cable por metro — DEMO', 'sku' => 'DEMO-CAB', 'sale_unit' => SaleUnit::Meter, 'price' => 250000, 'stock' => '25.500']));
            $paint = Product::firstOrCreate(['slug' => 'pintura-demo'], array_merge($defaults, ['name' => 'Pintura — DEMO', 'product_type' => ProductType::Variable, 'price' => null, 'stock' => null]));
            foreach ([['1 L', 1500000], ['4 L', 5000000], ['20 L', 20000000]] as [$size,$price]) {
                ProductVariant::firstOrCreate(['product_id' => $paint->id, 'value' => $size], ['name' => $size, 'sku' => 'DEMO-PIN-'.str_replace(' ', '', $size), 'price' => $price, 'stock' => '10', 'active' => true]);
            }
            $inquiry = Product::firstOrCreate(['slug' => 'corte-cano-demo'], array_merge($defaults, ['name' => 'Corte de caño a medida — DEMO', 'sku' => 'DEMO-CON', 'purchase_mode' => PurchaseMode::Inquiry, 'price' => null, 'stock' => null]));
            $drill->categories()->syncWithoutDetaching([$tools->id, $drills->id]);
            $cable->categories()->syncWithoutDetaching([$tools->id]);
            $paint->categories()->syncWithoutDetaching([$paints->id]);
            $inquiry->categories()->syncWithoutDetaching([$tools->id]);
        });
    }
}
