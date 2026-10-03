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
                        <h5 class="card-title mb-0">Edit CMS</h5>

                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">

                                <form class="form-horizontal" action="" method="post">
                                    @csrf

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

                                    <div class="form-group row">
                                        <label class="col-sm-3 text-end control-label col-form-label">Page</label >
                                        <div class="col-sm-9">
                                            <input readonly
                                                    type="text"
                                                    class="form-control"
                                                    name="page"
                                                    placeholder="" value="{{$data->page}}"
                                            />
                                            <input type="hidden" value="{{$data->id}}" name="id">
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label class="col-sm-3 text-end control-label col-form-label">Description</label >
                                        <div class="col-sm-9">
                                            <textarea name="description" id="description"  >{!! $data->description !!}</textarea>
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

        $('#description').trumbowyg();

    });
</script>
</body>
</html>
