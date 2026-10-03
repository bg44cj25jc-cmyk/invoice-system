<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
{{--    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">--}}
</head>

<body>
@include('include/header')

@include('include/left')

<div class="page-wrapper">


    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">Medicine</h5>
                        <button type="button" class="btn btn-primary customBtn" onclick="window.location.href='{{ url('dashboard/create-medicine') }}'">
                            Add New
                        </button>
                        <form method="GET" class="d-inline-block">
                            <input type="text" name="search" placeholder="Search by Name or Brand" class="form-control" style="display:inline-block; width:auto;">
                            <button type="submit" class="btn btn-secondary">Search</button>
                        </form>
                        <form method="POST" action="{{ url('dashboard/upload-csv') }}" enctype="multipart/form-data" class="d-inline-block ms-3">
                            @csrf
                            <input type="file" name="csv_file" accept=".csv" class="form-control" style="display:inline-block; width:auto;">
                            <button type="submit" class="btn btn-success">Upload CSV</button>
                        </form>
                        <form method="POST" action="{{ url('dashboard/bulk-delete') }}" id="bulkDeleteForm">
                            @csrf
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selected medicines?')">Delete Selected</button>
                        </form>
                        <form method="POST" action="{{ url('dashboard/delete-all-medicines') }}" class="d-inline-block ms-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete ALL medicines? This action cannot be undone.')">
                                Delete All Medicines
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        @if(session()->has('success'))
                            <div class="alert alert-success">
                                {{ session()->get('success') }}
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">
                                @foreach ($errors->all() as $error)
                                    {{ $error }}
                                @endforeach
                            </div>
                        @endif

                        <table class="table">
                            <thead class="thead-light">
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th scope="col">Sl No</th>
                                <th scope="col">Name</th>
                                <th scope="col">Brand</th>
                                <th scope="col">Dosage</th>
                                <th scope="col">Packaging</th>
                                <th scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody class="customtable">
                            @if($data->count() > 0)
                                @foreach($data as $index => $v)
                                    <tr>
                                        <td><input type="checkbox" name="ids[]" value="{{ $v->id }}" form="bulkDeleteForm"></td>
                                        <td>#{{ $data->firstItem() + $index }}</td>
                                        <td>{{$v->name}}</td>
                                        <td>{{$v->brand}}</td>
                                        <td>{{$v->dosage}}</td>
                                        <td>{{$v->packaging}}</td>
                                        <td>
                                            <a href="{{ url('dashboard/edit-medicine') }}/{{$v->id}}">
                                                <i class="me-2 mdi mdi-tooltip-edit" title="Edit"></i>
                                            </a>
                                            <a href="{{ url('dashboard/delete-medicine/' . $v->id) }}" onclick="return confirm('Are you sure?')">
                                                <i class="me-2 mdi mdi-delete" title="Delete"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center">No data available</td>
                                </tr>
                            @endif
                            </tbody>
                        </table>

                        <!-- Pagination Links -->
                            <div class="d-flex justify-content-center mt-4">
                                {{ $data->links('pagination::bootstrap-4') }}
                            </div>

                    </div>

                </div>
            </div>
        </div>

    </div>

</div>

</div>
@include('include/footer')
<script>
    document.getElementById('selectAll').addEventListener('click', function(e) {
        const checkboxes = document.querySelectorAll('input[name="ids[]"]');
        checkboxes.forEach(checkbox => checkbox.checked = e.target.checked);
    });
</script>

</body>
</html>
