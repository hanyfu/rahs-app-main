<?php

namespace Database\Seeders;

use App\Models\HospitalContact;
use App\Models\Island;
use Illuminate\Database\Seeder;

class HospitalsSeeder extends Seeder
{
    public function run(): void
    {
        $islandId = fn (string $name) => Island::query()->where('name', $name)->value('id');

        $contacts = [
            ['Indira Gandhi Memorial Hospital', 'Male City', 'Dr. Ibrahim Ahmed', '3335335', 'Clinical Director'],
            ['Hulhumale Hospital', 'Hulhumale', 'Ms. Aishath Mohamed', '3353355', 'Hospital Administrator'],
            ['Addu Equatorial Hospital', 'Hithadhoo', 'Dr. Mohamed Ali', '6885555', 'Medical Director'],
        ];

        foreach ($contacts as [$name, $island, $manager, $phone, $designation]) {
            HospitalContact::firstOrCreate(
                ['hospital_name' => $name],
                [
                    'island_id' => $islandId($island),
                    'manager_name' => $manager,
                    'contact_number' => $phone,
                    'contact_designation' => $designation,
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('Hospital contacts seeded.');
    }
}
