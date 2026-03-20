/* Vision HR - Modern UX helpers (Tailwind + Alpine baseline)
   - Lightweight AJAX utilities
   - Global error handling (Toastr/SweetAlert if available)
   - RTL/LTR quick toggle
   - Progressive enhancement for forms with .ajax-form
*/
(function(window, document){
    'use strict';

    /* ============================================
       SKELETON LOADER UTILITIES
       ============================================ */
    window.VHR = window.VHR || {};
    
    VHR.showSkeleton = function(container, type, count) {
        type = type || 'text';
        count = count || 1;
        var html = '';
        for (var i = 0; i < count; i++) {
            switch(type) {
                case 'stat':
                    html += '<div class="vhr-stat-card" style="min-height:100px;"><div class="vhr-skeleton vhr-skeleton-circle" style="width:56px;height:56px;"></div><div style="flex:1;"><div class="vhr-skeleton vhr-skeleton-text lg" style="width:60%;"></div><div class="vhr-skeleton vhr-skeleton-text sm" style="width:80%;margin-top:0.5rem;"></div></div></div>';
                    break;
                case 'card':
                    html += '<div class="vhr-card"><div class="vhr-card-header"><div class="vhr-skeleton vhr-skeleton-text" style="width:40%;"></div></div><div class="vhr-card-body"><div class="vhr-skeleton vhr-skeleton-text" style="width:100%;"></div><div class="vhr-skeleton vhr-skeleton-text" style="width:90%;"></div><div class="vhr-skeleton vhr-skeleton-text" style="width:75%;"></div></div></div>';
                    break;
                case 'list':
                    html += '<div class="vhr-flex vhr-items-center vhr-gap-3 vhr-p-3" style="border-bottom:1px solid var(--vhr-gray-100);"><div class="vhr-skeleton vhr-skeleton-circle" style="width:40px;height:40px;"></div><div style="flex:1;"><div class="vhr-skeleton vhr-skeleton-text" style="width:60%;"></div><div class="vhr-skeleton vhr-skeleton-text sm" style="width:40%;margin-top:0.25rem;"></div></div></div>';
                    break;
                default:
                    html += '<div class="vhr-skeleton vhr-skeleton-text" style="width:100%;"></div>';
            }
        }
        if (typeof container === 'string') container = document.querySelector(container);
        if (container) container.innerHTML = html;
    };
    
    VHR.hideSkeleton = function(container) {
        if (typeof container === 'string') container = document.querySelector(container);
        if (container) container.innerHTML = '';
    };

    /* ============================================
       MICRO-INTERACTIONS
       ============================================ */
    VHR.ripple = function(e) {
        var btn = e.currentTarget;
        var circle = document.createElement('span');
        var d = Math.max(btn.clientWidth, btn.clientHeight);
        circle.style.width = circle.style.height = d + 'px';
        circle.style.left = (e.clientX - btn.offsetLeft - d/2) + 'px';
        circle.style.top = (e.clientY - btn.offsetTop - d/2) + 'px';
        circle.classList.add('vhr-ripple');
        btn.appendChild(circle);
        setTimeout(function(){ circle.remove(); }, 600);
    };
    
    // Auto-attach ripple to vhr-btn elements
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.vhr-btn').forEach(function(btn) {
            btn.addEventListener('click', VHR.ripple);
        });
    });

    /* ============================================
       LOADING STATE HELPERS
       ============================================ */
    VHR.setLoading = function(el, loading) {
        if (typeof el === 'string') el = document.querySelector(el);
        if (!el) return;
        if (loading) {
            el.classList.add('vhr-loading');
            el.disabled = true;
        } else {
            el.classList.remove('vhr-loading');
            el.disabled = false;
        }
    };

    /* ============================================
       FADE IN ANIMATION ON SCROLL
       ============================================ */
    if ('IntersectionObserver' in window) {
        var fadeObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('vhr-fade-in');
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.vhr-animate-on-scroll').forEach(function(el) {
                fadeObserver.observe(el);
            });
        });
    }
  'use strict';

  // ---- Small utils ----
  var hasToastr = function(){ return typeof window.toastr !== 'undefined'; };
  var hasSwal   = function(){ return typeof window.Swal !== 'undefined'; };
  function notifySuccess(msg){
    if (hasToastr()) toastr.success(msg);
    else if (hasSwal()) Swal.fire({icon:'success', title: msg, timer:1500, showConfirmButton:false});
    else console.log('SUCCESS:', msg);
  }
  function notifyError(msg){
    if (hasToastr()) toastr.error(msg);
    else if (hasSwal()) Swal.fire({icon:'error', title:'حدث خطأ', text: msg});
    else console.error('ERROR:', msg);
  }

  // ---- API helpers (form-urlencoded by default) ----
  function toForm(data){
    if (!data) return '';
    try {
      if (window.$ && $.param) return $.param(data);
      return new URLSearchParams(data).toString();
    } catch(e){ return ''; }
  }

  var api = {
    post: function(url, data, opts){
      return fetch(url, Object.assign({
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: toForm(data)
      }, opts||{})).then(function(r){ return r.json().catch(function(){ return {result:false, msg:'JSON parse error'}; }); });
    },
    get: function(url, params){
      var u = new URL(url, window.location.origin);
      if (params) Object.keys(params).forEach(function(k){ u.searchParams.set(k, params[k]); });
      return fetch(u.toString(), { method: 'GET' }).then(function(r){ return r.json().catch(function(){ return {result:false, msg:'JSON parse error'}; }); });
    }
  };
  window.apiPost = api.post; // backward compat alias
  window.API = api;

  // ---- RTL/LTR toggle (Ctrl+Alt+L) ----
  document.addEventListener('keydown', function(e){
    try {
      if(e.ctrlKey && e.altKey && (e.key.toLowerCase && e.key.toLowerCase() === 'l')){
        var isRtl = document.documentElement.getAttribute('dir') === 'rtl';
        var url = new URL(window.location.href);
        url.searchParams.set('dir', isRtl ? 'ltr' : 'rtl');
        window.location.href = url.toString();
      }
    } catch(err) { /* no-op */ }
  });

  // ---- Progressive enhancement: .ajax-form ----
  function initAjaxForms(){
    if (!window.jQuery) return; // keep it simple with jQuery binding when available
    $(document).on('submit', '.ajax-form', function(e){
      e.preventDefault();
      var $form = $(this);
      var action = $form.attr('action') || window.location.href;
      var method = ($form.attr('method') || 'POST').toUpperCase();
      var data   = $form.serialize();
      var $btn   = $form.find('button[type=submit], .btn-submit').first();
      var oldTxt = $btn.text();
      $btn.prop('disabled', true).addClass('opacity-70 cursor-not-allowed');
      $btn.text('...');

      fetch(action, {
        method: method,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: data
      }).then(function(r){ return r.json().catch(function(){ return { result:false, msg:'JSON parse error' }; }); })
        .then(function(res){
          if (res && (res.result === true || res.success === true)) {
            notifySuccess(res.msg || 'تم الحفظ بنجاح');
            var redirect = res.redirect || res.url;
            if (redirect) setTimeout(function(){ window.location.href = redirect; }, 800);
          } else {
            notifyError((res && (res.msg||res.message)) || 'فشل العملية');
          }
        })
        .catch(function(err){ notifyError(err && err.message ? err.message : 'Network error'); })
        .finally(function(){ $btn.prop('disabled', false).removeClass('opacity-70 cursor-not-allowed').text(oldTxt); });
    });
  }

  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAjaxForms);
  } else {
    initAjaxForms();
  }

})(window, document);
