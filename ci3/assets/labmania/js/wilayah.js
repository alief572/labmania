/**
 * Helper Cascading Dropdown Wilayah Indonesia
 * Terintegrasi dengan endpoint /wilayah/provinces dan /wilayah/regencies
 */

(function () {
  'use strict';

  window.initWilayahSelect = function (provSelect, kabSelect, selectedProv, selectedKab) {
    var $prov = typeof provSelect === 'string' ? document.getElementById(provSelect) : provSelect;
    var $kab = typeof kabSelect === 'string' ? document.getElementById(kabSelect) : kabSelect;

    if (!$prov || !$kab) return;

    $prov.innerHTML = '<option value="">-- Memuat Provinsi... --</option>';
    $kab.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';

    // Fetch daftar provinsi
    fetch(window.BASE_URL ? window.BASE_URL + 'wilayah/provinces' : '/wilayah/provinces')
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (json.status && json.data) {
          var html = '<option value="">-- Pilih Provinsi --</option>';
          json.data.forEach(function (p) {
            var isSel = (selectedProv && (selectedProv === p.code || selectedProv.toLowerCase() === p.name.toLowerCase())) ? 'selected' : '';
            html += '<option value="' + p.code + '" data-name="' + p.name + '" ' + isSel + '>' + p.name + '</option>';
          });
          $prov.innerHTML = html;

          // Jika ada selectedProv awal, load regencies
          if ($prov.value) {
            loadKab($prov.value, selectedKab);
          }
        }
      })
      .catch(function (err) {
        console.error('Gagal memuat provinsi:', err);
        $prov.innerHTML = '<option value="">-- Gagal Memuat Data --</option>';
      });

    // Event saat provinsi berganti
    $prov.addEventListener('change', function () {
      var provCode = this.value;
      if (!provCode) {
        $kab.innerHTML = '<option value="">-- Pilih Provinsi Dahulu --</option>';
        return;
      }
      loadKab(provCode, null);
    });

    function loadKab(provCode, curKab) {
      $kab.innerHTML = '<option value="">-- Memuat Kota/Kabupaten... --</option>';
      fetch((window.BASE_URL ? window.BASE_URL : '/') + 'wilayah/regencies/' + encodeURIComponent(provCode))
        .then(function (res) { return res.json(); })
        .then(function (json) {
          if (json.status && json.data) {
            var html = '<option value="">-- Pilih Kota/Kabupaten --</option>';
            json.data.forEach(function (k) {
              var isSel = (curKab && (curKab === k.code || curKab.toLowerCase() === k.name.toLowerCase())) ? 'selected' : '';
              html += '<option value="' + k.code + '" data-name="' + k.name + '" ' + isSel + '>' + k.name + '</option>';
            });
            $kab.innerHTML = html;
          }
        })
        .catch(function (err) {
          console.error('Gagal memuat kabupaten:', err);
          $kab.innerHTML = '<option value="">-- Gagal Memuat Data --</option>';
        });
    }
  };
})();
