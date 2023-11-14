<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Company;
use App\Models\Bank;
use App\Models\BankAccount;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Models\Cover;
use App\Models\CoverDetail;
use Exception;
use Illuminate\Support\Facades\DB;

class BankAccounts extends Component
{
    use WithPagination;

    public $bank_id,$company_id,$amount,$amount_2,$action,$type,$currency,$description,$search,$pageTitle,$componentName,$selected_id;
    public $from,$to,$ref,$ref_det,$cov,$cov_det;
    private $pagination = 30;

    public function mount(){

        $this->pageTitle = 'Listado';
        $this->componentName = 'Cuentas de Banco';
        $this->bank_id = 'Elegir';
        $this->company_id = 'Elegir';
        $this->type = 'Elegir';
        $this->currency = 'Elegir';
        $this->from = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 00:00:00';
        $this->to = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 23:59:59';
        $this->ref = Cover::firstWhere('description','capital de trabajo inicial');
        $this->ref_det = $this->ref->details->where('cover_id',$this->ref->id)->whereBetween('created_at',[$this->from, $this->to])->first();
        $this->cov = [];
        $this->cov_det = [];
    }

    public function paginationView(){

        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {   

        if(strlen($this->search) > 0)

            $data = Company::join('bank_accounts as b_a','b_a.company_id','companies.id')
            ->join('banks as b','b.id','b_a.bank_id')
            ->select('b_a.*','b.description as bank','companies.description as company')
            ->where('companies.description', 'like', '%' . $this->search . '%')
            ->orWhere('b.description', 'like', '%' . $this->search . '%')
            ->orWhere('b_a.type', 'like', '%' . $this->search . '%')
            ->orWhere('b_a.currency', 'like', '%' . $this->search . '%')
            ->orderBy('b_a.id','asc')
            ->paginate($this->pagination);

        else

            $data = Company::join('bank_accounts as b_a','b_a.company_id','companies.id')
            ->join('banks as b','b.id','b_a.bank_id')
            ->select('b_a.*','b.description as bank','companies.description as company')
            ->orderBy('b_a.id','asc')
            ->paginate($this->pagination);
        
        
        return view('livewire.bank_account.bank-accounts', [
            'accounts' => $data,
            'banks' => Bank::orderBy('id','asc')->get(),
            'companies' => Company::orderBy('id','asc')->get(),
            //'banks' => Bank::with('account_type')->get()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store(){

        if($this->ref_det != null){

            $rules = [
                
                'company_id' => 'not_in:Elegir',
                'bank_id' => 'not_in:Elegir',
                'type' => 'not_in:Elegir',
                'currency' => 'not_in:Elegir',
                'amount' => 'required|numeric'
            ];

            $messages = [

                'company_id.not_in' => 'Elija un propietario para la cuenta',
                'bank_id.not_in' => 'Elija un banco para la cuenta',
                'type.not_in' => 'Elija un tipo de cuenta',
                'currency.not_in' => 'Elija una moneda para la cuenta',
                'amount.required' => 'El saldo de la cuenta es requerido',
                'amount.numeric' => 'Este campo solo admite numeros'
            ];

            $this->validate($rules, $messages);

            DB::beginTransaction();
            
                try {
                
                    $account = BankAccount::create([

                        'type' => $this->type,
                        'currency' => $this->currency,
                        'amount' => $this->amount,
                        'company_id' => $this->company_id,
                        'bank_id' => $this->bank_id
                    ]);

                    if($account){

                        $bank = Bank::firstWhere('id',$account->bank_id)->description;
                        $company = Company::firstWhere('id',$account->company_id)->description;

                        $cover = Cover::create([
                
                            'description' => $bank . ' ' . $account->type . ' ' . $account->currency . ' ' . $company,
                            'type' => 'depositos',
                            'balance' => $account->amount

                        ]);

                        if($cover){

                            CoverDetail::create([
                
                                'cover_id' => $cover->id,
                                'type' => $cover->type,
                                'previus_day_balance' => $cover->balance,
                                'ingress' => 0,
                                'egress' => 0,
                                'actual_balance' => $cover->balance
                            ]);
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

    public function Edit(BankAccount $account){
        
        $this->selected_id = $account->id;
        $this->company_id = $account->company_id;
        $this->bank_id = $account->bank_id;
        $this->type = $account->type;
        $this->currency = $account->currency;
        $this->amount = $account->amount;
        $this->action = 'Elegir';

        $this->emit('show-modal2', 'Abrir Modal');

    }

    public function Update(){

        if($this->ref_det != null){
        
            $account = BankAccount::find($this->selected_id);

            $rules = [
                
                'company_id' => 'not_in:Elegir',
                'bank_id' => 'not_in:Elegir',
                'type' => 'not_in:Elegir',
                'currency' => 'not_in:Elegir',
                'amount' => 'required|numeric',
                'description' => 'exclude_if:action,Elegir|required|min:10|max:255',
                'amount_2' => 'exclude_if:action,Elegir|required|numeric',
                'action' => 'not_in:Elegir',
            ];

            $messages = [

                'company_id.not_in' => 'Elija un propietario para la cuenta',
                'bank_id.not_in' => 'Elija un banco para la cuenta',
                'type.not_in' => 'Elija un tipo de cuenta',
                'currency.not_in' => 'Elija una moneda para la cuenta',
                'amount.required' => 'El saldo de la cuenta es requerido',
                'amount.numeric' => 'Este campo solo admite numeros',
                'description.required' => 'Los detalles son requeridos',
                'description.min' => 'Los detalles deben contener al menos 10 caracteres',
                'description.max' => 'La descripcion debe contener 255 caracteres como maximo',
                'amount_2.required' => 'El monto es requerido',
                'amount_2.numeric' => 'Este campo solo admite numeros',
                'action.not_in' => 'Elija una opcion',
            ];
            
            $this->validate($rules, $messages);

            DB::beginTransaction();
            
                try {

                    switch($this->action){

                        case 'Elegir': $account->update([

                            'type' => $this->type,
                            'currency' => $this->currency,
                            'amount' => $this->amount,
                            'company_id' => $this->company_id,
                            'bank_id' => $this->bank_id

                        ]);
                        
                        break;

                        case 'Ingreso': $detail = $account->details()->create([

                            'description' => $this->description,
                            'amount' => $this->amount_2,
                            'previus_balance' => $account->amount,
                            'actual_balance' => $account->amount + $this->amount_2
                        ]);
                        
                        if($detail){

                            $account->update([

                                'amount' => $account->amount + $this->amount_2
                
                            ]);
                
                            $bank = Bank::firstWhere('id',$account->bank_id)->description;
                            $company = Company::firstWhere('id',$account->company_id)->description;
                
                            $this->cov = Cover::firstWhere('description',$bank . ' ' . $account->type . ' ' . $account->currency . ' ' . $company);
                            $this->cov_det = $this->cov->details->where('cover_id',$this->cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();
                
                            $this->cov->update([
                                
                                'balance' => $this->cov->balance + $this->amount_2
                    
                            ]);
                    
                            $this->cov_det->update([
                
                                'ingress' => $this->cov_det->ingress + $this->amount_2,
                                'actual_balance' => $this->cov_det->actual_balance + $this->amount_2
                
                            ]);
                        }

                        break;

                        case 'Egreso': $detail = $account->details()->create([

                            'description' => $this->description,
                            'amount' => $this->amount_2,
                            'previus_balance' => $account->amount,
                            'actual_balance' => $account->amount - $this->amount_2
                        ]);
                        
                        if($detail){

                            $account->update([

                                'amount' => $account->amount - $this->amount_2
                
                            ]);
                
                            $bank = Bank::firstWhere('id',$account->bank_id)->description;
                            $company = Company::firstWhere('id',$account->company_id)->description;
                
                            $this->cov = Cover::firstWhere('description',$bank . ' ' . $account->type . ' ' . $account->currency . ' ' . $company);
                            $this->cov_det = $this->cov->details->where('cover_id',$this->cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();
                
                            $this->cov->update([
                                
                                'balance' => $this->cov->balance - $this->amount_2
                    
                            ]);
                    
                            $this->cov_det->update([
                
                                'egress' => $this->cov_det->egress + $this->amount_2,
                                'actual_balance' => $this->cov_det->actual_balance - $this->amount_2
                
                            ]);
                        }

                        break;
                    }

                    DB::commit();
                    $this->emit('item-updated', 'Registro actualizado');
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

    protected $listeners = [

        'destroy' => 'Destroy'
    ];

    public function Destroy(BankAccount $account){

        if($this->ref_det != null){

            DB::beginTransaction();
            
                try {
        
                    $bank = Bank::firstWhere('id',$account->bank_id)->description;
                    $company = Company::firstWhere('id',$account->company_id)->description;
                    $this->cov = Cover::firstWhere('description',$bank . ' ' . $account->type . ' ' . $account->currency . ' ' . $company);

                    if($this->cov->details->where('cover_id',$this->cov->id)->whereBetween('created_at',[$this->from, $this->to])->first() != null){

                        $this->cov_det = $this->cov->details->where('cover_id',$this->cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();
                        $this->cov_det->delete();
                    }
                    
                    $this->cov->delete();
                    $account->delete();
                    DB::commit();
                    $this->emit('item-deleted', 'Registro eliminado');
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

        $this->company_id = 'Elegir';
        $this->bank_id = 'Elegir';
        $this->type = 'Elegir';
        $this->currency = 'Elegir';
        $this->amount = '';
        $this->amount_2 = '';
        $this->description = '';
        $this->action = 'Elegir';
        $this->search = '';
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
