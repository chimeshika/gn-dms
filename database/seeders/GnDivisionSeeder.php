<?php

namespace Database\Seeders;

use App\Models\DsDivision;
use App\Models\GnDivision;
use Illuminate\Database\Seeder;

class GnDivisionSeeder extends Seeder
{
    public function run(): void
    {
        $gnByDs = [
            'Colombo' => [
                '01' => 'Pettah',
                '02' => 'Fort',
                '03' => 'Maradana',
                '04' => 'Punche Boralasgamuwa',
                '05' => 'Kotahena',
                '06' => 'Bambalapitiya',
                '07' => 'Wellawatte',
                '08' => 'Kollupitiya',
                '09' => 'Narahenpita',
                '10' => 'Thimbirigasyaya',
            ],
            'Homagama' => [
                '11' => 'Homagama',
                '12' => 'Rukmalgama',
                '13' => 'Katuwana',
                '14' => 'Kottawa',
                '15' => 'Meepe',
                '16' => 'Makumbura',
            ],
            'Kaduwela' => [
                '17' => 'Kaduwela',
                '18' => 'Battaramulla',
                '19' => 'Malabe',
                '20' => 'Koswatta',
                '21' => 'Kotikawatta',
            ],
        ];

        foreach ($gnByDs as $dsName => $gns) {
            $ds = DsDivision::where('name_en', $dsName)->first();

            if (! $ds) {
                continue;
            }

            foreach ($gns as $code => $name) {
                GnDivision::updateOrCreate(
                    ['ds_division_id' => $ds->id, 'code' => $code],
                    ['name_en' => $name]
                );
            }
        }
    }
}
