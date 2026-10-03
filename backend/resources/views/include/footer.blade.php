<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="{{asset('/public/assets/libs/jquery/dist/jquery.min.js')}}"></script>
<!-- Bootstrap tether Core JavaScript -->
<script src="{{asset('/public/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{asset('/public/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js')}}"></script>
<script src="{{asset('/public/assets/extra-libs/sparkline/sparkline.js')}}"></script>
<!--Wave Effects -->
<script src="{{asset('/public/dist/js/waves.js')}}"></script>
<!--Menu sidebar -->
<script src="{{asset('/public/dist/js/sidebarmenu.js')}}"></script>
<!--Custom JavaScript -->
<script src="{{asset('/public/dist/js/custom.min.js')}}"></script>
<!--This page JavaScript -->
<!-- <script src="{{asset('/public/dist/js/pages/dashboards/dashboard1.js')}}"></script> -->
<!-- Charts js Files -->
<script src="{{asset('/public/assets/libs/flot/excanvas.js')}}"></script>
<script src="{{asset('/public/assets/libs/flot/jquery.flot.js')}}"></script>
<script src="{{asset('/public/assets/libs/flot/jquery.flot.pie.js')}}"></script>
<script src="{{asset('/public/assets/libs/flot/jquery.flot.time.js')}}"></script>
<script src="{{asset('/public/assets/libs/flot/jquery.flot.stack.js')}}"></script>
<script src="{{asset('/public/assets/libs/flot/jquery.flot.crosshair.js')}}"></script>
<script src="{{asset('/public/assets/libs/flot.tooltip/js/jquery.flot.tooltip.min.js')}}"></script>
<script src="{{asset('/public/dist/js/pages/chart/chart-page-init.js')}}"></script>
<script src="{{asset('/public/assets/extra-libs/multicheck/datatable-checkbox-init.js')}}"></script>
<script src="{{asset('/public/assets/extra-libs/multicheck/jquery.multicheck.js')}}"></script>
<script src="{{asset('/public/assets/extra-libs/DataTables/datatables.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

    var span = document.getElementsByClassName("close")[0];
    span.onclick = function() {
        document.getElementById('myModal').style.display = "none";
    }
    /****************************************
     *       Basic Table                   *
     ****************************************/
    $("#zero_config").DataTable();
</script>
