<!DOCTYPE html>
<html lang="en">
<head>
    @include('include/meta')
	
	    <style>
        /* Clean invoice style inspired by your mockup */
        .invoice-card{background:#fff;border-radius:6px;padding:0;box-shadow:0 1px 0 rgba(0,0,0,.05);}
        .invoice-top{background:#03a9f4;color:#fff;padding:18px 24px;border-radius:6px 6px 0 0;display:flex;align-items:center;justify-content:space-between}
        .invoice-top h1{margin:0;font-weight:700;letter-spacing:1px}
        .from-block, .to-block{padding:18px}
        .invoice-meta{padding:18px 24px;border-top:1px solid rgba(0,0,0,.06);display:flex;gap:20px}
        .invoice-meta .box{flex:1}
        .items-table th, .items-table td{padding:14px;border-bottom:1px solid #e9eef0}
        .items-table thead th{background:transparent;border-bottom:2px solid #1e7fbf;color:#1e7fbf}
        .totals{padding:18px 24px;display:flex;justify-content:flex-end}
        .totals table{width:360px}
        .small{font-size:13px;color:#6c757d}
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
        <div class="row">
            <div class="col-12 d-flex no-block align-items-center">
                <h4 class="page-title">Payment Details</h4>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">Payment Details</h5>
                    </div>

                    <div class="card-body">
						
				



<div class="container-fluid">
    <div class="card shadow p-4">

        <!-- Header with Buttons -->
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0">View Receipt</h4>

            <div>
                <a href="#" onclick="window.print();" class="btn btn-outline-primary btn-sm mx-1">
                    <i class="fa fa-print"></i> PRINT
                </a>
                <a href="#" class="btn btn-primary btn-sm mx-1">
                    <i class="fa fa-download"></i> DOWNLOAD
                </a>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Invoice From -->
            <div class="col-md-8">
                <h6 class="fw-bold">{{ $payment->user->company_name }}</h6>
                <p class="mb-0">{{ $payment->user->address }}</p>
                <p class="mb-0">{{ $payment->user->city }}, {{ $payment->user->postal_code }}</p>
                <p class="mb-0">{{ $payment->user->bank_name }}</p>
                <p class="mb-0">{{ $payment->user->account_number }}</p>
            </div>

            <!-- Logo -->
            <div class="col-md-4 text-end">
                
				
				
				 @if(!empty($payment->user->profile_logo))
					<img src="{{ $mainUrl }}/public/uploads/profile_logos/{{ $payment->user->profile_logo }}" 
						 alt="Logo" style="height:64px;object-fit:contain;">
				@else
					<img src="{{ $mainUrl }}/default-logo.png" 
						 alt="No Logo" style="height:64px;object-fit:contain;">
				@endif
				
				
            </div>
        </div>

        <hr>

        <!-- Payment & Billing -->
        <div class="row mt-3">
            <div class="col-md-6">
                <h6 class="fw-bold">Payment Date</h6>
                <p>{{ \Carbon\Carbon::parse($payment->created_at)->format('d M, Y') }}</p>

                <h6 class="fw-bold">Invoice Number</h6>
                <p>#{{ $payment->id }}</p>
            </div>

            <div class="col-md-6 text-end">
                <h6 class="fw-bold">Billed To</h6>
                <p class="mb-0">{{ $payment->billed_name ?? '-' }}</p>
                <p class="mb-0">{{ $payment->billed_address }}</p>
                <p class="mb-0">{{ $payment->billed_city }}, {{ $payment->billed_zip }}</p>
                <p class="mb-0">{{ $payment->billed_country }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <table class="table mt-4">
            <thead class="table-light">
                <tr>
                    <th>Product Name</th>
                    <th class="text-center">Qty</th>
                    <th class="text-end">Price Excluding VAT</th>
                    <th class="text-end">VAT</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $payment->plan_name }}</td>
                    <td class="text-center">{{ $payment->quantity }}</td>
                    <td class="text-end">{{ $payment->price_excluding_vat }} Kr</td>
                    <td class="text-end">{{ $payment->vat_rate }}%</td>
                    <td class="text-end">{{ $payment->total }} Kr</td>
                </tr>
                <tr>
                    <td colspan="5" class="small">
                        Service Term: {{ \Carbon\Carbon::parse($payment->service_start)->format('d-M-Y') }} 
                        to {{ \Carbon\Carbon::parse($payment->service_end)->format('d-M-Y') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <hr>

        <!-- Summary -->
        <div class="row">
            <div class="col-md-8"></div>

            <div class="col-md-4">
                <table class="table">
                    <tr>
                        <th>Subtotal</th>
                        <td class="text-end">{{ $payment->subtotal }} Kr</td>
                    </tr>
                    <tr>
                        <th>VAT ({{ $payment->vat_rate }}%)</th>
                        <td class="text-end">{{ $payment->vat_amount }} Kr</td>
                    </tr>
                    <tr class="fw-bold">
                        <th>Total</th>
                        <td class="text-end">{{ $payment->total }} Kr</td>
                    </tr>
                </table>
            </div>
        </div>

        <small class="text-muted">
            Invoice was created in an electronic account system as per Icelandic ordinance no. 505/2013.
        </small>
    </div>
</div>


						
						
						
						
						
						
						
						
						
						
						
						
						
<!--
                        <table class="table table-bordered">
                            <tr>
                                <th>ID</th>
                                <td>{{ $payment->id }}</td>
                            </tr>
                            <tr>
                                <th>User / Company</th>
                                <td>
                                    @if($payment->user)
                                        {{ $payment->user->contact_person_name ?? $payment->user->company_name }} <br>
                                        @foreach($payment->user->companies as $company)
                                            <small class="text-muted">{{ $company->company_name }}</small><br>
                                        @endforeach
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Plan Name</th>
                                <td>{{ $payment->plan_name ?? '-' }}</td>
                            </tr>
							
                            <tr>
                                <th>Amount</th>
                                <td>{{ $payment->total }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>{{ $payment->status }}</td>
                            </tr>
                            <tr>
                                <th>Invoice ID</th>
                                <td>{{ $payment->order_id ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Gateway Subscription ID</th>
                                <td>{{ $payment->subscription_id ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Payment Date</th>
                                <td>{{ $payment->created_at ? $payment->created_at->format('d M, Y h:i A') : '-' }}</td>
                            </tr>
							<tr>
								<th>Billing Name</th>
								<td>{{ $payment->billed_name ?? '-' }}</td>
							</tr>
							<tr>
								<th>Billing Email</th>
								<td>{{ $payment->billed_city ?? '-' }}</td>
							</tr>
							<tr>
								<th>Billing Country</th>
								<td>{{ $payment->billed_country ?? '-' }}</td>
							</tr>
							<tr>
								<th>Billing Address</th>
								<td>{{ $payment->billed_address ?? '-' }}</td>
							</tr>
							<tr>
								<th>Service Start Date</th>
								<td>{{ $payment->service_start ? \Carbon\Carbon::parse($payment->service_start)->format('d M Y') : '-' }}</td>
							</tr>

							<tr>
								<th>Service End Date</th>
								<td>{{ $payment->service_end ? \Carbon\Carbon::parse($payment->service_end)->format('d M Y') : '-' }}</td>
							</tr>
						

                        </table>
-->

                        <a href="{{ url()->previous() }}" class="btn btn-secondary">Back</a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@include('include/footer')
</body>
</html>
