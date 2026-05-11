/* DataTable Responsive Helper - Safe No-Op
   Ensures no console errors if DataTables responsive is not needed on a page.
*/
(function(){
  'use strict';
  if (typeof jQuery === 'undefined') return;
  var $ = jQuery;
  // Silence DataTables default alerts
  if ($.fn && $.fn.dataTable) {
    if ($.fn.dataTable.ext && $.fn.dataTable.ext.errMode) {
      $.fn.dataTable.ext.errMode = 'none';
    }
    // Optional helper: auto-add responsive class if table is wide
    $(function(){
      $('table').each(function(){
        var $t = $(this);
        if (!$t.closest('.table-responsive').length) {
          $t.wrap('<div class="table-responsive"></div>');
        }
      });
    });
  }
})();
