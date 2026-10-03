<!DOCTYPE html>
<html lang="en">

@include('include/meta')

<body>
@include('include/header')
@include('include/left')

<div class="page-wrapper">

    <div class="page-breadcrumb">
        <h4 class="page-title">Company Details</h4>
    </div>

    <div class="container-fluid">

        <div class="card p-3">

            <h3>{{ $company->company_name }}</h3>
			<p><strong>Contact Name:</strong> {{ $company->contact_person_name }}</p>
            <p><strong>Email:</strong> {{ $company->email }}</p>
            <p><strong>Phone:</strong> {{ $company->phone_number }}</p>
            
            <p><strong>Plan:</strong> {{ $company->plan->name }}</p>
			<p><strong>Plan Activate Date:</strong> {{ $company->plan_activate_date }}</p>
            <p><strong>Plan Expiry Date:</strong> {{ $company->plan_expiry_date }}</p>
			<p><strong>Invoice Count:</strong> {{ $company->invoice_count }}</p>
			<p><strong>Is Cancel Plan:</strong> {{ $company->is_cancel_plan == 'yes' ? 'yes' : 'No' }}</p>
			<p><strong>Country:</strong> {{ $company->country_details->name ?? ""}}</p>
            <p><strong>City:</strong> {{ $company->city  ?? "" }}</p>
			<p><strong>Address:</strong> {{ $company->address  ?? ""}}</p>
			<p><strong>VAT No:</strong> {{ $company->vat ?? "" }}</p>
			<p><strong>Postal Code:</strong> {{ $company->postal_code }}
        - {{ $company->postalCodeDetail->location_name ?? '' }}</p>
			
            <p><strong>Status:</strong> {{ $company->status == 1 ? 'Active' : 'Inactive' }}</p>
			
			<p><strong>Invoice in online bank:</strong></p>
			<p><strong>Bank Name:</strong> {{ $company->bank_name ?? "" }}</p>
			<p><strong>Account Number:</strong> {{ $company->account_number ?? "" }}</p>
			<p><strong>Invoice in online bank:</strong> {{ $company->invoice_in_online_bank_misc == 1 ? 'Yes' : 'No' }}</p>
			<p><strong>Show account number in invoice:</strong> {{ $company->show_acc_no_invoice == 1 ? 'Yes' : 'No' }}</p>
			<p><strong>Connect to National Register:</strong> {{ $company->connect_national_register == 1 ? 'Yes' : 'No' }}</p>
			<p><strong>Access Key:</strong> {{ $company->access_key ?? "" }}</p>
            <hr>

            <h4>Employee List</h4>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td>{{ $e->id }}</td>
                        <td>{{ $e->contact_person_name == '' ?  $e->fname .' '. $e->lname : $e->contact_person_name }}</td>
                        <td>{{ $e->email }}</td>
                        <td>{{ $e->phone_number }}</td>
                        <td>{{ $e->status == 1 ? 'Active' : 'Inactive' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            No employees found
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
			
			<hr>
<h4>Product List</h4>

<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Product #</th>
            <th>Name</th>
            <th>Price Excl VAT</th>
            <th>Price Incl VAT</th>
            <th>Status</th>
        </tr>
    </thead>

    <tbody>
    @forelse($products as $p)
        <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->product_number }}</td>
            <td>{{ $p->name }}</td>
            <td>{{ $p->price_excl_vat }}</td>
            <td>{{ $p->price_incl_vat }}</td>
            <td>{{ $p->status == 1 ? 'Active' : 'Inactive' }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="6" class="text-center text-danger">No products found</td>
        </tr>
    @endforelse
    </tbody>
</table>


        </div>

    </div>

</div>

@include('include/footer')
</body>
</html>
