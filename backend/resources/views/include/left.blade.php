<aside class="left-sidebar" data-sidebarbg="skin5">
    <!-- Sidebar scroll-->
    <div class="scroll-sidebar">
        <!-- Sidebar navigation-->
        <nav class="sidebar-nav">
            <ul id="sidebarnav" class="pt-4">
                <li class="sidebar-item">
                    <a
                            class="sidebar-link waves-effect waves-dark sidebar-link"
                            href="{{url('/dashboard')}}"
                            aria-expanded="false"
                    ><i class="mdi mdi-view-dashboard"></i
                        ><span class="hide-menu">Dashboard</span></a
                    >
                </li>
				
				<li class="sidebar-item">
                    <a
                            class="sidebar-link waves-effect waves-dark sidebar-link"
                            href="{{url('/user/company-list')}}"
                            aria-expanded="false"
                    ><i class="mdi mdi-view-dashboard"></i
                        ><span class="hide-menu">Company Management</span></a
                    >
                </li>
				<li class="sidebar-item">
					<a class="sidebar-link waves-effect waves-dark sidebar-link"
					   href="{{ url('/user/employee-list') }}"
					   aria-expanded="false">
						<i class="mdi mdi-account-multiple"></i>
						<span class="hide-menu">Employee Management</span>
					</a>
				</li>

				<li class="sidebar-item">
					<a class="sidebar-link waves-effect waves-dark sidebar-link"
					   href="{{ url('/user/payment-history') }}"
					   aria-expanded="false">
						<i class="mdi mdi-cash-multiple"></i>
						<span class="hide-menu">Payment History</span>
					</a>
				</li>

				<li class="sidebar-item">
					<a class="sidebar-link waves-effect waves-dark sidebar-link"
					   href="{{ url('/user/invoice-history') }}"
					   aria-expanded="false">
						<i class="mdi mdi-receipt"></i>
						<span class="hide-menu">Invoices History</span>
					</a>
				</li>

                <li class="sidebar-item">
                            <a href="{{url('/dashboard/language')}}" class="sidebar-link"
                            ><i class="mdi mdi-note-outline"></i
                                ><span class="hide-menu"> Language </span></a
                            >
                        </li>

<!--
				<li class="sidebar-item">
					<a href="{{url('/dashboard/received-requests')}}" class="sidebar-link"
					><i class="mdi mdi-note-plus"></i
						><span class="hide-menu"> Received requests page </span></a
					>
				</li>
-->

				

			<!--	<li class="sidebar-item">
                    <a
                            class="sidebar-link waves-effect waves-dark sidebar-link"
                            href="{{url('/doctor/patient-list')}}"
                            aria-expanded="false"
                    ><i class="mdi mdi-view-dashboard"></i
                        ><span class="hide-menu">Patient List</span></a
                    >
                </li>

                <li class="sidebar-item">
                    <a href="{{url('/dashboard/cms')}}" class="sidebar-link"
                    ><i class="mdi mdi-note-plus"></i
                        ><span class="hide-menu">CMS </span></a
                    >
                </li>
-->

                

<!--
                <li class="sidebar-item">
                    <a
                            class="sidebar-link has-arrow waves-effect waves-dark"
                            href="javascript:void(0)"
                            aria-expanded="false"
                    ><i class="mdi mdi-receipt"></i
                        ><span class="hide-menu">General Selection</span></a
                    >
                    <ul aria-expanded="false" class="collapse first-level">
                        <li class="sidebar-item">
                            <a href="{{url('/dashboard/language')}}" class="sidebar-link"
                            ><i class="mdi mdi-note-outline"></i
                                ><span class="hide-menu"> Language </span></a
                            >
                        </li>
                        <li class="sidebar-item">
                            <a href="{{url('/dashboard/speciality')}}" class="sidebar-link"
                            ><i class="mdi mdi-note-plus"></i
                                ><span class="hide-menu"> Speciality </span></a
                            >
                        </li>
                        <li class="sidebar-item">
                            <a href="{{url('/dashboard/medicine')}}" class="sidebar-link"
                            ><i class="mdi mdi-note-plus"></i
                                ><span class="hide-menu"> Medicine </span></a
                            >
                        </li>
                        <li class="sidebar-item">
                            <a href="{{url('/dashboard/faq')}}" class="sidebar-link"
                            ><i class="mdi mdi-note-plus"></i
                                ><span class="hide-menu"> Faq </span></a
                            >
                        </li>

                    </ul>
                </li>
-->


            </ul>
        </nav>
        <!-- End Sidebar navigation -->
    </div>
    <!-- End Sidebar scroll-->
</aside>
