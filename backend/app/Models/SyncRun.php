<?php

namespace App\Models;

use App\Enums\SyncRunStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    use HasFactory;

    protected $fillable = ['source', 'started_at', 'finished_at', 'status', 'processed_count', 'created_count', 'updated_count', 'error_count', 'error_details'];

    protected function casts(): array
    {
        return ['status' => SyncRunStatus::class, 'started_at' => 'immutable_datetime', 'finished_at' => 'immutable_datetime', 'processed_count' => 'integer', 'created_count' => 'integer', 'updated_count' => 'integer', 'error_count' => 'integer', 'error_details' => 'array'];
    }
}
