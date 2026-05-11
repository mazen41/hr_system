    </div>
    <!-- /.content-wrapper -->

    <footer class="main-footer text-center" style="border:none;background:#f0f2f5;padding:16px;font-size:14px;color:#9ca3af;">
        <strong>&copy; <?= date('Y') ?> <a href="https://visionsys.net" style="color:var(--sidebar-active);text-decoration:none;">Vision HR</a></strong>
        <div class="d-sm-inline-block ml-2" style="font-size: 12px; opacity: 0.7;">| <b>Version</b> 6.0 (Fixes Active)</div>
    </footer>

</div>
<!-- ./wrapper -->

<!-- PERFORMANCE: Core JS (jQuery must load first, others deferred) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" defer></script>

<!-- DEFERRED: Non-critical JS loaded after page render -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" defer></script>
<?php if (!empty($loadSharedHeavyPlugins)): ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/js/bootstrap-select.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js" defer></script>

<!-- DataTables (defer) -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js" defer></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js" defer></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js" defer></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js" defer></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js" defer></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js" defer></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js" defer></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js" defer></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" defer></script>
<script src="dist/js/datatable-responsive-helper.js?v=6.1" defer></script>
<?php endif; ?>

<?php if (!empty($loadSharedChartJs)): ?>
<!-- Chart.js (loaded only on pages that need charts) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>

<script>
(function(){
    'use strict';

    // ===== ENHANCED SIDEBAR TOGGLE (mobile-first) =====
    var sidebar = document.getElementById('mainSidebar');
    var overlay = document.getElementById('sidebarOverlay');
    var toggleBtn = document.getElementById('sidebarToggle');
    var isMobile = function(){ return window.innerWidth < 992; };
    var getDesktopSidebarWidth = function(){
        var raw = window.getComputedStyle(document.documentElement).getPropertyValue('--sidebar-width') || '220px';
        var parsed = parseInt(raw, 10);
        return Number.isFinite(parsed) ? parsed : 220;
    };
    var syncDesktopLayout = function(){
        if (!document.body) return;

        var contentWrapper = document.querySelector('.content-wrapper');
        var navbar = document.querySelector('.main-header.navbar');
        var footer = document.querySelector('.main-footer');
        var width = document.body.classList.contains('sidebar-collapse') ? 0 : getDesktopSidebarWidth();
        var widthPx = width ? (width + 'px') : '0';
        var navbarWidth = width ? ('calc(100% - ' + width + 'px)') : '100%';

        if (contentWrapper) contentWrapper.style.marginRight = widthPx;
        if (navbar) {
            navbar.style.marginRight = widthPx;
            navbar.style.width = navbarWidth;
            navbar.style.paddingRight = width ? ('calc(' + width + 'px + 16px)') : '16px';
        }
        if (footer) footer.style.marginRight = widthPx;
    };

    // Robust global toast shim: queue calls until toastr is ready
    (function(){
        var toastQueue = [];
        if (typeof window.toast !== 'function') {
            window.toast = function(type, msg, title){
                if (window.toastr && typeof window.toastr[type] === 'function') {
                    window.toastr[type](msg || '', title || '');
                } else {
                    toastQueue.push([type, msg, title]);
                }
            };
        }
        function configureToastr(){
            if (!window.toastr) return false;
            try {
                window.toastr.options = window.toastr.options || {};
                window.toastr.options.closeButton = true;
                window.toastr.options.progressBar = true;
                window.toastr.options.positionClass = 'toast-top-left';
                window.toastr.options.timeOut = '4000';
                window.toastr.options.rtl = true;
                window.toastr.options.showDuration = '300';
                window.toastr.options.hideDuration = '300';
                window.toastr.options.extendedTimeOut = '2000';
            } catch(e) {}
            try {
                while (toastQueue.length) {
                    var t = toastQueue.shift();
                    var fn = (window.toastr && window.toastr[t[0]]) ? window.toastr[t[0]] : window.toastr.info;
                    fn(t[1] || '', t[2] || '');
                }
            } catch(e) {}
            return true;
        }
        document.addEventListener('DOMContentLoaded', function(){ setTimeout(configureToastr, 0); });
        window.addEventListener('load', function(){ setTimeout(configureToastr, 0); });
    })();

    var isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    function openSidebar(){
        if(sidebar && sidebar.classList){ 
            sidebar.classList.add('sidebar-open');
            sidebar.setAttribute('aria-hidden', 'false');
        }
        if(overlay && overlay.classList){ 
            overlay.classList.add('show');
            overlay.style.display = 'block';
        }
        if(document.body) {
            document.body.style.overflow = 'hidden';
            document.body.classList.add('sidebar-open');
        }
        
        // Focus management for accessibility
        if(toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
    }
    
    function closeSidebar(){
        if(sidebar && sidebar.classList){ 
            sidebar.classList.remove('sidebar-open');
            sidebar.setAttribute('aria-hidden', 'true');
        }
        if(overlay && overlay.classList){ 
            overlay.classList.remove('show');
            setTimeout(function(){ overlay.style.display = 'none'; }, 300);
        }
        if(document.body) {
            document.body.style.overflow = '';
            document.body.classList.remove('sidebar-open');
        }
        
        // Focus management for accessibility
        if(toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
    }

    // Make toggle button more responsive with larger click area
    if(toggleBtn){
        toggleBtn.style.cssText = 'cursor:pointer;padding:10px 15px;min-width:44px;min-height:44px;display:flex;align-items:center;justify-content:center;';
        
        // Support both click and touch
        var handleToggle = function(e){
            e.preventDefault();
            e.stopPropagation();
            if(isMobile()){
                if(sidebar && sidebar.classList && sidebar.classList.contains('sidebar-open')){
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                // Desktop: toggle sidebar width using the shared CSS variable
                if(document.body) {
                    var isCollapsed = document.body.classList.contains('sidebar-collapse');
                    
                    if(isCollapsed) {
                        document.body.classList.remove('sidebar-collapse');
                        if(sidebar) {
                            sidebar.classList.add('sidebar-open');
                            void sidebar.offsetWidth;
                        }
                    } else {
                        document.body.classList.add('sidebar-collapse');
                        if(sidebar) {
                            sidebar.classList.remove('sidebar-open');
                        }
                    }

                    syncDesktopLayout();
                }
            }
        };
        
        toggleBtn.addEventListener('click', handleToggle);
        if(isTouch) {
            toggleBtn.addEventListener('touchend', handleToggle);
        }
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
        if(!isMobile()){
            closeSidebar();
            syncDesktopLayout();
        }
    });

    document.addEventListener('DOMContentLoaded', function(){
        if(!isMobile()){
            syncDesktopLayout();
        }
    });
    window.addEventListener('load', function(){
        if(!isMobile()){
            syncDesktopLayout();
        }
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
if (window.toastr) {
    window.toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-left',
        timeOut: '4000',
        rtl: true,
        showDuration: '300',
        hideDuration: '300',
        extendedTimeOut: '2000'
    };
}

// ===== GLOBAL INIT =====
$(document).ready(function() {
    // Auto-wrap tables in table-responsive if not already wrapped
    $('table').each(function(){
        if(!$(this).parent().hasClass('table-responsive') && !$(this).closest('.table-responsive').length){
            $(this).wrap('<div class="table-responsive"></div>');
        }
    });

    // Select2 RTL
    if($.fn.select2){
        $('.select2').select2({ dir: "rtl", width: '100%' });
    }
    // Bootstrap Select
    if($.fn.selectpicker){
        $('.selectpicker').selectpicker();
    }

    if ($.validator && $.validator.messages) {
        $.extend($.validator.messages, {
            required: 'هذا الحقل مطلوب.',
            remote: 'يرجى تصحيح هذا الحقل.',
            email: 'يرجى إدخال بريد إلكتروني صحيح.',
            url: 'يرجى إدخال رابط صحيح.',
            date: 'يرجى إدخال تاريخ صحيح.',
            dateISO: 'يرجى إدخال تاريخ صحيح بصيغة YYYY-MM-DD.',
            number: 'يرجى إدخال رقم صحيح.',
            digits: 'يرجى إدخال أرقام فقط.',
            equalTo: 'القيمتان غير متطابقتين.',
            maxlength: $.validator.format('يرجى عدم إدخال أكثر من {0} أحرف.'),
            minlength: $.validator.format('يرجى إدخال {0} أحرف على الأقل.'),
            rangelength: $.validator.format('يرجى إدخال عدد أحرف بين {0} و{1}.'),
            range: $.validator.format('يرجى إدخال قيمة بين {0} و{1}.'),
            max: $.validator.format('يرجى إدخال قيمة أقل من أو تساوي {0}.'),
            min: $.validator.format('يرجى إدخال قيمة أكبر من أو تساوي {0}.'),
            step: $.validator.format('القيمة يجب أن تكون مضاعفًا للخطوة {0}.')
        });
    }

    // DataTables responsive defaults
    if($.fn.DataTable){
        $.extend(true, $.fn.dataTable.defaults, {
            responsive: true,
            language: {
                url: '/dist/js/dataTables.arabic.json',
                search: 'بحث:',
                lengthMenu: 'عرض _MENU_ سجل',
                info: 'عرض _START_ إلى _END_ من _TOTAL_ سجل',
                infoEmpty: 'لا توجد سجلات',
                infoFiltered: '(تصفية من _MAX_ سجل)',
                paginate: { first: 'الأول', last: 'الأخير', next: 'التالي', previous: 'السابق' },
                zeroRecords: 'لا توجد نتائج مطابقة'
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            pageLength: 10,
            order: [],
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: -1 }
            ]
        });
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

// ===== NOTIFICATION BELL =====
function loadHeaderNotifications() {
    $.get('hr-app/index.php?action=get-unread-notifications', function(res) {
        if (res.result && res.data) {
            var count = res.data.length;
            var $badge = $('#notifBadge');
            var $items = $('#notificationItems');
            
            if (count > 0) {
                $badge.text(count > 99 ? '99+' : count).show();
                var html = '';
                res.data.slice(0, 5).forEach(function(n) {
                    html += '<a href="#" class="dropdown-item notification-item-header" data-id="' + n.id + '" onclick="markNotifRead(' + n.id + ');return false;">';
                    html += '<div class="d-flex align-items-center justify-content-center text-center flex-column">';
                    html += '<div class="notification-icon bg-primary text-white mb-2"><i class="fas fa-bell"></i></div>';
                    html += '<div class="w-100">';
                    html += '<strong class="d-block">' + escapeHtmlNotif(n.title) + '</strong>';
                    html += '<small class="text-muted">' + escapeHtmlNotif(n.body || '').substring(0, 50) + '</small>';
                    html += '<small class="text-muted d-block">' + n.created_at + '</small>';
                    html += '</div></div></a>';
                });
                $items.html(html);
            } else {
                $badge.hide();
                $items.html('<div class="text-center text-muted py-3"><i class="fas fa-bell-slash"></i> لا توجد إشعارات جديدة</div>');
            }
        }
    });
}

var headerNotificationPoll = null;
var headerNotificationStream = null;
var browserNotificationPrimed = false;
var seenBrowserNotifications = {};

function loadSeenBrowserNotifications() {
    try {
        seenBrowserNotifications = JSON.parse(localStorage.getItem('vision_hr_seen_notifications') || '{}') || {};
    } catch (e) {
        seenBrowserNotifications = {};
    }
}

function persistSeenBrowserNotifications() {
    try {
        var entries = Object.entries(seenBrowserNotifications).slice(-100);
        localStorage.setItem('vision_hr_seen_notifications', JSON.stringify(Object.fromEntries(entries)));
    } catch (e) {
        // ignore storage errors
    }
}

function notificationTargetUrl(notification) {
    if (window.location.pathname.indexOf('ess-') !== -1) {
        return '/ess-notifications';
    }
    return '/admin-notifications';
}

function maybePromptBrowserNotifications() {
    var perfProfile = document.body ? document.body.getAttribute('data-perf-profile') : 'default';
    if (perfProfile === 'critical') {
        return;
    }
    if (!('Notification' in window) || Notification.permission !== 'default') {
        return;
    }
    if (localStorage.getItem('vision_hr_browser_notifications_prompted')) {
        return;
    }

    localStorage.setItem('vision_hr_browser_notifications_prompted', '1');
    if (typeof Swal === 'undefined') {
        return;
    }

    Swal.fire({
        title: 'تفعيل إشعارات المتصفح',
        text: 'هل تريد استقبال إشعارات فورية من Vision HR داخل المتصفح؟',
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'تفعيل',
        cancelButtonText: 'لاحقاً',
        confirmButtonColor: '#0d21a5'
    }).then(function(result) {
        if (result.isConfirmed) {
            Notification.requestPermission();
        }
    });
}

function showBrowserNotification(notification) {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
        return;
    }

    var title = notification.title || 'Vision HR';
    var options = {
        body: notification.body || 'لديك إشعار جديد',
        icon: '/dist/img/brand/logo-icon.png',
        badge: '/dist/img/brand/logo-icon.png',
        dir: 'rtl',
        lang: 'ar',
        data: {
            url: notificationTargetUrl(notification)
        }
    };

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.ready.then(function(registration) {
            registration.showNotification(title, options);
        }).catch(function() {
            new Notification(title, options);
        });
        return;
    }

    new Notification(title, options);
}

function handleBrowserNotifications(items) {
    var notifications = Array.isArray(items) ? items : [];
    if (!browserNotificationPrimed) {
        notifications.forEach(function(item) {
            seenBrowserNotifications[String(item.id)] = 1;
        });
        browserNotificationPrimed = true;
        persistSeenBrowserNotifications();
        return;
    }

    notifications.forEach(function(item) {
        var key = String(item.id);
        if (!seenBrowserNotifications[key]) {
            seenBrowserNotifications[key] = 1;
            showBrowserNotification(item);
        }
    });
    persistSeenBrowserNotifications();
}

function renderHeaderNotifications(items, totalCount) {
    var notifications = Array.isArray(items) ? items : [];
    var count = typeof totalCount === 'number' ? totalCount : notifications.length;
    var $badge = $('#notifBadge');
    var $items = $('#notificationItems');

    if (!$badge.length || !$items.length) {
        return;
    }

    if (count > 0) {
        $badge.text(count > 99 ? '99+' : count).show();
        var html = '';
        notifications.slice(0, 5).forEach(function(n) {
            html += '<a href="#" class="dropdown-item notification-item-header" data-id="' + n.id + '" onclick="markNotifRead(' + n.id + ');return false;">';
            html += '<div class="d-flex align-items-center justify-content-center text-center flex-column">';
            html += '<div class="notification-icon bg-primary text-white mb-2"><i class="fas fa-bell"></i></div>';
            html += '<div class="w-100">';
            html += '<strong class="d-block">' + escapeHtmlNotif(n.title) + '</strong>';
            html += '<small class="text-muted">' + escapeHtmlNotif(n.body || '').substring(0, 50) + '</small>';
            html += '<small class="text-muted d-block">' + (n.created_at || '') + '</small>';
            html += '</div></div></a>';
        });
        $items.html(html);
    } else {
        $badge.hide();
        $items.html('<div class="text-center text-muted py-3"><i class="fas fa-bell-slash"></i> لا توجد إشعارات جديدة</div>');
    }
}

loadHeaderNotifications = function() {
    $.get('hr-app/index.php?action=get-unread-notifications', function(res) {
        if (res.result) {
            var items = Array.isArray(res.data) ? res.data : ((res.data && Array.isArray(res.data.items)) ? res.data.items : []);
            var count = Number(res.count || (res.data && res.data.count) || items.length || 0);
            renderHeaderNotifications(items, count);
            handleBrowserNotifications(items);
        }
    });
};

function startHeaderNotificationPolling() {
    if (headerNotificationPoll) {
        return;
    }
    headerNotificationPoll = setInterval(loadHeaderNotifications, 60000);
}

function refreshNotificationViewsFromStream() {
    if (typeof window.loadNotifications === 'function') {
        try {
            window.loadNotifications();
        } catch (e) {
            console.error('Notification view refresh error:', e);
        }
    }
}

function initNotificationRealtime() {
    if (!window.EventSource) {
        startHeaderNotificationPolling();
        return;
    }

    if (headerNotificationStream) {
        return;
    }

    try {
        headerNotificationStream = new EventSource('notifications-stream.php');
        headerNotificationStream.addEventListener('notifications', function(event) {
            try {
                var payload = JSON.parse(event.data || '{}');
                renderHeaderNotifications(payload.notifications || [], Number(payload.count || 0));
                handleBrowserNotifications(payload.notifications || []);
                refreshNotificationViewsFromStream();
            } catch (e) {
                console.error('Notification stream parse error:', e);
            }
        });
        headerNotificationStream.onerror = function() {
            if (headerNotificationStream) {
                headerNotificationStream.close();
                headerNotificationStream = null;
            }
            startHeaderNotificationPolling();
        };
    } catch (e) {
        console.error('Notification stream init error:', e);
        startHeaderNotificationPolling();
    }
}

function markNotifRead(id) {
    $.post('hr-app/index.php?action=mark-notification-read', { notification_id: id }, function(res) {
        if (res.result) {
            loadHeaderNotifications();
        }
    });
}

function markAllNotificationsRead() {
    $.post('hr-app/index.php?action=mark-all-notifications-read', function(res) {
        if (res.result) {
            loadHeaderNotifications();
            toastr.success('تم تحديد جميع الإشعارات كمقروءة');
        }
    });
}

function escapeHtmlNotif(text) {
    if (!text) return '';
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== NAVBAR DROPDOWN TELEPORT FIX (robust) =====
(function() {
    function restoreAll() {
        $('body > .dropdown-menu[data-tp]').each(function() {
            var $m = $(this);
            var $p = $m.data('tp-parent');
            if ($p && $p.length) {
                $m.css({position:'',top:'',right:'',left:'',zIndex:'',display:'',maxWidth:'',minWidth:''})
                  .removeAttr('data-tp').removeData('tp-parent')
                  .detach().appendTo($p);
            }
        });
    }

    $(document).on('shown.bs.dropdown', '.main-header .nav-item.dropdown', function() {
        var $navItem = $(this);
        var $toggle  = $navItem.children('[data-toggle="dropdown"]');
        // Menu may already be in body from previous stuck state — restore first
        restoreAll();
        var $menu = $navItem.children('.dropdown-menu');
        if (!$menu.length) return;

        var rect     = $toggle[0].getBoundingClientRect();
        // Mobile RTL: Align to left side of screen with 10px padding if it would overflow
        var leftVal  = Math.max(10, rect.left);
        
        $menu.attr('data-tp', '1').data('tp-parent', $navItem)
             .detach().appendTo('body').css({
                 position : 'fixed',
                 top      : (rect.bottom + 4) + 'px',
                 left     : leftVal + 'px',
                 right    : 'auto',
                 zIndex   : '999999',
                 display  : 'block',
                 maxWidth : (window.innerWidth - 20) + 'px',
                 minWidth : '200px'
             });
    });

    $(document).on('hide.bs.dropdown', '.main-header .nav-item.dropdown', function() {
        restoreAll();
    });

    // Safety net: restore on any outside click
    $(document).on('click touchstart', function(e) {
        if (!$(e.target).closest('.main-header .nav-item.dropdown').length &&
            !$(e.target).closest('body > .dropdown-menu[data-tp]').length) {
            restoreAll();
        }
    });
})();

function runNonCriticalTask(task, fallbackDelay) {
    var delay = typeof fallbackDelay === 'number' ? fallbackDelay : 1200;
    if ('requestIdleCallback' in window) {
        requestIdleCallback(task, { timeout: delay });
        return;
    }
    window.setTimeout(task, delay);
}

// Load notifications after first paint on performance-sensitive pages
$(document).ready(function() {
    var perfProfile = document.body ? document.body.getAttribute('data-perf-profile') : 'default';
    var startNotifications = function() {
        loadSeenBrowserNotifications();
        maybePromptBrowserNotifications();
        loadHeaderNotifications();
        initNotificationRealtime();

        $('#notificationDropdown').on('show.bs.dropdown', function() {
            loadHeaderNotifications();
        });
    };

    if (perfProfile === 'critical') {
        runNonCriticalTask(startNotifications, 1600);
    } else {
        startNotifications();
    }
});

// Version-aware service worker refresh without clearing caches on every page load
(function syncAppVersion() {
    var APP_VERSION = '6.2';
    if (!('serviceWorker' in navigator)) {
        return;
    }

    try {
        var previousVersion = localStorage.getItem('vision_hr_app_version');
        localStorage.setItem('vision_hr_app_version', APP_VERSION);

        if (previousVersion && previousVersion !== APP_VERSION) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                registrations.forEach(function(registration) {
                    registration.update();
                });
            });
        }
    } catch (e) {
        // Ignore storage issues
    }
})();
</script>

<style>
/* Notification Bell Styles */
.notification-bell {
    position: relative;
    padding: 8px 12px !important;
}
.notification-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    background: #ef4444;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
    line-height: 1.2;
}
.notification-dropdown {
    border-radius: 12px !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
    border: 1px solid #e5e7eb !important;
}
.notification-dropdown .dropdown-header {
    padding: 12px 16px;
    background: #f9fafb;
    border-radius: 12px 12px 0 0;
}
.notification-item-header {
    padding: 12px 16px !important;
    border-bottom: 1px solid #f3f4f6;
    white-space: normal !important;
}
.notification-item-header:hover {
    background: #f9fafb !important;
}
.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
@media (max-width: 480px) {
    .notification-dropdown {
        width: calc(100vw - 20px) !important;
        left: 10px !important;
        right: 10px !important;
    }
}

/* PWA Install Banner */
.pwa-install-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #0d21a5 0%, #3d4fb8 100%);
    color: #fff;
    padding: 16px 20px;
    display: none;
    align-items: center;
    justify-content: space-between;
    z-index: 9999;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.2);
    direction: rtl;
}
.pwa-install-banner.show { display: flex; }
.pwa-install-banner .pwa-text {
    display: flex;
    align-items: center;
    gap: 12px;
}
.pwa-install-banner .pwa-text img {
    width: 40px;
    height: 40px;
    border-radius: 8px;
}
.pwa-install-banner .pwa-text span {
    font-size: 14px;
    font-weight: 600;
}
.pwa-install-banner .pwa-buttons {
    display: flex;
    gap: 10px;
}
.pwa-install-banner .btn-install {
    background: #fff;
    color: #0d21a5;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
}
.pwa-install-banner .btn-dismiss {
    background: transparent;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 8px 16px;
    border-radius: 8px;
    cursor: pointer;
}
</style>

<!-- PWA Install Banner -->
<div class="pwa-install-banner" id="pwaInstallBanner">
    <div class="pwa-text">
        <img src="dist/img/brand/logo-icon.png" alt="Vision HR" width="40" height="40" loading="lazy" decoding="async">
        <span>تثبيت تطبيق Vision HR على جهازك</span>
    </div>
    <div class="pwa-buttons">
        <button class="btn-install" id="pwaInstallBtn">تثبيت</button>
        <button class="btn-dismiss" id="pwaDismissBtn">لاحقاً</button>
    </div>
</div>

<script>
// PWA Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        var registerServiceWorker = function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    // ServiceWorker registered
                })
                .catch(function(err) {
                    // ServiceWorker registration failed
                });
        };

        var perfProfile = document.body ? document.body.getAttribute('data-perf-profile') : 'default';
        if (perfProfile === 'critical') {
            runNonCriticalTask(registerServiceWorker, 2500);
        } else {
            registerServiceWorker();
        }
    });
}

// PWA Install Prompt
let deferredPrompt;
const pwaInstallBanner = document.getElementById('pwaInstallBanner');
const pwaInstallBtn = document.getElementById('pwaInstallBtn');
const pwaDismissBtn = document.getElementById('pwaDismissBtn');

window.addEventListener('beforeinstallprompt', function(e) {
    e.preventDefault();
    deferredPrompt = e;
    // Show install banner if not dismissed before
    if (!localStorage.getItem('pwa-dismissed')) {
        pwaInstallBanner.classList.add('show');
    }
});

if (pwaInstallBtn) {
    pwaInstallBtn.addEventListener('click', function() {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    // User accepted PWA install
                }
                deferredPrompt = null;
                pwaInstallBanner.classList.remove('show');
            });
        }
    });
}

if (pwaDismissBtn) {
    pwaDismissBtn.addEventListener('click', function() {
        pwaInstallBanner.classList.remove('show');
        localStorage.setItem('pwa-dismissed', 'true');
    });
}

// Show iOS install instructions
if (/iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream) {
    // Check if not in standalone mode
    if (!window.navigator.standalone) {
        // Show iOS-specific install instructions after a delay
        setTimeout(function() {
            if (!localStorage.getItem('pwa-dismissed')) {
                Swal.fire({
                    title: 'تثبيت التطبيق',
                    html: '<div style="text-align:right;direction:rtl;">' +
                          '<p>لتثبيت التطبيق على جهازك:</p>' +
                          '<ol style="padding-right:20px;">' +
                          '<li>اضغط على زر المشاركة <i class="fas fa-share-square"></i></li>' +
                          '<li>اختر "إضافة إلى الشاشة الرئيسية"</li>' +
                          '</ol></div>',
                    icon: 'info',
                    confirmButtonText: 'فهمت',
                    confirmButtonColor: '#0d21a5'
                });
                localStorage.setItem('pwa-dismissed', 'true');
            }
        }, 3000);
    }
}
</script>
</body>
</html>
