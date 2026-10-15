<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\CategoryImages;
use App\Services\Catalog\ProductGallery;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StorefrontPreviewSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local', 'testing') || ! str_ends_with(str_replace('\\', '/', DB::connection()->getDatabaseName()), '/storefront-preview.sqlite')) {
            throw new \RuntimeException('La demostración solo se permite en una base aislada storefront-preview.sqlite, en local/testing.');
        }
        $root = Category::firstOrCreate(['slug' => 'herramientas'], ['name' => 'Herramientas', 'description' => 'Herramientas para el taller, la obra y los proyectos de todos los días.', 'active' => true, 'featured' => true, 'sort_order' => 0]);
        $power = Category::firstOrCreate(['slug' => 'herramientas-electricas'], ['parent_id' => $root->id, 'name' => 'Herramientas eléctricas', 'active' => true, 'sort_order' => 0]);
        $drills = Category::firstOrCreate(['slug' => 'taladros'], ['parent_id' => $power->id, 'name' => 'Taladros', 'active' => true, 'sort_order' => 0]);
        $paint = Category::firstOrCreate(['slug' => 'pinturas'], ['name' => 'Pinturas', 'description' => 'Encontrá la presentación adecuada para tu proyecto.', 'active' => true, 'featured' => true, 'sort_order' => 1]);
        $electric = Category::firstOrCreate(['slug' => 'electricidad'], ['name' => 'Electricidad', 'active' => true, 'featured' => true, 'sort_order' => 2]);
        $base = ['status' => 'published', 'purchase_mode' => 'calculated', 'sale_unit' => 'unit', 'product_type' => 'simple', 'featured' => true, 'stock' => '10.000', 'short_description' => 'Producto de demostración para revisar el catálogo.', 'description' => 'Datos ficticios para revisión visual. No representan una oferta comercial ni información real de stock.'];
        $drill = Product::firstOrCreate(['slug' => 'taladro-demo'], $base + ['name' => 'Taladro percutor · DEMO', 'sku' => 'DEMO-TAL', 'price' => 10000000]);
        $drill->categories()->sync([$root->id, $drills->id]);
        $cable = Product::firstOrCreate(['slug' => 'cable-demo'], array_merge($base, ['name' => 'Cable unipolar por metro · DEMO', 'sale_unit' => 'meter', 'sku' => 'DEMO-CAB', 'price' => 250051, 'stock' => '25.500']));
        $cable->categories()->sync([$electric->id]);
        $painting = Product::firstOrCreate(['slug' => 'pintura-demo'], array_merge($base, ['name' => 'Pintura interior · DEMO', 'product_type' => 'variable', 'price' => null, 'stock' => null]));
        $painting->categories()->sync([$paint->id]);
        foreach ([['1 L', 850000, '10.000'], ['4 L', 2700000, '0.000'], ['20 L', 10500000, null]] as [$name,$price,$stock]) {
            $painting->variants()->firstOrCreate(['name' => $name], ['value' => $name, 'sku' => 'DEMO-PIN-'.str_replace(' ', '', $name), 'price' => $price, 'stock' => $stock, 'active' => true]);
        }
        $painting->variants()->firstOrCreate(['name' => 'Color a pedido'], ['value' => 'Personalizado', 'purchase_mode' => 'inquiry', 'price' => null, 'stock' => null, 'active' => true]);
        $inquiry = Product::firstOrCreate(['slug' => 'corte-a-medida-demo'], array_merge($base, ['name' => 'Corte de caño a medida · DEMO', 'sale_unit' => 'meter', 'purchase_mode' => 'inquiry', 'price' => null, 'stock' => null]));
        $inquiry->categories()->sync([$root->id]);
        Product::firstOrCreate(['slug' => 'sin-stock-demo'], array_merge($base, ['name' => 'Juego de llaves · DEMO', 'price' => 1800000, 'stock' => '0.000', 'featured' => false]));
        Product::firstOrCreate(['slug' => 'sin-variantes-demo'], array_merge($base, ['name' => 'Presentación en preparación · DEMO', 'product_type' => 'variable', 'price' => null, 'stock' => null, 'featured' => false]));
        for ($i = 1; $i <= 10; $i++) {
            $item = Product::firstOrCreate(['slug' => 'accesorio-demo-'.$i], array_merge($base, ['name' => 'Accesorio '.str_pad((string) $i, 2, '0', STR_PAD_LEFT).' · DEMO', 'price' => 125000 + $i * 100, 'featured' => false]));
            $item->categories()->sync([$root->id]);
        }
        if (! $drill->images()->exists()) {
            app(ProductGallery::class)->upload($drill, $this->image('Taladro DEMO', false), 'Ilustración de prueba, vista lateral');
            app(ProductGallery::class)->upload($drill, $this->image('Detalle DEMO', true), 'Ilustración de prueba, otra vista');
        }
        if (! $painting->images()->exists()) {
            app(ProductGallery::class)->upload($painting, $this->image('Pintura DEMO', true), 'Ilustración de envase de demostración');
        }
        if ($paint->image_path === null) {
            app(CategoryImages::class)->replace($paint, $this->image('Pinturas', true));
        }
    }

    private function image(string $label, bool $alternate): UploadedFile
    {
        $canvas = imagecreatetruecolor(640, 480);
        $background = imagecolorallocate($canvas, 247, 248, 251);
        $navy = imagecolorallocate($canvas, 22, 31, 85);
        $orange = imagecolorallocate($canvas, 240, 120, 60);
        imagefill($canvas, 0, 0, $background);
        if ($alternate) {
            imagefilledrectangle($canvas, 200, 120, 440, 340, $orange);
            imagefilledellipse($canvas, 320, 120, 240, 40, $navy);
            imagefilledrectangle($canvas, 230, 180, 410, 270, $background);
        } else {
            imagefilledrectangle($canvas, 180, 140, 430, 230, $orange);
            imagefilledrectangle($canvas, 260, 230, 325, 350, $navy);
            imagefilledrectangle($canvas, 430, 170, 540, 200, $navy);
        }
        imagestring($canvas, 5, 230, 400, $label, $navy);
        ob_start();
        imagepng($canvas);
        $bytes = ob_get_clean();
        imagedestroy($canvas);

        return UploadedFile::fake()->createWithContent('preview.png', $bytes);
    }
}
