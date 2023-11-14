<?php

namespace App\Http\Livewire;

use App\Models\Category;
use App\Models\Subcategory;
use Livewire\Component;
use App\Models\Product;
use App\Models\Office;
use App\Models\State;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $descripcion,$marca,$aro,$trilla,$lona,$code,$cost,$price,$state,$search,$selected_id,$pageTitle,$componentName,$image,$catId,$subId;
    private $pagination = 40;

    public function paginationView(){

        return 'vendor.livewire.bootstrap';
    }

    public function mount(){

        $this->pageTitle = 'Listado';
        $this->componentName = 'Productos';
        $this->catId = 1;
        $this->subId = 'Elegir';
        $this->state = 'Elegir';
        //$this->subcategories = [];

    }

    public function render()
    {  
        $cat = Category::find($this->catId);
        $subcategories = $cat->subcategories()->get();
        //dd($subcategories);


        if(strlen($this->search) > 0){

            $data = Product::join('category_subcategory as c_s', 'c_s.id', 'products.category_subcategory_id')
            ->join('categories as c','c.id','c_s.category_id')
            ->join('subcategories as s','s.id','c_s.subcategory_id')
            ->select('products.*', 'c.name as category', 's.name as subcategory')
            ->where('products.code', 'like', '%' . $this->search . '%')
            ->orWhere('products.description', 'like', '%' . $this->search . '%')
            ->orWhere('products.brand', 'like', '%' . $this->search . '%')
            ->orWhere('products.ring', 'like', '%' . $this->search . '%')
            ->orWhere('products.threshing', 'like', '%' . $this->search . '%')
            ->orWhere('products.tarp', 'like', '%' . $this->search . '%')
            ->orWhere('c.name', 'like', '%' . $this->search . '%')
            ->orWhere('s.name', 'like', '%' . $this->search . '%')
            ->orderBy('products.category_subcategory_id', 'asc')
            ->orderBy('products.ring', 'asc')
            ->orderBy('products.code', 'asc')
            ->paginate($this->pagination);

        }else{

            $data = Product::join('category_subcategory as c_s', 'c_s.id', 'products.category_subcategory_id')
            ->join('categories as c','c.id','c_s.category_id')
            ->join('subcategories as s','s.id','c_s.subcategory_id')
            ->select('products.*', 'c.name as category', 's.name as subcategory')
            ->orderBy('products.category_subcategory_id', 'asc')
            ->orderBy('products.ring', 'asc')
            ->orderBy('products.code', 'asc')
            ->paginate($this->pagination);
        }

        return view('livewire.product.products', [

            'products' => $data,
            'categories' => Category::with('subcategories')->get(),
            'states' => State::where('type','product')->orderBy('id','asc')->get(),

        ],compact('subcategories'))
        ->extends('layouts.theme.app')
        ->section('content');

    }

    /*public function updatedcatId($category_id){

        $this->subcategories = Subcategory::join('category_subcategory as c_s','c_s.subcategory_id','subcategories.id')->where('category_id',$category_id)->get();
        
    }*/

    public function Store(){

        $rules = [

            'descripcion' => 'required|min:3',
            'code' => 'required|min:5|unique:products',
            'cost' => 'required',
            'price' => 'required',
            'state' => 'required|not_in:Elegir',
            'catId' => 'required|not_in:Elegir',
            'subId' => 'required|not_in:Elegir'
        ];

        $messages = [

            'descripcion.required' => 'La medida del producto es requerida',
            'descripcion.min' => 'La medida del producto debe contener al menos 3 caracteres',
            'code.required' => 'El codigo del producto es requerido',
            'code.min' => 'El codigo del producto debe contener al menos 5 caracteres',
            'code.unique' => 'El codigo de producto ya existe',
            'cost.required' => 'El costo es requerido',
            'price.required' => 'El precio es requerido',
            'state.required' => 'Seleccione una opcion',
            'state.not_in' => 'Seleccione una opcion',
            'catId.required' => 'Seleccione una opcion',
            'catId.not_in' => 'Seleccione una opcion',
            'subId.required' => 'Seleccione una opcion',
            'subId.not_in' => 'Seleccione una opcion'
        ];
        
        $this->validate($rules, $messages);

        $categories = Category::find($this->catId);
        $pivot = $categories->subcategories->firstWhere('id',$this->subId)->pivot->id;
        $offices = Office::with('products')->get();

        $product = Product::create([

            'description' => $this->descripcion,
            'brand' => $this->marca,
            'ring' => $this->aro,
            'threshing' => $this->trilla,
            'tarp' => $this->lona,
            'code' => $this->code,
            'cost' => $this->cost,
            'price' => $this->price,
            'category_subcategory_id' => $pivot,
            'state_id' => $this->state
        ]);

        /*if($product){

            if($this->image){
            
                $customFileName = uniqid() . '_.' . $this->image->extension();
                $this->image->storeAs('public/products', $customFileName);
                $product->image()->create(['url' => $customFileName]);
            }
        }*/

        if($product){

            foreach($offices as $office){

                $product->offices()->attach($office->id,[
                    'stock' => 0,
                    'alerts' => 1
                ]);
            }
        }

        $this->resetUI();
        $this->emit('item-added', 'Registro Exitoso');

    }

    public function Edit(Product $product){
        
        $pivot = Category::join('category_subcategory as c_s','c_s.category_id','categories.id')
        ->select('c_s.category_id as cat','c_s.subcategory_id as sub')
        ->firstWhere('c_s.id',$product->category_subcategory_id);
        
        $this->selected_id = $product->id;
        $this->descripcion = $product->description;
        $this->marca = $product->brand;
        $this->aro = $product->ring;
        $this->trilla = $product->threshing;
        $this->lona = $product->tarp;
        $this->code = $product->code;
        $this->cost = $product->cost;
        $this->price = $product->price;
        $this->catId = $pivot->cat;
        $this->subId = $pivot->sub;
        $this->state = $product->state_id;
        //$this->image = null;
        
        $this->emit('show-modal', 'Abrir Modal');

    }

    public function Update(){
        
        $category = Category::find($this->catId);
        $pivot = $category->subcategories->firstWhere('id',$this->subId)->pivot->id;
        
        $rules = [

            'descripcion' => 'required|min:3',
            'code' => "required|min:5|unique:products,code,{$this->selected_id}",
            'cost' => 'required',
            'price' => 'required',
            'catId' => 'required|not_in:Elegir',
            'subId' => 'required|not_in:Elegir',
            'state' => 'not_in:Elegir',
        ];

        $messages = [

            'descripcion.required' => 'La medida del producto es requerida',
            'descripcion.min' => 'La medida del producto debe contener al menos 3 caracteres',
            'code.required' => 'El codigo del producto es requerido',
            'code.min' => 'El codigo del producto debe contener al menos 5 caracteres',
            'code.unique' => 'El codigo de producto ya existe',
            'cost.required' => 'El costo es requerido',
            'price.required' => 'El precio es requerido',
            'catId.required' => 'Seleccione una categoria',
            'catId.not_in' => 'Elija una opcion',
            'subId.required' => 'Seleccione una subcategoria',
            'subId.not_in' => 'Elija una opcion',
            'state.not_in' => 'Seleccione una opcion',
        ];

        $this->validate($rules, $messages);

        $product = Product::find($this->selected_id);
        //dd($product->imagen);
        $product->Update([

            'description' => $this->descripcion,
            'brand' => $this->marca,
            'ring' => $this->aro,
            'threshing' => $this->trilla,
            'tarp' => $this->lona,
            'code' => $this->code,
            'cost' => $this->cost,
            'price' => $this->price,
            'category_subcategory_id' => $pivot,
            'state_id' => $this->state
        ]);

        /*if($this->image){
            
            $customFileName = uniqid() . '_.' . $this->image->extension();
            $this->image->storeAs('public/products', $customFileName);
            $imageTemp = $product->imagen;
            $product->imagen = $customFileName;
            $product->image()->create(['url' => $customFileName]);
            //$product->save();

            if($imageTemp != null){
                
                if(file_exists('storage/products/' . $imageTemp )){
                    
                    unlink('storage/products/' . $imageTemp);
                }
            }
        }*/

        $this->resetUI();
        $this->emit('item-updated', 'Registro Actualizado');
    }

    protected $listeners = [

        'destroy' => 'Destroy'
    ];

    public function Destroy(Product $product){

        //$imageTemp = $product->image;   //recuperar y guardar en una variable temporal el archivo almacenado originalmente
        $product->delete();
        /*
        if($imageTemp != null){ //validar si el registro tenia una imagen guardada
            if(file_exists('storage/products/' . $imageTemp )){ //validar si lo que tiene almacenado la variable temporal existe fisicamente en el directorio
                //metodo unlink de php requiere un parametro (directorio + nombre de archivo) para eliminar un archivo fisicamente
                unlink('storage/products/' . $imageTemp);   //si existe fisicamente se elimina ese archivo del directorio
            }
        }*/

        $this->resetUI();   //limpiar la informacion de los campos del formulario
        $this->emit('item-deleted', 'Registro Eliminado');  //evento a ser escuchado desde el frontend
    }

    public function resetUI(){  //metodo para limpiar la informacion de las propiedades publicas

        $this->descripcion = '';
        $this->marca = '';
        $this->aro = '';
        $this->trilla = '';
        $this->lona = '';
        $this->code = '';
        $this->cost = '';
        $this->price = '';
        $this->catId = 1;
        $this->subId = 'Elegir';
        $this->state = 'Elegir';
        //$this->image = null;
        $this->search = '';
        $this->selected_id = '0';
        $this->resetValidation();   //metodo para limpiar las validaciones del formulario
        $this->resetPage(); //metodo de livewire para volver al listado principal
    }
}
