    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer text-center" style="border:none;background:#f0f2f5;padding:16px;font-size:14px;color:#9ca3af;">
        <strong>&copy; <?= date('Y') ?> <a href="https://visionsys.net" style="color:var(--sidebar-active);text-decoration:none;">Vision HR</a></strong>
    </footer>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE removed — custom sidebar JS handles treeview/toggle -->
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Toastr -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<!-- Bootstrap Select -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/js/bootstrap-select.min.js"></script>
<!-- jQuery Validate -->
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<!-- Moment.js -->
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<!-- DateRangePicker -->
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<script>
(function(){
    'use strict';

    // ===== SIDEBAR TOGGLE (mobile-first) =====
    var sidebar = document.getElementById('mainSidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarToggle');
    var isMobile = function(){ return window.innerWidth < 992; };

    function openSidebar(){
        if(sidebar){ sidebar.classList.add('sidebar-open'); }
        if(overlay){ overlay.classList.add('show'); }
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar(){
        if(sidebar){ sidebar.classList.remove('sidebar-open'); }
        if(overlay){ overlay.classList.remove('show'); }
        document.body.style.overflow = '';
    }

    if(toggleBtn){
        toggleBtn.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            if(isMobile()){
                if(sidebar && sidebar.classList.contains('sidebar-open')){
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                // Desktop: toggle sidebar width
                document.body.classList.toggle('sidebar-collapse');
            }
        });
    }
    if(overlay){
        overlay.addEventListener('click', closeSidebar);
    }

    // Close sidebar on nav click (mobile)
    if(sidebar){
        sidebar.querySelectorAll('.nav-link[href]').forEach(function(link){
            link.addEventListener('click', function(){
                if(isMobile() && this.getAttribute('href') !== '#'){
                    closeSidebar();
                }
            });
        });
    }

    // Close sidebar on resize to desktop
    window.addEventListener('resize', function(){
        if(!isMobile()){ closeSidebar(); }
    });

    // ===== PRELOADER =====
    window.showPreloader = function(){ document.getElementById('preloading').classList.add('show'); };
    window.hidePreloader = function(){ document.getElementById('preloading').classList.remove('show'); };
    // Legacy support: pages use $('#preloading').show()/.hide()
    $(document).ready(function(){
        $('#preloading').hide = function(){ hidePreloader(); return this; };
        $('#preloading').show = function(){ showPreloader(); return this; };
    });

})();

// ===== GLOBAL TOASTR =====
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-left",
    "timeOut": "4000",
    "rtl": true,
    "showDuration": "300",
    "hideDuration": "300",
    "extendedTimeOut": "2000"
};

// ===== GLOBAL INIT =====
$(document).ready(function() {
    // Select2 RTL
    if($.fn.select2){
        $('.select2').select2({ dir: "rtl", width: '100%' });
    }
    // Bootstrap Select
    if($.fn.selectpicker){
        $('.selectpicker').selectpicker();
    }

    // Date range picker
    if($.fn.daterangepicker && $('.input-date-range').length) {
        $('.input-date-range').daterangepicker({
            autoUpdateInput: false,
            locale: { cancelLabel: 'مسح', applyLabel: 'تطبيق', format: 'YYYY-MM-DD' }
        });
        $('.input-date-range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });
        $('.input-date-range').on('cancel.daterangepicker', function() {
            $(this).val('');
        });
    }

    // Treeview accordion for sidebar submenus
    $(document).on('click', '.nav-sidebar .has-treeview > .nav-link', function(e){
        e.preventDefault();
        e.stopPropagation();
        var $parent = $(this).parent('.has-treeview');
        var $submenu = $parent.children('.nav-treeview');
        if($parent.hasClass('menu-open')){
            $submenu.slideUp(250, function(){ $parent.removeClass('menu-open'); });
        } else {
            // Close other open menus (accordion)
            $('.nav-sidebar .has-treeview.menu-open').each(function(){
                if(this !== $parent[0]){
                    $(this).children('.nav-treeview').slideUp(250);
                    $(this).removeClass('menu-open');
                }
            });
            $parent.addClass('menu-open');
            $submenu.slideDown(250);
        }
    });
});

// ===== LOGOUT =====
if(window.location.search.includes('logout=1')) {
    $.get('inc/logout.php', function() {
        window.location.href = 'login-sys';
    });
}
</script>
</body>
</html>
