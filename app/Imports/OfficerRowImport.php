<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OfficerRowImport implements ToCollection, WithHeadingRow
{
    /**
     * Transform and return the imported rows collection.
     *
     * @param  Collection  $rows
     * @return Collection
     */
    public function collection(Collection $rows): Collection
    {
        return $rows;
    }
}