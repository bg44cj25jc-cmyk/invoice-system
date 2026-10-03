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
                <h4 class="page-title">Company List</h4>
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <div class="row">
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">Company List</h5>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
<div class="row px-3 mt-3">
    <div class="col-md-4">
        <input type="text" id="search_company" class="form-control"
               placeholder="Search by Company name or Email">
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary" onclick="loadCompanies()">Search</button>
    </div>
</div>

                            <table class="table table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Company Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Country</th>
                                    <th>City</th>
                                    <th>Active Plan</th>
									<th>Plan Expiry Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                                </thead>

                                <tbody id="company-table"></tbody>

                            </table>
							<div id="pagination"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>



@include('include/footer')
<script>

function loadCompanies(page = 1) {
    let search = $("#search_company").val();

    $.ajax({
        url: "{{ url('/user/companies') }}?page=" + page + "&search=" + search,
        method: "GET",
        success: function(response) {

            $("#company-table").html("");

            if(response.data.length === 0){
                $("#company-table").html(
                    `<tr><td colspan="10" class="text-center text-danger">No companies found</td></tr>`
                );
                $("#pagination").html("");
                return;
            }

            $.each(response.data, function(i, c) {
                $("#company-table").append(`
                    <tr>
                        <td>${c.id}</td>
                        <td>${c.company_name ?? '-'}</td>
                        <td>${c.email}</td>
                        <td>${c.phone_number ?? '-'}</td>
                        <td>${c.country_details?.name ?? '-'}</td>
                        <td>${c.city ?? '-'}</td>
                        <td>${c.active_plan_name ?? '-'}</td>
                        <td>${c.plan_expiry_date ?? '-'}</td>
                        <td>
                            <button class="btn btn-sm toggle-status" data-id="${c.id}" data-status="${c.status}">
                                ${c.status == 1 
                                    ? '<span class="badge badge-success">Active</span>' 
                                    : '<span class="badge badge-danger">Inactive</span>'}
                            </button>
                        </td>
                        <td>
                            <a href="{{ url('/user/company-details') }}/${c.id}" class="btn btn-sm btn-primary">
                                View
                            </a>
                        </td>
                    </tr>
                `);
            });

            let pagination = `<ul class="pagination">`;
            for (let i = 1; i <= response.list.last_page; i++) {
                pagination += `
                    <li class="page-item ${response.list.current_page == i ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="loadCompanies(${i})">${i}</a>
                    </li>`;
            }
            pagination += `</ul>`;
            $("#pagination").html(pagination);
        }
    });
}

$(document).ready(function() {
    loadCompanies();

    $("#search_company").keyup(function () {
        loadCompanies();
    });
});


$(document).on("click", ".toggle-status", function () {
    let id = $(this).data("id");
    let currentStatus = $(this).data("status");

    $.ajax({
        url: "{{ url('/user/company/status-update') }}",
        type: "POST",
        data: {
            id: id,
            status: currentStatus,
            _token: "{{ csrf_token() }}"
        },
        success: function (response) {
            if (response.status) {
                loadCompanies(); 
            } else {
                alert(response.message);
            }
        }
    });
});

// run function on page load
$(document).ready(function() {
    loadCompanies();
});
</script>

</body>
</html>
