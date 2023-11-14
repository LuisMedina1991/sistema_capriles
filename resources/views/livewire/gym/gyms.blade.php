<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title text-uppercase">
                    <b>{{$componentName}} | {{$pageTitle}}</b>
                </h4>
                <div class="container">
                    <div class="row row-cols-3">
                        <div class="col">
                            <h5 class="mr-5">TOTAL POR PAGAR: ${{ number_format($my_total,2)}}</h5>
                        </div>
                        @can('agregar_registro')
                        <div class="col">
                            <a href="javascript:void(0)" class="btn btn-dark btn-md" data-toggle="modal" data-target="#theModal">Agregar</a>
                        </div>
                        @endcan
                        <div class="col">
                            <a href="{{ url('gym_report/pdf' . '/' . $my_total) }}" class="btn btn-dark btn-md" target="_blank">
                                Generar PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            @include('common.searchbox')

            <div class="widget-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white text-center">DESCRIPCION</th>
                                <th class="table-th text-white text-center">SALDO</th>
                                <th class="table-th text-white text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($gyms as $gym)
                            <tr>
                                <td><h6 class="text-center">{{$gym->description}}</h6></td>
                                <td><h6 class="text-center">${{number_format($gym->amount,2)}}</h6></td>
                                <td class="text-center">
                                    {{--<a href="javascript:void(0)" wire:click.prevent="Edit({{$gym->id}})" class="btn btn-dark mtmobile" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>--}}
                                    @can('eliminar_registro')
                                    <a href="javascript:void(0)" onclick="Confirm('{{$gym->id}}')" class="btn btn-dark" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{$gyms->links()}}
                </div>
            </div>
        </div>
    </div>
    @include('livewire.gym.form')
</div>


<script>

    document.addEventListener('DOMContentLoaded', function(){
        
        window.livewire.on('item-added', msg=>{  //evento al agregar registro
            $('#theModal').modal('hide')
            noty(msg)
        });

        window.livewire.on('item-deleted', msg=>{    //evento al eliminar registro
            noty(msg)
        });

        window.livewire.on('show-modal', msg=>{ //evento para mostral modal
            $('#theModal').modal('show')
        });

        window.livewire.on('modal-hide', msg=>{ //evento para cerrar modal
            $('#theModal').modal('hide')
        });

        $('#theModal').on('shown.bs.modal', function(e){    //metodo para autofocus a campo determinado
            $('.component-name').focus()
        });

        window.livewire.on('show-detail', msg=>{ //evento para mostral modal
            $('#modal-details').modal('show')
        });

        window.livewire.on('cover-error', msg=>{    //evento al eliminar registro
            noty(msg,2)
        });

        window.livewire.on('movement-error', Msg => {   //evento para los errores del componente
            noty(Msg,2)
        });

    });

    function Confirm(id){

        swal({

            title: 'CONFIRMAR',
            text: '¿CONFIRMA ELIMINAR EL REGISTRO?',
            type: 'warning',
            showCancelButton: true,
            cancelButtonText: 'CERRAR',
            cancelButtonColor: '#fff',
            confirmButtonColor: '#3B3F5C',
            confirmButtonText: 'ACEPTAR'

        }).then(function(result){

            if(result.value){

                window.livewire.emit('destroy', id)
                swal.close()
            }
        })
    }

</script>