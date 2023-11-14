<ul class="navbar-item flex-row search-ul">
    <li class="nav-item align-self-center search-animated">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-search toggle-search"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        <form class="form-inline search-full form-inline search" role="search">
            <div class="search-bar">
                <!--directiva de livewire para emitir evento al presionar la tecla indicada-->
                <!--$emit emite el evento que sera escuchado por cualquier controlador que este escuchando el evento-->
                <input id="code" type="text" wire:keydown.enter.prevent="$emit('scan-code', $('#code').val(),$('#sale_price').val(),$('#office').val(),$('#pf').val())" 
                class="form-control search-form-control ml-lg-auto" placeholder="Codigo de producto...">
            </div>
        </form>
    </li>
    <li class="nav-item align-self-center search-animated">
        <form class="form-inline search-full form-inline search">
            <div class="search-bar">
                <input id="sale_price" type="text" wire.model="sale_price" class="form-control search-form-control ml-lg-auto text-center" placeholder="Precio de venta...">
            </div>
        </form>
    </li>
    <li class="nav-item align-self-center search-animated">
        <form class="form-inline search-full form-inline search">
            <div class="search-bar">
                <input id="pf" type="text" wire.model="pf" class="form-control search-form-control ml-lg-auto text-center" placeholder="N° Proforma...">
            </div>
        </form>
    </li>
    <li>
        <form class="form-inline">
            <div class="col-sm-12 col-md-4">
                <select id="office" wire:model="office" class="form-control">
                    @foreach ($offices as $office) <!--iteracion para obtener todas las categorias-->
                        <option value="{{$office->name}}">{{$office->name}}</option>  <!--se obtiene el nombre de las categorias a traves de su id-->
                    @endforeach
                </select>
            </div>
        </form>
    </li>
</ul>

<!--script de eventos provenientes del backend a ser escuchados-->
<script>

    document.addEventListener('DOMContentLoaded', function(){

        livewire.on('scan-code', action => {    //evento para limpiar caja de busqueda

            $('#code').val('')
        })
    });

</script>