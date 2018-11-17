<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->name }}</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <style>
        h1,h2,h3,h4,p,span,div { font-family: DejaVu Sans; }
    </style>
</head>
<body>
<div style="clear:both; position:relative;">
    <div style="position:absolute; left:0pt; width:250pt;">
        <img class="img-rounded" height="{{ $invoice->logo_height }}" src="{{ $invoice->logo }}">
    </div>
    <div style="margin-left:300pt;">
        <b>Invoice Date: </b>{{ $invoice->date }}<br>
        <b>Status:</b> {{ $invoice->status }}
    </div>
</div>
<br />
<h2>{{ $invoice->name }} {{ $invoice->number ? '#' . $invoice->number : '' }}</h2>
<div style="clear:both; position:relative;">
    <div style="position:absolute; left:0pt; width:250pt;">
        <h4>Pay to:</h4>
        <div class="panel panel-default">
            <div class="panel-body">
                {{ $invoice->business_details->get('name') }}<br>
                {{ $invoice->business_details->get('address') }}<br>
                {{ $invoice->business_details->get('registration') }}<br>
                {{ $invoice->business_details->get('taxcode') }}
            </div>
        </div>
    </div>
    <div style="margin-left: 300pt;">
        <h4>Invoiced to:</h4>
        <div class="panel panel-default">
            <div class="panel-body">
                {{ $invoice->customer_details->get('name') }}<br />
                {{ $invoice->customer_details->get('email') }}<br />
                {{ $invoice->customer_details->get('address') }}<br />
            </div>
        </div>
    </div>
</div>
<h4 style="margin-top: 25px;">Items:</h4>
<table class="table table-bordered">
    <thead>
    <tr>
        <th>#</th>
        <th>Name</th>
        <th>Cycle</th>
        <th>Price</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($invoice->items as $item)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->get('name') }}</td>
            <td>@if($item->get('start') !== ''){{ $item->get('start') }} - {{ $item->get('end') }}@endif</td>
            <td>{{ $invoice->formatCurrency()->symbol }}{{ $item->get('price') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
<div style="clear:both; position:relative;">
    @if($invoice->notes)
        <div style="position:absolute; left:0pt; width:250pt;">
            <h4>Notes:</h4>
            <div class="panel panel-default">
                <div class="panel-body">
                    {{ $invoice->notes }}
                </div>
            </div>
        </div>
    @endif
    <div style="margin-left: 300pt;">
        <h4>Total:</h4>
        <table class="table table-bordered">
            <tbody>
            <tr>
                <td><b>Subtotal</b></td>
                <td>{{ $invoice->formatCurrency()->symbol }}{{ $invoice->subTotalPriceFormatted() }}</td>
            </tr>
            <tr>
                <td>
                    <b>
                        Tax ({{ round($invoice->tax, 2) }}%)
                    </b>
                </td>
                <td>{{ $invoice->formatCurrency()->symbol }}{{ $invoice->taxPriceFormatted() }}</td>
            </tr>
            <tr>
                <td><b>Total</b></td>
                <td><b>{{ $invoice->formatCurrency()->symbol }}{{ $invoice->totalPriceFormatted() }}</b></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
@if ($invoice->footnote)
    <br /><br />
    <div class="well">
        {{ $invoice->footnote }}
    </div>
@endif
</body>
</html>