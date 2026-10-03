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
                <h4 class="page-title">List of the created temporary profiles</h4>
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
            <div class="col-md-12">
                <div class="card">

                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">List of the created temporary profiles</h5>
                        <button type="button" class="btn btn-primary customBtn"
                                onclick="window.location.href='{{ url('/doctor/create') }}'">
                            Create New
                        </button>
                    </div>
                        <div class="row">
                            <!-- column -->
                            <div class="col-lg-12">
                                <form id="search-form" class="form-inline mb-3">
                                    <div class="form-group">
                                        <input type="text" name="search" id="search" placeholder="Search..." class="form-control mr-2">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Search</button>



								<div class="form-group">
									<div class="col-sm-9">
										<select id="city" name="city" class="form-control mr-2">
											<option value="">Select Province</option>
											@foreach($cities as $city)
												<option value="{{ $city->id }}">{{ $city->name }}</option>
											@endforeach
										</select>
									</div>
								</div>

								<div class="form-group">
									<div class="col-sm-9">
										<select name="speciality" id="speciality" class="form-control mr-2">
											<option value="">Select Speciality</option>
											@foreach($speciality as $spec)
												<option value="{{$spec['id']}}">{{$spec['name']}}</option>
											@endforeach
										</select>
									</div>
								</div>
                                </form>

                                <table class="table table-striped">
                                    <thead>
                                    <tr>
                                        {{--                                            <th><a href="#" class="sort" data-sort="first_name">First Name</a></th>--}}
                                        {{--                                            <th><a href="#" class="sort" data-sort="last_name">Last Name</a></th>--}}
                                        {{--                                            <th><a href="#" class="sort" data-sort="email">Email</a></th>--}}
                                        {{--                                            <th><a href="#" class="sort" data-sort="postal_code">Code Postal Cabin</a></th>--}}
                                        {{--                                            <th><a href="#" class="sort" data-sort="cell_phone">Cell Phone</a></th>--}}
                                        {{--                                            <th><a href="#" class="sort" data-sort="speciality">Speciality</a></th>--}}
                                        <th>Image</th>
										<th>Standard Message</th>
                                        <th>First Name</th>
                                        <th>Last Name</th>

										<th>Province</th>
                                        <th>Municipality</th>


                                        <th>Cabin Address</th>
                                        <th>Speciality</th>
<!--										<th>Appointment List</th>-->
                                        <th style="width: 10%">Action</th>
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



    </div>

</div>

</div>
@include('include/footer')
<script>
    $(document).ready(function() {
    fetch_data();

    // Fetch data with filters
    function fetch_data(page = 1, sortBy = 'id', sortDirection = 'asc', search = '', city = '', speciality = '') {
        document.getElementById('cover-spin').style.display = 'block';
        $.ajax({
            url: "{{URL::to('/doctor/get-all-list')}}",
            method: 'GET',
            data: {
                page: page,
                sortBy: sortBy,
                sortDirection: sortDirection,
                search: search,
                city: city,       // Send city filter
                speciality: speciality // Send speciality filter
            },
            success: function(response) {
                $('#doctor-requests-table').html('');
                if (response.list.data.length > 0) {
                    $.each(response.list.data, function(index, request) {
                        $('#doctor-requests-table').append(
                            '<tr id="tr_' + request.id + '">' +
                            '<td><img src="{{env('WEB_SERVICE_URL')}}/public/' + request.profile_pic + '" style="width:80px"/></td>' +
                            '<td>' + request.standard_message_subject + '</td>' +
							'<td>' + request.first_name + '</td>' +
							 '<td>' + request.last_name + '</td>' +
							 '<td>' + request.city_name + '</td>' +
                            '<td>' + request.municipality_name + '</td>' +
                            '<td>' + request.cabin_address + '</td>' +
                            '<td>' + request.speciality_name + '</td>' +
                            '<td>' +
                            '<a href="{{ env('MAIN_URL') }}/search/doctor-details/'+request.slug+'?types=1" target="_blank">' +
                            '<i class="me-2 mdi mdi-eye" style="color: green; font-size: 1.2rem;" title="View Details"></i>' +
                            '</a> ' +
                            '<a href="javascript:void(0)" onclick="removeRequest(' + request.id + ')"><i class="me-2 mdi mdi-close-circle" style="color: red; font-size: 1.2rem;" title="remove"></i></a> ' +
							'<a href="{{ url('/doctor/edit')}}/'+request.id+'" ><i class="me-2 mdi mdi-pencil" style="color: blue; font-size: 1.2rem;" title="Edit"></i></a></td> ' +
                            '</tr>'
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

    // Handle search form submission
    $('#search-form').on('submit', function(e) {
        e.preventDefault();
        var search = $('#search').val();
        var city = $('#city').val();  // Get selected city
        var speciality = $('select[name="speciality"]').val();  // Get selected speciality
        fetch_data(1, 'id', 'asc', search, city, speciality);
    });

    // Handle change in city dropdown
    $('#city').on('change', function() {
        var city = $(this).val();
        var search = $('#search').val();
        var speciality = $('select[name="speciality"]').val(); // Get selected speciality
        fetch_data(1, 'id', 'asc', search, city, speciality);
    });

    // Handle change in speciality dropdown
    $('select[name="speciality"]').on('change', function() {
        var speciality = $(this).val();
        var search = $('#search').val();
        var city = $('#city').val();  // Get selected city
        fetch_data(1, 'id', 'asc', search, city, speciality);
    });

    // Handle pagination
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        var sortBy = $('.sort').data('sort');
        var sortDirection = $('.sort').data('direction');
        var search = $('#search').val();
        var city = $('#city').val();       // Get selected city
        var speciality = $('select[name="speciality"]').val(); // Get selected speciality
        fetch_data(page, sortBy, sortDirection, search, city, speciality);
    });
});


    function removeRequest(id)
    {
        Swal.fire({
            title: 'Are you sure?',
            text: ``,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{URL::to('/doctor/delete-doctor-admin')}}",
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


</script>
</body>
</html>
