<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
</head>

<body>
@include('include/header')
@include('include/left')

<div class="page-wrapper">

    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-12 d-flex no-block align-items-center">
                <h4 class="page-title">Payment History</h4>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">Payment History</h5>
                    </div>

					
					
					
					
					
				<form method="GET">
    <div class="row p-3">

        <div class="col-md-3">
    <select name="user_id" id="company-filter" class="form-control">
        <option value="">Filter by Company</option>
        @foreach($users as $u)
            @if($u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->company_name ?? $u->contact_person_name ?? 'Unknown' }}
                </option>
            @endif
        @endforeach
    </select>
</div>


        <div class="col-md-3">
            <input type="text" id="from_date" name="from_date" class="form-control" placeholder="From Date" value="{{ request('from_date') }}">
        </div>

        <div class="col-md-3">
            <input type="text" id="to_date" name="to_date" class="form-control" placeholder="To Date" value="{{ request('to_date') }}">
        </div>

        <div class="col-md-3 d-flex">
            <button class="btn btn-primary mr-2">Filter</button>
            <a href="{{ url()->current() }}" class="btn btn-danger">Reset</a>
        </div>
    </div>
</form>
	
					
					
					
					
					
					
					
					
					
					
					

                    <div class="row">
                        <div class="col-lg-12">
							
							


                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Billed Name</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
									<th>View</th>
                                </tr>
                                </thead>

                                <tbody>
								@foreach($payments as $payment)
								<tr>
									<td>{{ $payment->id }}</td>
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


									<td>{{ $payment->plan_name ?? '-' }}</td>
									<td>{{ $payment->total }}</td>
									<td>{{ $payment->created_at ? $payment->created_at->format('d M Y') : '-' }}</td>
									<td>{{ $payment->order_id ?? '-' }}</td>
									<td>{{ $payment->status }}</td>
									<td><a href="{{ url('user/payment-details/'.$payment->id) }}" class="btn btn-sm btn-info">View</a>
</td>

								</tr>
								@endforeach


								</tbody>

                            </table>
                            <div class="d-flex justify-content-center mt-3">
								{{ $payments->onEachSide(1)->links('pagination::bootstrap-4') }}
							</div>


                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>

@include('include/footer')

<!-- jQuery UI Datepicker -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#company-filter').select2({
        placeholder: "Search Company",
        allowClear: true
    });
});
</script>

<script>
$(function () {
    $("#from_date, #to_date").datepicker({
        dateFormat: "dd-mm-yy"
    });
});
</script>


</body>
</html>
