<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Provider;
use Livewire\WithPagination;

class Providers extends Component
{
    use WithPagination;

    public $description,$phone,$fax,$email,$country,$city,$search,$selected_id,$pageTitle,$componentName;
    private $pagination =20;

    public function paginationView(){

        return 'vendor.livewire.bootstrap';
    }

    public function mount(){

        $this->pageTitle = 'listado';
        $this->componentName = 'proveedores';
    }

    public function render()
    {   
        if(strlen($this->search) > 0){

            $data = Provider::where('description', 'like', '%' . $this->search . '%')
            ->orWhere('phone', 'like', '%' . $this->search . '%')
            ->orWhere('fax', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhere('country', 'like', '%' . $this->search . '%')
            ->orWhere('city', 'like', '%' . $this->search . '%')
            ->paginate($this->pagination);

        }else{

            $data = Provider::orderBy('id', 'asc')->paginate($this->pagination);
        }

        return view('livewire.provider.providers', [
            'providers' => $data,
        ])
        ->extends('layouts.theme.app')
        ->section('content');

    }

    public function Store(){

        $rules = [

            'description' => 'required|min:3|unique:providers',
            'country' => 'required',
            'city' => 'required'
        ];

        $messages = [

            'description.required' => 'La descripcion del proveedor es requerida',
            'description.min' => 'La descripcion del proveedor debe contener al menos 3 caracteres',
            'description.unique' => 'El proveedor ya fue registrado',
            'country.required' => 'El pais del proveedor es requerido',
            'city.required' => 'La ciudad del proveedor es requerida'
        ];
        
        $this->validate($rules, $messages);

        Provider::create([

            'description' => $this->description,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city
        ]);

        $this->resetUI();
        $this->emit('item-added', 'Registro Exitoso');

    }

    public function Edit(Provider $provider){
        
        $this->selected_id = $provider->id;
        $this->description = $provider->description;
        $this->phone = $provider->phone;
        $this->fax = $provider->fax;
        $this->email = $provider->email;
        $this->country = $provider->country;
        $this->city = $provider->city;
        
        $this->emit('show-modal', 'Abrir Modal');

    }

    public function Update(){
        
        $rules = [

            'description' => "required|min:3|unique:providers,description,{$this->selected_id}",
            'country' => 'required',
            'city' => 'required'
        ];

        $messages = [

            'description.required' => 'La descripcion del proveedor es requerida',
            'description.min' => 'La descripcion del proveedor debe contener al menos 3 caracteres',
            'description.unique' => 'El proveedor ya fue registrado',
            'country.required' => 'El pais del proveedor es requerido',
            'city.required' => 'La ciudad del proveedor es requerida'
        ];
        $this->validate($rules, $messages);

        $provider = Provider::find($this->selected_id);
        
        $provider->Update([

            'description' => $this->description,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'email' => $this->email,
            'country' => $this->country,
            'city' => $this->city
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Registro Actualizado');
    }

    protected $listeners = [

        'destroy' => 'Destroy'
    ];

    public function Destroy(Provider $provider){
        
        $provider->delete();

        $this->resetUI();
        $this->emit('item-deleted', 'Registro Eliminado');
    }

    public function resetUI(){

        $this->description = '';
        $this->phone = '';
        $this->fax = '';
        $this->email = '';
        $this->country = '';
        $this->city = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
