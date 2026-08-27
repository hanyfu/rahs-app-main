<?php

namespace Database\Seeders;

use App\Models\Atoll;
use App\Models\Department;
use App\Models\Island;
use Illuminate\Database\Seeder;

class BaseDataSeeder extends Seeder
{
    public function run(): void
    {
        $atolls = ['Male Atoll', 'Addu Atoll', 'Haa Alif Atoll'];
        foreach ($atolls as $atollName) {
            $atoll = Atoll::firstOrCreate(['name' => $atollName], ['status' => 'active']);

            $islands = match ($atollName) {
                'Male Atoll' => ['Male City', 'Hulhumale'],
                'Addu Atoll' => ['Hithadhoo'],
                default => ['Dhiddhoo'],
            };

            foreach ($islands as $islandName) {
                Island::firstOrCreate(
                    ['name' => $islandName, 'atoll_id' => $atoll->id],
                    ['status' => 'active']
                );
            }
        }

        $departments = [
            ['Biomedical', '#0d9488'],
            ['IT Support', '#2563eb'],
            ['Facility Management', '#ea580c'],
        ];

        foreach ($departments as [$name, $color]) {
            Department::firstOrCreate(['name' => $name], ['color' => $color, 'status' => 'active']);
        }

        $this->command?->info('Base data seeded.');
    }
}
