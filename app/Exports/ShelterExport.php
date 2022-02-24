<?php

namespace App\Exports;

use App\Models\Shelter;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;

class ShelterExport implements FromView
{


    public function view(): View
    {
        return view('exports.shelters', [
            'shelters' => Shelter::query()->select("id", "city", "region", "address", "balance_holder", "responsible_person", "description")->get()
        ]);

    }
}
