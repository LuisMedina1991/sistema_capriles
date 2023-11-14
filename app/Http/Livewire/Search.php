<?php

namespace App\Http\Livewire;

use App\Models\Office;
use Livewire\Component;

class Search extends Component
{
    public $search,$office,$sale_price,$pf;

    public function render()
    {
        return view('livewire.search',[
            'offices' => Office::orderBy('id', 'asc')->get(),
        ]);
    }
}
