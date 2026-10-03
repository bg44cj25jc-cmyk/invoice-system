<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
</head>

<body>
@include('include/header')

@include('include/left')

    <div class="page-wrapper">

        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-12 d-flex no-block align-items-center">
                    <h4 class="page-title">Received requests</h4>
                    <div class="ms-auto text-end">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="#">Received requests page</a></li>
<!--
                                <li class="breadcrumb-item active" aria-current="page">
                                    Library
                                </li>
-->
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
                        <div class="card-body">
                            <div class="d-md-flex align-items-center">
                                <div>
                                    <h4 class="card-title">Practitioner Contact List</h4>

                                </div>
                            </div>
                            <div class="row">

								@if($errors->any())
                                            <div class="alert alert-danger">
                                                @foreach ($errors->all() as $error)
                                                    <p>{{ $error }}</p>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if(session()->has('success'))
                                            <div class="alert alert-success">
                                                {{ session()->get('success') }}
                                            </div>
                                        @endif

								<div id="messageContainer" class="alert alert-success" style="display: none; color: green;"></div>
                                <!-- column -->
                                <div class="col-lg-12">
                                    <form id="search-form" class="form-inline mb-3">
                                        {{-- <div class="form-group">
                                            <input type="text" name="search" id="search" placeholder="Search..." class="form-control mr-2">
                                        </div> --}}

										<div class="form-group mr-2">
											<input type="text" name="request_date" id="request_date" placeholder="Request Date" class="form-control datepicker">
										</div>

										<!-- Postal Code Dropdown -->
										<div class="form-group mr-2">
											<select name="postal_code" id="postal_code" class="form-control">
												<option value="">Select Postal Code</option>
												@foreach($postalCodes as $code)
													<option value="{{ $code['postal_code'] }}">{{ $code['postal_code'] }}</option>
												@endforeach
											</select>
										</div>

										<!-- Status Dropdown -->
										<div class="form-group mr-2">
											<select name="status" id="status" class="form-control">
												<option value="">Select Status</option>
												<option value="0">Waiting</option>
												<option value="1">Contacted</option>
												<option value="2">Visited</option>
												<option value="3">Revisit later</option>
												<option value="4">Failed for now</option>
												<option value="5">Subscribed (close)</option>
											</select>
										</div>

                                        <button type="submit" class="btn btn-primary">Search</button>
                                    </form>



                                    <table class="table table-striped">
                                        <thead>
                                        <tr>

                                            <th>Request date</th>
											<th>First Name</th>
                                            <th>Last Name</th>
                                            <th>Cell Phone</th>
                                            <th>Email</th>
                                            <th>Code Postal</th>
											<th>Province</th>
                                        	<th>Municipality</th>
                                            {{--<th>Speciality</th>--}}
											<th>Status</th>
											<th>Related comment</th>
<!--                                            <th>Action</th>-->
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
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
$(document).ready(function() {
	fetch_data();
function fetch_data(page = 1, sortBy = 'id', sortDirection = 'asc', search = '', request_date = '', postal_code = '', status = '') {
	document.getElementById('cover-spin').style.display = 'block';
	$.ajax({
		url: "{{URL::to('/dashboard/doctor-request')}}",
		method: 'GET',
		//data: { page: page, sortBy: sortBy, sortDirection: sortDirection, search: search },
		data: { page, sortBy, sortDirection, search, request_date, postal_code, status },
		success: function(response) {
			$('#doctor-requests-table').html('');
			if (response.list.data.length > 0) {
				$.each(response.list.data, function(index, request) {

//					let createdAt = new Date(request.created_at.replace(' ', 'T'));
//let options = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true };
//let formattedDateTime = createdAt.toLocaleString('en-GB', options).replace(',', '');
//
//console.log(formattedDateTime);

//let createdAt = new Date('2025-02-10T19:39:18Z'); // Append 'Z' to indicate UTC
					let dateString = request.created_at.replace(' ');
					//console.log(dateString);
					let createdAt = new Date(dateString);
					let options = {
  weekday: 'long',
  day: '2-digit',
  month: 'long',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
  hour12: true,
  timeZone: 'UTC' // Explicitly set the timezone to UTC
};

let formattedDateTime = createdAt.toLocaleString('en-GB', options).replace(',', '');

//console.log(formattedDateTime);


					$('#doctor-requests-table').append(
						`<tr id="tr_${request.id}">
							<td>${formattedDateTime}</td>
							<td>${request.first_name}</td>
							<td>${request.last_name}</td>
							<td>${request.cell_phone}</td>
							<td>${request.email}</td>
							<td>${request.postal_code}</td>
							<td>${request.city_name}</td>
							<td>${request.municipality_name}</td>
							<td>
								<select name="status" class="status-dropdown" data-id="${request.id}">
									<option value="0" ${request.status == 0 ? 'selected' :''}  ${request.status > 0 ? 'disabled' : ''}>Waiting</option>
									<option value="1" ${request.status == 1 ? 'selected' : ''}  ${request.status > 1 ? 'disabled' : ''}>Contacted</option>
									<option value="2" ${request.status == 2 ? 'selected' : ''}  ${request.status > 2 ? 'disabled' : ''}>Visited</option>

									<option value="3" ${request.status == 3 ? 'selected' : ''} ${request.status > 3 ? 'disabled' : ''}>Revisit later</option>
									<option value="4" ${request.status == 4 ? 'selected' : ''} ${request.status > 4 ? 'disabled' : ''}>Failed for now</option>
									<option value="5" >Subscribed (close)</option>
								</select>
							</td>

<td>
    <textarea name="comment" class="comment-box" data-id="${request.id}">${request.comment ? decodeURIComponent(escape(request.comment)) : ''}</textarea>
    <button class="submit-comment btn btn-primary" data-id="${request.id}">Submit</button>
</td>

						</tr>`
					);
				});
			} else {
				$('#doctor-requests-table').append('<tr><td colspan="7" align="center">No record found</td></tr>');
			}

//			<td>
//								<a href="javascript:void(0)" onclick="removeRequest(${request.id})">
//									<i class="me-2 mdi mdi-delete-forever" style="color: red;"></i>
//								</a>
//								<a href="javascript:void(0)" onclick="approve(${request.id})">
//									<i class="me-2 mdi mdi-check" style="color: green;"></i>
//								</a>
//							</td>
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

	$(".datepicker").datepicker({
        dateFormat: "yy-mm-dd",
		changeMonth: true,
            changeYear: true,
    });

	$('#search-form').on('submit', function(e) {
            e.preventDefault();
            var request_date = $('#request_date').val();
			var postal_code = $('#postal_code').val();
			var status = $('#status').val();
			var search = $('#search').val();
			fetch_data(1, 'id', 'asc', search, request_date, postal_code, status);
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

    // Listen for status change
    $(document).on('change', '.status-dropdown', function() {
        let id = $(this).data('id');
        let status = $(this).val();
        updateStatus(id, status);
    });

    function updateStatus(id, status) {
		if (status == 5) {
			approve(id); // Call approve function if status is 3
		}else{
			 $.ajax({
				url: "{{URL::to('/dashboard/update-status')}}",
				method: 'POST',
				dataType: "json",
				headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
				data: { id: id, status: status },
				success: function(response) {
					if (response.success) {
						$("#messageContainer").text(response.message).show(); // Ensure the div exists
					}
				}
			});
		}
    }


	// Handle comment submission
    $(document).on('click', '.submit-comment', function() {
        let id = $(this).data('id');
        let comment = $(`textarea[data-id='${id}']`).val();

        $.ajax({
            url: "{{URL::to('/dashboard/update-comment')}}",
            method: 'POST',
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: { id: id, comment: comment },
            success: function(response) {
                Swal.fire("Success", "Comment updated successfully!", "success");
            }
        });
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
