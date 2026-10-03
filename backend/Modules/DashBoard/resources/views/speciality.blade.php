<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
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
                        <h5 class="card-title mb-0">Speciality</h5>
                        <button type="button" class="btn btn-primary customBtn" onclick="window.location.href='{{ url('dashboard/create-speciality') }}'">
                            Add New
                        </button>
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
                                <th scope="col">Sl No</th>
                                <th scope="col">Image</th>
                                <th scope="col">Name</th>
                                <th scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody class="customtable">
                            @if(is_array($data) && !empty($data))
                                @foreach($data as $index => $v)
                                    <tr>
                                        <td>#{{ $index + 1 }}</td>
                                        <td>
                                            @if (!empty($v['speciality_image']))
                                                <img src="{{ env('WEB_SERVICE_URL') . '/public/' . $v['speciality_image'] }}" style="width:80px"/>

                                            @endif
                                        </td>
                                        <td>{{$v['name']}}</td>
                                        <td><a href="{{ url('dashboard/edit-speciality') }}/{{$v['id']}}"><i class="me-2 mdi mdi-tooltip-edit" title="Edit"></i></a></td>
                                    </tr>
                                @endforeach
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

</div>
@include('include/footer')
</body>
</html>
