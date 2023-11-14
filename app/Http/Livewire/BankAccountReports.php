<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\BankAccount;
use App\Models\Detail;
use App\Models\Cover;
use App\Models\Bank;
use App\Models\Company;
use Carbon\Carbon;

class BankAccountReports extends Component
{   

    public $componentName,$details,$reportRange,$company_id,$dateFrom,$dateTo;

    public function mount(){

        $this->componentName = 'MOVIMIENTOS BANCARIOS';
        $this->details = [];
        $this->reportRange = 0;
        $this->company_id = 0;
        $this->from = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 00:00:00';
        $this->to = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 23:59:59';
        //$this->cov = Cover::firstWhere('description',$this->componentName);
        //$this->cov_det = $this->cov->details->where('cover_id',$this->cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();
    }

    public function render()
    {   
        $this->ReportsByDate();

        return view('livewire.bank_account_report.bank-account-reports', [

            'accounts' => BankAccount::with('company','bank')->get()
        ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function ReportsByDate(){

        if($this->reportRange == 0){

            $from = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 00:00:00';
            $to = Carbon::parse(Carbon::now())->format('Y-m-d') . ' 23:59:59';

        }else{

            $from = Carbon::parse($this->dateFrom)->format('Y-m-d') . ' 00:00:00';
            $to = Carbon::parse($this->dateTo)->format('Y-m-d') . ' 23:59:59';

        }

        if($this->reportRange == 1 && ($this->dateFrom == '' || $this->dateTo == '')){

            $this->emit('report-error', 'Seleccione fecha de inicio y fecha de fin');
            return;
        }

        if($this->company_id == 0){

            $this->details = BankAccount::join('details as d','d.detailable_id','bank_accounts.id')
            ->select('d.*')
            ->whereBetween('d.created_at', [$from, $to])
            ->where('d.detailable_type','App\Models\BankAccount')
            ->orderBy('d.detailable_id', 'asc')->get();

        }else{
            
            $this->details = BankAccount::join('details as d','d.detailable_id','bank_accounts.id')
            ->select('d.*')
            ->whereBetween('d.created_at', [$from, $to])
            ->where('d.detailable_id',$this->company_id)
            ->where('d.detailable_type','App\Models\BankAccount')
            ->orderBy('d.detailable_id', 'asc')->get();
        }

    }

    protected $listeners = [

        'destroy' => 'Destroy'
    ];

    public function Destroy(Detail $det){

        $account = BankAccount::firstWhere('id',$det->detailable_id);
        $company = Company::firstWhere('id',$account->company_id)->description;
        $bank = Bank::firstWhere('id',$account->bank_id)->description;
        $cov = Cover::firstWhere('description',$bank . ' ' . $account->type . ' ' . $account->currency . ' ' . $company);
        $cov_det = $cov->details->where('cover_id',$cov->id)->whereBetween('created_at',[$this->from, $this->to])->first();

        if($det->actual_balance > $det->previus_balance){

            if(($det->actual_balance - $det->amount) == ($account->amount - $det->amount)){

                $account->update([
                
                    'amount' => $account->amount - $det->amount

                ]);

                $cov->update([

                    'balance' => $cov->balance - $det->amount

                ]);

                $cov_det->update([

                    'ingress' => $cov_det->ingress - $det->amount,
                    'actual_balance' => $cov_det->actual_balance - $det->amount

                ]);

                $det->delete();
                $this->emit('report-error', 'Movimiento Anulado.');

            }else{

                $this->emit('report-error', 'El saldo no coincide. Anule los movimientos mas recientes.');
                return;
            }

        }else{

            if(($det->actual_balance + $det->amount) == ($account->amount + $det->amount)){
                
                $account->update([
            
                    'amount' => $account->amount + $det->amount
    
                ]);
    
                $cov->update([
        
                    'balance' => $cov->balance + $det->amount
    
                ]);
    
                $cov_det->update([
    
                    'egress' => $cov_det->egress - $det->amount,
                    'actual_balance' => $cov_det->actual_balance + $det->amount
    
                ]);

                $det->delete();
                $this->emit('report-error', 'Movimiento Anulado.');

            }else{

                $this->emit('report-error', 'El saldo no coincide. Anule los movimientos mas recientes.');
                return;
            }
        }

        $this->mount();
        $this->render();
    }

}
