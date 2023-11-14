<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>CUENTAS DE BANCO</title>
    <link rel="stylesheet" href="{{ public_path('css/custom_pdf.css') }}">    <!--estilos de hoja pdf-->
    <link rel="stylesheet" href="{{ public_path('css/custom_page.css') }}"> <!--estilos de hoja pdf-->
</head>
<body>
    <section class="header" style="top: -287px;">
        <table cellpading="0" cellspacing="0" width="100%">
            <tr>
                <td colspan="2" class="text-center">
                    <span style="font-size: 25px; font-weigth: bold;">Importadora Capriles</span>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top; padding-top: 5px; padding-right: 15px; position: relative" width="30%">
                    <img src="{{ public_path('assets/img/LOGO3.png') }}" alt="" class="invoice-logo" style="">
                </td>
                <td width="70%" class="text-left text-company" style="vertical-align: top; padding-top: 10px;">
                    @if ($reportRange == 0)
                        <span style="font-size: 16px"><strong>Movimientos del Dia</strong></span>
                        <br>
                        <span style="font-size: 16px"><strong>Fecha de Consulta: {{ \Carbon\Carbon::now()->format('d-m-Y') }}</strong></span>
                    @else
                        <span style="font-size: 16px"><strong>Movimientos por Fecha</strong></span>
                        <br>
                        <span style="font-size: 16px"><strong>Fecha de Consulta: {{\Carbon\Carbon::parse($dateFrom)->format('d-m-Y')}} al {{\Carbon\Carbon::parse($dateTo)->format('d-m-Y')}}</strong></span>
                    @endif
                    <br>
                    <span style="font-size: 14px"><strong>Cuenta: {{ $account }}</strong></span>
                </td>
            </tr>
        </table>
    </section>
    <section style="margin-top: -150px">
        <table cellpadding="0" cellspacing="0" class="table-items" width="100%">
            <thead>
                <tr>
                    <th width="40%">Descripcion</th>
                    <th width="15%">Monto</th>
                    <th width="15%">Saldo previo</th>
                    <th width="15%">Saldo Actual</th>
                    <th width="15%">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($details as $detail)
                    <tr>
                        <td align="center">{{ $detail->description }}</td>
                        <td align="center">${{ number_format($detail->amount,2) }}</td>
                        <td align="center">${{ number_format($detail->previus_balance,2) }}</td>
                        <td align="center">${{ number_format($detail->actual_balance,2) }}</td>
                        <td align="center">{{\Carbon\Carbon::parse($detail->created_at)->format('d-m-Y')}}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
    <section class="footer">
        <table cellpadding="0" cellspacing="0" class="table-items" width="100%">
            <tr>
                <td width="20%">
                    <span>IMPORTADORA CAPRILES</span>
                </td>
                <td width="60%" class="text-center">
                    importadoracapriles.com.bo
                </td>
                <td class="text-center" width="20%">
                    Pagina <span class="pagenum"></span>
                </td>
            </tr>
        </table>
    </section>
</body>
</html>