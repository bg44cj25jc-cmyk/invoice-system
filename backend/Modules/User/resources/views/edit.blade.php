<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
    <style>
        #map {
            height: 500px;
            width: 800px;
        }
        #details {
            margin-top: 20px;
        }
    </style>
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
                        <h5 class="card-title mb-0">Edit Doctor</h5>
                        <button type="button" class="btn btn-primary customBtn"
                                onclick="window.location.href='{{ url('/doctor/doctor-list-admin') }}'">
                            Back
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <form action="{{ url('/doctor/edit/'.$doctor->id) }}" method="POST" enctype="multipart/form-data">
    								@csrf

                                    <div class="card-body">
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
                                        <div class="form-group row">
                                            <label class="col-sm-3 text-end control-label col-form-label">First Name</label>
                                            <div class="col-sm-9">
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        name="first_name"
                                                        placeholder=""
													    value="{{ $doctor->first_name }}"
                                                />
                                            </div>
                                        </div>
                                            <div class="form-group row">
                                                <label class="col-sm-3 text-end control-label col-form-label">Last Name</label>
                                                <div class="col-sm-9">
                                                    <input
                                                            type="text"
                                                            class="form-control"
                                                            name="last_name"
                                                            placeholder=""
														    value="{{ $doctor->last_name }}"
                                                    />
                                                </div>
                                            </div>
											<div class="form-group row">
                                                <label class="col-sm-3 text-end control-label col-form-label">Phone number 1</label>
                                                <div class="col-sm-9">
                                                    <input
                                                            type="text"
                                                            class="form-control"
                                                            name="cell_phone1"
                                                            placeholder=""
														    value="{{ $doctor->cell_phone1 }}"
                                                    />
                                                </div>
                                            </div>
											<div class="form-group row">
                                                <label class="col-sm-3 text-end control-label col-form-label">Phone number 2</label>
                                                <div class="col-sm-9">
                                                    <input
                                                            type="text"
                                                            class="form-control"
                                                            name="cell_phone2"
                                                            placeholder=""
														    value="{{ $doctor->cell_phone2 }}"
                                                    />
                                                </div>
                                            </div>


												@if (!empty($doctor->profile_pic))
												<div class="form-group row">
													<div class="col-sm-9">
													<label class="col-sm-3 text-end control-label col-form-label">Image</label>
													<img src="{{ env('WEB_SERVICE_URL') . '/public/' . $doctor->profile_pic }}" style="width:80px"/>
													</div>
												</div>
												@endif

                                            <div class="form-group row">
                                                <label class="col-sm-3 text-end control-label col-form-label">Profile Image</label>
                                                <div class="col-sm-9">
                                                    <input
                                                            type="file"
                                                            class="form-control"
                                                            name="profile_pic"
                                                    />
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 text-end control-label col-form-label">Speciality</label>
                                                <div class="col-sm-9">
                                                    <select name="speciality" class="form-control">
                                                        <option value="">Select</option>
                                                        @foreach($speciality as $spec)
                                                        <option value="{{$spec['id']}}" {{$doctor->speciality == $spec['id'] ? 'selected' : ''}}>{{$spec['name']}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
										<div class="form-group row">
												<label class="col-sm-3 text-end control-label col-form-label">Displayed message </label>
												<div class="col-sm-9">
													<select id="standard_message" name="standard_message" class="form-control">
														<option value="">Select message</option>
														@foreach($standard_message as $val)
															<option value="{{ $val->id }}" {{$doctor->standard_message == $val->id ? 'selected' : ''}} >{{ $val->subject }}</option>
														@endforeach
													</select>
												</div>
											</div>

										<div class="form-group row">
											<label class="col-sm-3 text-end control-label col-form-label">City</label>
											<div class="col-sm-9">
												<select id="city" name="city" class="form-control">
													<option value="">Select City</option>
													@foreach($cities as $city)
														<option value="{{ $city->id }}" {{$doctor->city == $city->id ? 'selected' : ''}}   >{{ $city->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

										<div class="form-group row">
											<label class="col-sm-3 text-end control-label col-form-label">Municipality</label>
											<div class="col-sm-9">
												<select id="municipality" name="municipality" class="form-control">
													<option value="">Select Municipality</option>
													@foreach($municipalities as $val)
														<option value="{{ $val->id }}" {{$doctor->municipality == $val->id ? 'selected' : ''}}   >{{ $val->name }}</option>
													@endforeach
												</select>
											</div>
										</div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 text-end control-label col-form-label">Cabin Address</label>
                                                <div class="col-sm-9">
                                                    <input id="autocomplete"
                                                            type="text"
                                                            class="form-control"
                                                            name="cabin_address"
                                                            placeholder=""
														    value="{{ $doctor->cabin_address }}"
                                                    />


                                                </div>



                                            </div>

                                            <div class="form-group row" style="display: block">
                                                <label style="float: left;" class="col-sm-3 text-end control-label col-form-label">Co-Ordinate</label>
                                            <div class="col-sm-9 d-flex">


                                                <input id="lat"
                                                       type="text"
                                                       class="form-control"
                                                       name="lat"
                                                       placeholder="Latitude" style="width: 50%"
													    value="{{ $doctor->lat }}"
                                                />

                                                <input id="lng"
                                                       type="text"
                                                       class="form-control"
                                                       name="lng"
                                                       placeholder="Longitude" style="width: 50%;margin-left: 10px"
													    value="{{ $doctor->lng }}"
                                                />
                                            </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 text-end control-label col-form-label">Maps</label>
                                                <div class="col-sm-9">
                                                    <div id="map"></div>
                                                </div>
                                            </div>

                                    </div>
                                    <div class="border-top">
                                        <div class="card-body">
                                            <button type="submit" class="btn btn-primary" style="float:right;">
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </form>
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
@if(config('services.google_maps.key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google_maps.key')) }}&libraries=places"></script>
@endif
<script>


$(document).ready(function() {
    $('#city').change(function() {
        var cityId = $(this).val();
        if (cityId) {
            $.ajax({
                url: '/tabsbook/backend/doctor/get-municipalities/' + cityId,

				//url:"https://updates.properwebtechnologies.co.in/tabsbook/backend/doctor/get-municipalities/1",
                type: 'GET',
                success: function(data) {
                    $('#municipality').empty(); // Clear existing options
                    $('#municipality').append('<option value="">Select Municipality</option>');
                    $.each(data, function(key, municipality) {
                        $('#municipality').append('<option value="' + municipality.id + '">' + municipality.name + '</option>');
                    });
                }
            });
        } else {
            $('#municipality').empty();
            $('#municipality').append('<option value="">Select Municipality</option>');
        }
    });
});







@if(config('services.google_maps.key'))
document.getElementById('lat').addEventListener('input', updateAddressFromLatLng);
document.getElementById('lng').addEventListener('input', updateAddressFromLatLng);

function updateAddressFromLatLng() {
    var lat = document.getElementById("lat").value;
    var lng = document.getElementById("lng").value;

    if (lat && lng) {
        var geocoder = new google.maps.Geocoder();
        var latlng = { lat: parseFloat(lat), lng: parseFloat(lng) };

        geocoder.geocode({ location: latlng }, function (results, status) {
            if (status === "OK") {
                if (results[0]) {
                    //document.getElementById("autocomplete").value = results[0].formatted_address;
                } else {
                    console.log("No results found");
                }
            } else {
                console.log("Geocoder failed due to: " + status);
            }
        });
    }
}


    function initAutocomplete() {
        var autocomplete = new google.maps.places.Autocomplete(
            document.getElementById('autocomplete'), {
                types: ['address'],
                componentRestrictions: { country: 'DZ' }  // Restrict to Algeria
            }
        );

        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();

            if (!place.geometry) {
                console.log("No details available for input: '" + place.name + "'");
                return;
            }

            var address = place.formatted_address;
            var lat = place.geometry.location.lat();
            var lng = place.geometry.location.lng();

            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
        });
    }


    function initMap() {
        // Create the map
        var map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: 36.75, lng: 3.06 },  // Center of Algeria
            zoom: 11
        });

        // Create the Geocoder
        var geocoder = new google.maps.Geocoder();

        // Add click event listener
        map.addListener('click', function(event) {
            var latLng = event.latLng;
            geocoder.geocode({ 'location': latLng }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        var address = results[0].formatted_address;

                        var lat = latLng.lat();
                        var lng = latLng.lng();

                        // Update textboxes
                        document.getElementById('autocomplete').value = address;
                        document.getElementById('lat').value = lat;
                        document.getElementById('lng').value = lng;
                    } else {
                        console.log('No results found');
                    }
                } else {
                    console.log('Geocoder failed due to: ' + status);
                }
            });
        });
    }

    function getAddress(lat, lng) {
        var geocoder = new google.maps.Geocoder();
        var latlng = { lat: parseFloat(lat), lng: parseFloat(lng) };

        geocoder.geocode({ location: latlng }, function (results, status) {
            if (status === "OK") {
                if (results[0]) {
                    console.log(results[0].formatted_address); // Display the address
                    document.getElementById("address").innerText = results[0].formatted_address;
                } else {
                    console.log("No results found");
                }
            } else {
                console.log("Geocoder failed due to: " + status);
            }
        });
    }

    function showAddress() {
        var lat = document.getElementById("lat").value;
        var lng = document.getElementById("lng").value;
        getAddress(lat, lng);
    }

    function initialize() {
        initAutocomplete();
        initMap();
    }

    window.onload = initialize;
@endif

</script>
</body>
</html>
