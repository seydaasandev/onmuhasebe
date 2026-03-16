# onMuhasebe

Seyda AŞAN tarafından hazırlanmış ön muhasebe programı. Müşteri ürün stok bakiye takip web programı.

## Özellikler

- **Müşteri Yönetimi** – Müşteri ekleme, düzenleme, silme ve bakiye takibi
- **Ürün Yönetimi** – Ürün ekleme, düzenleme, silme; alış/satış fiyatı ve barkod yönetimi
- **Stok Takibi** – Stok giriş/çıkış hareketleri; kritik stok seviyesi uyarıları
- **Fatura Yönetimi** – Fatura oluşturma, ürün kalemleri ekleme, ödeme kaydetme
- **Dashboard** – Özet istatistikler, kritik stok uyarıları, son işlemler

## Kurulum ve Çalıştırma

### Gereksinimler

- Python 3.8+

### Adımlar

```bash
# Bağımlılıkları yükle
pip install -r requirements.txt

# Uygulamayı başlat
python app.py
```

Tarayıcıda `http://localhost:5000` adresini açın.

İlk açılışta örnek veri yüklemek için dashboard'daki **"Örnek Veri Yükle"** butonuna tıklayın.

## Teknolojiler

- **Python / Flask** – Web framework
- **SQLAlchemy / SQLite** – Veritabanı
- **Bootstrap 5** – Arayüz
- **Font Awesome** – İkonlar
