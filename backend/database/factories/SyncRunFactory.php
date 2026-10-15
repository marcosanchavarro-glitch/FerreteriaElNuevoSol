<?php

namespace Database\Factories;

use App\Enums\SyncRunStatus;
use App\Models\SyncRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SyncRun> */
class SyncRunFactory extends Factory
{
    protected $model = SyncRun::class;

    public function definition(): array
    {
        return ['source' => 'test-only', 'started_at' => now(), 'status' => SyncRunStatus::Running, 'processed_count' => 0, 'created_count' => 0, 'updated_count' => 0, 'error_count' => 0];
    }
}
