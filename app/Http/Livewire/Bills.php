<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Bill;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\Cover;
use App\Models\Detail;
use Exception;
use Illuminate\Support\Facades\DB;

class Bills extends Component
{
    use WithPagination;

    public $description,$description_2,$reference,$amount,$amount_2,$type,$search,$selected_id,$pageTitle,$componentName,$details,$action;
    public $from,$to,$cov,$cov_det,$bll,$bll_det,$bll1,$bll1_det;
    public $my_total;
    private $pagination = 20;

    public function paginationView(){

        return 'vendor.livewire.bootstrap';
    }

    public function mount(){

        $this->pageTitle = 'listado';
        $this->componentName = 'facturas/impuestos';
        $this->type = 'Elegir';
        //$this->my_total = 0;
        $this->details = [];
        $this->from = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 00:00:00';
        $this->to = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 23:59:59';
        $this->cov = Cover::firstWhere('description',$this->componentName);
        $this->cov_det = $this->cov->details->where('cover_id',$this->cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();
        $this->bll = Cover::firstWhere('description','facturas 6% acumulado');
        $this->bll_det = $this->bll->details->where('cover_id',$this->bll->id)->whereBetween('created_at',[$this->from, $this->to])->first();
        $this->bll1 = Cover::firstWhere('description','facturas 6% del dia');
        $this->bll1_det = $this->bll1->details->where('cover_id',$this->bll1->id)->whereBetween('created_at',[$this->from, $this->to])->first();
    }

    public function render()
    {   
        $this->my_total = 0;

        if(strlen($this->search) > 0){

            $data = Bill::where('description', 'like', '%' . $this->search . '%')
            ->orWhere('reference', 'like', '%' . $this->search . '%')
            ->orWhere('type', 'like', '%' . $this->search . '%')
            ->paginate($this->pagination);

            $this->my_total = Bill::where('reference', 'like', '%' . $this->search . '%')
            ->sum('amount');

        }else{

            $data = Bill::orderBy('reference', 'asc')->paginate($this->pagination);

            $vars = Bill::all();

            foreach($vars as $var){
    
                $this->my_total += $var->amount;
            }
            //$this->my_total = $this->cov->balance;   
        }

        return view('livewire.bill.bills', [

            'bills' => $data
        ])
        ->extends('layouts.theme.app')
        ->section('content');

    }

    public function Store(){

        if($this->cov_det != null){

            $rules = [

                'reference' => 'required|min:5|max:45',
                'description' => 'required|min:10|max:255',
                'type' => 'not_in:Elegir',
                'amount' => 'required|numeric'
            ];

            $messages = [

                'reference.required' => 'La referencia es requerida',
                'reference.min' => 'La referencia debe contener al menos 5 caracteres',
                'reference.max' => 'La referencia debe contener 45 caracteres como maximo',
                'description.required' => 'La descripcion es requerida',
                'description.min' => 'La descripcion debe contener al menos 10 caracteres',
                'description.max' => 'La descripcion debe contener 255 caracteres como maximo',
                'type.not_in' => 'Seleccione una opcion',
                'amount.required' => 'El monto es requerido',
                'amount.numeric' => 'Este campo solo admite numeros'
            ];
            
            $this->validate($rules, $messages);

            DB::beginTransaction();
            
                try {

                    Bill::create([

                        'reference' => $this->reference,
                        'description' => $this->description,
                        'type' => $this->type,
                        'amount' => $this->amount
                    ]);

                    $this->cov->update([
                    
                        'balance' => $this->cov->balance + $this->amount
            
                    ]);
            
                    $this->cov_det->update([
            
                        'ingress' => $this->cov_det->ingress + $this->amount,
                        'actual_balance' => $this->cov_det->actual_balance + $this->amount
            
                    ]);

                    if($this->type == 'acumulativa'){

                        $this->bll->update([
                
                            'balance' => $this->bll->balance + $this->amount
                
                        ]);

                        $this->bll_det->update([

                            'ingress' => $this->bll_det->ingress + $this->amount,
                            'actual_balance' => $this->bll_det->actual_balance + $this->amount
                
                        ]);
                        
                        $this->bll1_det->update([

                            'actual_balance' => $this->bll1_det->actual_balance + $this->amount
                
                        ]);
                        
                    }

                    DB::commit();
                    $this->resetUI();
                    $this->mount();
                    $this->render();
                    $this->emit('item-added', 'Registro Exitoso');
                
                } catch (Exception) {
                    
                    DB::rollback();
                    $this->emit('movement-error', 'Algo salio mal');
                }

        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }

    }

    public function Edit(Bill $bill){
        
        $this->selected_id = $bill->id;
        $this->description = $bill->description;
        $this->reference = $bill->reference;
        $this->type = $bill->type;
        $this->amount = $bill->amount;
        $this->description_2 = '';
        $this->amount_2 = '';
        $this->action = 'Elegir';
        
        $this->emit('show-modal2', 'Abrir Modal');

    }

    public function Update(){

        if($this->cov_det != null){
        
            $bill = Bill::find($this->selected_id);

            $rules = [

                'reference' => 'required|min:5|max:45',
                'description' => 'required|min:10|max:255',
                'type' => 'not_in:Elegir',
                'amount' => 'required|numeric',
                'amount_2' => 'exclude_if:action,edicion|required|numeric',
                'description_2' => 'exclude_if:action,edicion|required|min:10|max:255',
                'action' => 'not_in:Elegir',
            ];

            $messages = [

                'reference.required' => 'La referencia es requerida',
                'reference.min' => 'La referencia debe contener al menos 5 caracteres',
                'reference.max' => 'La referencia debe contener 45 caracteres como maximo',
                'description.required' => 'La descripcion es requerida',
                'description.min' => 'La descripcion debe contener al menos 10 caracteres',
                'description.max' => 'La descripcion debe contener 255 caracteres como maximo',
                'type.not_in' => 'Seleccione una opcion',
                'amount.required' => 'El monto es requerido',
                'amount.numeric' => 'Este campo solo admite numeros',
                'amount_2.required' => 'El monto es requerido',
                'amount_2.numeric' => 'Este campo solo admite numeros',
                'description_2.required' => 'Los detalles son requeridos',
                'description_2.min' => 'Los detalles deben contener al menos 10 caracteres',
                'description_2.max' => 'Los detalles deben contener 255 caracteres como maximo',
                'action.not_in' => 'Seleccione una opcion',
            ];

            $this->validate($rules, $messages);

            DB::beginTransaction();
            
                try {
            
                    switch($this->action){
                        
                        case 'edicion':

                            $bill->Update([

                                'reference' => $this->reference,
                                'description' => $this->description,
                                'type' => $this->type,
                                'amount' => $this->amount
                            ]);

                        break;

                        case 'ingreso':

                            $detail = $bill->details()->create([

                                'description' => $this->description_2,
                                'amount' => $this->amount_2,
                                'previus_balance' => $bill->amount,
                                'actual_balance' => $bill->amount + $this->amount_2
                                
                            ]);
                
                            if($detail){
                
                                $bill->Update([
                
                                    'amount' => $bill->amount + $this->amount_2
                                ]);

                                $this->cov->update([
                            
                                    'balance' => $this->cov->balance + $this->amount_2
                        
                                ]);
                        
                                $this->cov_det->update([
                    
                                    'ingress' => $this->cov_det->ingress + $this->amount_2,
                                    'actual_balance' => $this->cov_det->actual_balance + $this->amount_2
                    
                                ]);
                
                                if($bill->type == 'acumulativa'){
                
                                    $this->bll->update([
                                
                                        'balance' => $this->bll->balance + $this->amount_2
                            
                                    ]);
                            
                                    $this->bll_det->update([
                        
                                        'ingress' => $this->bll_det->ingress + $this->amount_2,
                                        'actual_balance' => $this->bll_det->actual_balance + $this->amount_2
                        
                                    ]);
                        
                                    $this->bll1_det->update([
                        
                                        'actual_balance' => $this->bll1_det->actual_balance + $this->amount_2
                            
                                    ]);
                                }
                            }

                        break;

                        case 'egreso':

                            $detail = $bill->details()->create([

                                'description' => $this->description_2,
                                'amount' => $this->amount_2,
                                'previus_balance' => $bill->amount,
                                'actual_balance' => $bill->amount - $this->amount_2
                                
                            ]);
                
                            if($detail){
                                
                                //if($bill->amount > $this->amount_2){

                                    $bill->Update([
                
                                        'amount' => $bill->amount - $this->amount_2
                                    ]);

                                /*}else{

                                    $bill->delete();
                                }*/

                                $this->cov->update([
                            
                                    'balance' => $this->cov->balance - $this->amount_2
                        
                                ]);
                        
                                $this->cov_det->update([
                    
                                    'egress' => $this->cov_det->egress + $this->amount_2,
                                    'actual_balance' => $this->cov_det->actual_balance - $this->amount_2
                    
                                ]);
        
                                if($bill->type == 'acumulativa'){
        
                                    $this->bll->update([
                                
                                        'balance' => $this->bll->balance - $this->amount_2
                            
                                    ]);
                            
                                    $this->bll_det->update([
                        
                                        'egress' => $this->bll_det->egress + $this->amount_2,
                                        'actual_balance' => $this->bll_det->actual_balance - $this->amount_2
                        
                                    ]);
                        
                                    /*$this->bll1_det->update([
                        
                                        'actual_balance' => $this->bll1_det->actual_balance - $this->amount_2
                            
                                    ]);*/
                                }
                            }

                        break;
                    }

                    DB::commit();
                    $this->resetUI();
                    $this->mount();
                    $this->render();
                    $this->emit('item-updated', 'Registro Actualizado');

                } catch (Exception) {
                    
                    DB::rollback();
                    $this->emit('movement-error', 'Algo salio mal');
                }

        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }

    }

    protected $listeners = [
        'destroy' => 'Destroy',
        'cancel' => 'Cancel',
    ];

    public function Destroy(Bill $bill){

        if($this->cov_det != null){

            DB::beginTransaction();
            
                try {
            
                    $this->cov->update([
                        
                        'balance' => $this->cov->balance - $bill->amount

                    ]);

                    $this->cov_det->update([

                        'ingress' => $this->cov_det->ingress - $bill->amount,
                        'actual_balance' => $this->cov_det->actual_balance - $bill->amount

                    ]);

                    if($bill->type == 'acumulativa'){

                        $this->bll->update([
                        
                            'balance' => $this->bll->balance - $bill->amount
                
                        ]);
                
                        $this->bll_det->update([
                
                            'ingress' => $this->bll_det->ingress - $bill->amount,
                            'actual_balance' => $this->bll_det->actual_balance - $bill->amount
                
                        ]);

                        $this->bll1_det->update([
                
                            'actual_balance' => $this->bll1_det->actual_balance - $bill->amount
                
                        ]);
                    }

                    $bill->delete();
                    DB::commit();
                    $this->resetUI();
                    $this->mount();
                    $this->render();
                    $this->emit('item-deleted', 'Registro Eliminado');

                } catch (Exception) {
                    
                    DB::rollback();
                    $this->emit('movement-error', 'Algo salio mal');
                }

        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }

    }

    public function Details(Bill $bill){

        $this->details = $bill->details;
        $this->emit('show-detail', 'Mostrando modal');
    }

    public function Cancel(Detail $det){

        if($this->cov_det != null){

            $bill = Bill::firstWhere('id',$det->detailable_id);

            DB::beginTransaction();
            
                try {

                    if($det->actual_balance > $det->previus_balance){

                        if(($det->actual_balance - $det->amount) == ($bill->amount - $det->amount)){

                            $bill->update([
                            
                                'amount' => $bill->amount - $det->amount

                            ]);

                            $this->cov->update([

                                'balance' => $this->cov->balance - $det->amount

                            ]);

                            $this->cov_det->update([

                                'ingress' => $this->cov_det->ingress - $det->amount,
                                'actual_balance' => $this->cov_det->actual_balance - $det->amount

                            ]);

                            if($bill->type == 'acumulativa'){
                                

                                $this->bll->update([

                                    'balance' => $this->bll->balance - $det->amount
                
                                ]);
                
                                $this->bll_det->update([
                
                                    'ingress' => $this->bll_det->ingress - $det->amount,
                                    'actual_balance' => $this->bll_det->actual_balance - $det->amount
                
                                ]);
                                
                                $this->bll1_det->update([
                
                                    'actual_balance' => $this->bll1_det->actual_balance - $det->amount
                
                                ]);
                            }

                            $det->delete();
                            $this->emit('cancel-detail', 'Registro Anulado');

                        }else{

                            $this->emit('report-error', 'El saldo no coincide. Anule los movimientos mas recientes.');
                            return;
                        }

                    }else{

                        if(($det->actual_balance + $det->amount) == ($bill->amount + $det->amount)){
                            
                            $bill->update([
                        
                                'amount' => $bill->amount + $det->amount
                
                            ]);
                
                            $this->cov->update([
                    
                                'balance' => $this->cov->balance + $det->amount
                
                            ]);
                
                            $this->cov_det->update([
                
                                'egress' => $this->cov_det->egress - $det->amount,
                                'actual_balance' => $this->cov_det->actual_balance + $det->amount
                
                            ]);

                            if($bill->type == 'acumulativa'){

                                $this->bll->update([

                                    'balance' => $this->bll->balance + $det->amount
                
                                ]);
                
                                $this->bll_det->update([
                
                                    'egress' => $this->bll_det->egress - $det->amount,
                                    'actual_balance' => $this->bll_det->actual_balance + $det->amount
                
                                ]);
                                
                                /*$this->bll1_det->update([
                
                                    'actual_balance' => $this->bll1_det->actual_balance + $det->amount
                
                                ]);*/
                            }

                            $det->delete();
                            $this->emit('cancel-detail', 'Registro Anulado');

                        }else{

                            $this->emit('report-error', 'El saldo no coincide. Anule los movimientos mas recientes.');
                            return;
                        }
                    }

                    DB::commit();
                    $this->resetUI();
                    $this->mount();
                    $this->render();

                } catch (Exception) {
                    
                    DB::rollback();
                    $this->emit('report-error', 'Algo salio mal');
                }

        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }
        
    }

    public function resetUI(){

        $this->description = '';
        $this->description_2 = '';
        $this->reference = '';
        $this->amount = '';
        $this->amount_2 = '';
        $this->search = '';
        $this->action = 'Elegir';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
