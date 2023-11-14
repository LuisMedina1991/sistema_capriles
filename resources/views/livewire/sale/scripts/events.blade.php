<script>

    document.addEventListener('DOMContentLoaded', function(){

        window.livewire.on('scan-ok', Msg => {  //evento para capturar todas las operaciones correctas con codigo de barras
            noty(Msg)
        })

        window.livewire.on('scan-notfound', Msg => {    //evento para capturar todas las operaciones incorrectas con codigo de barras
            noty(Msg, 2)
        })

        window.livewire.on('no-stock', Msg => { //evento para stock insuficiente
            noty(Msg, 2)
        })

        window.livewire.on('sale-error', Msg => {   //evento para capturar errores al realizar venta
            noty(Msg, 2)
        })

        window.livewire.on('print-ticket', saleId => {  //evento para impresion de ticket de venta
            window.open("print://" + saleId , '_blank')
        })

    })

</script>