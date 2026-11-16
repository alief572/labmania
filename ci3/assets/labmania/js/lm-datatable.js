/**
 * Labmania - pembungkus DataTables untuk daftar di tiap modul.
 * Tampilan tetap mengikuti mockup (toolbar & pager sendiri); DataTables hanya mengurus
 * pencarian, urutan, dan paginasi. Dipakai lewat atribut data-dt pada <table>, atau lmDataTable(selector, opts).
 */
(function ($) {
  'use strict';

  var LANG = {
    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
    processing: 'Memuat data…',
    lengthMenu: 'Tampilkan _MENU_ data',
    infoEmpty: '0 data',
    infoFiltered: '',
    zeroRecords: 'Tidak ada data yang cocok.',
    emptyTable: 'Tidak ada data.',
    paginate: { previous: '‹', next: '›' }
  };

  window.lmDataTable = function (sel, opts) {
    opts = opts || {};
    if (!opts.searchCols && $(sel).data('search-cols')) opts.searchCols = $(sel).data('search-cols');
    var $t = $(sel);
    if (!$t.length || !$.fn.DataTable || $.fn.DataTable.isDataTable($t)) return null;
    // Baris kosong (colspan) tidak bisa dibaca DataTables; tidak ada yang perlu dipaginasi.
    if ($t.find('tbody tr:first td[colspan]').length) return null;

    var $ths = $t.find('thead th');
    var noIdx = $.trim($ths.eq(0).text()).toLowerCase() === 'no' ? 0 : -1;
    var plain = [];
    $ths.each(function (i) {
      var $th = $(this);
      if (i === noIdx || $th.hasClass('act') || $th.hasClass('hist')) plain.push(i);
    });

    // Mode server-side: data-ajax="url" (atau opts.ajax) -> paging/cari/urut dikerjakan di server.
    var url = opts.ajax || $t.data('ajax');
    var ssp = url ? {
      serverSide: true,
      processing: true,
      ajax: {
        url: url, type: 'POST',
        // data-filter="#id": nilai select ikut dikirim sebagai parameter `status` (atau data-filter-name).
        data: function (d) {
          // Beberapa filter: data-filter="#a,#b" dan data-filter-name="status,jenis" (urutan sama).
          var fs = String($t.data('filter') || '').split(','), ns = String($t.data('filter-name') || '').split(',');
          fs.forEach(function (f, i) { if (f) d[ns[i] || 'status'] = $(f).val(); });
        }
      },
      columns: $ths.map(function () { return { className: this.className || '' }; }).get()
    } : {};

    var dt = $t.DataTable($.extend({
      dom: 'rt<"pager"<"pg-info"li><"pg-btns"p>>',
      lengthMenu: [10, 25, 50, 100],
      pagingType: 'simple_numbers',
      pageLength: opts.pageLength || 10,
      autoWidth: false,
      order: [],
      ordering: !$t.data('no-order'), // tabel berurutan tetap (mis. pohon menu)
      language: LANG,
      columnDefs: [{ targets: plain, orderable: false, searchable: false }].concat(
        opts.searchCols ? [{ targets: $.map($ths.toArray(), function (th, i) { return $.inArray(i, opts.searchCols) < 0 ? i : null; }), searchable: false }] : []
      ),
      drawCallback: function () {
        var api = this.api();
        if (noIdx >= 0) {
          var start = api.page.info().start;
          api.column(noIdx, { page: 'current' }).nodes().each(function (cell, i) { cell.textContent = start + i + 1; });
        }
        var root = document.getElementById('menu-root');
        if (root) root.innerHTML = '';
        if (window.lmInitGears) window.lmInitGears();
      }
    }, ssp));

    // Pager dipindah ke luar .table-wrap supaya tidak ikut ter-scroll horizontal.
    var $wrap = $t.closest('.table-wrap');
    var $pager = $t.closest('.dataTables_wrapper').find('.pager');
    if ($wrap.length && $pager.length) $wrap.after($pager);

    if (url && $t.data('filter')) $($t.data('filter')).on('change', function () { dt.draw(); });

    // Kotak pencarian di toolbar (sesuai mockup) mengendalikan pencarian DataTables.
    var $q = $(opts.search || '#table-search');
    if ($q.length) {
      if ($q.val()) dt.search($q.val()).draw();
      var tm;
      $q.on('input', function () {
        var v = this.value;
        clearTimeout(tm);
        tm = setTimeout(function () { dt.search(v).draw(); }, url ? 300 : 0);
      });
    }
    return dt;
  };

  $(function () {
    $('table[data-dt]').each(function () { window.lmDataTable(this); });
  });
})(jQuery);
