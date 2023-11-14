<?php

namespace App\Http\Livewire;

use App\Models\BankAccount;
use App\Models\Bank;
use App\Models\Cover;
use App\Models\Sale;
use App\Models\Costumer;
use App\Models\Paydesk;
use App\Models\CheckReceivable;
use App\Models\Company;
use App\Models\Import;
use App\Models\Provider;
use App\Models\Bill;
use App\Models\Anticretic;
use App\Models\ProviderPayable;
use App\Models\OtherReceivable;
use App\Models\CostumerReceivable;
use App\Models\Payable;
use App\Models\Appropriation;
use App\Models\OtherProvider;
use App\Models\Detail;
use App\Models\Gym;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class Paydesks extends Component
{
    use WithPagination;

    public $description,$action,$amount,$search,$selected_id,$pageTitle,$componentName,$i_total,$e_total,$my_total;
    public $type,$temp,$temp1,$temp2,$temp3,$temp4,$details,$balance,$from,$to;
    public $gen,$gen_det,$reportRange,$reportType,$dateFrom,$dateTo,$details_2;
    public $dr1,$dr2,$dr3,$chc1,$chc2,$chc3;
    private $pagination = 40;

    public function paginationView(){

        return 'vendor.livewire.bootstrap';
    }

    public function mount(){

        $this->pageTitle = 'listado';
        $this->componentName = 'caja general';
        $this->action = 'Elegir';
        $this->type = 'Elegir';
        $this->temp = 'Elegir';
        $this->temp1 = 'Elegir';
        $this->temp2 = 'Elegir';
        $this->details = [];
        $this->reportRange = 0;
        $this->reportType = 0;
        $this->balance = 0;
        $this->my_total = 0;
        //$this->i_total = 0;
        //$this->e_total = 0;
        $this->from = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 00:00:00';
        $this->to = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 23:59:59';
        $this->gen = Cover::firstWhere('description',$this->componentName);
        $this->gen_det = $this->gen->details->where('cover_id',$this->gen->id)->whereBetween('created_at',[$this->from, $this->to])->first();
        $this->dr1 = 'Elegir';
        $this->dr2 = 'Elegir';
        $this->dr3 = 'Elegir';
        $this->chc1 = 'Elegir';
        $this->chc2 = 'Elegir';
        $this->chc3 = '';
    }

    public function render()
    {   

        $this->ReportsByDate();
        
        return view('livewire.paydesk.paydesks', [

            'accounts' => BankAccount::with('company','bank')->get(),
            'providers' => Provider::with('payables')->orderBy('description','asc')->get(),
            'imports' => Import::where('amount','>',0)->orderBy('id','asc')->get(),
            'checks' => CheckReceivable::orderBy('id','asc')->get(),
            'others' => OtherReceivable::orderby('reference','asc')->where('amount', '>', 0)->get(),
            'antics' => Anticretic::orderby('id','asc')->get(),
            'pays' => Payable::orderby('reference','asc')->get(),
            'payables' => Payable::orderby('reference','asc')->where('amount', '>', 0)->get(),
            'bills' => Bill::where('amount','>',0)->orderby('reference','asc')->get(),
            'appropiations' => Appropriation::orderby('id','asc')->get(),
            'other_providers' => OtherProvider::where('amount','>',0)->orderby('reference','asc')->get(),
            'gyms' => Gym::orderby('id','asc')->get(),
            'clients' => Costumer::orderBy('description','asc')->get(),
            'c_clients' => Costumer::with('checks')->whereHas('checks')->orderBy('description','asc')->get(),
            'd_clients' => Costumer::with('debts')->whereHas('debts')->orderBy('description','asc')->get(),
            'banks' => Bank::orderBy('id','asc')->get(),
            'companies' => Company::orderBy('id','asc')->get(),
            'covers' => Cover::orderBy('id','asc')
            ->where([['type','utilidad_diaria'],['description','<>','utilidad bruta del dia']])
            ->orWhere([['type','gasto_diario'],['description','<>','facturas 6% del dia']])->get(),

        ])
        ->extends('layouts.theme.app')
        ->section('content');

    }

    public function ReportsByDate(){

        //$this->my_total = $this->gen->balance;

        if($this->reportRange == 0){

            $fecha1 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 00:00:00';
            $fecha2 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 23:59:59';

        }else{

            $fecha1 = Carbon::parse($this->dateFrom)->format('Y-m-d') . ' 00:00:00';
            $fecha2 = Carbon::parse($this->dateTo)->format('Y-m-d') . ' 23:59:59';

        }

        if($this->reportRange == 1 && ($this->dateFrom == '' || $this->dateTo == '')){

            $this->emit('paydesk-error', 'Seleccione fecha de inicio y fecha de fin');
            return;
        }

        if($this->reportType == 0){

            $this->details_2 = Paydesk::orderBy('action', 'asc')->whereBetween('created_at', [$fecha1, $fecha2])->get();

            /*$pos = Paydesk::where('action','ingreso')->get();

            foreach($pos as $p){

                $this->i_total += $p->amount;
            }

            $neg = Paydesk::where('action','egreso')->get();

            foreach($neg as $n){

                $this->e_total += $n->amount;
            }*/

            $this->my_total = $this->gen->balance;
            
        }else{
            
            $this->details_2 = Paydesk::orderBy('id', 'asc')->whereBetween('created_at', [$fecha1, $fecha2])->where('type',$this->reportType)->get();

            if((count($this->details_2->where('action','ingreso')) > 0) && (count($this->details_2->where('action','egreso')) > 0)){

                $this->my_total = $this->details_2->where('action','ingreso')->sum('amount') - $this->details_2->where('action','egreso')->sum('amount');

            }else{

                $this->my_total = $this->details_2->sum('amount');
            }

            
        }


    }

    public function updateddr2($id){

        if(BankAccount::where('bank_id',$id)->where('company_id',$this->dr1)->get() != null){

            $this->details = BankAccount::where('bank_id',$id)->where('company_id',$this->dr1)->get();

        }else{

            $this->details = [];
        }
    }

    public function updatedchc1($id){

        $this->details = CheckReceivable::where('costumer_id',$id)->get();
    }

    public function updatedchc2($id){

        if(CheckReceivable::firstWhere('id',$id) != null){

            $this->balance = CheckReceivable::firstWhere('id',$id)->amount;
            $this->temp3 = CheckReceivable::firstWhere('id',$id)->number;
            $this->temp = Bank::firstWhere('id',CheckReceivable::firstWhere('id',$id)->bank_id)->description;

        }else{

            $this->balance = 0;
            $this->temp3 = '';
            $this->temp = 'Elegir';
        }
    }

    public function updatedtemp1($id){

        switch($this->type){

            case 'anticreticos':

                $this->details = Anticretic::where('id',$id)->get();

            break;

            case 'otros por pagar':

                if(Payable::firstWhere('id',$id) != null){

                    $this->balance = Payable::firstWhere('id',$id)->amount;
                    $this->temp2 = Payable::firstWhere('id',$id)->description;
        
                }else{
        
                    $this->balance = 0;
                    $this->temp3 = '';
                }

            break;

            case 'otros por cobrar':

                $this->details = OtherReceivable::where('id',$id)->get();

            break;

            case 'clientes por cobrar':

                $this->details = CostumerReceivable::where('costumer_id',$id)->where('amount', '>', 0)->get();

            break;

            case 'proveedores por pagar':

                $this->details = ProviderPayable::where('provider_id',$id)->where('amount', '>', 0)->get();

            break;

            case 'consignaciones':

                $this->details = Appropriation::where('id',$id)->get();

            break;

            case 'otros proveedores':

                if(OtherProvider::firstWhere('id',$id) != null){

                    $this->balance = OtherProvider::firstWhere('id',$id)->amount;
                    $this->temp2 = OtherProvider::firstWhere('id',$id)->description;
        
                }else{
        
                    $this->balance = 0;
                    $this->temp3 = '';
                }

            break;

            case 'facturas/impuestos':

                if(Bill::firstWhere('id',$id) != null){

                    $this->balance = Bill::firstWhere('id',$id)->amount;
                    $this->temp2 = Bill::firstWhere('id',$id)->description;
        
                }else{
        
                    $this->balance = 0;
                    $this->temp3 = '';
                }

            break;

            case 'gastos de importacion':

                if($this->action == 'ingreso' && $this->temp1 != 'Elegir'){

                    $this->balance = Import::firstWhere('id',$id)->amount;
                }

            break;

        }
       
    }

    public function updatedtemp2($id){

        switch($this->type){

            case 'anticreticos':

                if(Anticretic::firstWhere('id',$id) != null){

                    $this->balance = Anticretic::firstWhere('id',$id)->amount;
        
                }else{
        
                    $this->balance = 0;
                }

            break;

            case 'otros por cobrar':

                if(OtherReceivable::firstWhere('id',$id) != null){

                    $this->balance = OtherReceivable::firstWhere('id',$id)->amount;
        
                }else{
        
                    $this->balance = 0;
                }

            break;

            case 'clientes por cobrar':

                if(CostumerReceivable::firstWhere('id',$id) != null){

                    $this->balance = CostumerReceivable::firstWhere('id',$id)->amount;
        
                }else{
        
                    $this->balance = 0;
                }

            break;

            case 'proveedores por pagar':

                if(ProviderPayable::firstWhere('id',$id) != null){

                    $this->balance = ProviderPayable::firstWhere('id',$id)->amount;
        
                }else{
        
                    $this->balance = 0;
                }

            break;

            case 'consignaciones':

                if(Appropriation::firstWhere('id',$id) != null){

                    $this->balance = Appropriation::firstWhere('id',$id)->amount;
        
                }else{
        
                    $this->balance = 0;
                }

            break;

        }
       
    }

    public function Store(){

        if($this->gen_det != null){

            $rules = [

                'description' => 'required|min:10|max:255',
                'action' => 'not_in:Elegir',
                'type' => 'exclude_if:action,Elegir|not_in:Elegir',
                'amount' => 'required|numeric',
                'dr1' => 'exclude_unless:type,deposito/retiro|not_in:Elegir',
                'dr2' => 'exclude_unless:type,deposito/retiro|not_in:Elegir',
                'dr3' => 'exclude_unless:type,deposito/retiro|not_in:Elegir',
                'chc1' => 'exclude_unless:type,cheques por cobrar|not_in:Elegir',
                'chc2' => 'exclude_unless:type,cheques por cobrar|not_in:Elegir',
                'chc3' => 'exclude_unless:type,cheques por cobrar|exclude_if:action,ingreso|required|numeric',
            ];

            $messages = [

                'description.required' => 'La descripcion es requerida',
                'description.min' => 'La descripcion debe contener al menos 10 caracteres',
                'description.max' => 'La descripcion debe contener 255 caracteres como maximo',
                'action.not_in' => 'Seleccione una opcion',
                'type.not_in' => 'Seleccione una opcion',
                'amount.required' => 'El monto es requerido',
                'amount.numeric' => 'Este campo solo admite numeros',
                'dr1.not_in' => 'Seleccione una opcion',
                'dr2.not_in' => 'Seleccione una opcion',
                'dr3.not_in' => 'Seleccione una opcion',
                'chc1.not_in' => 'Seleccione una opcion',
                'chc2.not_in' => 'Seleccione una opcion',
                'chc3.required' => 'El numero de cheque es requerido',
                'chc3.numeric' => 'Este campo solo admite numeros',
            ];
            
            $this->validate($rules, $messages);

            DB::beginTransaction();
            
                try {

                    if($this->action == 'ingreso'){

                        $paydesk = Paydesk::create([

                            'action' => $this->action,
                            'description' => $this->description,
                            'type' => $this->type,
                            'relation' => 0,
                            'amount' => $this->amount
                        ]);

                        $this->gen->update([

                            'balance' => $this->gen->balance + $this->amount

                        ]);

                        $this->gen_det->update([

                            'ingress' => $this->gen_det->ingress + $this->amount,
                            'actual_balance' => $this->gen_det->actual_balance + $this->amount

                        ]);

                    }else{

                        $paydesk = Paydesk::create([

                            'action' => $this->action,
                            'description' => $this->description,
                            'type' => $this->type,
                            'relation' => 0,
                            'amount' => $this->amount
                        ]);

                        $this->gen->update([

                            'balance' => $this->gen->balance - $this->amount

                        ]);

                        $this->gen_det->update([

                            'egress' => $this->gen_det->egress + $this->amount,
                            'actual_balance' => $this->gen_det->actual_balance - $this->amount

                        ]);

                    }

                    if($paydesk){

                        if(Cover::firstWhere('description',$this->type) != null){

                            $cov = Cover::firstWhere('description',$this->type);
                            $cov_det = $cov->details->where('cover_id',$cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();

                            switch($this->type){

                                case 'gimnasio':

                                    if($this->action == 'ingreso'){

                                        $cov->update([
                    
                                            'balance' => $cov->balance + $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'ingress' => $cov_det->ingress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance + $this->amount
                            
                                        ]);

                                        $debt = Gym::create([
                                            
                                            'description' => $this->description,
                                            'amount' => $this->amount
                    
                                        ]);

                                        $paydesk->update([

                                            'relation' => $debt->id
                            
                                        ]);

                                    }else{

                                        $cov->update([
                    
                                            'balance' => $cov->balance - $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'egress' => $cov_det->egress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance - $this->amount
                            
                                        ]);

                                        $debt = Gym::create([
                                            
                                            'description' => $this->description,
                                            'amount' => - $this->amount
                    
                                        ]);

                                        $paydesk->update([

                                            'relation' => $debt->id
                            
                                        ]);
                                    }

                                break;

                                case 'anticreticos': 
                
                                    if($this->action == 'ingreso'){
                
                                        if($this->temp != 'Elegir'){
                
                                            $cov->update([
                            
                                                'balance' => $cov->balance + $this->amount
                                
                                            ]);
                                
                                            $cov_det->update([
                            
                                                'ingress' => $cov_det->ingress + $this->amount,
                                                'actual_balance' => $cov_det->actual_balance + $this->amount
                                
                                            ]);
                
                                            if($this->temp == 'Nuevo'){
                
                                                $debt = Anticretic::create([
                        
                                                    'description' => $this->description,
                                                    'reference' => $this->temp3,
                                                    'amount' => $this->amount
                            
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $debt->id
                                    
                                                ]);
                        
                                            }else{
                        
                                                $anticretic = Anticretic::find($this->temp1);
                        
                                                $det = $anticretic->details()->create([
                        
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance + $this->amount
                                                ]);
                        
                                                $anticretic->update([
                                    
                                                    'amount' => $this->balance + $this->amount
                                        
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $det->id
                                    
                                                ]);
                    
                                            }
                                        }
                
                
                                    }else{
                
                                        $anticretic = Anticretic::find($this->temp1);
                
                                        $detail = $anticretic->details()->create([
                
                                            'description' => $this->description,
                                            'amount' => $this->amount,
                                            'previus_balance' => $this->balance,
                                            'actual_balance' => $this->balance - $this->amount
                                        ]);
                
                                        //if($this->amount < $this->balance){
                
                                            $anticretic->update([
                            
                                                'amount' => $this->balance - $this->amount
                                    
                                            ]);
                
                                        /*}else{
                
                                            $anticretic->delete();
                                        }*/
                
                                        $cov->update([
                            
                                            'balance' => $cov->balance - $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'egress' => $cov_det->egress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance - $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $detail->id
                            
                                        ]);
                
                                    }
                
                                break;
                
                                case 'otros por pagar': 
                
                                    if($this->action == 'ingreso'){
                
                                        if($this->temp != 'Elegir'){
                
                                            $cov->update([
                            
                                                'balance' => $cov->balance + $this->amount
                                
                                            ]);
                                
                                            $cov_det->update([
                            
                                                'ingress' => $cov_det->ingress + $this->amount,
                                                'actual_balance' => $cov_det->actual_balance + $this->amount
                                
                                            ]);
                
                                            if($this->temp == 'Nueva'){
                
                                                $payable = Payable::create([
                    
                                                    'description' => $this->description,
                                                    'reference' => $this->temp3,
                                                    'amount' => $this->amount
                            
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $payable->id
                                    
                                                ]);
                    
                                            }else{
                    
                                                $payable = Payable::find($this->temp1);
                    
                                                $detail = $payable->details()->create([
                    
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance + $this->amount
                                                ]);
                    
                                                $payable->update([
                                
                                                    'amount' => $this->balance + $this->amount
                                        
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $detail->id
                                    
                                                ]);
                                            }
                                        }
                
                                    }else{
                
                                        $payable = Payable::find($this->temp1);
                
                                        $detail = $payable->details()->create([
                
                                            'description' => $this->description,
                                            'amount' => $this->amount,
                                            'previus_balance' => $this->balance,
                                            'actual_balance' => $this->balance - $this->amount
                                        ]);

                                        $payable->update([
                            
                                            'amount' => $this->balance - $this->amount
                                
                                        ]);
                
                                        $cov->update([
                            
                                            'balance' => $cov->balance - $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'egress' => $cov_det->egress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance - $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $detail->id
                            
                                        ]);

                                    }
                
                                break;
                
                                case 'otros por cobrar': 
                
                                    if($this->action == 'egreso'){
                
                                        $other = OtherReceivable::create([
                
                                            'description' => $this->description,
                                            'reference' => $this->temp3,
                                            'amount' => $this->amount
                    
                                        ]);
                
                                        $cov->update([
                            
                                            'balance' => $cov->balance + $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'ingress' => $cov_det->ingress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance + $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $other->id
                            
                                        ]);
                
                                    }else{
                
                                        $other = OtherReceivable::find($this->temp1);
                
                                        if($this->amount <= $this->balance){

                                            $debt = $other->update([
                
                                                'amount' => $this->balance - $this->amount
                    
                                            ]);

                                            if($debt){

                                                $detail = $other->details()->create([
                
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance - $this->amount
                                                ]);
                        
                                                $cov->update([
                                    
                                                    'balance' => $cov->balance - $this->amount
                                    
                                                ]);
                                    
                                                $cov_det->update([
                                
                                                    'egress' => $cov_det->egress + $this->amount,
                                                    'actual_balance' => $cov_det->actual_balance - $this->amount
                                    
                                                ]);
        
                                                $paydesk->update([
        
                                                    'relation' => $detail->id
                                    
                                                ]);
                                            }

                                        }else{

                                            $this->emit('movement-error','El pago es mayor a la deuda');
                                            return;
                                        }

                                    }
                
                                break;
                
                                case 'cheques por cobrar': 
                
                                    if($this->action == 'egreso'){
                                        
                                        $costumer = Costumer::find($this->chc1);
                                        $bank = Bank::find($this->chc2);
                
                                        $debt = CheckReceivable::create([
                
                                            'description' => $this->description,
                                            'amount' => $this->amount,
                                            'number' => $this->chc3,
                                            'bank_id' => $bank->id,
                                            'costumer_id' => $costumer->id
                
                                        ]);
                
                                        $cov->update([
                            
                                            'balance' => $cov->balance + $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'ingress' => $cov_det->ingress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance + $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $debt->id
                            
                                        ]);
                
                                    }else{
                
                                        $check = CheckReceivable::find($this->chc2);
                
                                        if($this->amount <= $this->balance){

                                            $debt = $check->update([
                
                                                'amount' => $this->balance - $this->amount
                    
                                            ]);

                                            if($debt){

                                                $detail = $check->details()->create([
                
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance - $this->amount
                                                    
                                                ]);
        
                                                $cov->update([
                                    
                                                    'balance' => $cov->balance - $this->amount
                                    
                                                ]);
                                    
                                                $cov_det->update([
                                
                                                    'egress' => $cov_det->egress + $this->amount,
                                                    'actual_balance' => $cov_det->actual_balance - $this->amount
                                    
                                                ]);
        
                                                $paydesk->update([
        
                                                    'relation' => $detail->id
                                    
                                                ]);
                                            }

                                        }else{

                                            $this->emit('movement-error','El monto es mayor al saldo');
                                            return;
                                        }

                                    }
                
                                break;
                
                                case 'clientes por cobrar': 
                
                                    if($this->action == 'egreso'){
                
                                        $costumer = Costumer::find($this->temp);
                
                                        $debt = CostumerReceivable::create([
                
                                            'description' => $this->description,
                                            'amount' => $this->amount,
                                            'costumer_id' => $costumer->id
                
                                        ]);
                
                                        $cov->update([
                            
                                            'balance' => $cov->balance + $this->amount
                            
                                        ]);
                
                                        $cov_det->update([

                                            'ingress' => $cov_det->ingress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance + $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $debt->id
                            
                                        ]);
                
                                    }else{
                
                                        $costumer = CostumerReceivable::find($this->temp2);
                
                                        if($this->amount <= $this->balance){

                                            $update = $costumer->update([
                
                                                'amount' => $this->balance - $this->amount
                        
                                            ]);

                                            if($update){

                                                $detail = $costumer->details()->create([
                
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance - $this->amount
                                                ]);

                                                $cov->update([
                            
                                                    'balance' => $cov->balance - $this->amount
                                    
                                                ]);
                        
                                                $cov_det->update([
        
                                                    'egress' => $cov_det->egress + $this->amount,
                                                    'actual_balance' => $cov_det->actual_balance - $this->amount
                                    
                                                ]);
        
                                                $paydesk->update([
        
                                                    'relation' => $detail->id
                                    
                                                ]);
                                            }

                                        }else{

                                            $this->emit('movement-error','El pago es mayor a la deuda');
                                            return;
                                        }

                                    }
                
                                break;
                
                                case 'facturas/impuestos': 
                
                                    if($this->action == 'ingreso'){
                
                                        if($this->temp != 'Elegir'){
                
                                            $cov->update([
                            
                                                'balance' => $cov->balance + $this->amount
                                
                                            ]);
                                
                                            $cov_det->update([
                            
                                                'ingress' => $cov_det->ingress + $this->amount,
                                                'actual_balance' => $cov_det->actual_balance + $this->amount
                                
                                            ]);
                
                                            if($this->temp == 'Nueva'){
                
                                                $debt = Bill::create([
                    
                                                    'description' => $this->temp4,
                                                    'reference' => $this->temp3,
                                                    'type' => 'normal',
                                                    'amount' => $this->amount
                            
                                                ]);

                                                $detail = $debt->details()->create([
                
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => 0,
                                                    'actual_balance' => $this->amount
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $detail->id
                                    
                                                ]);
                    
                                            }else{
                    
                                                $debt = Bill::find($this->temp1);
                    
                                                $detail = $debt->details()->create([
                    
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance + $this->amount
                                                ]);
                    
                                                $debt->update([
                                
                                                    'amount' => $this->balance + $this->amount
                                        
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $detail->id
                                    
                                                ]);
                                            }
                                        }
                
                                    }else{
                
                                        $debt = Bill::find($this->temp1);

                                        $detail = $debt->details()->create([

                                            'description' => $this->description,
                                            'amount' => $this->amount,
                                            'previus_balance' => $this->balance,
                                            'actual_balance' => $this->balance - $this->amount
                                        ]);

                                        $debt->update([
                            
                                            'amount' => $this->balance - $this->amount
                                
                                        ]);
                
                                        $cov->update([
                            
                                            'balance' => $cov->balance - $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'egress' => $cov_det->egress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance - $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $detail->id
                            
                                        ]);

                                    }
                
                                break;
                
                                case 'proveedores por pagar': 
                                    
                                    $provider = ProviderPayable::find($this->temp2);
                
                                    $detail = $provider->details()->create([
                
                                        'description' => $this->description,
                                        'amount' => $this->amount,
                                        'previus_balance' => $this->balance,
                                        'actual_balance' => $this->balance - $this->amount
                                    ]);
                
                                    //if($this->amount < $this->balance){
                
                                        $provider->update([
                
                                            'amount' => $this->balance - $this->amount
                    
                                        ]);
                
                                    /*}else{
                
                                        $provider->delete();
                                    }*/
                
                                    $cov->update([
                            
                                        'balance' => $cov->balance - $this->amount
                        
                                    ]);
                
                                    $cov_det->update([

                                        'egress' => $cov_det->egress + $this->amount,
                                        'actual_balance' => $cov_det->actual_balance - $this->amount
                        
                                    ]);

                                    $paydesk->update([

                                        'relation' => $detail->id
                        
                                    ]);
                
                                break;
                
                                case 'consignaciones': 
                                    
                                    $app = Appropriation::find($this->temp2);
                
                                    $detail = $app->details()->create([
                
                                        'description' => $this->description,
                                        'amount' => $this->amount,
                                        'previus_balance' => $this->balance,
                                        'actual_balance' => $this->balance - $this->amount
                                    ]);
                                    
                                    //if($this->balance > $this->amount){
                
                                        $app->update([
                
                                            'amount' => $this->balance - $this->amount
                    
                                        ]);
                
                                    /*}else{
                
                                        $app->delete();
                                    }*/
                
                                    $cov->update([
                            
                                        'balance' => $cov->balance - $this->amount
                        
                                    ]);
                
                                    $cov_det->update([

                                        'egress' => $cov_det->egress + $this->amount,
                                        'actual_balance' => $cov_det->actual_balance - $this->amount
                        
                                    ]);

                                    $paydesk->update([

                                        'relation' => $detail->id
                        
                                    ]);
                
                                break;
                
                                case 'otros proveedores': 
                
                                    if($this->action == 'ingreso'){
                
                                        if($this->temp != 'Elegir'){
                
                                            $cov->update([
                            
                                                'balance' => $cov->balance + $this->amount
                                
                                            ]);
                                
                                            $cov_det->update([
                            
                                                'ingress' => $cov_det->ingress + $this->amount,
                                                'actual_balance' => $cov_det->actual_balance + $this->amount
                                
                                            ]);
                
                                            if($this->temp == 'Nueva'){
                
                                                $debt = OtherProvider::create([
                    
                                                    'description' => $this->description,
                                                    'reference' => $this->temp3,
                                                    'amount' => $this->amount
                            
                                                ]);

                                                $detail = $debt->details()->create([
                
                                                    'description' => $this->temp4,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => 0,
                                                    'actual_balance' => $this->amount
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $detail->id
                                    
                                                ]);
                    
                                            }else{
                    
                                                $debt = OtherProvider::find($this->temp1);
                    
                                                $detail = $debt->details()->create([
                    
                                                    'description' => $this->temp4,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance + $this->amount
                                                ]);
                    
                                                $debt->update([
                                
                                                    'amount' => $this->balance + $this->amount
                                        
                                                ]);

                                                $paydesk->update([

                                                    'relation' => $detail->id
                                    
                                                ]);
                                            }
                                        }
                
                                    }else{
                
                                        $debt = OtherProvider::find($this->temp1);

                                        $detail = $debt->details()->create([

                                            'description' => $this->description,
                                            'amount' => $this->amount,
                                            'previus_balance' => $this->balance,
                                            'actual_balance' => $this->balance - $this->amount
                                        ]);

                                        $debt->update([
                            
                                            'amount' => $this->balance - $this->amount
                                
                                        ]);
                
                                        $cov->update([
                            
                                            'balance' => $cov->balance - $this->amount
                            
                                        ]);
                            
                                        $cov_det->update([
                        
                                            'egress' => $cov_det->egress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance - $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $detail->id
                            
                                        ]);

                                    }
                
                                break;
                
                                case 'gastos de importacion': 

                                    if($this->action == 'egreso'){

                                        $debt = Import::create([
                
                                            'description' => $this->description,
                                            'amount' => $this->amount
                    
                                        ]);
                    
                                        $cov->update([
                                
                                            'balance' => $cov->balance + $this->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                
                                            'ingress' => $cov_det->ingress + $this->amount,
                                            'actual_balance' => $cov_det->actual_balance + $this->amount
                            
                                        ]);

                                        $paydesk->update([

                                            'relation' => $debt->id
                            
                                        ]);

                                    }else{
                                        
                                        $import = Import::find($this->temp1);

                                        if($this->amount <= $this->balance){

                                            $debt = $import->update([
                
                                                'amount' => $this->balance - $this->amount
                        
                                            ]);

                                            if($debt){

                                                $det = $import->details()->create([
                
                                                    'description' => $this->description,
                                                    'amount' => $this->amount,
                                                    'previus_balance' => $this->balance,
                                                    'actual_balance' => $this->balance - $this->amount
                                                ]);
                            
                                                $cov->update([
                                    
                                                    'balance' => $cov->balance - $this->amount
                                    
                                                ]);
                        
                                                $cov_det->update([
        
                                                    'egress' => $cov_det->egress + $this->amount,
                                                    'actual_balance' => $cov_det->actual_balance - $this->amount
                                    
                                                ]);
        
                                                $paydesk->update([
        
                                                    'relation' => $det->id
                                    
                                                ]);
                                            }

                                        }else{

                                            $this->emit('movement-error','El pago es mayor a la deuda');
                                            return;
                                        }
                                    }
                
                                break;
                
                                case 'diferencia por t/c': 
                
                                    if($this->action == 'ingreso'){
                
                                        $cov_det->update([
                
                                            'actual_balance' => $cov_det->actual_balance + $this->amount
                            
                                        ]);
                
                                    }else{
                
                                        $cov_det->update([
                
                                            'actual_balance' => $cov_det->actual_balance - $this->amount
                            
                                        ]);
                
                                    }
                
                                break;
                
                                case 'gastos importadora': 
                
                                    $cov_det->update([
                
                                        'actual_balance' => $cov_det->actual_balance + $this->amount
                        
                                    ]);
                
                                break;
                
                                case 'gastos gorky': 
                
                                    $cov_det->update([
                
                                        'actual_balance' => $cov_det->actual_balance + $this->amount
                        
                                    ]);
                
                                break;
                
                                case 'gastos construccion': 
                
                                    $cov_det->update([
                
                                        'actual_balance' => $cov_det->actual_balance + $this->amount
                        
                                    ]);
                
                                break;

                                case 'comisiones': 
                
                                    $cov_det->update([
                
                                        'actual_balance' => $cov_det->actual_balance + $this->amount
                        
                                    ]);
                
                                break;

                                case 'utilidad': 
                
                                    $cov_det->update([
                
                                        'actual_balance' => $cov_det->actual_balance + $this->amount
                        
                                    ]);
                
                                break;

                                case 'perdida por devolucion': 
                
                                    $cov_det->update([
                
                                        'actual_balance' => $cov_det->actual_balance + $this->temp3
                        
                                    ]);
                
                                break;

                                case 'cambio de llantas': 
                
                                    $cov_det->update([
                
                                        'actual_balance' => $cov_det->actual_balance + $this->amount
                        
                                    ]);
                
                                break;
                                    
                            }
                
                        }else{
                
                            $account = BankAccount::firstWhere('id',$this->dr3);
                            $company = Company::firstWhere('id',$account->company_id)->description;
                            $bank = Bank::firstWhere('id',$account->bank_id)->description;
                            $cov = Cover::firstWhere('description',$bank . ' ' . $account->type . ' ' . $account->currency . ' ' . $company);
                            $cov_det = $cov->details->where('cover_id',$cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();

                            if($this->action == 'egreso'){

                                $detail = $account->details()->create([

                                    'description' => $this->description,
                                    'amount' => $this->amount,
                                    'previus_balance' => $account->amount,
                                    'actual_balance' => $account->amount + $this->amount
                                ]);
                                
                                $account->update([
                    
                                'amount' => $account->amount + $this->amount
                    
                                ]);

                                $cov->update([
                            
                                    'balance' => $cov->balance + $this->amount
                    
                                ]);

                                $cov_det->update([

                                    'ingress' => $cov_det->ingress + $this->amount,
                                    'actual_balance' => $cov_det->actual_balance + $this->amount
                    
                                ]);

                                $paydesk->update([

                                    'relation' => $detail->id
                    
                                ]);

                            }else{

                                $detail = $account->details()->create([

                                    'description' => $this->description,
                                    'amount' => $this->amount,
                                    'previus_balance' => $account->amount,
                                    'actual_balance' => $account->amount - $this->amount
                                ]);
                                
                                $account->update([
                    
                                'amount' => $account->amount - $this->amount
                    
                                ]);

                                $cov->update([
                            
                                    'balance' => $cov->balance - $this->amount
                    
                                ]);

                                $cov_det->update([

                                    'egress' => $cov_det->egress + $this->amount,
                                    'actual_balance' => $cov_det->actual_balance - $this->amount
                    
                                ]);

                                $paydesk->update([

                                    'relation' => $detail->id
                    
                                ]);
                            }

                        }

                    }

                    DB::commit();
                    $this->emit('item-added', 'Registro Exitoso');
                    $this->resetUI();
                    $this->mount();
                    $this->render();

                } catch (Exception) {
                    
                    DB::rollback();
                    $this->emit('movement-error', 'Algo salio mal');
                }

        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }

    }

    public function Edit(Paydesk $paydesk){
        
        $this->selected_id = $paydesk->id;
        $this->description = $paydesk->description;
        $this->action = $paydesk->action;
        $this->type = $paydesk->type;
        $this->amount = $paydesk->amount;
        
        $this->emit('show-modal2', 'Abrir Modal');

    }

    public function Update(){
        
        $paydesk = Paydesk::find($this->selected_id);

        $rules = [

            'description' => 'required|min:20',
            'action' => 'required|not_in:Elegir',
            'amount' => 'required',
        ];

        $messages = [

            'action.required' => 'La accion es requerida',
            'action.not_in' => 'Seleccione una opcion',
            'description.required' => 'La descripcion es requerida',
            'description.min' => 'La descripcion debe contener al menos 20 caracteres',
            'amount.required' => 'El monto es requerido',
        ];

        $this->validate($rules, $messages);
            
        $paydesk->Update([

            'description' => $this->description,
            'type' => $this->type,
            'action' => $this->action,
            'amount' => $this->amount
        ]);

        $this->resetUI();
        $this->emit('item-updated', 'Registro Actualizado');
    }

    public function Utility(){

        if($this->gen_det != null){

            DB::beginTransaction();
            
                try {

                    $ud = Cover::firstWhere('description','utilidad');
                    $ud_det = $ud->details->where('cover_id',$ud->id)->whereBetween('created_at',[$this->from, $this->to])->first();

                    $rules = [

                        'amount' => 'required|numeric'
                    ];

                    $messages = [

                        'amount.required' => 'El monto es requerido',
                        'amount.numeric' => 'Este campo solo admite numeros'
                    ];
                    
                    $this->validate($rules, $messages);

                    $ud_det->update([

                        'actual_balance' => $ud_det->actual_balance + $this->amount

                    ]);

                    DB::commit();
                    $this->emit('item-updated', 'Registro Exitoso');
                    $this->resetUI();
                    $this->mount();
                    $this->render();

                } catch (Exception) {
                    
                    DB::rollback();
                    $this->emit('movement-error', 'Algo salio mal');
                }

        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }
        
    }

    public function Collect(){

        if($this->gen_det != null){

            $data = Paydesk::orderBy('id', 'asc')->whereBetween('created_at', [$this->from, $this->to])->where('type','Ventas')->get();

            if(count($data) == 0){

                $sales = Sale::with('product')->whereBetween('created_at',[$this->from,$this->to])->where('state_id',8)->get();

                if(count($sales) > 0){

                    DB::beginTransaction();
            
                        try {

                            $total_cost = 0;

                            foreach($sales as $sale){

                                $total_cost += $sale->quantity * $sale->product->cost;
                            }
                            
                            $ud = Cover::firstWhere('description','utilidad bruta del dia');
                            $ud_det = $ud->details->where('cover_id',$ud->id)->whereBetween('created_at',[$this->from, $this->to])->first();
                            $sa = Cover::firstWhere('description','inventario');
                            $sa_det = $sa->details->where('cover_id',$sa->id)->whereBetween('created_at',[$this->from, $this->to])->first();
                            
                            Paydesk::create([
                
                                'action' => 'ingreso',
                                'description' => 'Ventas del dia',
                                'type' => 'Ventas',
                                'relation' => 0,
                                'amount' => $sales->sum('total')
                            ]);
                            
                            $this->gen->update([
                
                                'balance' => $this->gen->balance + $sales->sum('total')
                
                            ]);
                
                            $this->gen_det->update([
                
                                'ingress' => $this->gen_det->ingress + $sales->sum('total'),
                                'actual_balance' => $this->gen_det->actual_balance + $sales->sum('total')
                
                            ]);
                
                            $ud_det->update([
                
                                'actual_balance' => $ud_det->actual_balance + $sales->sum('utility')
                
                            ]);
                
                            $sa->update([
                
                                'balance' => $sa->balance - $total_cost
                
                            ]);
                
                            $sa_det->update([
                
                                'egress' => $sa_det->egress + $total_cost,
                                'actual_balance' => $sa_det->actual_balance - $total_cost
                
                            ]);
                            
                            DB::commit();
                            $this->emit('item-added', 'Registro Exitoso');
                            $this->resetUI();
                            $this->mount();
                            $this->render();

                        } catch (Exception) {
                    
                            DB::rollback();
                            $this->emit('movement-error', 'Algo salio mal');
                        }

                }else{

                    $this->emit('paydesk-error', 'No se han registrado ventas aun');
                    return;
                }

            }else{

                $this->emit('paydesk-error', 'Ya se obtuvieron las ventas del dia');
                return;
            }
        
        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }
    }

    protected $listeners = [

        'destroy' => 'Destroy',
        'collect' => 'Collect'
    ];

    public function Destroy(Paydesk $paydesk){

        if($this->gen_det != null){

            DB::beginTransaction();
            
                try {

                    if(Cover::firstWhere('description',$paydesk->type) != null){
                
                        $cov = Cover::firstWhere('description',$paydesk->type);
                        $cov_det = $cov->details->where('cover_id',$cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();

                        //dd($cov_det->actual_balance - $paydesk->amount);
                        
                        if($paydesk->action == 'ingreso'){

                            switch($paydesk->type){

                                case 'gimnasio':
                                    
                                    $debt = Gym::find($paydesk->relation);

                                    $cov->update([
                        
                                        'balance' => $cov->balance - $debt->amount
                        
                                    ]);
                
                                    $cov_det->update([
                
                                        'ingress' => $cov_det->ingress - $debt->amount,
                                        'actual_balance' => $cov_det->actual_balance - $debt->amount
                        
                                    ]);

                                    $debt->delete();

                                    $this->resetUI();
                
                                break;

                                case 'clientes por cobrar':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = CostumerReceivable::find($det->detailable_id);

                                        $debt->update([
                                
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                                
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'otros por pagar':

                                    $debt = Payable::find($paydesk->relation);

                                    if($debt != null){

                                        if(count($debt->details) < 1){

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $debt->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $debt->amount,
                                                'actual_balance' => $cov_det->actual_balance - $debt->amount
                                
                                            ]);

                                            $debt->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'La deuda inicial ha sufrido cambios. Elimine esos movimientos primero.');
                                            return;
                                        }

                                    }else{

                                        $det = Detail::find($paydesk->relation);

                                        if($det != null){

                                            $payable = Payable::find($det->detailable_id);

                                            $payable->update([

                                                'amount' => $payable->amount - $det->amount
                                            ]);

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $det->amount                       
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $det->amount,
                                                'actual_balance' => $cov_det->actual_balance - $det->amount
                                            ]);

                                            $det->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                            return;
                                        }
                                    }
                                    
                                    $this->resetUI();

                                break;

                                case 'otros por cobrar':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = OtherReceivable::find($det->detailable_id);

                                        $debt->update([
                                
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                                
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'cheques por cobrar':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = CheckReceivable::find($det->detailable_id);

                                        $debt->update([
                                
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                                
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'gastos de importacion':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = Import::find($det->detailable_id);

                                        $debt->update([
                                
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                                
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'otros proveedores':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = OtherProvider::find($det->detailable_id);

                                        if(($debt->amount - $det->amount) >= 0){

                                            $debt->update([
                                
                                                'amount' => $debt->amount - $det->amount
                                
                                            ]);
                
                                            $cov->update([
                                    
                                                'balance' => $cov->balance - $det->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $det->amount,
                                                'actual_balance' => $cov_det->actual_balance - $det->amount
                                
                                            ]);
                    
                                            $det->delete();
                
                                            /*if(count($debt->details) < 1){
                
                                                $debt->delete();
                                            }*/

                                        }else{

                                            $this->emit('paydesk-error', 'El saldo quedara negativo. Debe eliminar movimientos anteriores primero');
                                            return;
                                        }

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'anticreticos':

                                    $debt = Anticretic::find($paydesk->relation);

                                    if($debt != null){

                                        if(count($debt->details) < 1){

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $debt->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $debt->amount,
                                                'actual_balance' => $cov_det->actual_balance - $debt->amount
                                
                                            ]);

                                            $debt->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'La deuda inicial ha sufrido cambios. Elimine esos movimientos primero.');
                                            return;
                                        }

                                    }else{

                                        $det = Detail::find($paydesk->relation);

                                        if($det != null){

                                            $payable = Anticretic::find($det->detailable_id);

                                            $payable->update([

                                                'amount' => $payable->amount - $det->amount
                                            ]);

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $det->amount                       
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $det->amount,
                                                'actual_balance' => $cov_det->actual_balance - $det->amount
                                            ]);

                                            $det->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                            return;
                                        }
                                    }
                                    
                                    $this->resetUI();

                                break;

                                case 'facturas/impuestos':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = Bill::find($det->detailable_id);

                                        if(($debt->amount - $det->amount) >= 0){

                                            $debt->update([
                                
                                                'amount' => $debt->amount - $det->amount
                                
                                            ]);
                
                                            $cov->update([
                                    
                                                'balance' => $cov->balance - $det->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $det->amount,
                                                'actual_balance' => $cov_det->actual_balance - $det->amount
                                
                                            ]);
                    
                                            $det->delete();
                
                                            /*if(count($debt->details) < 1){
                
                                                $debt->delete();
                                            }*/

                                        }else{

                                            $this->emit('paydesk-error', 'El saldo quedara negativo. Debe eliminar movimientos anteriores primero.');
                                            return;
                                        }

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'diferencia por t/c':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance - $paydesk->amount
                        
                                    ]);

                                break;

                                case 'utilidad':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance - $paydesk->amount
                        
                                    ]);

                                break;

                                case 'cambio de llantas':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance - $paydesk->amount
                        
                                    ]);

                                break;

                            }

                            $this->gen->update([
                        
                                'balance' => $this->gen->balance - $paydesk->amount
                    
                            ]);
                    
                            $this->gen_det->update([
                    
                                'ingress' => $this->gen_det->ingress - $paydesk->amount,
                                'actual_balance' => $this->gen_det->actual_balance - $paydesk->amount
                    
                            ]);
                        
                        }else{

                            switch($paydesk->type){

                                case 'gimnasio':
                                    
                                    $debt = Gym::find($paydesk->relation);

                                    $cov->update([
                        
                                        'balance' => $cov->balance - $debt->amount
                        
                                    ]);
                
                                    $cov_det->update([
                
                                        'egress' => $cov_det->egress + $debt->amount,
                                        'actual_balance' => $cov_det->actual_balance - $debt->amount
                        
                                    ]);

                                    $debt->delete();

                                    $this->resetUI();
                
                                break;

                                case 'clientes por cobrar':
                                    
                                    $debt = CostumerReceivable::find($paydesk->relation);

                                    if($debt != null){

                                        if(count($debt->details) < 1){

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $debt->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $debt->amount,
                                                'actual_balance' => $cov_det->actual_balance - $debt->amount
                                
                                            ]);
                    
                                            $debt->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'La deuda inicial ha sufrido cambios. Elimine esos movimientos primero.');
                                            return;
                                        }

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'otros por pagar':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $payable = Payable::find($det->detailable_id);

                                        $payable->update([

                                            'amount' => $payable->amount + $det->amount
                                        ]);

                                        $cov->update([
                                
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);

                                        $det->delete();
                                        
                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }
                                    
                                    $this->resetUI();

                                break;

                                case 'otros por cobrar':
                                    
                                    $debt = OtherReceivable::find($paydesk->relation);

                                    if($debt != null){

                                        if(count($debt->details) < 1){

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $debt->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $debt->amount,
                                                'actual_balance' => $cov_det->actual_balance - $debt->amount
                                
                                            ]);
                    
                                            $debt->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'La deuda inicial ha sufrido cambios. Elimine esos movimientos primero.');
                                            return;
                                        }

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'cheques por cobrar':
                                    
                                    $debt = CheckReceivable::find($paydesk->relation);

                                    if($debt != null){

                                        if(count($debt->details) < 1){

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $debt->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $debt->amount,
                                                'actual_balance' => $cov_det->actual_balance - $debt->amount
                                
                                            ]);
                    
                                            $debt->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'La deuda inicial ha sufrido cambios. Elimine esos movimientos primero.');
                                            return;
                                        }

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'gastos de importacion':
                                    
                                    $debt = Import::find($paydesk->relation);

                                    if($debt != null){

                                        if(count($debt->details) < 1){

                                            $cov->update([
                                
                                                'balance' => $cov->balance - $debt->amount
                                
                                            ]);
                        
                                            $cov_det->update([
                        
                                                'ingress' => $cov_det->ingress - $debt->amount,
                                                'actual_balance' => $cov_det->actual_balance - $debt->amount
                                
                                            ]);
                    
                                            $debt->delete();

                                        }else{

                                            $this->emit('paydesk-error', 'La deuda inicial ha sufrido cambios. Elimine esos movimientos primero.');
                                            return;
                                        }

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'anticreticos':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = Anticretic::find($det->detailable_id);

                                        $debt->update([
                            
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                            
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'otros proveedores':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = OtherProvider::find($det->detailable_id);

                                        $debt->update([
                            
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                            
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'proveedores por pagar':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = ProviderPayable::find($det->detailable_id);

                                        $debt->update([
                            
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                            
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'consignaciones':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = Appropriation::find($det->detailable_id);

                                        $debt->update([
                            
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                            
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'facturas/impuestos':
                                    
                                    $det = Detail::find($paydesk->relation);

                                    if($det != null){

                                        $debt = Bill::find($det->detailable_id);

                                        $debt->update([
                            
                                            'amount' => $debt->amount + $det->amount
                            
                                        ]);

                                        $cov->update([
                            
                                            'balance' => $cov->balance + $det->amount
                            
                                        ]);
                    
                                        $cov_det->update([
                    
                                            'egress' => $cov_det->egress - $det->amount,
                                            'actual_balance' => $cov_det->actual_balance + $det->amount
                            
                                        ]);
                
                                        $det->delete();

                                    }else{

                                        $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                        return;
                                    }

                                    $this->resetUI();
                
                                break;

                                case 'diferencia por t/c':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance + $paydesk->amount
                        
                                    ]);

                                break;

                                case 'comisiones':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance - $paydesk->amount
                        
                                    ]);

                                break;

                                case 'perdida por devolucion':

                                    $cov_det->update([
                    
                                        'actual_balance' => 0
                        
                                    ]);

                                break;

                                case 'gastos gorky':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance - $paydesk->amount
                        
                                    ]);

                                break;

                                case 'gastos importadora':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance - $paydesk->amount
                        
                                    ]);

                                break;

                                case 'gastos construccion':

                                    $cov_det->update([
                    
                                        'actual_balance' => $cov_det->actual_balance - $paydesk->amount
                        
                                    ]);

                                break;

                            }

                            $this->gen->update([
                        
                                'balance' => $this->gen->balance + $paydesk->amount
                
                            ]);
                
                            $this->gen_det->update([
                
                                'egress' => $this->gen_det->egress - $paydesk->amount,
                                'actual_balance' => $this->gen_det->actual_balance + $paydesk->amount
                
                            ]);
                        }
                    
                    }else{

                        if($paydesk->type == 'deposito/retiro'){

                            $detail = Detail::find($paydesk->relation);
                            $account = BankAccount::find($detail->detailable_id);
                            $bank = Bank::find($account->bank_id)->description;
                            $company = Company::find($account->company_id)->description;

                            $cov = Cover::firstWhere('description',$bank . ' ' . $account->type . ' ' . $account->currency . ' ' . $company);
                            $cov_det = $cov->details->where('cover_id',$cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();

                            if($paydesk->action == 'ingreso'){

                                $account->update([
                        
                                    'amount' => $account->amount + $detail->amount
                    
                                ]);

                                $cov->update([
                        
                                    'balance' => $cov->balance + $detail->amount
                    
                                ]);

                                $cov_det->update([

                                    'egress' => $cov_det->egress - $detail->amount,
                                    'actual_balance' => $cov_det->actual_balance + $detail->amount
                    
                                ]);

                                $this->gen->update([
                            
                                    'balance' => $this->gen->balance - $detail->amount
                    
                                ]);
                    
                                $this->gen_det->update([
                    
                                    'ingress' => $this->gen_det->ingress - $detail->amount,
                                    'actual_balance' => $this->gen_det->actual_balance - $detail->amount
                    
                                ]);

                                $detail->delete();

                                $this->resetUI();

                            }else{

                                $account->update([
                        
                                    'amount' => $account->amount - $detail->amount
                    
                                ]);

                                $cov->update([
                        
                                    'balance' => $cov->balance - $detail->amount
                    
                                ]);

                                $cov_det->update([

                                    'ingress' => $cov_det->ingress - $detail->amount,
                                    'actual_balance' => $cov_det->actual_balance - $detail->amount
                    
                                ]);

                                $this->gen->update([
                            
                                    'balance' => $this->gen->balance + $detail->amount
                    
                                ]);
                    
                                $this->gen_det->update([
                    
                                    'egress' => $this->gen_det->egress - $detail->amount,
                                    'actual_balance' => $this->gen_det->actual_balance + $detail->amount
                    
                                ]);

                                $detail->delete();

                                $this->resetUI();
                            }

                        }else{

                            if($paydesk->type == 'Ventas'){

                                $sales = Sale::with('product')->whereBetween('created_at',[$this->from,$this->to])->where('state_id',8)->get();
                
                                $ud = Cover::firstWhere('description','utilidad bruta del dia');
                                $ud_det = $ud->details->where('cover_id',$ud->id)->whereBetween('created_at',[$this->from, $this->to])->first();
                                $sa = Cover::firstWhere('description','inventario');
                                $sa_det = $sa->details->where('cover_id',$sa->id)->whereBetween('created_at',[$this->from, $this->to])->first();
                
                                $total_cost = 0;
                
                                foreach($sales as $sale){
                
                                    $total_cost += $sale->quantity * $sale->product->cost;
                                }

                                $this->gen->update([
                        
                                    'balance' => $this->gen->balance - $sales->sum('total')
                    
                                ]);
                    
                                $this->gen_det->update([
                    
                                    'ingress' => $this->gen_det->ingress - $sales->sum('total'),
                                    'actual_balance' => $this->gen_det->actual_balance - $sales->sum('total')
                    
                                ]);
                    
                                $ud_det->update([
                    
                                    'actual_balance' => $ud_det->actual_balance - $sales->sum('utility')
                    
                                ]);
                    
                                $sa->update([
                    
                                    'balance' => $sa->balance + $total_cost
                    
                                ]);
                    
                                $sa_det->update([
                    
                                    'egress' => $sa_det->egress - $total_cost,
                                    'actual_balance' => $sa_det->actual_balance + $total_cost
                    
                                ]);
                
                            }else{
                
                                $this->emit('paydesk-error', 'Error desconocido al eliminar');
                                return;
                            }
                        }

                    }
                    
                    $paydesk->delete();
                    DB::commit();
                    $this->emit('item-deleted', 'Registro Eliminado');
                    $this->resetUI();
                    $this->mount();
                    $this->render();

                } catch (Exception) {
                    
                    DB::rollback();
                    $this->emit('movement-error', 'Algo salio mal');
                }

        }else{

            $this->emit('cover-error','Se debe crear caratula del dia');
            return;
        }

    }

    public function resetUI(){

        $this->description = '';
        $this->action = 'Elegir';
        $this->amount = '';
        $this->search = '';
        $this->selected_id = 0;
        $this->type = 'Elegir';
        $this->temp = 'Elegir';
        $this->temp1 = 'Elegir';
        $this->temp2 = 'Elegir';
        $this->temp3 = '';
        $this->temp4 = '';
        $this->details = [];
        $this->balance = 0;
        $this->reportRange = 0;
        $this->dr1 = 'Elegir';
        $this->dr2 = 'Elegir';
        $this->dr3 = 'Elegir';
        $this->chc1 = 'Elegir';
        $this->chc2 = 'Elegir';
        $this->chc3 = '';
        $this->resetValidation();
        //$this->resetPage();
    }
}
