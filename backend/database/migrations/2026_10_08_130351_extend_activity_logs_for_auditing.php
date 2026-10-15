<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role')->nullable();
            $table->string('module')->default('legacy');
            $table->string('result')->default('unknown');
            $table->string('origin')->default('legacy');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->index(['actor_id', 'created_at']);
            $table->index(['created_at', 'id']);
            $table->index(['action', 'created_at']);
            $table->index(['module', 'created_at']);
            $table->index(['result', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            foreach (['actor_id_created_at', 'created_at_id', 'action_created_at', 'module_created_at', 'result_created_at'] as $index) {
                $table->dropIndex('activity_logs_'.$index.'_index');
            }
            $table->dropColumn(['actor_id', 'actor_name', 'actor_role', 'module', 'result', 'origin', 'before_values', 'after_values']);
        });
    }
};
