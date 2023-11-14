<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Cover;
use App\Models\CoverDetail;
use App\Models\Detail;
use App\Models\Income;
use App\Models\Paydesk;
use App\Models\Sale;
use App\Models\Transfer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class CoverReports extends Component
{   

    public $componentName,$details,$reportRange,$date,$amount;
    public $sum1,$sum2,$sum3,$sum4,$sum5,$sum6,$sum7,$sum8,$sum9,$sum10;
    public $uti,$uti_det,$date1,$date2,$date3;

    public function mount(){

        $this->componentName = 'CARATULA';
        $this->reportRange = 0;
        $this->date = '';
        $this->date3 = '';
        $this->amount = '';
        $this->date1 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 00:00:00';
        $this->date2 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 23:59:59';
        $this->uti = Cover::firstWhere('description','utilidad acumulada');
        $this->uti_det = $this->uti->details->where('cover_id',$this->uti->id)->whereBetween('created_at',[$this->date1, $this->date2])->first();
    }

    public function render()
    {   
        $this->ReportsByDate();

        return view('livewire.cover_report.cover-reports')
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function ReportsByDate(){

        $this->sum1 = 0;
        $this->sum2 = 0;
        $this->sum3 = 0;
        $this->sum4 = 0;
        $this->sum5 = 0;
        $this->sum6 = 0;
        $this->sum7 = 0;
        $this->sum8 = 0;
        $this->sum9 = 0;
        $this->sum10 = 0;

        if($this->reportRange == 0){

            $fecha1 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 00:00:00';
            $fecha2 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 23:59:59';

        }else{

            $fecha1 = Carbon::parse($this->date)->format('Y-m-d'). ' 00:00:00';
            $fecha2 = Carbon::parse($this->date)->format('Y-m-d'). ' 23:59:59';

        }

        if($this->reportRange == 1 && $this->date == ''){

            $this->emit('report-error', 'Seleccione una fecha');
            return;
        }

        if($this->reportRange == 2 && ($this->date == '' || $this->date3 == '')){

            $this->emit('report-error', 'Seleccione ambas fechas');
            return;
        }

        $this->details = CoverDetail::whereBetween('created_at', [$fecha1, $fecha2])->orderBy('id', 'asc')->get();
        

        foreach($this->details as $detail){

            if($detail->type == 'efectivo' || $detail->type == 'depositos'){

                $this->sum1 += $detail->actual_balance;
            }

            if($detail->type == 'mercaderia' || $detail->type == 'creditos'){

                $this->sum2 += $detail->actual_balance;
            }

            if($detail->type == 'por_pagar'){

                $this->sum4 += $detail->actual_balance;
            }

            if($detail->type == 'utilidad_diaria'){

                $this->sum6 += $detail->actual_balance;
            }

            if($detail->type == 'gasto_diario'){

                $this->sum7 += $detail->actual_balance;
            }

            if($detail->cover->description == 'capital de trabajo inicial'){

                $this->sum9 = $detail->actual_balance;
            }
        }

        $this->sum3 = $this->sum1 + $this->sum2;
        $this->sum5 = $this->sum3 - $this->sum4;
        $this->sum8 = $this->sum6 - $this->sum7;
        $this->sum10 = $this->sum5 - $this->sum9;
        
    }

    public function Create(){

        if(count($this->details) < 1){
            
            $covers = Cover::all();
            
            foreach($covers as $cover){

                CoverDetail::create([
    
                    'cover_id' => $cover->id,
                    'type' => $cover->type,
                    'previus_day_balance' => $cover->balance,
                    'ingress' => 0,
                    'egress' => 0,
                    'actual_balance' => $cover->balance
    
                ]);
            }

        }else{

            $this->emit('cover-error', 'Ya se creo caratula para hoy');
            return;
        }

        $this->emit('item-added','Registro Exitoso');
        $this->mount();
        $this->render();

    }

    /*public function Delete(){


    }*/

    public function Collect(){

        if($this->reportRange == 0){

            if($this->uti_det->ingress == 0 && $this->uti_det->egress == 0){

                if($this->sum8 > 0){

                    $this->uti->update([
                    
                        'balance' => $this->uti->balance + $this->sum8
            
                    ]);
            
                    $this->uti_det->update([
            
                        'ingress' => $this->uti_det->ingress + $this->sum8,
                        'actual_balance' => $this->uti_det->actual_balance + $this->sum8
            
                    ]);
    
                }else{
    
                    $this->uti->update([
                
                        'balance' => $this->uti->balance + $this->sum8
            
                    ]);
            
                    $this->uti_det->update([
            
                        'egress' => $this->uti_det->egress - $this->sum8,
                        'actual_balance' => $this->uti_det->actual_balance + $this->sum8
            
                    ]);
    
                }
    
                $this->emit('item-added','Registro Exitoso');
                $this->mount();
                $this->render();
                
            }else{

                $this->emit('cover-error','Ya se ingreso la utilidad acumulada del dia');
                return;
            }

        }else{

            $this->emit('cover-error','No se puede alterar fechas pasadas');
            return;
        }

    }

    public function Revert(){

        if($this->reportRange == 0){

            if($this->uti_det->previus_day_balance != $this->uti_det->actual_balance){

                $this->uti->update([
                    
                    'balance' => $this->uti_det->previus_day_balance
        
                ]);
        
                $this->uti_det->update([
        
                    'ingress' => 0,
                    'egress' => 0,
                    'actual_balance' => $this->uti_det->previus_day_balance
        
                ]);
    
                $this->emit('item-added','Registro Exitoso');
                $this->mount();
                $this->render();
                
            }else{

                $this->emit('cover-error','No hay nada que revertir');
                return;
            }

        }else{

            $this->emit('cover-error','No se puede alterar fechas pasadas');
            return;
        }

    }

    public function Force_Balance(){

        if($this->reportRange == 0){

            $rules = [

                'amount' => 'required|numeric'
            ];

            $messages = [

                'amount.required' => 'El monto es requerido',
                'amount.numeric' => 'Este campo solo admite numeros'
            ];
            
            $this->validate($rules, $messages);

            $fecha1 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 00:00:00';
            $fecha2 = Carbon::parse(Carbon::today())->format('Y-m-d') . ' 23:59:59';

            $cov = Cover::firstWhere('description','diferencia por t/c');
            $cov_det = $cov->details->where('cover_id',$cov->id)->whereBetween('created_at',[$fecha1, $fecha2])->first();

            $cov_det->update([
        
                'actual_balance' => $cov_det->actual_balance + $this->amount

            ]);

            $this->emit('item-updated','Registro Exitoso');
            $this->resetUI();
            $this->render();

        }else{

            $this->emit('cover-error','No se puede alterar fechas pasadas');
            return;
        }

    }

    public function Close(){

        /*$time_1 = now();
        $time_2 = $time_1->copy()->endOfMonth();

        if($time_1->diffInDays($time_2) == 0 || ($time_1->diffInDays($time_2) == 1 && $time_2->dayOfWeek == 7)){*/

            $cap = Cover::firstWhere('description','capital de trabajo inicial');
            $uti = Cover::firstWhere('description','utilidad acumulada');
            $fact = Cover::firstWhere('description','facturas 6% acumulado');

            $cap->update([
                
                'balance' => $cap->balance + $uti->balance

            ]);

            $uti->update([
                
                'balance' => 0

            ]);

            $fact->update([
                
                'balance' => 0

            ]);

            $this->emit('item-added','Registro Exitoso');
            $this->mount();
            $this->render();

        /*}else{

            $this->emit('cover-error', 'No se cumplen las condiciones');
            return;
        }*/

    }

    public function ChangeDate(){

        $detail = CoverDetail::all();

        $compare = Carbon::parse($detail->last()->created_at)->format('Y-m-d');
        
        if($compare == $this->date){

            $time = Carbon::now();
            $fecha1 = Carbon::parse($this->date)->format('Y-m-d'). ' 00:00:00';
            $fecha2 = Carbon::parse($this->date)->format('Y-m-d'). ' 23:59:59';
            $fecha3 = Carbon::parse($this->date3)->format('Y-m-d'). ' 00:00:00';
            $fecha4 = Carbon::parse($this->date3)->format('Y-m-d'). ' 23:59:59';

            $compare_2 = $detail->whereBetween('created_at', [$fecha3, $fecha4]);

            if(count($compare_2) == 0){

                $details_table = Detail::whereBetween('created_at', [$fecha1, $fecha2])->orderBy('id', 'asc')->get();
                $incomes_table = Income::whereBetween('created_at', [$fecha1, $fecha2])->orderBy('id', 'asc')->get();
                $sales_table = Sale::whereBetween('created_at', [$fecha1, $fecha2])->orderBy('id', 'asc')->get();
                $transfers_table = Transfer::whereBetween('created_at', [$fecha1, $fecha2])->orderBy('id', 'asc')->get();
                $paydesks_table = Paydesk::whereBetween('created_at', [$fecha1, $fecha2])->orderBy('id', 'asc')->get();
                
                DB::beginTransaction();
                
                try {

                    if(count($details_table) > 0){

                        foreach($details_table as $dt){

                            $dt->update([
                            
                                'created_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                                'updated_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                    
                            ]);
                        }
                    }

                    if(count($incomes_table) > 0){

                        foreach($incomes_table as $it){

                            $it->update([
                            
                                'created_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                                'updated_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                    
                            ]);
                        }
                    }

                    if(count($sales_table) > 0){

                        foreach($sales_table as $st){

                            $st->update([
                            
                                'created_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                                'updated_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                    
                            ]);
                        }
                    }

                    if(count($transfers_table) > 0){

                        foreach($transfers_table as $tt){

                            $tt->update([
                            
                                'created_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                                'updated_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                    
                            ]);
                        }
                    }

                    if(count($paydesks_table) > 0){

                        foreach($paydesks_table as $pt){

                            $pt->update([
                            
                                'created_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                                'updated_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                    
                            ]);
                        }
                    }

                    if(count($this->details) > 0){

                        foreach($this->details as $cdt){

                            $cdt->update([
                            
                                'created_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                                'updated_at' => $this->date3 . ' ' . $time->hour . ':' . $time->minute . ':' . $time->second,
                    
                            ]);
                        }
                    }

                    DB::commit();

                } catch (Exception) {
                    DB::rollback();
                    $this->emit('cover-error', 'Algo salio mal');
                }

                $this->emit('item-added','Fecha Modificada');
                $this->mount();
                $this->render();

            }else{

                $this->emit('cover-error', 'Ya existe una caratula con esa fecha');
                return;
            }
          

        }else{

            $this->emit('cover-error', 'Solo puede cambiar fecha a la ultima caratula registrada');
            return;
        }
    }

    public function resetUI(){

        $this->resetValidation();
    }

}
