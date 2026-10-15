<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CheckoutPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing') || ! str_ends_with(str_replace('\\', '/', DB::connection()->getDatabaseName()), '/storefront-preview.sqlite')) {
            throw new \RuntimeException('Solo se permite preparar checkout en storefront-preview.sqlite local/testing.');
        }
        $this->call(StorefrontPreviewSeeder::class);
        Product::where('slug', 'pintura-demo')->firstOrFail()->variants()->where('name', '4 L')->update(['stock' => 10000]);
        $user = User::firstOrNew(['email' => 'cliente.checkout@example.test']);
        if (! $user->exists) {
            $user->fill(['first_name' => 'Cliente', 'last_name' => 'DEMO', 'phone' => '1155550000', 'password' => 'SoloDesarrollo!2026']);
            $user->forceFill(['role' => 'customer', 'active' => true])->save();
        }
    }
}
