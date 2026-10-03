<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
<style>
    .trumbowyg {
        width: 800px !important; /* Change to your desired width */
        height: 300px !important; /* Change to your desired height */

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
							<h5 class="card-title mb-0">Standard messages </h5>
<!--
							<button type="button" class="btn btn-primary customBtn"
									onclick="window.location.href='{{ url('/doctor/doctor-list-admin') }}'">
								Back
							</button>
-->
						</div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">

                                	<form class="form-horizontal" action="{{ url('/dashboard/standard-message') }}" method="post">

										@csrf
										@if($errors->any())
											<div class="alert alert-danger">
												@foreach ($errors->all() as $error)
													{{ $error }}
												@endforeach
											</div>
										@endif
										@if(session()->has('success'))
											<div class="alert alert-success">
												{{ session()->get('success') }}
											</div>
										@endif

										@foreach($data as $index => $message)

										@if($message['subject']==2)

										@endif
											<div class="form-group row" style="margin-top: 30px">
												<label class="col-sm-3 text-end control-label col-form-label">{!! $message['subject'] !!}</label>

												@if($message['subject']==2)
													<input type="text" value="" name="">
												@endif

												<div class="col-sm-9">
													<textarea name="messages[{{ $message['id'] }}]" id="lang_en{{ $index }}" rows="10" cols="20">{!! $message['lang_en'] !!}</textarea>
												</div>
											</div>
										@endforeach


										<div class="form-group row">
											<label class="col-sm-3 text-end control-label col-form-label">Suspended message </label>
											<div class="col-sm-9">
												<select id="standard_message" name="standard_message" class="form-control">
													<option value="">Select message</option>
													@foreach($standard_message as $val)
														<option value="{{ $val->id }}" {{$val->suspended == 1 ? 'selected' : ''}} >{{ $val->subject }}</option>
													@endforeach
												</select>
											</div>
										</div>


										<div class="form-group row">
											<label class="col-sm-3 text-end control-label col-form-label">Default temporary profile message </label>
											<div class="col-sm-9">
												<select id="profile_message" name="profile_message" class="form-control">
													<option value="">Select message</option>
													@foreach($standard_message as $val)
														<option value="{{ $val->id }}" {{$val->profile == 1 ? 'selected' : ''}} >{{ $val->subject }}</option>
													@endforeach
												</select>
											</div>
										</div>


										<div class="border-top">
											<div class="card-body">
												<button type="submit" class="btn btn-primary" style="float:right;margin-top: 30px">
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

</div>
@include('include/footer')

<script src="https://cdn.jsdelivr.net/npm/trumbowyg@2.27.3/dist/trumbowyg.min.js"></script>
<script>



    $(document).ready(function () {
        @foreach($data as $index => $message)
            $('#lang_en{{ $index }}').trumbowyg();
        @endforeach
    });
</script>
</body>
</html>
