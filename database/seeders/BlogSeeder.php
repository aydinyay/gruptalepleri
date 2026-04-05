<?php

namespace Database\Seeders;

use App\Models\BlogKategorisi;
use App\Models\BlogYazisi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Kategoriler
        $grupUcus   = BlogKategorisi::firstOrCreate(['slug' => 'grup-ucus-rehberi'],  ['ad' => 'Grup Uçuş Rehberi']);
        $charter    = BlogKategorisi::firstOrCreate(['slug' => 'charter-kiralama'],   ['ad' => 'Charter & Kiralama']);
        $operasyon  = BlogKategorisi::firstOrCreate(['slug' => 'seyahat-operasyonu'], ['ad' => 'Seyahat Operasyonu']);

        $yazılar = [
            // ─── Grup Uçuş Rehberi ───────────────────────────────────────────
            [
                'kategori_id'       => $grupUcus->id,
                'baslik'            => 'Grup Uçuş Talebi Nasıl Yapılır? Acente Rehberi 2025',
                'slug'              => 'grup-ucus-talebi-nasil-yapilir',
                'ozet'             => '50 kişilik bir okul gezisinden 500 kişilik hacca kadar — grup uçuş talebi oluşturmanın adım adım rehberi.',
                'meta_baslik'      => 'Grup Uçuş Talebi Nasıl Yapılır? 2025 Rehberi',
                'meta_aciklama'    => 'Seyahat acenteleri için grup uçuş talebi oluşturma, fiyat alma ve onay süreçleri. 2025 güncel rehber.',
                'yazar'            => 'GrupTalepleri Editör',
                'durum'            => 'yayinda',
                'yayinlanma_tarihi' => now()->subDays(10),
                'goruntuleme'      => 0,
                'icerik'           => <<<'HTML'
<h2>Grup Uçuşu Nedir?</h2>
<p>Grup uçuşu, genellikle <strong>10 kişi ve üzeri</strong> yolcuların aynı uçuşta seyahat etmesi için özel fiyat ve koltuk bloğu ayrılmasıdır. Havayolları, grup rezervasyonlarına özel indirimler ve esnek ödeme koşulları sunar.</p>

<h2>Kaç Kişiden İtibaren Grup Talebi Açılabilir?</h2>
<p>Türkiye'de faaliyet gösteren çoğu havayolu için <strong>minimum 10 yolcu</strong> grup talebi eşiğini oluşturur. THY, Pegasus ve SunExpress gibi havayollarında bu sayı 10-15 arasında değişir. Charter kiralamada ise uçağın tamamı kiralanır.</p>
<ul>
<li><strong>10-49 yolcu:</strong> Reguler hat üzerinden grup bloğu</li>
<li><strong>50-199 yolcu:</strong> Özel tahsis + ekstra bagaj imkânları</li>
<li><strong>200+ yolcu:</strong> Charter kiralama veya çok sayıda blok kombinasyonu</li>
</ul>

<h2>GrupTalepleri ile Talep Oluşturma</h2>
<p>GrupTalepleri platformunda talep oluşturmak için şu adımları izleyin:</p>
<ol>
<li>Ücretsiz acente hesabınızı açın</li>
<li>"Yeni Talep" butonuna tıklayın</li>
<li>Kalkış / varış noktası, tarih ve yolcu sayısını girin</li>
<li>Talebi yayınlayın — operatörler size fiyat tekliflerini iletir</li>
<li>Teklifleri karşılaştırın ve müşterinize en uygun seçeneği sunun</li>
</ol>

<h2>Teklifleri Karşılaştırırken Dikkat Edilecekler</h2>
<p>Sadece bilet fiyatına bakmak yanıltıcı olabilir. Aşağıdaki kalemleri toplam maliyet hesabına katın:</p>
<ul>
<li>Kişi başı bagaj ücreti (genellikle 20-23 kg dahil)</li>
<li>Kalkış havalimanı vergisi ve hizmet bedeli</li>
<li>İptal/değişiklik politikaları</li>
<li>Koltuk bloğunun düşüş süresi (kaç güne kadar geri iade edebilirsiniz?)</li>
</ul>

<h2>Sonuç</h2>
<p>Doğru platform ve hazırlıkla grup uçuş talebi oluşturmak hem hızlı hem de kârlıdır. GrupTalepleri'nde kayıtlı onlarca operatör, talebinize saatler içinde yanıt verir.</p>
HTML,
            ],

            [
                'kategori_id'       => $grupUcus->id,
                'baslik'            => 'Hac ve Umre Grup Uçuşlarında Acente Süreci',
                'slug'              => 'hac-umre-grup-ucusu-acente-sureci',
                'ozet'             => 'Hac ve umre organizasyonlarında grup uçuş süreçleri, kota yönetimi ve Diyanet koordinasyonu hakkında bilmeniz gerekenler.',
                'meta_baslik'      => 'Hac Umre Grup Uçuşu | Acente Rehberi',
                'meta_aciklama'    => 'Hac ve umre turlarında grup uçuş talebi süreci, kota yönetimi ve operatör koordinasyonu. Seyahat acenteleri için kapsamlı rehber.',
                'yazar'            => 'GrupTalepleri Editör',
                'durum'            => 'yayinda',
                'yayinlanma_tarihi' => now()->subDays(7),
                'goruntuleme'      => 0,
                'icerik'           => <<<'HTML'
<h2>Hac ve Umre Uçuşlarının Özellikleri</h2>
<p>Dini seyahatler, standart grup uçuşlarından farklı bir süreç gerektirir. Diyanet İşleri Başkanlığı koordinasyonunda yürütülen <strong>hac organizasyonları</strong> için kota sistemi işler; umreler ise yıl boyu gerçekleştirilebilir.</p>

<h2>Hac Kota Süreci</h2>
<p>Türkiye'ye tahsis edilen hac kontenjanı Diyanet tarafından il bazında dağıtılır. Kayıtlı hac turcu acenteler bu kotadan yararlanabilir. Süreci şöyle özetleyebiliriz:</p>
<ul>
<li>İl Müftülüğü'ne başvuru ve kayıt</li>
<li>Kota tahsisi ve yolcu listesi onayı</li>
<li>Grup uçuş rezervasyonu (charter veya reguler hat)</li>
<li>Vize ve pasaport koordinasyonu</li>
</ul>

<h2>Umre Grup Uçuşlarında Dikkat Edilecekler</h2>
<p>Umre turları, yıl genelinde planlanabildiğinden daha esnek bir süreç sunar. Ancak özellikle Ramazan döneminde kapasite ciddi biçimde daralır. Bu nedenle <strong>en az 3-4 ay önceden</strong> grup bloğu ayırtmanız tavsiye edilir.</p>

<h2>GrupTalepleri'nde Hac/Umre Talebi</h2>
<p>Platformumuzda "Hac/Umre" kategorisinde talep oluştururken şu bilgileri eksiksiz girin:</p>
<ul>
<li>Kalkış şehri ve havalimanı</li>
<li>Medine veya Cidde varış tercihi</li>
<li>Kesin veya tahmini yolcu sayısı</li>
<li>Konaklamanın dahil edilip edilmeyeceği</li>
</ul>
<p>Uzman operatörler, talebinize özel paket fiyatıyla dönecektir.</p>
HTML,
            ],

            // ─── Charter & Kiralama ───────────────────────────────────────────
            [
                'kategori_id'       => $charter->id,
                'baslik'            => 'Charter Uçak Kiralama: Fiyatlar, Uçak Tipleri ve Süreç',
                'slug'              => 'charter-ucak-kiralama-fiyatlar-ucak-tipleri',
                'ozet'             => 'Charter kiralama nasıl fiyatlanır? Hangi uçak tipi ne kadar kişi taşır? Tüm detaylar bu rehberde.',
                'meta_baslik'      => 'Charter Uçak Kiralama Fiyatları ve Süreç 2025',
                'meta_aciklama'    => 'Charter uçak kiralama fiyatları, uçak tipleri, kapasiteler ve rezervasyon süreci hakkında kapsamlı rehber.',
                'yazar'            => 'GrupTalepleri Editör',
                'durum'            => 'yayinda',
                'yayinlanma_tarihi' => now()->subDays(5),
                'goruntuleme'      => 0,
                'icerik'           => <<<'HTML'
<h2>Charter Kiralama Nedir?</h2>
<p>Charter kiralama, bir uçağın <strong>tamamının</strong> belirli bir güzergah için kiralanmasıdır. Reguler hat uçuşlarından farklı olarak, tarih ve güzergahı siz belirlersiniz. Bu nedenle özellikle 100 kişi üzeri gruplarda maliyet avantajı sağlar.</p>

<h2>Yaygın Charter Uçak Tipleri</h2>
<ul>
<li><strong>ATR 72:</strong> ~70 koltuk — küçük gruplar, kısa mesafe</li>
<li><strong>Boeing 737-800:</strong> ~189 koltuk — en yaygın charter uçağı</li>
<li><strong>Airbus A320:</strong> ~150-180 koltuk — orta mesafe gruplar</li>
<li><strong>Airbus A321:</strong> ~220 koltuk — büyük gruplar, orta-uzun mesafe</li>
<li><strong>Boeing 767:</strong> ~260 koltuk — uzun mesafe charter</li>
</ul>

<h2>Charter Fiyatını Etkileyen Faktörler</h2>
<p>Charter fiyatı "blok ücret" üzerinden hesaplanır; yolcu sayısından bağımsızdır. Fiyatı belirleyen başlıca unsurlar:</p>
<ul>
<li>Güzergah uzunluğu ve yakıt maliyeti</li>
<li>Uçak tipi ve yaşı</li>
<li>Tarih (yoğun sezon vs. düşük sezon)</li>
<li>Bekleme süresi (uçak gidecek mi, bekleyecek mi?)</li>
<li>Mürettebat konaklama giderleri</li>
</ul>

<h2>GrupTalepleri'nde Charter Talebi</h2>
<p>Platformumuzun <em>Air Charter</em> bölümünden talebinizi oluşturduğunuzda, Türkiye'deki charter operatörleri size rekabetçi teklifler iletir. Genellikle <strong>24-48 saat</strong> içinde birden fazla teklif alırsınız.</p>

<h2>Rezervasyon Sürecinde Kritik Adımlar</h2>
<ol>
<li>Talep oluştur ve teklifleri karşılaştır</li>
<li>Operatörle teknik detayları netleştir (slot, yolcu listesi formatı)</li>
<li>Opsiyon ver (genellikle 5-7 gün)</li>
<li>Depozito öde ve rezervasyonu kesinleştir</li>
<li>Son yolcu listesini 72 saat öncesine kadar teslim et</li>
</ol>
HTML,
            ],

            [
                'kategori_id'       => $charter->id,
                'baslik'            => 'Yat Kiralama ve Mavi Yolculuk: Grup Organizasyonu Rehberi',
                'slug'              => 'yat-kiralama-mavi-yolculuk-grup-organizasyonu',
                'ozet'             => 'Gulet, katamaran veya motor yat — grup için doğru tekneyi nasıl seçersiniz? Mavi yolculuk organizasyonunun tüm detayları.',
                'meta_baslik'      => 'Yat Kiralama Grup Organizasyonu | GrupTalepleri',
                'meta_aciklama'    => 'Mavi yolculuk ve yat kiralama grup organizasyonu rehberi. Gulet, katamaran tipleri, rotalar ve fiyatlandırma.',
                'yazar'            => 'GrupTalepleri Editör',
                'durum'            => 'yayinda',
                'yayinlanma_tarihi' => now()->subDays(3),
                'goruntuleme'      => 0,
                'icerik'           => <<<'HTML'
<h2>Mavi Yolculuk Nedir?</h2>
<p>Mavi yolculuk, Ege ve Akdeniz kıyılarında tekneyle yapılan <strong>çok günlü deniz tatili</strong>dir. Bodrum, Marmaris, Göcek ve Fethiye gibi başlangıç noktalarından hareket edilerek koydan koya gezilir. Kurumsal etkinlikler, aile toplantıları ve arkadaş grupları için ideal bir alternatif.</p>

<h2>Tekne Tipleri ve Kapasiteler</h2>
<ul>
<li><strong>Gulet (Ahşap):</strong> 6-24 kişi — geleneksel, konforlu, en çok tercih edilen</li>
<li><strong>Katamaran:</strong> 8-12 kişi — geniş güverte, az sallanma</li>
<li><strong>Motor Yat:</strong> 6-16 kişi — hız avantajı, lüks kabinler</li>
<li><strong>Mega Yat:</strong> 12-30+ kişi — premium segment, kapsamlı donanım</li>
</ul>

<h2>Popüler Mavi Yolculuk Rotaları</h2>
<ul>
<li>Bodrum → Gökova Körfezi (3-7 gece)</li>
<li>Marmaris → Bozburun Yarımadası (4-7 gece)</li>
<li>Göcek → Ölüdeniz → Fethiye (5-7 gece)</li>
<li>Antalya → Kaş → Kekova (4-6 gece)</li>
</ul>

<h2>Fiyatlandırma Nasıl Çalışır?</h2>
<p>Yat kiralamada fiyat tekne başınadır — kişi sayısına göre değil. Bu nedenle grubu doldurmak, kişi başı maliyeti düşürür. Tipik bir gulet kiralamada şu kalemler yer alır:</p>
<ul>
<li>Tekne kiralama bedeli (haftalık)</li>
<li>Yakıt (genellikle ayrı ücretlendirilir)</li>
<li>Mürettebat bahşişi (isteğe bağlı ama beklenen bir gelenek)</li>
<li>Gıda ve içecek (yarı pansiyon veya tam pansiyon seçeneğiyle)</li>
</ul>

<h2>GrupTalepleri'nde Yat Talebi</h2>
<p>Platformumuzda yat/gulet talebi oluşturarak Türkiye'nin önde gelen charter operatörlerinden teklif alabilirsiniz. Tekne tipi, kabin sayısı, rota ve tarih bilgilerinizi eksiksiz girin; 24 saat içinde size özel teklifler gelsin.</p>
HTML,
            ],

            // ─── Seyahat Operasyonu ───────────────────────────────────────────
            [
                'kategori_id'       => $operasyon->id,
                'baslik'            => 'Seyahat Acenteleri İçin Grup Turu Karlılık Hesabı',
                'slug'              => 'grup-turu-karlilik-hesabi-seyahat-acenteleri',
                'ozet'             => 'Grup turunda kâr marjını nasıl hesaplarsınız? Gizli maliyetler, komisyon yapıları ve fiyatlama stratejileri.',
                'meta_baslik'      => 'Grup Turu Karlılık Hesabı | Acente Rehberi',
                'meta_aciklama'    => 'Seyahat acenteleri için grup turu kârlılık analizi, gizli maliyetler ve fiyatlama stratejileri rehberi.',
                'yazar'            => 'GrupTalepleri Editör',
                'durum'            => 'yayinda',
                'yayinlanma_tarihi' => now()->subDays(1),
                'goruntuleme'      => 0,
                'icerik'           => <<<'HTML'
<h2>Grup Turunda Temel Maliyet Kalemleri</h2>
<p>Doğru bir fiyatlama yapabilmek için önce tüm maliyetleri tek tek hesaplamanız gerekir. Atlanan her kalem, kârınızdan gider.</p>
<ul>
<li><strong>Ulaşım:</strong> Uçak/otobüs + transfer</li>
<li><strong>Konaklama:</strong> Oda tipi, kahvaltı dahilliği, grup indirimi</li>
<li><strong>Rehber ücreti:</strong> Lisanslı rehber zorunluluğu (yurt dışı turlar)</li>
<li><strong>Giriş ücretleri:</strong> Müze, kültürel mekânlar</li>
<li><strong>Yemek programı:</strong> Öğle/akşam dahil mi?</li>
<li><strong>Sigorta:</strong> Seyahat sigortası zorunlu mu, dahil mi?</li>
<li><strong>Acente operasyon gideri:</strong> Personel, iletişim, evrak</li>
</ul>

<h2>Kâr Marjı Nasıl Belirlenir?</h2>
<p>Sektör ortalaması grup turlarında <strong>%10-20 net kâr marjı</strong>dır. Ancak bu oran; tur tipine, rekabete ve müşteri segmentine göre önemli ölçüde değişir. Kurumsal gruplarda (şirket gezileri) marjınız daha yüksek olabilir; fiyat duyarlı müşteri gruplarında (okul gezileri) baskı altında kalabilir.</p>

<h2>Gizli Maliyet Tuzakları</h2>
<ul>
<li><strong>Döviz kuru riski:</strong> Yurt dışı turlar için ödeme TL, gelir bazen dolar/euro ise kur koruması yapın</li>
<li><strong>Son dakika iptalleri:</strong> İptal politikasını sözleşmeye ekleyin</li>
<li><strong>Minimum doluluk garantisi:</strong> 40 kişilik otel bloğu için en az 35 kişi satmazsanız ne olur?</li>
<li><strong>Ekstra hizmet talepleri:</strong> Müşterinin tur sırasında isteyeceği ekstralar kimin hesabına?</li>
</ul>

<h2>Fiyatlama Stratejisi</h2>
<p>Grubu oluştururken yolcu sayısına göre farklı fiyat kırılımları belirleyin:</p>
<ul>
<li>30-39 kişi: Taban fiyat</li>
<li>40-49 kişi: %5 indirim</li>
<li>50+ kişi: %10 indirim + ücretsiz rehber</li>
</ul>
<p>Bu yapı hem müşteriyi grubu büyütmeye teşvik eder hem de kârlılığınızı korur.</p>
HTML,
            ],

            [
                'kategori_id'       => $operasyon->id,
                'baslik'            => 'Kurumsal Gezi Organizasyonu: Şirket Grupları İçin Rehber',
                'slug'              => 'kurumsal-gezi-organizasyonu-sirket-gruplari',
                'ozet'             => 'Şirket motivasyon gezisi, incentive tur ve takım toplantılarını organize etmek için bilinmesi gereken her şey.',
                'meta_baslik'      => 'Kurumsal Gezi Organizasyonu | İncentive Tur Rehberi',
                'meta_aciklama'    => 'Şirket motivasyon gezisi ve incentive tur organizasyonu rehberi. Bütçe planlama, destinasyon seçimi ve program oluşturma.',
                'yazar'            => 'GrupTalepleri Editör',
                'durum'            => 'yayinda',
                'yayinlanma_tarihi' => now(),
                'goruntuleme'      => 0,
                'icerik'           => <<<'HTML'
<h2>Kurumsal Gezi Nedir?</h2>
<p>Kurumsal geziler; şirketlerin <strong>motivasyon, ödüllendirme veya takım oluşturma</strong> amacıyla düzenlediği organizasyonlardır. "Incentive tur" olarak da bilinen bu geziler, standart turlardan farklı bir yaklaşım gerektirir: müşteri şirkettir, deneyim tasarımı ön plandadır.</p>

<h2>Kurumsal Gezi Türleri</h2>
<ul>
<li><strong>Motivasyon gezisi:</strong> Satış ekibi veya yüksek performanslı çalışanlara ödül</li>
<li><strong>Takım toplantısı (retreat):</strong> Strateji toplantısı + gezi kombinasyonu</li>
<li><strong>Bayi/dealer toplantısı:</strong> İş ortaklarını ağırlama</li>
<li><strong>Kongre transferi:</strong> Fuar veya kongreye katılan grubun transferi ve programı</li>
</ul>

<h2>Destinasyon Seçiminde Dikkat Edilecekler</h2>
<p>Kurumsal müşteriler genellikle destinasyon seçiminde pratik faktörleri öne çıkarır:</p>
<ul>
<li>Uçuş süresi (2-4 saat arası ideal)</li>
<li>Vize gerekliliği (Schengen veya vizesiz ülkeler tercih edilir)</li>
<li>Konferans salonu imkânı (toplantı dahilse)</li>
<li>Aktivite çeşitliliği (team building için)</li>
</ul>

<h2>Teklif Hazırlarken Kurumsal Müşteriyi İkna Etmek</h2>
<p>Kurumsal tekliflerde fiyat kadar <strong>profesyonel sunum</strong> da önemlidir:</p>
<ol>
<li>Kurumsal kimliğe uygun PDF teklif hazırlayın</li>
<li>Dakika dakika program taslağı ekleyin</li>
<li>Referans gezileri (fotoğraf/video) gösterin</li>
<li>KDV dahil/hariç fiyatları net belirtin</li>
<li>Fatura kesilecek şirket bilgilerini erkenden alın</li>
</ol>

<h2>GrupTalepleri ile Kurumsal Gezi Talebi</h2>
<p>Platformumuzda "Kurumsal" etiketiyle oluşturduğunuz talepler, incentive tur konusunda uzmanlaşmış operatörlere iletilir. Detaylı brief ne kadar iyi olursa, aldığınız teklifler o kadar isabetli olur.</p>
HTML,
            ],
        ];

        foreach ($yazılar as $veri) {
            BlogYazisi::firstOrCreate(
                ['slug' => $veri['slug']],
                $veri
            );
        }

        $this->command->info('Blog seederi tamamlandı: ' . count($yazılar) . ' yazı eklendi.');
    }
}
