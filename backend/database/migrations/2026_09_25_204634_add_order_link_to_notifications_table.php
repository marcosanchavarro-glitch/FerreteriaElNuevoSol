<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unique(['order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropUnique(['order_id', 'type']);
        });
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
