<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $venta->codigo }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #16232e; }
        .cabecera { border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 14px; }
        .negocio { font-size: 17px; font-weight: bold; color: #0f766e; }
        .sub { font-size: 10px; color: #5a6b78; margin-top: 2px; }
        .comprobante { float: right; text-align: right; margin-top: -34px; }
        .comprobante .codigo { font-size: 13px; font-weight: bold; }
        .comprobante .fecha { font-size: 10px; color: #5a6b78; }
        .anulada { background: #fbe9e9; border: 1px solid #be2f2f; color: #be2f2f;
                   padding: 6px 10px; margin-bottom: 12px; font-weight: bold; text-align: center; }
        .datos { width: 100%; margin-bottom: 14px; }
        .datos td { padding: 3px 0; font-size: 10px; }
        .datos .etiqueta { color: #5a6b78; width: 90px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th { background: #eef1f4; text-align: left; padding: 7px 8px;
                         font-size: 9px; text-transform: uppercase; color: #5a6b78; border-bottom: 1px solid #dde3e9; }
        table.items td { padding: 7px 8px; border-bottom: 1px solid #eef1f4; }
        .der { text-align: right; }
        .totales { width: 210px; float: right; border-collapse: collapse; }
        .totales td { padding: 4px 8px; }
        .totales .total td { border-top: 2px solid #16232e; font-size: 14px; font-weight: bold; padding-top: 7px; }
        .pie { clear: both; padding-top: 26px; text-align: center; font-size: 9px; color: #8a99a6; }
    </style>
</head>
<body>

<div class="cabecera">
    <div class="negocio">Bodega Central</div>
    <div class="sub">Sistema de Inventario y Ventas</div>
    <div class="comprobante">
        <div class="codigo">{{ $venta->codigo }}</div>
        <div class="fecha">{{ $venta->created_at->format('d/m/Y H:i') }}</div>
    </div>
</div>

@if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
    <div class="anulada">
        COMPROBANTE ANULADO el {{ $venta->anulada_at?->format('d/m/Y H:i') }}
    </div>
@endif

<table class="datos">
    <tr>
        <td class="etiqueta">Atendido por</td>
        <td>{{ $venta->usuario->name }}</td>
        <td class="etiqueta">Método de pago</td>
        <td>{{ $venta->metodo_pago->label() }}</td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th>Producto</th>
            <th class="der">Cant.</th>
            <th class="der">P. unit.</th>
            <th class="der">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($venta->detalles as $detalle)
            <tr>
                <td>{{ $detalle->producto->nombre }}</td>
                <td class="der">{{ $detalle->cantidad }}</td>
                <td class="der">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                <td class="der">S/ {{ number_format($detalle->subtotal, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totales">
    <tr>
        <td>Ítems</td>
        <td class="der">{{ $venta->detalles->sum('cantidad') }}</td>
    </tr>
    <tr class="total">
        <td>TOTAL</td>
        <td class="der">S/ {{ number_format($venta->total, 2) }}</td>
    </tr>
</table>

<div class="pie">
    Gracias por su compra · Documento generado el {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
