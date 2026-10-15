<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ActivityLog> */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return ['user_id' => UserFactory::new(), 'action' => 'test.created', 'entity_type' => 'test', 'entity_id' => '1', 'metadata' => ['test' => true]];
    }
}
