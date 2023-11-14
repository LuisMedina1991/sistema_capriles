<?php

namespace App\Http\Livewire;

use App\Models\Denomination;
use App\Models\Product;
use App\Models\Office;
use App\Models\Sale;
use App\Models\State;
use App\Models\Cover;
use App\Models\Paydesk;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Livewire\Component;

class Sales extends Component
{
    public $total,$itemsQuantity,$efectivo,$change;
    public $cov,$cov_det,$from,$to;

    public function mount(){

        $this->efectivo = 0;
        $this->change = 0;
        $this->total = Cart::getTotal();
        $this->itemsQuantity = Cart::getTotalQuantity();
        $this->from = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 00:00:00';
        $this->to = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 23:59:59';
        $this->cov = Cover::firstWhere('description','inventario');
        $this->cov_det = $this->cov->details->where('cover_id',$this->cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();
    }

    public function render()
    {
        return view('livewire.sale.sales',[

            'denominations' => Denomination::orderBy('id', 'asc')->get(),
            'cart' => Cart::getContent()->sortBy('name')
        ])
        ->extends('app')
        ->section('content');
    }

    public function ACash($value){  //metodo para acumular el valor al clickear las botonos de denominaciones
        //sumatoria de lo que tiene en $efectivo + lo que se vaya clickeando
        //si se clickea exacto(0) la caja $efectivo muestra el valor de $total
        $this->efectivo += ($value == 0 ? $this->total : $value);   //caso contrario muestra el acumulativo de $value
        $this->change = ($this->efectivo - $this->total);   //obtenemos el cambio
    }

    protected $listeners = [

        'scan-code' => 'ScanCode',
        'removeItem' => 'removeItem',
        'clearCart' => 'clearCart',
        'saveSale' => 'saveSale'
    ];

    
    public function ScanCode($code,$sale_price,$office,$pf,$cant = 1){

        $product = Product::firstWhere('code', $code);

            if($product == null || empty($code) || $product->offices()->firstWhere('name',$office) == null){

                $this->emit('scan-notfound', 'El producto no esta registrado');

            }else{
                
                if($this->InCart($product->offices()->first()->pivot->id, $office)){

                    $this->increaseQty($product->offices()->first()->pivot->id);
                    return;
                }

                if($product->offices()->firstWhere('name',$office)->pivot->stock < 1){

                    $this->emit('no-stock','Stock insuficiente');
                    return;
                }

                //Cart::add($product->offices()->firstWhere('name',$office)->pivot->id,$office,$sale_price,$cant,array($product->image,$product->description,$product->id,$product->brand,$pf));
                Cart::add($product->offices()->firstWhere('name',$office)->pivot->id,$office,$sale_price,$cant,array($product->threshing,$product->description,$product->id,$product->brand,$pf,$product->tarp));

                $this->total = Cart::getTotal();
                $this->itemsQuantity = Cart::getTotalQuantity();
                $this->emit('scan-ok', 'Producto agregado al carrito');
            }
            
    }

    public function InCart($id, $office){     //metodo para verificar si un producto ya existe en el carrito
        //metodo get obtiene un item por su id, de no ser encontrado devuelve null
        $exist = Cart::get($id); //obtener item del carrito y lo guardamos en variable

        //validar si el id del producto en el carrito es el mismo id de office_product
        if($exist && $exist->name == $office)
            return true;
        else
            return false;
    }

    public function increaseQty($cart_id, $cant = 1){
        $title = '';
        $exist = Cart::get($cart_id);
        $office = Office::firstWhere('name',$exist->name);
        
        if($exist)

            $title = 'Cantidad Actualizada';

        else

            $title = 'Producto Agregado';
        
        
        if($exist){

            if($office->products()->get()->firstWhere('pivot.id',$exist->id)->pivot->stock < ($cant + $exist->quantity)){

                $this->emit('no-stock', 'Stock insuficiente');
                return;
            }
        }

        Cart::update($exist->id,array('quantity'=> $cant));

        $this->total = Cart::getTotal();
        $this->itemsQuantity = Cart::getTotalQuantity();
        $this->emit('scan-ok', $title);
        
    }

    public function decreaseQty($cart_id){

        $exist = Cart::get($cart_id);
        $office = Office::firstWhere('name',$exist->name);
        Cart::remove($cart_id);
        $newQty = ($exist->quantity) - 1;
        $threshing = $office->products()->get()->firstWhere('pivot.id',$exist->id)->threshing;
        $tarp = $office->products()->get()->firstWhere('pivot.id',$exist->id)->tarp;
        //$image = $office->products()->get()->firstWhere('pivot.id',$exist->id)->image;
        $description = $office->products()->get()->firstWhere('pivot.id',$exist->id)->description;
        $product_id = $office->products()->get()->firstWhere('pivot.id',$exist->id)->id;
        $brand = $office->products()->get()->firstWhere('pivot.id',$exist->id)->brand;

        if($newQty > 0){

            //Cart::add($office->products()->get()->firstWhere('pivot.id',$exist->id)->pivot->id, $exist->name, $exist->price, $newQty, array($image,$description,$product_id,$brand,$exist->attributes[4]));
            Cart::add($office->products()->get()->firstWhere('pivot.id',$exist->id)->pivot->id, $exist->name, $exist->price, $newQty, array($threshing,$description,$product_id,$brand,$exist->attributes[4],$tarp));
            $this->total = Cart::getTotal();
            $this->itemsQuantity = Cart::getTotalQuantity();
            $this->emit('scan-ok', 'Cantidad actualizada');

        }else{

            $this->removeItem($cart_id);
            $this->emit('scan-ok', 'Producto eliminado del carrito');
        }
    }


    public function updateQty($cart_id, $cant = 1){

        $title = '';
        $exist = Cart::get($cart_id);
        $office = Office::firstWhere('name',$exist->name);
        $threshing = $office->products()->get()->firstWhere('pivot.id',$exist->id)->threshing;
        $tarp = $office->products()->get()->firstWhere('pivot.id',$exist->id)->tarp;
        //$image = $office->products()->get()->firstWhere('pivot.id',$exist->id)->image;
        $description = $office->products()->get()->firstWhere('pivot.id',$exist->id)->description;
        $product_id = $office->products()->get()->firstWhere('pivot.id',$exist->id)->id;
        $brand = $office->products()->get()->firstWhere('pivot.id',$exist->id)->brand;

        if($exist)  //validar si se obtuvo un valor distinto de null para el id buscado
            $title = 'Cantidad Actualizada';
        else
            $title = 'Producto Agregado';

        if($exist){ //validar si se obtuvo un valor distinto de null para el id buscado

            if($office->products()->get()->firstWhere('pivot.id',$exist->id)->pivot->stock < $cant){    //validar si las existencias del producto son suficientes

                $this->emit('no-stock', 'Stock insuficiente');  //evento a ser escuchado desde el frontend
                return; //detener el flujo del proceso
            }
        }

        $this->removeItem($cart_id);  //eliminar producto del carrito

        if($cant > 0){  //validar la cantidad

            //metodo add agrega o actualiza item en el carrito de compras
            //aqui obtenemos el id de office_product a travez de la relacion entre offices y products
            //Cart::add($office->products()->get()->firstWhere('pivot.id',$exist->id)->pivot->id, $exist->name, $exist->price, $cant, array($image,$description,$product_id,$brand,$exist->attributes[4]));
            Cart::add($office->products()->get()->firstWhere('pivot.id',$exist->id)->pivot->id, $exist->name, $exist->price, $cant, array($threshing,$description,$product_id,$brand,$exist->attributes[4],$tarp));
            //Cart::update($exist->id,array('quantity'=> $cant));
            $this->total = Cart::getTotal();    //actualizar el total con el metodo getTotal del carrito de compras
            $this->itemsQuantity = Cart::getTotalQuantity();    //actualizar la cantidad de item con el metodo getTotalQuantity del carrito de compras
            $this->emit('scan-ok', $title); //evento a ser escuchado desde el frontend
        }
    }

    public function removeItem($cart_id){ //metodo para eliminar item del carrito

        Cart::remove($cart_id);   //metodo remove del carrito que recibe el id del producto y lo elimina
        $this->total = Cart::getTotal();    //actualizar el total con el metodo getTotal del carrito de compras
        $this->itemsQuantity = Cart::getTotalQuantity();    //actualizar la cantidad de item con el metodo getTotalQuantity del carrito de compras
        $this->emit('scan-ok', 'Producto eliminado del carrito');   //evento a ser escuchado desde el frontend
    }

    public function clearCart(){    //metodo para limpiar el carrito y reinicializar propiedades publicas

        Cart::clear();  //metodo clear de carrito para eliminar items del carrito
        $this->efectivo = 0;
        $this->change = 0;
        $this->total = Cart::getTotal();    //actualizar el total con el metodo getTotal del carrito de compras
        $this->itemsQuantity = Cart::getTotalQuantity();    //actualizar la cantidad de item con el metodo getTotalQuantity del carrito de compras
        $this->emit('scan-ok', 'Carrito vacio');    //evento a ser escuchado desde el frontend
    }

    public function saveSale(){

        if($this->cov_det != null){

            $paydesk = Paydesk::orderBy('id', 'asc')->whereBetween('created_at', [$this->from, $this->to])->where('type','Ventas')->get();

            if(count($paydesk) == 0){

                if($this->total <= 0){

                    $this->emit('sale-error', 'AGREGA PRODUCTOS AL CARRITO');
                    return;
                }
        
                if($this->efectivo <= 0){
        
                    $this->emit('sale-error', 'INGRESE EL EFECTIVO');
                    return;
                }
        
                if($this->total > $this->efectivo){
        
                    $this->emit('sale-error', 'EL EFECTIVO ES INSUFICIENTE PARA LA COMPRA');
                    return;
                }
        
                DB::beginTransaction();
                
                try {
        
                    $items = Cart::getContent();
                    $state = State::firstWhere('name','realizado');
        
                    foreach($items as $item){
        
                        $product = Product::find($item->attributes[2]);
                        
                        Sale::create([
        
                            'quantity' => $item->quantity,
                            'total' => ($item->price * $item->quantity),
                            'utility' => (($item->price * $item->quantity) - ($product->cost * $item->quantity)),
                            'cash' => $this->efectivo,
                            'change' => $this->change,
                            'office' => $item->name,
                            'pf' => $item->attributes[4],
                            'state_id' => $state->id,
                            'user_id' => Auth()->user()->id,
                            'product_id' => Product::find($item->attributes[2])->id
                        ]);
                        
                        $product->offices()->updateExistingPivot($product->offices()->get()->firstWhere('pivot.id',$item->id)->pivot->office_id,[
                            'stock' => $product->offices()->get()->firstWhere('pivot.id',$item->id)->pivot->stock - $item->quantity,
                        ]);               
                    }
        
                    DB::commit();
        
                    Cart::clear();
                    $this->efectivo = 0;
                    $this->change = 0;
                    $this->total = Cart::getTotal();
                    $this->itemsQuantity = Cart::getTotalQuantity();
                    $this->emit('scan-ok', 'Venta registrada con exito');
                    //$this->emit('print-ticket', $sale->id); //evento a ser escuchado desde el frontend
        
                } catch (Exception $e) {    //capturar error en variable 
                    DB::rollback();     //deshacer todas las operaciones en caso de error
                    $this->emit('sale-error', $e->getMessage());    //evento a ser escuchado desde el frontend
                }

            }else{

                $this->emit('sale-error', 'Anule las ventas del dia desde caja general primero');
                return;
            }


        }else{

            $this->emit('sale-error', 'Se debe crear caratula del dia');
            return;
        }

    }

    public function printTicket($sale){ //metodo para imprimir tickets de venta

        return Redirect::to("print://$sale->id");   
    }
}
