<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Costumer;
use Livewire\WithPagination;

class Costumers extends Component
{
    use WithPagination;

    public $description,$phone,$fax,$email,$nit,$search,$selected_id,$pageTitle,$componentName;
    private $pagination = 20;

    public function paginationView(){

        return 'vendor.livewire.bootstrap';
    }

    public function mount(){

        $this->pageTitle = 'listado';
        $this->componentName = 'clientes';
    }

    public function render()
    {   
        if(strlen($this->search) > 0){

            $data = Costumer::where('description', 'like', '%' . $this->search . '%')
            ->orWhere('phone', 'like', '%' . $this->search . '%')
            ->orWhere('fax', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhere('nit', 'like', '%' . $this->search . '%')
            ->paginate($this->pagination);

        }else{

            $data = Costumer::orderBy('id', 'asc')->paginate($this->pagination);
        }

        return view('livewire.costumer.costumers', [
            'costumers' => $data,
        ])
        ->extends('layouts.theme.app')
        ->section('content');

    }

    public function Store(){

        $rules = [

            'description' => 'required|min:5|unique:costumers'
        ];

        $messages = [

            'description.required' => 'El nombre del cliente es requerido',
            'description.min' => 'El nombre del cliente debe contener al menos 5 caracteres',
            'description.unique' => 'El nombre del cliente ya fue registrado'
        ];
        
        $this->validate($rules, $messages);

        Costumer::create([

            'description' => $this->description,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'email' => $this->email,
            'nit' => $this->nit
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Registro Exitoso');

    }

    public function Edit(Costumer $costumer){
        
        $this->selected_id = $costumer->id;
        $this->description = $costumer->description;
        $this->phone = $costumer->phone;
        $this->fax = $costumer->fax;
        $this->email = $costumer->email;
        $this->nit = $costumer->nit;
        $this->emit('show-modal', 'Abrir Modal');

    }

    public function Update(){
        
        $rules = [

            'description' => "required|min:5|unique:costumers,description,{$this->selected_id}"
        ];

        $messages = [

            'description.required' => 'El nombre del cliente es requerido',
            'description.min' => 'El nombre del cliente debe contener al menos 5 caracteres',
            'description.unique' => 'El nombre del cliente ya fue registrado'
        ];

        $this->validate($rules, $messages);

        $costumer = Costumer::find($this->selected_id);
        
        $costumer->Update([

            'description' => $this->description,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'email' => $this->email,
            'nit' => $this->nit
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Registro Actualizado');
    }

    protected $listeners = [

        'destroy' => 'Destroy'
    ];

    public function Destroy(Costumer $costumer){
        
        $costumer->delete();
        $this->resetUI();
        $this->emit('item-deleted', 'Registro Eliminado');
    }

    public function resetUI(){

        $this->description = '';
        $this->nit = '';
        $this->phone = '';
        $this->fax = '';
        $this->email = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
