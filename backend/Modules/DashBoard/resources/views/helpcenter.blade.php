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
                        <h5 class="card-title mb-0">Help Center</h5>

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
                                <th scope="col">Type</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone No</th>
                                <th scope="col">Subject</th>
                                <th scope="col">Action</th>
                            </tr>
                            </thead>
                            <tbody class="customtable">
                            @if(is_array($data) && !empty($data))
                                @foreach($data as $index => $v)
                                    <tr id="tr_{{$v->id}}">
                                        <td>{{$v->types}}</td>
                                        <td>{{$v->name}}</td>
                                        <td>{{$v->email}}</td>
                                        <td>{{$v->phno}}</td>
                                        <td>{{$v->subject}}</td>
                                        <td><a href="javascript:void(0)" onclick="removeRequest({{$v->id}})"><i class="me-2 mdi mdi-delete" title="Delete"></i></a></td>
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

<script>
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
                    url: "{{URL::to('/dashboard/delete-help')}}",
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
