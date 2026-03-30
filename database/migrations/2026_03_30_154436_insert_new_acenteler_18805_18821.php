<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [18805, 'MAYA GLOBAL DMC TRAVEL AGENCY',          'MAYA GLOBAL DMC TURİZM TİCARET SANAYİ LTD. ŞTİ.',                                                          'A', 'ANTALYA',  'KEPEZ',        '533 6211092', 'mihriban.yilmaz@mayaglobaldmc.com', 'ALTINOVA SİNAN MAH. ANTALYA 1 CAD. NO:8/53'],
            [18806, 'CARNOSA TRAVEL AGENCY',                  'DEGÜL TURİZM TİC. LTD. ŞTİ.',                                                                              'A', 'İSTANBUL', 'FATİH',        '212 5237259', 'bayramgunen@hotmail.com',           'MOLLA GÜRANİ MAH. TURGUT ÖZAL MİLLET CAD. FİLDİŞİ İŞ MERKEZİ NO: 90/408'],
            [18807, 'SARAY VİZYON TURİZM SEYAHAT ACENTASI',  'SARAY HNS TRAVEL TURİZM LTD. ŞTİ.',                                                                        'A', 'TEKİRDAĞ', 'SARAY',        '282 6060559', 'info@saraytravel.com',              'AYASPAŞA MAH.YEĞEN CAD. NO:23/A'],
            [18808, 'DİLCEM TURİZM SEYAHAT ACENTASI',        'DİLCEM SEYAHAT TURİZM TİC. LTD. ŞTİ.',                                                                     'A', 'İSTANBUL', 'BAHÇELİEVLER', '533 6089280', 'dilcemturizm@gmail.com',            'BAHÇELİEVLER MAH. HATTAT KAMİL SOK. EVREN NO: 2/7'],
            [18809, 'BURAKLINE TRAVEL AGENCY',                'BURAKLİNE TAŞIMACILIK İNŞAAT LTD. ŞTİ.',                                                                   'A', 'TOKAT',    'TURHAL',       '545 2620427', 'gezigotravel@gmail.com',            'CELAL MAH. CUMHURİYET CAD. NO: 92/130'],
            [18810, 'KAYSERİ ERENLER TURİZM SEYAHAT ACENTASI','TUR38 SEYAHAT TURİZM TAŞIMACILIK SAN. VE TİC. LTD. ŞTİ.',                                                 'A', 'KAYSERİ',  'MELİKGAZİ',   '553 1816090', 'ebruerenler@outlook.com',           'KILIÇASLAN MAH. SİVAS BUL. AKAY APT. NO: 54/2'],
            [18811, 'BETNAHRIN TOUR TRAVEL',                  'BETNAHRİN KUYUMCULUK TURİZM İNŞAAT NAKLİYAT TARIM İTHALAT VE İHRACAT SAN. VE TİC. LTD. ŞTİ.',           'A', 'MARDİN',   'MİDYAT',       '530 2308047', 'betnahrinkuyumculuık@gmail.com',    'SANAYİ MAH. 9 CAD NO:13/F'],
            [18812, 'GOVISAGO WORLD TRAVEL AGENCY',           'TMGD TR GRUP ÇEVRE DANIŞMANLIK VE KOZMETİK İHR. İTH. LTD. ŞTİ.',                                          'A', 'İSTANBUL', 'BAYRAMPAŞA',  '212 6741787', 'serdem@tmgdmuhendislik.com',        'YILDIRIM MAH. KIBRIS SOK. NO:3/2'],
            [18813, 'GÖK STAY TURİZM SEYAHAT ACENTASI',      'KİRPA TURİZM SEYAHAT ORGANİZASYON KAPLAMA İNŞAAT SAN. VE TİC. LTD. ŞTİ.',                                'A', 'İSTANBUL', 'KADIKÖY',      '530 7999036', 'gkhn.ormn@gmail.com',               'OSMANAĞA MAH. PAZAR YOLU SOK. EKİZOĞLU İŞ HANI NO:2/25'],
            [18814, 'ROSEGARDEN TRAVEL AGENCY',               'DUMAN TRAVEL TURİZM TAŞIMACILIK OTOMOTİV SAN. VE TİC. LTD. ŞTİ.',                                         'A', 'İSTANBUL', 'SULTANBEYLİ', '536 7886622', 'serpildumanyakup@gmail.com',        'YAVUZ SELİM MAH. EYYUBİ CAD. NO:205-207/A'],
            [18815, 'FESAVIA TRAVEL AGENCY',                  'FESA ORGANİZASYON TURİZM LTD. ŞTİ.',                                                                       'A', 'İZMİR',    'BORNOVA',      '532 6213534', 'emre@fesspa.com.tr',                'KAZIMDİRİK MAH. ANKARA CAD. NO:108/1'],
            [18816, 'AYT SMILE ESCAPE TRAVEL AGENCY',         'MEDİCAL PRİME TURİZM TİC. LTD. ŞTİ.',                                                                      'A', 'ANTALYA',  'MURATPAŞA',   '534 4751170', 'medicalprimetravel@gmail.com',      'ÇAYBAŞI MAH.1358 SOK. A BLOK NO:1/A-302'],
            [18817, 'ZEN WAY TOUR TRAVEL AGENCY',             'ZENWAY TRAVEL TURİZM SEYAHAT VE TİC. LTD. ŞTİ.',                                                           'A', 'İZMİR',    'KONAK',        '533 1640100', 'feridekarakus22@gmail.com',         'KÜLTÜR MAH. ATATÜRK CAD. KERESTECİ NO: 222/1'],
            [18818, 'MAVİ BİLET TURİZM SEYAHAT ACENTASI',    'MAVİ BİLET TURİZM TİC. LTD. ŞTİ.',                                                                         'A', 'MUĞLA',    'FETHİYE',      '533 4090053', 'info@mavibilet.com',                'KARAGÖZLER MAH. FEVZİ ÇAKMAK (KGO) CAD. NO:29/B'],
            [18819, 'WORLD MOWENTA GLOBAL TRAVEL AGENCY',     'MOVENTA TRAVEL SEYAHAT ACENTASI LTD. ŞTİ.',                                                                 'A', 'İSTANBUL', 'EYÜPSULTAN',  '544 6355524', 'info@moventatravel.com',            'ALİBEYKÖY MAH. NAMIK KEMAL CAD. ATMACA AVM NO:73-77/33'],
            [18820, 'SPINE SOLUTIONS TRAVEL AGENCY',          'SPİNE TURİZM VE SEYAHAT HİZMETLERİ A.Ş.',                                                                  'A', 'İSTANBUL', 'ŞİŞLİ',        '535 5744547', 'travel@thisisspine.com',            'CUMHURİYET MAH. YENİ YOL 1 SOK. NOW BOMONTI NO: 2/77'],
            [18821, 'BOSTANCIOĞLU TRIROYAL TRAVEL AGENCY',    'TRİROYAL TURİZM EMLAK İNŞAAT TİC. LTD. ŞTİ.',                                                              'A', 'MUĞLA',    'FETHİYE',      '533 2610991', null,                                'PATLANGAÇ MAH. BAHA ŞIKMAN (PTL) CAD. NO: 142/B'],
        ];

        foreach ($rows as $r) {
            DB::table('acenteler')->insertOrIgnore([
                'belge_no'      => $r[0],
                'sube_sira'     => 0,
                'is_sube'       => 0,
                'acente_unvani' => $r[1],
                'ticari_unvan'  => $r[2],
                'grup'          => $r[3],
                'il'            => $r[4],
                'il_ilce'       => $r[5],
                'telefon'       => $r[6],
                'eposta'        => $r[7],
                'adres'         => $r[8],
                'kaynak'        => 'bakanlik',
                'durum'         => 'GEÇERLİ',
                'internal_id'   => null,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('acenteler')
            ->where('kaynak', 'bakanlik')
            ->whereBetween('belge_no', [18805, 18821])
            ->delete();
    }
};
