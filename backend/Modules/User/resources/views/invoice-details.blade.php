<!DOCTYPE html>
<html lang="en">
<head>
    @include('include/meta')
    <style>
        /* Clean invoice style inspired by your mockup */
        .invoice-card{background:#fff;border-radius:6px;padding:0;box-shadow:0 1px 0 rgba(0,0,0,.05);}
        .invoice-top{background:#00B6D3;color:#fff;padding:15px 25px;display:flex;align-items:center;justify-content:flex-end;}
		.invoice-top .btn {background:none;color:#fff;text-transform:uppercase;font-size:12px;font-weight:600;padding:0;border:none;}
		.invoice-top .btn .fa {font-size:16px;margin-right:2px;}
		/*.invoice-top h1{margin:0;font-weight:700;letter-spacing:1px}*/
		.invoice-top > div { display:flex; gap:25px;}
		.billFrom {display:flex;justify-content:space-between;}
        .from-block, .to-block{padding:18px}
		.wrapperDiv {padding:0 25px 25px 25px;}
        .invoice-meta{padding:10px 0;display:flex;justify-content:space-between;}
		.invoice-meta h3{font-weight:700;margin:0;}
		.invoice-meta > div {width:50%;padding:15px;}
		.invoice-meta > div:first-child {padding-left:0;}
		.invoice-meta .box{flex:1}
		.billedTo {padding:0;border-top:1px solid #00538A;}
		.billedTo > div:not(:last-child) {border-right:1px solid #00538A;}
		.items-table {border:none;border-top:1px solid #00538A;margin:0;}
		.items-table tr {border-bottom:1px solid #00538A;}
		.items-table tr th:first-child, .items-table tr td:first-child {padding-left:0;}
		.items-table tr th:last-child, .items-table tr td:last-child {padding-right:0;}
		.items-table th, .items-table td{padding:10px;border:none !important;}
        .items-table thead th{font-size:14px;color:#00538A;font-weight:700;}
        .totals{display:flex;justify-content:flex-end;margin-bottom:75px;}
        .totals table{width:300px;}
		.totals .items-table {border-top:none;}
        .small{font-size:13px;color:#6c757d}
		.small strong {font-size:14px;}
		strong {color:#00538A;}
		.tblFooter p:first-child {padding-bottom:10px;margin-bottom:10px;border-bottom:1px solid #f1f1f1;}
		.tblFooter p strong {color:#000;}
		.tblFooter p:last-child {margin:0;display:flex;justify-content:space-between;align-items:center;gap:20px;}
		.tblFooter p:last-child span:last-child {width:170px;text-align:right;}
        .action-bar{display:flex;gap:8px;justify-content:flex-end;padding:12px 0}
        .btn{padding:8px 12px;border-radius:6px;border:0;cursor:pointer}
        .btn-primary{background:#03a9f4;color:#fff}
        .btn-outline{background:#fff;border:1px solid #03a9f4;color:#03a9f4}
        @media print{ .no-print{display:none!important} }
    </style>
</head>
<body>
@include('include/header')
@include('include/left')

<div class="page-wrapper">
    <div class="page-breadcrumb">
		<h4 class="page-title">Invoice Preview</h4>
	</div>
    <div class="container-fluid">
        <div class="invoice-card" id="invoice-root">
            
			<div class="invoice-top">
				<div>
					<button class="btn btn-outline" id="btn-download"><i class="fa fa-download"></i> Download</button>
					<button class="btn btn-outline" id="btn-print"><i class="fa fa-print"></i> Print</button>
				</div>
            </div>
            <div class="wrapperDiv">
			<div class="invoice-meta">
				<div>
					<span>
						<h3>INVOICE</h3>
						<div class="small">#{{ $invoice->invoice_no ?? $invoice->id }}</div>
					</span>
                </div>
				<div class="billFrom">
					<span>
						<strong>From</strong>
						<div class="small">{{ $invoice->user->company_name ?? ($invoice->user->contact_person_name ?? '-') }}</div>
						<div class="small">{{ $invoice->user->address ?? '' }}</div>
						<div class="small">{{ $invoice->user->bank_name ?? '' }}</div>
						<div class="small">{{ $invoice->user->account_number ?? '' }}</div>
					</span>
                    {{-- Logo: use local preview path if available --}}
                    @if(!empty($invoice->user->profile_logo))
					<img src="{{ $mainUrl }}/public/uploads/profile_logos/{{ $invoice->user->profile_logo }}" alt="Logo" style="height:66px;">
					@else
					<img src="{{ $mainUrl }}/default-logo.png" alt="No Logo" style="height:66px;">
					@endif
                </div>
			</div>
			
			<div class="invoice-meta billedTo">
                <div>
                    <div class="small" style="margin-bottom:10px;"><strong>Issued</strong><br>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M, Y') }}</div>
                    <div class="small"><strong>Due</strong><br>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') : '-' }}</div>
                    <!--<div class="small"><strong>Payment status</strong><br>{{ ucfirst($invoice->payment_status ?? '-') }}</div>-->
                </div>
				<div>
                    <strong>Billed to</strong>
                    <div class="small">{{ $invoice->customer_name ?? '-' }}</div>
                    <div class="small">{{ $invoice->customer_address ?? '-' }}</div>
                    <div class="small">{{ $invoice->customer_email ?? '' }}</div>
                    <div class="small">{{ $invoice->ssn ?? '' }}</div>
                </div>
            </div>
			
                <table class="table items-table">
                    <thead>
                        <tr>
                            <th style="text-align:left">Product Name</th>
                            <th style="width:90px;text-align:center">Qty</th>
                            <th style="width:180px;text-align:right">Price excluding VAT</th>
                            <th style="width:180px;text-align:right">Price Including VAT</th>
                            <th style="width:140px;text-align:right">Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td style="text-align:center">{{ (float)$item->quantity }}</td>
                            <td style="text-align:right">{{ number_format($item->price_excl_vat, 2) }} Kr</td>
                            <td style="text-align:right">{{ number_format($item->price_incl_vat ?? $item->price_incl_vat, 2) }} Kr</td>
                            <td style="text-align:right">{{ number_format(($item->quantity * $item->price_incl_vat), 2) }} Kr</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            <div class="totals">
                <table class="table items-table">
                    <tr>
                        <td><strong>Subtotal</strong></td>
                        <td style="text-align:right">{{ number_format($invoice->subtotal, 2) }} Kr</td>
                    </tr>
<!--
                    <tr>
                        <td class="small">VAT</td>
                        <td style="text-align:right">{{ number_format($invoice->total_vat, 2) }} Kr</td>
                    </tr>
-->
                    <tr>
                        <td><strong>Total</strong></td>
                        <td style="text-align:right;font-weight:700;">{{ number_format($invoice->total_amount_incl_vat ?: $invoice->total_amount_incl_vat, 2) }} Kr</td>
                    </tr>
                </table>
            </div>

            <div class="tblFooter">
                <p><strong>Thank you for the business!</strong></p>
                <p class="small">
					<span>Invoice created in our system.</span>
					<span>www.invoicesystem.com</span>
				</p>
            </div>
				</div>
        </div> <!-- /invoice-card -->
        <div class="action-bar no-print">
            <button class="btn btn-primary" id="btn-download">Download PDF</button>
            <button class="btn btn-outline" id="btn-print">Print</button>
            <a href="{{ url()->previous() }}" class="btn" style="background:#6c757d;color:#fff">Back</a>
        </div>
    </div>
</div>

@include('include/footer')

<!-- html2pdf (client-side) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
<script>
    document.getElementById('btn-print').addEventListener('click', function(){
        window.print();
    });

    document.getElementById('btn-download').addEventListener('click', function(){
        const element = document.getElementById('invoice-root');
        const opt = {
            margin:       0.4,
            filename:     'invoice-{{ $invoice->invoice_no ?? $invoice->id }}.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    });
</script>

</body>
</html>
