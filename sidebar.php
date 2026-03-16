 <div class="app-menu navbar-menu">
            <!-- LOGO -->
            <div class="navbar-brand-box">
                <!-- Dark Logo-->
                <a href="panel.php" class="logo logo-dark">
                    <span class="logo-sm">
                        <img src="assets/Nextario.png" alt="" height="20">
                    </span>
                    <span class="logo-lg">
                        <img src="assets/Nextario.png" alt="" height="90">
                    </span>
                </a>
                <!-- Light Logo-->
                <a href="panel.php" class="logo logo-light">
                    <span class="logo-sm">
                        <img src="assets/Nextario-b.png" alt="" height="20">
                    </span>
                    <span class="logo-lg">
                        <img src="assets/Nextario-b.png" alt="" height="90">
                    </span>
                </a>
                <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
                    <i class="ri-record-circle-line"></i>
                </button>
            </div>
    
          
            <div id="scrollbar">
                <div class="container-fluid">


                    <div id="two-column-menu">
                    </div>
                    <ul class="navbar-nav" id="navbar-nav">
                        <li class="menu-title"><span data-key="t-menu">Ön Muhasebe Programı</span></li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="panel.php" >
                                <i class="ri-dashboard-2-line"></i> <span data-key="t-dashboards">Anasayfa</span>
                            </a>
                           
                        </li>
                         <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarusers" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarusers">
                                <i class="ri-account-circle-line"></i> <span data-key="t-apps">Kullanıcılar</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarusers">
                                <ul class="nav nav-sm flex-column">
                                   
                                    <li class="nav-item">
                                        <a href="kullanicilar.php" class="nav-link" data-key="urunler"> Tüm Kullanıcılar </a>
                                    </li>
                                  <li class="nav-item">
                                        <a href="yeni-kullanici.php" class="nav-link" data-key="yeniurun"> Yeni Kullanıcı Ekle </a>
                                    </li>
                                    
                                </ul>
                            </div>
                        </li>
                        
                        
                       
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarApps" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarApps">
                                <i class="ri-apps-2-line"></i> <span data-key="t-apps">Ürünler</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarApps">
                                <ul class="nav nav-sm flex-column">
                                   
                                    <li class="nav-item">
                                        <a href="urunler.php" class="nav-link" data-key="urunler"> Tüm Ürünler </a>
                                    </li>
                                  <li class="nav-item">
                                        <a href="yeni-urun.php" class="nav-link" data-key="yeniurun"> Yeni Ürün Ekle </a>
                                    </li>
                                    
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarApps1" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarApps1">
                                <i class="ri-user-add-fill"></i> <span data-key="t-apps">Müşteriler</span>
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarApps1">
                                <ul class="nav nav-sm flex-column">
                                   
                                    <li class="nav-item">
                                        <a href="tum-musteriler.php" class="nav-link" data-key="musteriler"> Tüm Müşteriler </a>
                                    </li>
                                  <li class="nav-item">
                                        <a href="yeni-musteri.php" class="nav-link" data-key="yenimsuteri"> Yeni Müşteri Ekle </a>
                                    </li>
                                    
                                </ul>
                            </div>
                        </li>
                        

                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarLayouts" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarLayouts">
                                <i class="ri-wallet-3-fill"></i> <span data-key="t-layouts">Ödemeler</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarLayouts">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="tum-odemeler.php" class="nav-link" data-key="t-detached">Ödemeler</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="odeme-ekle.php" class="nav-link" data-key="t-horizontal">Yeni Ödeme Kaydet</a>
                                    </li>
                                    
                                   
                                </ul>
                            </div>
                        </li> 
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarPages" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarPages">
                                <i class="ri-newspaper-line"></i> <span data-key="t-layouts">Faturalar</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarPages">
                                <ul class="nav nav-sm flex-column">
                                    <li class="nav-item">
                                        <a href="faturalar.php" class="nav-link" data-key="t-horizontal">Faturaları Görüntüle</a>
                                    <!-- </li>
                                      <li class="nav-item">
                                        <a href="dis-faturalar.php" class="nav-link" data-key="t-horizontal">Dış Faturalar</a>
                                    </li> -->
                                    
                                   
                                </ul>
                            </div>
                        </li> 
                            <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarAuth" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarAuth">
                                <i class="ri-layout-3-line"></i> <span data-key="t-layouts">Satışlar</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarAuth">
                                <ul class="nav nav-sm flex-column">
                                     <li class="nav-item">
                                        <a href="tum-satislar.php" class="nav-link" data-key="t-detached">Satışlar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="yeni-satis.php" class="nav-link" data-key="t-horizontal">Yeni Satış Kaydet</a>
                                    </li>
                                   
                                   
                                </ul>
                            </div>
                        </li> 
                         <!--
                         <li class="nav-item">
                            <a class="nav-link menu-link" href="#sidebarUI" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarUI">
                                <i class="ri-shopping-cart-2-line"></i> <span data-key="t-layouts">Siparişler</span> 
                            </a>
                            <div class="collapse menu-dropdown" id="sidebarUI">
                                <ul class="nav nav-sm flex-column">
                                     <li class="nav-item">
                                        <a href="siparis.php" class="nav-link" data-key="t-detached">Siparişler</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="yeni-siparis.php" class="nav-link" data-key="t-horizontal">Yeni Sipariş Kaydet</a>
                                    </li>
                                   
                                   
                                </ul>
                            </div>
                        </li>
                        -->

                        <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Raporlar</span></li>
                             <!-- <li class="nav-item">
                            <a class="nav-link menu-link" href="pazarlamaciistatistik.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Pazarlamacı İstatikleri</span>
                            </a>
                        </li>   -->
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="ekstre-sorgula.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Müşteri Ekstresi</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="bakiyeli-ekstre-sorgula.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Bakiyeli Müşteri Ekstresi</span>
                            </a>
                        </li>
                       <!--  <li class="nav-item">
                            <a class="nav-link menu-link" href="pazarlamaci-ekstre.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Pazarlamacı Ekstresi</span>
                            </a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link menu-link" href="bolgeye-gore-ekstre.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Bölgeye Göre Ekstre</span>
                            </a>
                        </li>
                        
                         <li class="menu-title"><i class="ri-more-fill"></i> <span data-key="t-pages">Güncellemeler</span></li>
                         <li class="nav-item">
                            <a class="nav-link menu-link" href="kur.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Ürün Fiyatları Güncelle</span>
                            </a>
                        </li>
                          <!-- <li class="nav-item">
                            <a class="nav-link menu-link" href="musteri-aktar.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Müşteri Aktar</span>
                            </a>
                        </li> -->
                         <li class="nav-item">
                            <a class="nav-link menu-link" href="stok-raporu.php">
                                <i class="ri-honour-line"></i> <span data-key="t-widgets">Stok Raporu</span>
                            </a>
                        </li>
                         </ul>
                </div>
                <!-- Sidebar -->
            </div>

            <div class="sidebar-background"></div>
        </div>