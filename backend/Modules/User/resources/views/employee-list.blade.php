<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body>
@include('include/header')
@include('include/left')

<div class="page-wrapper">

    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-12 d-flex no-block align-items-center">
                <h4 class="page-title">Employee Management</h4>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-md-4">
                <select id="filter-company" class="form-control select2">
                    <option value="">Filter by Company</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->company_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <input type="text" id="filter-name" class="form-control" placeholder="Search Employee Name">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary btn-block" onclick="loadEmployees()">Search</button>
            </div>

            <div class="col-md-2">
                <button class="btn btn-danger btn-block" onclick="resetSearch()">Reset</button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">Employee List</h5>
                    </div>

                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody id="employee-table"></tbody>
                    </table>

                    <div id="pagination"></div>

                </div>
            </div>
        </div>

    </div>

</div>

@include('include/footer')

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(".select2").select2();

function loadEmployees(page = 1) {
    let company_id = $("#filter-company").val();
    let employee_name = $("#filter-name").val();

    $.ajax({
        url: "{{ url('/user/employees') }}?page=" + page + "&company_id=" + company_id + "&employee_name=" + employee_name,
        method: "GET",
        success: function(response) {

            $("#employee-table").html("");

            if (response.data.length === 0) {
                $("#employee-table").html(`<tr><td colspan="7" class="text-center text-danger">No employees found</td></tr>`);
                return;
            }

            $.each(response.data, function(i, e) {
                $("#employee-table").append(`
                    <tr>
                        <td>${e.id}</td>
                        <td>${e.full_name ?? '-'}</td>
                        <td>${e.email}</td>
                        <td>${e.phone_number ?? '-'}</td>
                        <td>${e.company_name ?? '-'}</td>
                        <td>${e.status == 1 ? '<span class="badge badge-success">Active</span>' :
                            '<span class="badge badge-danger">Inactive</span>'}</td>
                        <td>
                            <a href="{{ url('/user/view-employee') }}/${e.id}" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                `);
            });

            let pagination = `<ul class="pagination">`;
            for (let i = 1; i <= response.list.last_page; i++) {
                pagination += `
                    <li class="page-item ${response.list.current_page == i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadEmployees(${i})">${i}</a>
                    </li>`;
            }
            pagination += `</ul>`;
            $("#pagination").html(pagination);
        }
    });
}

function resetSearch(){
    $("#filter-company").val("").trigger('change');
    $("#filter-name").val("");
    loadEmployees();
}

$(document).ready(function () {
    loadEmployees();
});
</script>

</body>
</html>
