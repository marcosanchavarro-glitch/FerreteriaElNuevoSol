<?php

namespace Database\Seeders;

use App\Models\HomepageSection;
use App\Services\Content\Homepage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            if (HomepageSection::exists()) {
                return;
            }
            foreach (Homepage::defaults() as $i => $data) {
                HomepageSection::create($data + ['sort_order' => $i, 'active' => true]);
            }
        });
    }
}
