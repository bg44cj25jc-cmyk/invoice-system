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
                    <h4 class="page-title">Dashboard</h4>
                    <div class="ms-auto text-end">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Library
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">

            <div class="row">
                <!-- Column -->
                <?php /*?><div class="col-md-6 col-lg-2 col-xlg-3">
                    <div class="card card-hover">
                        <div class="box bg-cyan text-center">
                            <h1 class="font-light text-white">
                                <i class="mdi mdi-view-dashboard"></i>
                            </h1>
                            <h6 class="text-white">Total Doctor({{count($doctor_list)}})</h6>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-4 col-xlg-3">
                    <div class="card card-hover">
                        <div class="box bg-success text-center">
                            <h1 class="font-light text-white">
                                <i class="mdi mdi-chart-areaspline"></i>
                            </h1>
                            <h6 class="text-white">Total Patient({{count($patient_list)}})</h6>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-2 col-xlg-3">
                    <div class="card card-hover">
                        <div class="box bg-warning text-center">
                            <h1 class="font-light text-white">
                                <i class="mdi mdi-collage"></i>
                            </h1>
                            <h6 class="text-white">Total Nurse({{count($nurse_list)}})</h6>
                        </div>
                    </div>
                </div>
                <!-- Column -->
                <div class="col-md-6 col-lg-2 col-xlg-3">
                    <div class="card card-hover">
                        <div class="box bg-danger text-center">
                            <h1 class="font-light text-white">
                                <i class="mdi mdi-border-outside"></i>
                            </h1>
                            <h6 class="text-white">Total Appointment({{count($appointments_list)}})</h6>
                        </div>
                    </div>
                </div>
                <!-- Column -->
                <div class="col-md-6 col-lg-2 col-xlg-3">
                    <div class="card card-hover">
                        <div class="box bg-info text-center">
                            <h1 class="font-light text-white">
                                <i class="mdi mdi-arrow-all"></i>
                            </h1>
                            <h6 class="text-white">Total Tomporary Doctor({{count($temp_doctor_list)}})</h6>
                        </div>
                    </div>
                </div><?php */?>
                <!-- Column -->
                <!-- Column -->
                

            </div>
	{{-- 
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-md-flex align-items-center">
                                <div>
                                    <h4 class="card-title">Practitioner Contact List</h4>

                                </div>
                            </div>
                            <div class="row">
                                <!-- column -->
                                <div class="col-lg-12">
                                    <form id="search-form" class="form-inline mb-3">
                                        <div class="form-group">
                                            <input type="text" name="search" id="search" placeholder="Search..." class="form-control mr-2">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </form>
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>


                                            <th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Email</th>
                                            <th>Code Postal Cabin</th>
                                            <th>Cell Phone</th>
                                            <th>Speciality</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody id="doctor-requests-table">
                                        </tbody>
                                    </table>

                                    <nav>
                                        <ul class="pagination" id="pagination">
                                            <!-- AJAX pagination links will be loaded here -->
                                        </ul>
                                    </nav>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
--}}


        </div>

    </div>

</div>
@include('include/footer')
<script>
    $(document).ready(function() {
        //fetch_data();

        function fetch_data(page = 1, sortBy = 'id', sortDirection = 'asc', search = '') {
            document.getElementById('cover-spin').style.display = 'block';
            $.ajax({
                url: "{{URL::to('/dashboard/doctor-request')}}",
                method: 'GET',
                data: {
                    page: page,
                    sortBy: sortBy,
                    sortDirection: sortDirection,
                    search: search
                },
                success: function(response) {
                    console.log(response);

                    $('#doctor-requests-table').html('');
                    if (response.list.data.length > 0) {
                        $.each(response.list.data, function(index, request) {
                            $('#doctor-requests-table').append(
                                '<tr id="tr_'+request.id+'">' +
                                '<td>' + request.first_name + '</td>' +
                                '<td>' + request.last_name + '</td>' +
                                '<td>' + request.email + '</td>' +
                                '<td>' + request.postal_code + '</td>' +
                                '<td>' + request.cell_phone + '</td>' +
                                '<td>' + request.speciality_name + '</td>' +
                                '<td>' +
                                '<a href="javascript:void(0)" onclick="removeRequest('+request.id+')">' +
                                '<i class="me-2 mdi mdi-delete-forever" style="color: red;" title="Delete"></i></a> ' +
                                '<a href="javascript:void(0)" onclick="approve('+request.id+')" title="Approve">' +
                                '<i class="me-2 mdi mdi-check" style="color: green; size: xl"></i></a>' +
                                '</td></tr>'
                            );
                        });
                    } else {
                        $('#doctor-requests-table').append('<tr><td colspan="7" align="center">No record found</td></tr>');
                    }

                    $('#pagination').html('');
                    if (response.list.last_page > 1) {
                        for (var i = 1; i <= response.list.last_page; i++) {
                            var active = response.list.current_page === i ? 'active' : '';
                            $('#pagination').append('<li class="page-item ' + active + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
                        }
                    }
                    document.getElementById('cover-spin').style.display = 'none';
                }
            });
        }

        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            var search = $('#search').val();
            fetch_data(1, 'id', 'asc', search);
        });

        $(document).on('click', '.sort', function(e) {
            e.preventDefault();
            var sortBy = $(this).data('sort');
            var sortDirection = $(this).data('direction') === 'asc' ? 'desc' : 'asc';
            $(this).data('direction', sortDirection);
            fetch_data(1, sortBy, sortDirection);
        });

        $(document).on('click', '.page-link', function(e) {
            e.preventDefault();
            var page = $(this).data('page');
            var sortBy = $('.sort').data('sort');
            var sortDirection = $('.sort').data('direction');
            var search = $('#search').val();
            fetch_data(page, sortBy, sortDirection, search);
        });
    });

    function removeRequest(id)
    {
        Swal.fire({
            title: 'Are you sure?',
            text: `You won't be able to revert this request again`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{URL::to('/dashboard/delete-request')}}",
                    method: 'POST',
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        id: id
                    },
                    success: function(response) {
                        var row = document.getElementById("tr_"+id);
                        row.remove();
                    }
                });

            }
        });
    }

    function approve(id)
    {


        Swal.fire({
            title: 'Are you sure, You want to approve this doctor?',
            text: ``,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, approve it!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('cover-spin').style.display = 'block';

                $.ajax({
                    url: "{{URL::to('/dashboard/approve-request')}}",
                    method: 'POST',
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        id: id
                    },
                    success: function(response) {
                        document.getElementById('cover-spin').style.display = 'none';
                        document.getElementById("myModal").style.display = 'block';
                        document.getElementById("alertText").innerText = 'Approved Successfully!';
                        var row = document.getElementById("tr_"+id);
                        row.remove();

                    }
                });

            }
        });
    }


</script>
</body>
</html>
