from flask_sqlalchemy import SQLAlchemy
from datetime import datetime

db = SQLAlchemy()

class Musteri(db.Model):
    __tablename__ = 'musteriler'
    id = db.Column(db.Integer, primary_key=True)
    ad = db.Column(db.String(100), nullable=False)
    soyad = db.Column(db.String(100), nullable=False)
    telefon = db.Column(db.String(20))
    email = db.Column(db.String(120))
    adres = db.Column(db.Text)
    bakiye = db.Column(db.Float, default=0.0)
    created_at = db.Column(db.DateTime, default=datetime.now)
    faturalar = db.relationship('Fatura', backref='musteri', lazy=True)

class Urun(db.Model):
    __tablename__ = 'urunler'
    id = db.Column(db.Integer, primary_key=True)
    ad = db.Column(db.String(200), nullable=False)
    barkod = db.Column(db.String(50), unique=True, nullable=True)
    birim = db.Column(db.String(20), default='adet')
    alis_fiyati = db.Column(db.Float, default=0.0)
    satis_fiyati = db.Column(db.Float, default=0.0)
    stok_miktari = db.Column(db.Float, default=0.0)
    kritik_stok = db.Column(db.Float, default=0.0)
    created_at = db.Column(db.DateTime, default=datetime.now)
    stok_hareketleri = db.relationship('StokHareketi', backref='urun', lazy=True)
    fatura_kalemleri = db.relationship('FaturaKalem', backref='urun', lazy=True)

class StokHareketi(db.Model):
    __tablename__ = 'stok_hareketleri'
    id = db.Column(db.Integer, primary_key=True)
    urun_id = db.Column(db.Integer, db.ForeignKey('urunler.id'), nullable=False)
    hareket_tipi = db.Column(db.String(10), nullable=False)  # 'giris' or 'cikis'
    miktar = db.Column(db.Float, nullable=False)
    birim_fiyat = db.Column(db.Float, default=0.0)
    aciklama = db.Column(db.Text)
    tarih = db.Column(db.DateTime, default=datetime.now)

class Fatura(db.Model):
    __tablename__ = 'faturalar'
    id = db.Column(db.Integer, primary_key=True)
    fatura_no = db.Column(db.String(50), unique=True, nullable=False)
    musteri_id = db.Column(db.Integer, db.ForeignKey('musteriler.id'), nullable=False)
    tarih = db.Column(db.DateTime, default=datetime.now)
    vade_tarihi = db.Column(db.DateTime)
    toplam_tutar = db.Column(db.Float, default=0.0)
    odenen_tutar = db.Column(db.Float, default=0.0)
    durum = db.Column(db.String(20), default='beklemede')  # beklemede, kismi_odendi, odendi
    notlar = db.Column(db.Text)
    created_at = db.Column(db.DateTime, default=datetime.now)
    kalemler = db.relationship('FaturaKalem', backref='fatura', lazy=True, cascade='all, delete-orphan')

class FaturaKalem(db.Model):
    __tablename__ = 'fatura_kalemleri'
    id = db.Column(db.Integer, primary_key=True)
    fatura_id = db.Column(db.Integer, db.ForeignKey('faturalar.id'), nullable=False)
    urun_id = db.Column(db.Integer, db.ForeignKey('urunler.id'), nullable=False)
    miktar = db.Column(db.Float, nullable=False)
    birim_fiyat = db.Column(db.Float, nullable=False)
    toplam = db.Column(db.Float, nullable=False)
