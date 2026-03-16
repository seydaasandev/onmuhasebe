from flask import Flask, render_template, request, redirect, url_for, flash
from models import db, Musteri, Urun, StokHareketi, Fatura, FaturaKalem
from datetime import datetime
import os

app = Flask(__name__)
app.config['SECRET_KEY'] = 'onmuhasebe-secret-key-2024'
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///onmuhasebe.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False

db.init_app(app)

def para_formatla(tutar):
    """Format currency as Turkish Lira"""
    if tutar is None:
        tutar = 0
    return f"₺{tutar:,.2f}".replace(',', 'X').replace('.', ',').replace('X', '.')

app.jinja_env.filters['para'] = para_formatla

@app.route('/')
def index():
    musteri_sayisi = Musteri.query.count()
    urun_sayisi = Urun.query.count()
    dusuk_stok = Urun.query.filter(Urun.stok_miktari <= Urun.kritik_stok).all()

    bekleyen_faturalar = Fatura.query.filter(Fatura.durum != 'odendi').all()
    toplam_alacak = sum(f.toplam_tutar - f.odenen_tutar for f in bekleyen_faturalar)

    son_faturalar = Fatura.query.order_by(Fatura.created_at.desc()).limit(5).all()
    son_hareketler = StokHareketi.query.order_by(StokHareketi.tarih.desc()).limit(5).all()

    return render_template('index.html',
        musteri_sayisi=musteri_sayisi,
        urun_sayisi=urun_sayisi,
        dusuk_stok=dusuk_stok,
        toplam_alacak=toplam_alacak,
        son_faturalar=son_faturalar,
        son_hareketler=son_hareketler
    )

# ---- MÜŞTERILER ----

@app.route('/musteriler')
def musteriler():
    arama = request.args.get('arama', '')
    if arama:
        liste = Musteri.query.filter(
            (Musteri.ad.ilike(f'%{arama}%')) |
            (Musteri.soyad.ilike(f'%{arama}%')) |
            (Musteri.telefon.ilike(f'%{arama}%')) |
            (Musteri.email.ilike(f'%{arama}%'))
        ).all()
    else:
        liste = Musteri.query.order_by(Musteri.ad).all()
    return render_template('musteriler/liste.html', musteriler=liste, arama=arama)

@app.route('/musteri/ekle', methods=['GET', 'POST'])
def musteri_ekle():
    if request.method == 'POST':
        try:
            musteri = Musteri(
                ad=request.form['ad'],
                soyad=request.form['soyad'],
                telefon=request.form.get('telefon', ''),
                email=request.form.get('email', ''),
                adres=request.form.get('adres', ''),
                bakiye=float(request.form.get('bakiye', 0))
            )
            db.session.add(musteri)
            db.session.commit()
            flash('Müşteri başarıyla eklendi.', 'success')
            return redirect(url_for('musteriler'))
        except Exception as e:
            db.session.rollback()
            flash(f'Hata: {str(e)}', 'danger')
    return render_template('musteriler/form.html', musteri=None, baslik='Yeni Müşteri Ekle')

@app.route('/musteri/<int:id>/duzenle', methods=['GET', 'POST'])
def musteri_duzenle(id):
    musteri = db.get_or_404(Musteri, id)
    if request.method == 'POST':
        try:
            musteri.ad = request.form['ad']
            musteri.soyad = request.form['soyad']
            musteri.telefon = request.form.get('telefon', '')
            musteri.email = request.form.get('email', '')
            musteri.adres = request.form.get('adres', '')
            db.session.commit()
            flash('Müşteri bilgileri güncellendi.', 'success')
            return redirect(url_for('musteri_detay', id=id))
        except Exception as e:
            db.session.rollback()
            flash(f'Hata: {str(e)}', 'danger')
    return render_template('musteriler/form.html', musteri=musteri, baslik='Müşteri Düzenle')

@app.route('/musteri/<int:id>/sil', methods=['POST'])
def musteri_sil(id):
    musteri = db.get_or_404(Musteri, id)
    try:
        db.session.delete(musteri)
        db.session.commit()
        flash('Müşteri silindi.', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Hata: {str(e)}', 'danger')
    return redirect(url_for('musteriler'))

@app.route('/musteri/<int:id>')
def musteri_detay(id):
    musteri = db.get_or_404(Musteri, id)
    faturalar = Fatura.query.filter_by(musteri_id=id).order_by(Fatura.tarih.desc()).all()
    return render_template('musteriler/detay.html', musteri=musteri, faturalar=faturalar)

# ---- ÜRÜNLER ----

@app.route('/urunler')
def urunler():
    arama = request.args.get('arama', '')
    if arama:
        liste = Urun.query.filter(
            (Urun.ad.ilike(f'%{arama}%')) |
            (Urun.barkod.ilike(f'%{arama}%'))
        ).all()
    else:
        liste = Urun.query.order_by(Urun.ad).all()
    return render_template('urunler/liste.html', urunler=liste, arama=arama)

@app.route('/urun/ekle', methods=['GET', 'POST'])
def urun_ekle():
    if request.method == 'POST':
        try:
            barkod = request.form.get('barkod', '').strip() or None
            urun = Urun(
                ad=request.form['ad'],
                barkod=barkod,
                birim=request.form.get('birim', 'adet'),
                alis_fiyati=float(request.form.get('alis_fiyati', 0)),
                satis_fiyati=float(request.form.get('satis_fiyati', 0)),
                stok_miktari=float(request.form.get('stok_miktari', 0)),
                kritik_stok=float(request.form.get('kritik_stok', 0))
            )
            db.session.add(urun)
            db.session.commit()
            flash('Ürün başarıyla eklendi.', 'success')
            return redirect(url_for('urunler'))
        except Exception as e:
            db.session.rollback()
            flash(f'Hata: {str(e)}', 'danger')
    return render_template('urunler/form.html', urun=None, baslik='Yeni Ürün Ekle')

@app.route('/urun/<int:id>/duzenle', methods=['GET', 'POST'])
def urun_duzenle(id):
    urun = db.get_or_404(Urun, id)
    if request.method == 'POST':
        try:
            barkod = request.form.get('barkod', '').strip() or None
            urun.ad = request.form['ad']
            urun.barkod = barkod
            urun.birim = request.form.get('birim', 'adet')
            urun.alis_fiyati = float(request.form.get('alis_fiyati', 0))
            urun.satis_fiyati = float(request.form.get('satis_fiyati', 0))
            urun.kritik_stok = float(request.form.get('kritik_stok', 0))
            db.session.commit()
            flash('Ürün bilgileri güncellendi.', 'success')
            return redirect(url_for('urun_detay', id=id))
        except Exception as e:
            db.session.rollback()
            flash(f'Hata: {str(e)}', 'danger')
    return render_template('urunler/form.html', urun=urun, baslik='Ürün Düzenle')

@app.route('/urun/<int:id>/sil', methods=['POST'])
def urun_sil(id):
    urun = db.get_or_404(Urun, id)
    try:
        db.session.delete(urun)
        db.session.commit()
        flash('Ürün silindi.', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Hata: {str(e)}', 'danger')
    return redirect(url_for('urunler'))

@app.route('/urun/<int:id>')
def urun_detay(id):
    urun = db.get_or_404(Urun, id)
    hareketler = StokHareketi.query.filter_by(urun_id=id).order_by(StokHareketi.tarih.desc()).all()
    return render_template('urunler/detay.html', urun=urun, hareketler=hareketler)

# ---- STOK ----

@app.route('/stok')
def stok():
    urun_id = request.args.get('urun_id', '')
    if urun_id:
        hareketler = StokHareketi.query.filter_by(urun_id=urun_id).order_by(StokHareketi.tarih.desc()).all()
    else:
        hareketler = StokHareketi.query.order_by(StokHareketi.tarih.desc()).all()
    urun_listesi = Urun.query.order_by(Urun.ad).all()
    return render_template('stok/liste.html', hareketler=hareketler, urunler=urun_listesi, secili_urun=urun_id)

@app.route('/stok/ekle', methods=['GET', 'POST'])
def stok_ekle():
    urun_listesi = Urun.query.order_by(Urun.ad).all()
    if request.method == 'POST':
        try:
            urun_id = int(request.form['urun_id'])
            urun = db.get_or_404(Urun, urun_id)
            miktar = float(request.form['miktar'])
            hareket_tipi = request.form['hareket_tipi']

            hareket = StokHareketi(
                urun_id=urun_id,
                hareket_tipi=hareket_tipi,
                miktar=miktar,
                birim_fiyat=float(request.form.get('birim_fiyat', 0)),
                aciklama=request.form.get('aciklama', ''),
                tarih=datetime.now()
            )

            if hareket_tipi == 'giris':
                urun.stok_miktari += miktar
            elif hareket_tipi == 'cikis':
                urun.stok_miktari -= miktar

            db.session.add(hareket)
            db.session.commit()
            flash('Stok hareketi başarıyla eklendi.', 'success')
            return redirect(url_for('stok'))
        except Exception as e:
            db.session.rollback()
            flash(f'Hata: {str(e)}', 'danger')
    return render_template('stok/form.html', urunler=urun_listesi)

# ---- FATURALAR ----

@app.route('/faturalar')
def faturalar():
    durum = request.args.get('durum', '')
    if durum:
        liste = Fatura.query.filter_by(durum=durum).order_by(Fatura.tarih.desc()).all()
    else:
        liste = Fatura.query.order_by(Fatura.tarih.desc()).all()
    return render_template('faturalar/liste.html', faturalar=liste, secili_durum=durum)

@app.route('/fatura/ekle', methods=['GET', 'POST'])
def fatura_ekle():
    musteri_listesi = Musteri.query.order_by(Musteri.ad).all()
    urun_listesi = Urun.query.order_by(Urun.ad).all()
    if request.method == 'POST':
        try:
            musteri_id = int(request.form['musteri_id'])
            musteri = db.get_or_404(Musteri, musteri_id)

            tarih = datetime.strptime(request.form['tarih'], '%Y-%m-%d')
            vade_str = request.form.get('vade_tarihi', '')
            vade_tarihi = datetime.strptime(vade_str, '%Y-%m-%d') if vade_str else None

            fatura = Fatura(
                fatura_no='TEMP',
                musteri_id=musteri_id,
                tarih=tarih,
                vade_tarihi=vade_tarihi,
                toplam_tutar=0.0,
                odenen_tutar=0.0,
                durum='beklemede',
                notlar=request.form.get('notlar', '')
            )
            db.session.add(fatura)
            db.session.flush()

            fatura.fatura_no = f"FTR-{tarih.year}-{fatura.id:04d}"

            urun_ids = request.form.getlist('urun_id[]')
            miktarlar = request.form.getlist('miktar[]')
            birim_fiyatlar = request.form.getlist('birim_fiyat[]')

            toplam = 0.0
            for uid, mik, bf in zip(urun_ids, miktarlar, birim_fiyatlar):
                if uid and mik and bf:
                    urun = db.session.get(Urun, int(uid))
                    if urun:
                        miktar = float(mik)
                        birim_fiyat = float(bf)
                        kalem_toplam = miktar * birim_fiyat

                        kalem = FaturaKalem(
                            fatura_id=fatura.id,
                            urun_id=urun.id,
                            miktar=miktar,
                            birim_fiyat=birim_fiyat,
                            toplam=kalem_toplam
                        )
                        db.session.add(kalem)
                        toplam += kalem_toplam

                        urun.stok_miktari -= miktar
                        stok_hareket = StokHareketi(
                            urun_id=urun.id,
                            hareket_tipi='cikis',
                            miktar=miktar,
                            birim_fiyat=birim_fiyat,
                            aciklama=f'Fatura: {fatura.fatura_no}',
                            tarih=tarih
                        )
                        db.session.add(stok_hareket)

            fatura.toplam_tutar = toplam
            musteri.bakiye += toplam

            db.session.commit()
            flash(f'Fatura {fatura.fatura_no} başarıyla oluşturuldu.', 'success')
            return redirect(url_for('fatura_detay', id=fatura.id))
        except Exception as e:
            db.session.rollback()
            flash(f'Hata: {str(e)}', 'danger')
    return render_template('faturalar/form.html', musteriler=musteri_listesi, urunler=urun_listesi, bugun=datetime.now().strftime('%Y-%m-%d'))

@app.route('/fatura/<int:id>')
def fatura_detay(id):
    fatura = db.get_or_404(Fatura, id)
    return render_template('faturalar/detay.html', fatura=fatura)

@app.route('/fatura/<int:id>/odeme', methods=['POST'])
def fatura_odeme(id):
    fatura = db.get_or_404(Fatura, id)
    try:
        odeme_miktari = float(request.form['odeme_miktari'])
        kalan = fatura.toplam_tutar - fatura.odenen_tutar

        if odeme_miktari > kalan:
            odeme_miktari = kalan

        fatura.odenen_tutar += odeme_miktari
        fatura.musteri.bakiye -= odeme_miktari

        if fatura.odenen_tutar >= fatura.toplam_tutar:
            fatura.durum = 'odendi'
        elif fatura.odenen_tutar > 0:
            fatura.durum = 'kismi_odendi'

        db.session.commit()
        flash(f'{para_formatla(odeme_miktari)} ödeme kaydedildi.', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Hata: {str(e)}', 'danger')
    return redirect(url_for('fatura_detay', id=id))

@app.route('/init-data')
def init_data():
    try:
        m1 = Musteri(ad='Ahmet', soyad='Yılmaz', telefon='0532 111 2233', email='ahmet@email.com', adres='Ankara, Türkiye')
        m2 = Musteri(ad='Fatma', soyad='Kaya', telefon='0543 444 5566', email='fatma@email.com', adres='İstanbul, Türkiye')
        m3 = Musteri(ad='Mehmet', soyad='Demir', telefon='0555 777 8899', email='mehmet@email.com', adres='İzmir, Türkiye')
        db.session.add_all([m1, m2, m3])

        u1 = Urun(ad='A4 Kağıt (500 yaprak)', barkod='8690123456789', birim='paket', alis_fiyati=45.0, satis_fiyati=65.0, stok_miktari=50, kritik_stok=10)
        u2 = Urun(ad='Kalem (Tükenmez)', barkod='8690987654321', birim='adet', alis_fiyati=3.5, satis_fiyati=6.0, stok_miktari=200, kritik_stok=50)
        u3 = Urun(ad='Zımba Makinesi', barkod='8691234500001', birim='adet', alis_fiyati=85.0, satis_fiyati=130.0, stok_miktari=5, kritik_stok=3)
        u4 = Urun(ad='Dosya (Plastik)', barkod='8691234500002', birim='adet', alis_fiyati=8.0, satis_fiyati=14.0, stok_miktari=2, kritik_stok=10)
        db.session.add_all([u1, u2, u3, u4])
        db.session.flush()

        sh1 = StokHareketi(urun_id=u1.id, hareket_tipi='giris', miktar=50, birim_fiyat=45.0, aciklama='İlk stok girişi')
        sh2 = StokHareketi(urun_id=u2.id, hareket_tipi='giris', miktar=200, birim_fiyat=3.5, aciklama='İlk stok girişi')
        sh3 = StokHareketi(urun_id=u3.id, hareket_tipi='giris', miktar=5, birim_fiyat=85.0, aciklama='İlk stok girişi')
        sh4 = StokHareketi(urun_id=u4.id, hareket_tipi='giris', miktar=2, birim_fiyat=8.0, aciklama='İlk stok girişi')
        db.session.add_all([sh1, sh2, sh3, sh4])

        db.session.commit()
        flash('Örnek veriler başarıyla yüklendi.', 'success')
    except Exception as e:
        db.session.rollback()
        flash(f'Hata: {str(e)}', 'danger')
    return redirect(url_for('index'))

if __name__ == '__main__':
    with app.app_context():
        db.create_all()
    debug_mode = os.environ.get('FLASK_DEBUG', 'false').lower() == 'true'
    app.run(debug=debug_mode)
