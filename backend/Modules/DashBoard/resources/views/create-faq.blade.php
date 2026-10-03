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
                        <h5 class="card-title mb-0">Create Faq</h5>
                        <button type="button" class="btn btn-primary customBtn"
                                onclick="window.location.href='{{ url('dashboard/faq') }}'">
                            Back
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <form class="form-horizontal" action="" method="post">
                                    @csrf
                                    <div class="card-body">
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
                                            <label class="col-sm-3 text-end control-label col-form-label">Faq</label >
                                            <div class="col-sm-9">
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        name="name"
                                                        placeholder=""
                                                />
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
</body>
</html>
