<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>{{$componentName}} | {{$pageTitle}}</b>
                </h4>
                @can('crear_producto')
                <ul class="tabs tab-pills">
                    <li>
                        <a href="javascript:void(0)" class="tabmenu bg-dark" data-toggle="modal" data-target="#theModal">Agregar</a>
                    </li>
                </ul>
                @endcan
            </div>
            
            @include('common.searchbox')

            <div class="widget-content">
                <div class="table-responsive-xxl">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white text-center">MEDIDA</th>
                                <th class="table-th text-white text-center">CODIGO</th>
                                <th class="table-th text-white text-center">MARCA</th>
                                <th class="table-th text-white text-center">ARO</th>
                                <th class="table-th text-white text-center">TRILLA</th>
                                <th class="table-th text-white text-center">LONA</th>
                                <th class="table-th text-white text-center">COSTO</th>
                                <th class="table-th text-white text-center">PRECIO</th>
                                <th class="table-th text-white text-center">CATEGORIA</th>
                                <th class="table-th text-white text-center">SUBCATEGORIA</th>
                                <th class="table-th text-white text-center">ESTADO</th>
                                {{--<th class="table-th text-white text-center">IMAGEN</th>--}}
                                <th class="table-th text-white text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                            <tr>
                                <td><h6 class="text-center">{{$product->description}}</h6></td>
                                <td><h6 class="text-center">{{$product->code}}</h6>
                                    {{--<a href="javascript:void(0)" class="btn btn-dark mtmobile">
                                        <i class="fas fa-paperclip"></i>
                                    </a>--}}
                                </td>                            
                                <td><h6 class="text-center">{{$product->brand}}</h6></td>
                                <td><h6 class="text-center">{{$product->ring}}</h6></td>
                                <td><h6 class="text-center">{{$product->threshing}}</h6></td>
                                <td><h6 class="text-center">{{$product->tarp}}</h6></td>
                                <td><h6 class="text-center">${{$product->cost}}</h6></td>
                                <td><h6 class="text-center">${{$product->price}}</h6></td>
                                <td><h6 class="text-center">{{$product->category}}</h6></td>
                                <td><h6 class="text-center">{{$product->subcategory}}</h6></td>
                                <td class="text-center">
                                    <span class="badge {{$product->state->name == 'ok' ? 'badge-success' : 'badge-danger'}} text-uppercase">{{$product->state->name}}</span>
                                </td>
                                {{--<td class="text-center">
                                    <span>
                                        <img src="{{ asset('storage/products/' . $product->imagen) }}" alt="imagen de ejemplo" height="70" width="80" class="rounded">
                                    </span>
                                </td>--}}
                                @can('editar_producto')
                                <td class="text-center">
                                    <a href="javascript:void(0)" wire:click.prevent="Edit({{$product->id}})" class="btn btn-dark mtmobile" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </td>
                                @endcan
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{$products->links()}}  <!--paginacion de laravel-->
                </div>
            </div>
        </div>
    </div>
    @include('livewire.product.form')  <!--formulario modal-->
</div>

<!--script de eventos provenientes del backend a ser escuchados-->
<script>

    document.addEventListener('DOMContentLoaded', function(){
        
        window.livewire.on('item-added', msg=>{  //evento para agregar registro
            $('#theModal').modal('hide')
            noty(msg)
        });
        window.livewire.on('item-updated', msg=>{    //evento para actualizar registro
            $('#theModal').modal('hide')
            noty(msg)
        });
        window.livewire.on('item-deleted', msg=>{    //evento para eliminar registro
            noty(msg)
        });
        window.livewire.on('show-modal', msg=>{ //evento para mostral modal
            $('#theModal').modal('show')
        });
        window.livewire.on('modal-hide', msg=>{ //evento para cerrar modal
            $('#theModal').modal('hide')
        });
        $('#theModal').on('shown.bs.modal', function(e){    //metodo para autofocus al campo nombre
            $('.component-name').focus()
        });
    });

    function Confirm(id){   //metodo para alerta de confirmacion que recibe el id

        swal({  //alerta sweetalert
            title: 'CONFIRMAR',
            text: '¿CONFIRMA ELIMINAR EL REGISTRO?',
            type: 'warning',
            showCancelButton: true,
            cancelButtonText: 'CERRAR',
            cancelButtonColor: '#fff',
            confirmButtonColor: '#3B3F5C',
            confirmButtonText: 'ACEPTAR'
        }).then(function(result){
            if(result.value){   //validar si se presiono el boton de confirmacion
                window.livewire.emit('destroy', id)   //emision de evento para hacer llamado al metodo Destroy del controlador
                swal.close()    //cerrar alerta
            }
        })
    }

</script>