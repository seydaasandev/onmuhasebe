$(function() {
    $('#musteri_id').select2({ width: "100%" });

    $('#urun_id').select2({
        placeholder: "Ürün ara",
        width: "100%",
        ajax: {
            url: "urun-ara.php",
            dataType: "json",
            delay: 250,
            data: function(params) { return { term: params.term }; },
            processResults: function(data) { return data; }
        }
    });

    var birimFiyat = <?= $satis['urun_fiyat'] ?>;
    $('#kdv').val(<?= $satis['kdv'] ?>);
    $('#stok').val(<?= $satis['stok'] ?>);
    $('#adet').val(<?= $satis['adet'] ?>);
    $('#tutar').val(<?= $satis['tutar'] ?>);
    $('#kdv_tutar').val(0);
    $('#indirim_toplami').val(<?= $satis['indirim_toplami'] ?>);
    $('#indirim_yuzde').val(0);
    $('#genel_toplam').val(0);

    function hesaplaTutar() {
        var adet = parseFloat($('#adet').val()) || 0;
        var kdv = parseFloat($('#kdv').val()) || 0;
        var indirimYuzde = parseFloat($('#indirim_yuzde').val()) || 0;

        var tutar = birimFiyat * adet;
        var kdvTutar = tutar * kdv / 100;
        $('#kdv_tutar').val(kdvTutar.toFixed(2));

        var tutarKdvDahil = tutar + kdvTutar;
        $('#tutar').val(tutarKdvDahil.toFixed(2));

        var toplamIndirim = tutar * indirimYuzde / 100;
        $('#indirim_toplami').val(toplamIndirim.toFixed(2));

        var genelToplam = tutarKdvDahil - toplamIndirim;
        $('#genel_toplam').val(genelToplam.toFixed(2));
    }

    function resetUrun() {
        $('#adet').val('');
        $('#tutar').val('');
        $('#kdv_tutar').val('');
        $('#indirim_toplami').val('');
        $('#indirim_yuzde').val(0);
        $('#genel_toplam').val('');
    }

    $('#urun_id').on('change', function() {
        var urun_id = $(this).val();
        if(!urun_id) return;
        $.ajax({
            url: 'urun-detay.php',
            type: 'GET',
            data: { id: urun_id },
            dataType: 'json',
            success: function(data) {
                $('#kdv').val(data.kdv);
                $('#stok').val(data.stok);
                birimFiyat = data.birim_fiyat;  // unutma dediğin şekilde
                resetUrun();
            }
        });
    });

    $('#adet').on('input', function() {
        var adet = parseFloat($(this).val()) || 0;
        var stok = parseFloat($('#stok').val()) || 0;
        if(adet > stok){
            alert('Stok yetersiz! Maksimum ' + stok + ' adet seçebilirsiniz.');
            $(this).val(stok);
            adet = stok;
        }
        hesaplaTutar();
    });

    $('#indirim_yuzde').on('input', function() {
        var indirim = parseFloat($(this).val()) || 0;
        if(indirim > 100){
            $(this).val(100);
            indirim = 100;
        }
        hesaplaTutar();
    });

    hesaplaTutar();
});

$(function() {
    var birimFiyat = <?= $satis['urun_fiyat'] ?>;

    function hesaplaTutar() {
        var adet = parseFloat($('#adet').val()) || 0;
        var kdv = parseFloat($('#kdv').val()) || 0;
        var indirimYuzde = parseFloat($('#indirim_yuzde').val()) || 0;

        // Temel tutar
        var tutar = birimFiyat * adet;

        // KDV tutarı
        var kdvTutar = tutar * kdv / 100;

        // Tutar + KDV
        var tutarKdvDahil = tutar + kdvTutar;

        // Toplam indirim
        var toplamIndirim = tutar * indirimYuzde / 100;

        // Genel toplam = tutar + kdv - indirim
        var genelToplam = tutarKdvDahil - toplamIndirim;

        // Input alanlarını güncelle
        $('#tutar').val(tutarKdvDahil.toFixed(2));
        $('#kdv_tutar').val(kdvTutar.toFixed(2));
        $('#indirim_toplami').val(toplamIndirim.toFixed(2));
        $('#genel_toplam').val(genelToplam.toFixed(2));

        // List itemları güncelle
        $('#ara_toplam').text(tutar.toFixed(2) + ' ₺');
        $('#indirim_toplam').text(toplamIndirim.toFixed(2) + ' ₺');
        $('#kdv_toplam').text(kdvTutar.toFixed(2) + ' ₺');
        $('#genel_toplam_li').text(genelToplam.toFixed(2) + ' ₺');
    }

    function resetUrun() {
        $('#adet').val('');
        $('#tutar').val('');
        $('#kdv_tutar').val('');
        $('#indirim_toplami').val('');
        $('#indirim_yuzde').val(0);
        $('#genel_toplam').val('');
        $('#ara_toplam').text('0 ₺');
        $('#indirim_toplam').text('0 ₺');
        $('#kdv_toplam').text('0 ₺');
        $('#genel_toplam_li').text('0 ₺');
    }

    $('#urun_id').on('change', function() {
        var urun_id = $(this).val();
        if(!urun_id) return;
        $.ajax({
            url: 'urun-detay.php',
            type: 'GET',
            data: { id: urun_id },
            dataType: 'json',
            success: function(data) {
                $('#kdv').val(data.kdv);
                $('#stok').val(data.stok);
                birimFiyat = data.birim_fiyat;  // unutma dediğin şekilde
                resetUrun();
            }
        });
    });

    $('#adet, #indirim_yuzde').on('input', function() {
        var adet = parseFloat($('#adet').val()) || 0;
        var stok = parseFloat($('#stok').val()) || 0;
        if(adet > stok){
            alert('Stok yetersiz! Maksimum ' + stok + ' adet seçebilirsiniz.');
            $(this).val(stok);
            adet = stok;
        }
        hesaplaTutar();
    });

    // Sayfa açıldığında hesapla
    hesaplaTutar();
});