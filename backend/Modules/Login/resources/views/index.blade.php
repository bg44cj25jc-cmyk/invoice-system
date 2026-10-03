<!DOCTYPE html>
<html dir="ltr">
<head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="robots" content="noindex,nofollow"/>
    <title>x-us</title>
    <link
            rel="icon"
            type="image/png"
            sizes="16x16"
            href="{{asset('/public/assets/images/favicon.png')}}"
    />

    <link href="{{asset('/public/dist/css/style.min.css')}}" rel="stylesheet"/>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js')}}"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js')}}"></script>
    <![endif]-->
</head>

<body>
<div class="main-wrapper">
    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
    <div class="
          auth-wrapper
          d-flex
          no-block
          justify-content-center
          align-items-center
          bg-dark
        " style="height: 100vh"
    >
        <div class="auth-box bg-dark border-top border-secondary">
            <div id="loginform">
                <div class="text-center pt-3 pb-3">
              <span class="db"
              ><img src="{{asset('/public/assets/images/logo.png')}}" alt="logo"
                  /></span>
                </div>
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </div>
                @endif
                <form action="" method="post">
                    @csrf
                    <div class="row pb-4">
                        <div class="col-12">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                      <span
                              class="input-group-text bg-success text-white h-100"
                              id="basic-addon1"
                      ><i class="mdi mdi-account fs-4"></i
                          ></span>
                                </div>
                                <input type="email" id="email" name="email" class="form-control form-control-lg"
                                       id="exampleInputEmail1" placeholder="Username">
                            </div>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                      <span
                              class="input-group-text bg-warning text-white h-100"
                              id="basic-addon2"
                      ><i class="mdi mdi-lock fs-4"></i
                          ></span>
                                </div>
                                <input type="password" id="password" name="password"
                                       class="form-control form-control-lg" id="exampleInputPassword1"
                                       placeholder="Password">
                            </div>
                        </div>
                    </div>
                    <div class="row border-top border-secondary">
                        <div class="col-12">
                            <div class="form-group">
                                <div class="pt-3">
                                    <button class="btn btn-success float-end text-white" type="submit">
                                        Login
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="{{asset('/public/assets/libs/jquery/dist/jquery.min.js')}}"></script>
<script src="{{asset('/public/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js')}}"></script>
<script>
    $(".preloader").fadeOut();
    $("#to-recover").on("click", function () {
        $("#loginform").slideUp();
        $("#recoverform").fadeIn();
    });
    $("#to-login").click(function () {
        $("#recoverform").hide();
        $("#loginform").fadeIn();
    });
</script>
</body>
</html>
