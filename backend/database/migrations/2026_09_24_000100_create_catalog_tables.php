<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->nullable();
            $table->string('sku')->nullable();
            $table->index('external_id');
            $table->index('sku');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('product_type')->default('simple');
            $table->string('sale_unit')->default('unit');
            $table->string('purchase_mode')->default('calculated');
            $table->bigInteger('price')->nullable()->comment('Integer minor currency units; never float');
            $table->bigInteger('stock')->nullable()->comment('Fixed scale 3: 10500 means 10.500');
            $table->string('status')->default('draft');
            $table->boolean('featured')->default(false);
            $table->timestamp('external_synced_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'featured']);
            $table->index('name');
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('external_id')->nullable();
            $table->string('sku')->nullable();
            $table->index('external_id');
            $table->index('sku');
            $table->string('name');
            $table->string('value')->nullable();
            $table->string('sale_unit')->nullable();
            $table->string('purchase_mode')->nullable();
            $table->bigInteger('price')->nullable()->comment('Integer minor currency units; never float');
            $table->bigInteger('stock')->nullable()->comment('Fixed scale 3: 10500 means 10.500');
            $table->boolean('active')->default(true);
            $table->timestamp('external_synced_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'active']);
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['parent_id', 'sort_order']);
        });
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->primary(['product_id', 'category_id']);
            $table->index('category_id');
        });
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }
};
