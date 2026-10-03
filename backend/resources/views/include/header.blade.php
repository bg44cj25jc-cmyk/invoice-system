<div id="cover-spin"></div>
<div class="preloader">
    <div class="lds-ripple">
        <div class="lds-pos"></div>
        <div class="lds-pos"></div>
    </div>
</div>
<div id="myModal" class="custom-modal">

    <!-- Modal content -->
    <div class="custom-modal-content">
        <span class="close">&times;</span>
        <p id="alertText"></p>
    </div>

</div>
<div
        id="main-wrapper"
        data-layout="vertical"
        data-navbarbg="skin5"
        data-sidebartype="full"
        data-sidebar-position="absolute"
        data-header-position="absolute"
        data-boxed-layout="full"
>
    <header class="topbar" data-navbarbg="skin5">
        <nav class="navbar top-navbar navbar-expand-md navbar-dark">
            <div class="navbar-header" data-logobg="skin5">
                <a class="navbar-brand" href="{{ url('dashboard')}}">
<!--
                    <b class="logo-icon ps-2">
                        <img
                                src="{{asset('/public/assets/images/logo-icon.png')}}"
                                alt="homepage"
                                class="light-logo"
                                width="25"
                        />
                    </b>
-->

                    <span class="logo-text ms-2">

                <img
                        src="{{asset('/public/assets/images/logo-text.png')}}"
                        alt="homepage"
                        class="light-logo"
					 	style="width: 100%;"

                />
              </span>
                </a>
                <a
                        class="nav-toggler waves-effect waves-light d-block d-md-none"
                        href="javascript:void(0)"
                ><i class="ti-menu ti-close"></i
                    ></a>
            </div>
            <div
                    class="navbar-collapse collapse"
                    id="navbarSupportedContent"
                    data-navbarbg="skin5"
            >
                <ul class="navbar-nav float-start me-auto">
                    <li class="nav-item d-none d-lg-block">
                        <a
                                class="nav-link sidebartoggler waves-effect waves-light"
                                href="javascript:void(0)"
                                data-sidebartype="mini-sidebar"
                        ><i class="mdi mdi-menu font-24"></i
                            ></a>
                    </li>


                </ul>

                <ul class="navbar-nav float-end">


                    <li class="nav-item dropdown">
                        <a
                                class="
                    nav-link
                    dropdown-toggle
                    text-muted
                    waves-effect waves-dark
                    pro-pic
                  "
                                href="#"
                                id="navbarDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                        >
                            <img
                                    src="{{asset('/public/assets/images/users/1.jpg')}}"
                                    alt="user"
                                    class="rounded-circle"
                                    width="31"
                            />
                        </a>
                        <ul
                                class="dropdown-menu dropdown-menu-end user-dd animated"
                                aria-labelledby="navbarDropdown"
                        >

                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ url('change-password')}}"
                            ><i class="mdi mdi-settings me-1 ms-1"></i> Change Password</a
                            >
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ url('dashboard/log-out')}}"
                            ><i class="fa fa-power-off me-1 ms-1"></i> Log out</a
                            >

                        </ul>
                    </li>

                </ul>
            </div>
        </nav>
    </header>
