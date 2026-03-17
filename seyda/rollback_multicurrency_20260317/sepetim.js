$(document).ready(function () {

  // ADIM 1 – MÜŞTERİ
  const $stepBtn = $('#stepMusteriBtn');       // Sol menüdeki Adım 1
  const $musteriSelect = $('#musteri_id');     // Müşteri select
  const $btnDevam = $('#btnMusteriDevam');    // Devam et butonu

  // Başlangıçta buton pasif
  $btnDevam.prop('disabled', true);

  // Select2 initialize
  $musteriSelect.select2({
    placeholder: "Müşteri seçiniz",
    allowClear: true,
    width: '100%'
  });

  // Müşteri seçildiğinde
  $musteriSelect.on('select2:select', function (e) {
    const musteriAdi = e.params.data.text;
   $stepBtn.html(`
  <i class="ri-user-fill me-2"></i> Adım 1 -
  <span class="badge bg-success">
    <i class="mdi mdi-circle-medium"></i> ${musteriAdi}
  </span>
`);
    $btnDevam.prop('disabled', false);
  });

  // Seçim temizlenirse
  $musteriSelect.on('select2:clear', function () {
    $stepBtn.html('<i class="ri-user-fill me-2"></i> Adım 1 - Müşteri');
    $btnDevam.prop('disabled', true);
  });

  // DEVAM ET butonu tab geçişi
  $btnDevam.on('click', function (e) {
    if (!$musteriSelect.val()) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Uyarı',
        text: 'Lütfen önce müşteri seçin'
      });
      return;
    }

    const nextTabId = $(this).data('nexttab');
    const $nextNavBtn = $('.nav-pills button[data-bs-target="#' + nextTabId + '"]');
    if ($nextNavBtn.length) {
      new bootstrap.Tab($nextNavBtn[0]).show();
    }
  });

});

$(document).ready(function () {

  const $musteriSelect = $('select[name="musteri_id"]');
  const $devamBtn = $('.nexttab');

  /* ==========================
     MÜŞTERİ SELECT2
  ========================== */
  $musteriSelect.select2({
    placeholder: "Müşteri seçiniz",
    allowClear: true,
    width: '100%'
  });

  // başlangıçta pasif
  $devamBtn.prop('disabled', true);

  /* ==========================
     SELECT2 SEÇİLDİ
  ========================== */
  $musteriSelect.on('select2:select', function (e) {
    if (e.params.data && e.params.data.id) {
      $devamBtn.prop('disabled', false);
    }
  });

  /* ==========================
     SELECT2 TEMİZLENDİ
  ========================== */
  $musteriSelect.on('select2:clear', function () {
    $devamBtn.prop('disabled', true);
  });

  /* ==========================
     DEVAM ET
  ========================== */
  $('.nexttab').on('click', function (e) {

    if (!$musteriSelect.val()) {
      e.preventDefault();
      Swal.fire({
        icon: 'warning',
        title: 'Uyarı',
        text: 'Lütfen önce müşteri seçin'
      });
      return;
    }

    const nextTabId = $(this).data('nexttab');
    const $nextNavBtn = $(
      '.nav-pills button[data-bs-target="#' + nextTabId + '"]'
    );

    if ($nextNavBtn.length) {
      new bootstrap.Tab($nextNavBtn[0]).show();
    }
  });

});
;

$(document).ready(function () {

  let sepet = [];

    /* ==========================
      SELECT2 – ÜRÜN ARAMA
  ========================== */
  $('#urun_id').select2({
    placeholder: "Ürün ara (isim / barkod)",
    allowClear: true,
    minimumInputLength: 1,
    width: '100%',
    dropdownParent: $('#step-urun'),
    ajax: {
      url: 'urun-ara.php',
      type: 'GET',
      dataType: 'json',
      delay: 300,
      data: function (params) {
        return { term: params.term };
      },
      processResults: function (data) {
        return { results: data.results };
      }
    }
  });

  /* ==========================
     ÜRÜN SEÇİLDİ
  ========================== */
  $('#urun_id').on('select2:select', function (e) {

    const urunId = e.params.data.id;
    temizleUrunAlanlari();

    $.getJSON('urun-detay.php', { id: urunId }, function (data) {

      if (data.error) {
        hataMesaji(data.error);
        return;
      }

      var fiyat = data.birim_fiyat || 0;

      $('#birim_fiyat').val(fiyat);

      $('#kdv').val(data.kdv);
      $('#stok').val(data.stok);
      $('#adet').val(1);

    }).fail(function () {
      hataMesaji('Ürün detayları alınamadı');
    });
  });

  /* ==========================
     ADET – STOK KONTROL
  ========================== */
  $('#adet').on('input', function () {
    const adet = parseInt($(this).val()) || 0;
    const stok = parseInt($('#stok').val()) || 0;

    if (stok > 0 && adet > stok) {
      $('#adet_error').text('Stok yetersiz! Max: ' + stok).removeClass('d-none');
    } else {
      $('#adet_error').addClass('d-none').text('');
    }
  });

  /* ==========================
     SEPETE EKLE
  ========================== */
  $('#sepeteEkle').on('click', function () {

    const urunData = $('#urun_id').select2('data')[0];
    if (!urunData) return hataMesaji('Ürün seçiniz');

    const fiyat   = parseFloat($('#birim_fiyat').val());
    const adet    = parseInt($('#adet').val());
    const stok    = parseInt($('#stok').val());
    const kdv     = parseFloat($('#kdv').val());
    const indirim = parseFloat($('#indirim').val()) || 0;

    if (adet > stok) return hataMesaji('Stok yetersiz');

    const indirimOran = Math.max(0, Math.min(100, indirim));
    const tutar = fiyat * adet;                         // Ara toplam (KDV hariç)
    const indirimTutar = tutar * indirimOran / 100;    // İndirim, sadece ara toplamdan düşer
    const indirimliTutar = tutar - indirimTutar;       // İndirim sonrası ara toplam
    const kdvTutar = tutar * kdv / 100;                // KDV, indirimsiz tutardan hesaplanır
    const genel = indirimliTutar + kdvTutar;           // Genel toplam

    sepet.push({
      id: urunData.id,
      ad: urunData.text,
      fiyat,
      adet,
      kdv,
      indirim: indirimOran,
      tutar,
      indirimTutar,
      kdvTutar,
      genel
    });

    sepetiGuncelle();
  });

  /* ==========================
     SEPETTEN SİL
  ========================== */
  window.sepettenSil = function (i) {
    sepet.splice(i, 1);
    sepetiGuncelle();
  };

  /* ==========================
     SEPET GÜNCELLE
  ========================== */
 function sepetiGuncelle() {

  const s = '₺';

  const liste = $('#sepet_listesi').empty();

  if (sepet.length === 0) {
    liste.html('<li class="list-group-item text-center text-muted">Sepet boş</li>');
    $('#ara_toplam').text('0 ' + s);
    $('#indirim_toplam').text('0 ' + s);
    $('#kdv_toplam').text('0 ' + s);
    $('#genel_toplam').text('0 ' + s);
    return;
  }

  let ara = 0, ind = 0, kdvT = 0, genel = 0;

  sepet.forEach((u, i) => {

    ara   += u.tutar;
    ind   += u.indirimTutar;
    kdvT  += u.kdvTutar;
    genel += u.genel;

    liste.append(`
      <li class="list-group-item d-flex justify-content-between">
        <div>
          <strong>${u.ad}</strong><br>
          <small>${u.adet} × ${u.fiyat} ${s}</small><br>
          <small>İndirim (%${u.indirim}): ${u.indirimTutar.toFixed(2)} ${s}</small><br>
          <small>KDV (%${u.kdv}): ${u.kdvTutar.toFixed(2)} ${s}</small>
        </div>
        <div class="text-end">
          <strong>${u.genel.toFixed(2)} ${s}</strong><br>
          <button class="btn btn-sm btn-danger" onclick="sepettenSil(${i})">✖</button>
        </div>
      </li>
    `);
  });

  $('#ara_toplam').text(ara.toFixed(2) + ' ' + s);
  $('#indirim_toplam').text(ind.toFixed(2) + ' ' + s);
  $('#kdv_toplam').text(kdvT.toFixed(2) + ' ' + s);
  $('#genel_toplam').text(genel.toFixed(2) + ' ' + s);

  $('#sepet_json').val(JSON.stringify(sepet));
}

  function hataMesaji(msg) {
    $('#adet_error').text(msg).removeClass('d-none');
  }

  function temizleUrunAlanlari() {
    $('#birim_fiyat, #kdv, #stok').val('');
    $('#adet_error').addClass('d-none').text('');
  }

});
