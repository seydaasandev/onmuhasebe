<?php
$reqId = 'REQ-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
$reqTime = date('d.m.Y H:i:s');
?>
<!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>403 | Erişim Engeli</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*:focus{outline:2px solid #1c4e80;outline-offset:2px}
html,body{height:100%}
body{margin:0;background:linear-gradient(135deg,#0e2a47,#1c4e80);display:flex;align-items:center;justify-content:center;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:#0b1220}
.card{background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(13,30,70,.18);width:min(760px,calc(100% - 32px));border:1px solid rgba(28,78,128,.12)}
.header{padding:28px 28px 0 28px;display:flex;align-items:center;gap:16px}
.icon{width:56px;height:56px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(135deg,#e74c3c,#f39c12);color:#fff}
.code{margin-left:auto;font-weight:700;color:#1c4e80}
.content{padding:0 28px 24px 28px}
.title{font-size:24px;margin:10px 0 6px 0;color:#12263a}
.desc{margin:0 0 16px 0;color:#4b5563}
.list{margin:0 0 16px 0;padding:0;list-style:none;color:#374151}
.list li{display:flex;align-items:flex-start;gap:10px;margin:8px 0}
.list .dot{width:10px;height:10px;border-radius:50%;background:#f39c12;margin-top:6px}
.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:8px}
.btn{display:inline-flex;align-items:center;justify-content:center;height:44px;padding:0 16px;border-radius:10px;border:1px solid transparent;text-decoration:none;cursor:pointer;transition:all .2s ease;font-weight:600}
.btn-primary{background:#1c4e80;color:#fff;border-color:#1c4e80}
.btn-primary:hover{background:#173f69;border-color:#173f69}
.btn-outline{background:#fff;color:#1c4e80;border-color:#1c4e80}
.btn-outline:hover{background:#f0f6fb}
.footer{display:flex;justify-content:space-between;align-items:center;padding:18px 28px;border-top:1px solid rgba(28,78,128,.12);color:#6b7280;font-size:14px;border-bottom-left-radius:16px;border-bottom-right-radius:16px}
.meta{display:flex;gap:14px;flex-wrap:wrap}
.brand{display:flex;align-items:center;gap:8px;color:#4b5563}
.brand img{height:24px}
</style>
</head>
<body>
<div class="card" role="region" aria-label="403 erişim engeli">
  <div class="header">
    <div class="icon" aria-hidden="true">
      <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M12 1.75c-3.59 0-6.5 2.91-6.5 6.5v2.5H4.5c-.97 0-1.75.78-1.75 1.75v7.25c0 .97.78 1.75 1.75 1.75h15c.97 0 1.75-.78 1.75-1.75V12.5c0-.97-.78-1.75-1.75-1.75h-1v-2.5c0-3.59-2.91-6.5-6.5-6.5Zm-4.5 9V8.25c0-2.49 2.01-4.5 4.5-4.5s4.5 2.01 4.5 4.5V10.75H7.5Zm4.5 3.25c.62 0 1.12.5 1.12 1.12v1.76c0 .62-.5 1.12-1.12 1.12s-1.12-.5-1.12-1.12v-1.76c0-.62.5-1.12 1.12-1.12Z" fill="currentColor"/></svg>
    </div>
    <div>
      <div class="title">Erişim Engellendi</div>
      <div class="desc">Bu dizin güvenlik politikaları gereği listelenemez.</div>
    </div>
    <div class="code">403</div>
  </div>
  <div class="content">
    <ul class="list" aria-label="Olası nedenler">
      <li><span class="dot"></span><span>Yetersiz yetki: Bu klasöre yalnızca sistem süreçleri erişebilir.</span></li>
      <li><span class="dot"></span><span>Oturum süresi dolmuş olabilir: Yeniden giriş yapmanız gerekir.</span></li>
      <li><span class="dot"></span><span>Bu yükleme klasörü doğrudan görüntülemeye kapalıdır.</span></li>
      <li><span class="dot"></span><span>Kaynağa ait URL yanlış veya erişim kapsamı dışındadır.</span></li>
    </ul>
    <div class="actions" aria-label="Eylemler">
      <a class="btn btn-primary" href="/eski/index.php">Giriş Yap</a>
      <button class="btn btn-outline" onclick="history.back()">Geri Dön</button>
      <a class="btn btn-outline" href="tel:+905391063431">Destek ile İletişim</a>
    </div>
  </div>
  <div class="footer">
    <div class="meta">
      <span>İstek Kimliği: <strong><?php echo htmlspecialchars($reqId); ?></strong></span>
      <span>Zaman: <strong><?php echo htmlspecialchars($reqTime); ?></strong></span>
    </div>
    <div class="brand">
      <img src="/eski/assets/Nextario-b.png" alt="Nextario">
      <span>Nextario</span>
    </div>
  </div>
</div>
</body>
</html>
