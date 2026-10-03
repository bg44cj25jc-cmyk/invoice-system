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
        <h4 class="page-title">Employee Details</h4>
    </div>

    <div class="container-fluid">

        <div class="card p-3">

            <h3>{{ $employee->contact_person_name ?? '-' }}</h3>
            <p><strong>Email:</strong> {{ $employee->email }}</p>
            <p><strong>Phone:</strong> {{ $employee->phone_number ?? '-' }}</p>
            <p><strong>Status:</strong> {{ $employee->status == 1 ? 'Active' : 'Inactive' }}</p>
            <p><strong>Role:</strong> {{ ucfirst($employee->role) }}</p>

            <hr>

            <h4>Company List</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($employee->companies as $c)
                    <tr>
                        <td>{{ $c->id }}</td>
                        <td>{{ $c->company_name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->phone_number }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-danger">No companies assigned</td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            <a href="{{ url('/user/employee-list') }}" class="btn btn-secondary mt-3">Back to list</a>

        </div>

    </div>

</div>

@include('include/footer')
</body>
</html>
