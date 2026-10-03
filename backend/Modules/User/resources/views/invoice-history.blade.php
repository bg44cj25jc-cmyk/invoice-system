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
                <h4 class="page-title">Invoice History</h4>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">Invoice History</h5>
                    </div>

					
					
					
					
					
				<form method="GET">
    <div class="row p-3">

        <div class="col-md-3">
            <select name="user_id" id="companyFilter" class="form-control">
    <option value="">Filter by Company</option>
    @foreach($users as $u)
        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
            {{ $u->company_name ?? $u->full_name ?? 'Unknown' }}
        </option>
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
										<th>Invoice No</th>
										<th>Customer</th>
										<th>Issue Date</th>
										<th>Created By</th>
										<th>Company Name</th>
										<th>Total Amount</th>
										<th>Status</th>
										<th>Payment</th>
										<th>View</th>
									</tr>
								</thead>
								<tbody>
									@foreach($invoices as $inv)
									<tr>
										<td>{{ $inv->id }}</td>
										<td>{{ $inv->invoice_no }}</td>
										<td>{{ $inv->customer->full_name ?? $inv->customer->full_name }}</td>
										<td>{{ $inv->invoice_date->format('d M Y') }}</td>
										<td>{{ $inv->user->company_name ?? $inv->user->full_name }}</td>
										<td>{{ $inv->company->company_name ?? $inv->company->full_name }}</td>
										<td>{{ $inv->total_amount }}</td>
										<td>{{ $inv->status }}</td>
										<td>{{ $inv->payment_status }}</td>
										<td>
											<a href="{{ url('user/invoice-details/'.$inv->id) }}" class="btn btn-info btn-sm">View</a>
										</td>
									</tr>
									@endforeach
								</tbody>
							</table>

                            <div class="d-flex justify-content-center mt-3">
								{{ $invoices->onEachSide(1)->links('pagination::bootstrap-4') }}
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
	
	<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#companyFilter').select2({
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
