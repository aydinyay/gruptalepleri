"""
GrupTalepleri - Eski Veri Migration Scripti
grupmesajlari.csv → Laravel requests + flight_segments tabloları

Kullanım:
  pip install pymysql --break-system-packages
  python migrate_old_data.py

  ya da önce test için:
  python migrate_old_data.py --dry-run
"""

import csv
import re
import sys
import pymysql
from datetime import datetime

# ─── AYARLAR ────────────────────────────────────────────────────────────────
DB_HOST     = '127.0.0.1'
DB_PORT     = 3306
DB_USER     = 'root'
DB_PASSWORD = 'root123'
DB_NAME     = 'gruptalepleri'
CSV_FILE    = 'grupmesajlari.csv'   # scriptle aynı klasörde olmalı
DRY_RUN     = '--dry-run' in sys.argv
# ─────────────────────────────────────────────────────────────────────────────

# Durum mapping: eski rakam → Laravel status string
STATUS_MAP = {
    '0': 'beklemede',
    '1': 'islemde',
    '2': 'fiyatlandirildi',
    '3': 'iptal',
    '4': 'biletlendi',
    '5': 'depozito',
}

# Talep tipi mapping
TYPE_MAP = {
    'UGT': 'group_flight',
    'OGT': 'group_flight',
    'UKT': 'group_flight',
    'TKT': 'group_flight',
    'AKT': 'group_flight',
}

# Para birimi normalize
CURRENCY_MAP = {
    'TL'   : 'TRY',
    'USD'  : 'USD',
    'EURO' : 'EUR',
    'EUR'  : 'EUR',
    ''     : None,
    'NULL' : None,
}


def parse_iata(raw):
    """'IST, İstanbul Havalimanı, İstanbul, Türkiye' → ('IST', 'İstanbul Havalimanı')"""
    if not raw or raw.strip() == '':
        return '', ''
    raw = raw.strip()
    m = re.match(r'^([A-Z]{3}),\s*(.+?)(?:,|$)', raw)
    if m:
        return m.group(1), m.group(2).strip()[:100]
    return '', raw[:100]


def clean_date(val):
    """'2026-04-18', '18.04.2026' veya boş → date ya da None"""
    if not val or val.strip() in ('', '0000-00-00', 'NULL'):
        return None
    val = val.strip()[:10]
    # yyyy-mm-dd
    try:
        return datetime.strptime(val, '%Y-%m-%d').date()
    except:
        pass
    # dd.mm.yyyy
    try:
        return datetime.strptime(val, '%d.%m.%Y').date()
    except:
        pass
    return None


def clean_time(hour, minute):
    """'14', '30' → '14:30' ya da None"""
    try:
        h = int(hour)
        m = int(minute)
        if 0 <= h <= 23 and 0 <= m <= 59:
            return f'{h:02d}:{m:02d}'
    except:
        pass
    return None


def clean_int(val, default=0):
    try:
        return int(val) if val and val.strip() not in ('', 'NULL') else default
    except:
        return default


def clean_str(val, maxlen=None):
    if not val or val.strip() in ('NULL', ''):
        return None
    v = val.strip()
    return v[:maxlen] if maxlen else v


def clean_datetime(val):
    """'26.3.2021 14:41:06', '2021-03-26 14:41:06', unix timestamp → MySQL datetime ya da None"""
    if not val or val.strip() in ('', 'NULL', '0000-00-00 00:00:00'):
        return None
    val = val.strip()
    # Unix timestamp (sadece rakam)
    if val.isdigit():
        try:
            return datetime.fromtimestamp(int(val)).strftime('%Y-%m-%d %H:%M:%S')
        except:
            return None
    # '26.3.2021 14:41:06' formatı
    try:
        return datetime.strptime(val, '%d.%m.%Y %H:%M:%S').strftime('%Y-%m-%d %H:%M:%S')
    except:
        pass
    # Standart format
    try:
        return datetime.strptime(val[:19], '%Y-%m-%d %H:%M:%S').strftime('%Y-%m-%d %H:%M:%S')
    except:
        pass
    return None


def clean_decimal(val):
    try:
        return float(val) if val and val.strip() not in ('', 'NULL') else None
    except:
        return None


def now():
    return datetime.now().strftime('%Y-%m-%d %H:%M:%S')


def migrate():
    print(f"{'[DRY-RUN] ' if DRY_RUN else ''}Migration başlıyor...")

    # CSV oku
    with open(CSV_FILE, encoding='utf-8') as f:
        rows = list(csv.DictReader(f))
    print(f"  CSV okundu: {len(rows)} satır")

    # DB bağlantısı
    if not DRY_RUN:
        conn = pymysql.connect(
            host=DB_HOST, port=DB_PORT,
            user=DB_USER, password=DB_PASSWORD,
            database=DB_NAME, charset='utf8mb4',
            autocommit=False
        )
        cursor = conn.cursor()
    else:
        conn = cursor = None

    # Migration için admin user_id = 1 (superadmin)
    MIGRATION_USER_ID = 1

    ok = skip = error = 0

    for row in rows:
        old_id  = row['id']
        old_gtpnr = row.get('gtpnr', '').strip()

        try:
            # ── Temel alanlar ──────────────────────────────────────────────
            status   = STATUS_MAP.get(row['islemdurumu'], 'beklemede')
            req_type = TYPE_MAP.get(row['taleptipi'], 'group_flight')

            adult  = clean_int(row['kisisayisi'])
            child  = clean_int(row['cocuksayisi'])
            infant = clean_int(row['bebeksayisi'])
            pax    = adult + child + infant
            if pax == 0:
                pax = adult  # güvence

            # Kalkış / varış
            from_iata, from_city = parse_iata(row['gidiskalkishavalimani'])
            to_iata,   to_city   = parse_iata(row['gidisvarishavalimani'])

            # Tarihler
            dep_date    = clean_date(row['gidiszamani'])
            ret_date    = clean_date(row['donuszamani'])

            # Gidiş saat
            dep_time = clean_time(row['gidissaat1'], row['gidisdakika1'])

            # Fiyat
            fiyat    = clean_decimal(row['fiyat'])
            currency = CURRENCY_MAP.get(row.get('parabirimi', ''), None) or 'TRY'

            # Diğer
            acente_adi  = clean_str(row['acentaadi'], 200)
            telefon     = clean_str(row['telefon'], 50)
            email       = clean_str(row['email'], 100)
            notes       = clean_str(row['notlar'])
            airline     = clean_str(row['hangihavayolu'], 200)
            hotel       = 1 if row.get('otel', '0') == '1' else 0
            visa        = 1 if row.get('vize', '0') == '1' else 0
            purpose     = clean_str(row['ucusamaci'], 200)
            nationality = clean_str(row['yolcumilliyeti'], 100)
            group_firm  = clean_str(row['grupfirmabilgisi'], 200)
            created_at  = clean_datetime(row['created_at']) or now()
            updated_at  = now()

            # trip_type: dönüş tarihi varsa roundtrip
            trip_type = 'roundtrip' if ret_date else 'oneway'

            # GTPNR: eski sistemdekini koru, yoksa yeni üret
            # Mükerrer ise suffix ekle
            base_gtpnr = old_gtpnr if old_gtpnr else f'MIG-{old_id}'
            gtpnr = base_gtpnr
            suffix = 2
            while True:
                cursor.execute('SELECT id FROM requests WHERE gtpnr = %s', (gtpnr,))
                if not cursor.fetchone():
                    break
                gtpnr = f'{base_gtpnr}-{suffix}'
                suffix += 1

            if DRY_RUN:
                print(f"  [{old_id}] {gtpnr} | {from_iata}→{to_iata} | {dep_date} | {pax}PAX | {status}")
                ok += 1
                continue

            # ── requests INSERT ────────────────────────────────────────────
            cursor.execute("""
                INSERT INTO requests (
                    gtpnr, user_id, type, status,
                    agency_name, phone, email,
                    group_company_name, flight_purpose, trip_type,
                    pax_total, pax_adult, pax_child, pax_infant,
                    preferred_airline, hotel_needed, visa_needed,
                    passenger_nationality, notes,
                    created_at, updated_at
                ) VALUES (
                    %s, %s, %s, %s,
                    %s, %s, %s,
                    %s, %s, %s,
                    %s, %s, %s, %s,
                    %s, %s, %s,
                    %s, %s,
                    %s, %s
                )
            """, (
                gtpnr, MIGRATION_USER_ID, req_type, status,
                acente_adi, telefon, email,
                group_firm, purpose, trip_type,
                pax, adult, child, infant,
                airline, hotel, visa,
                nationality, notes,
                created_at, updated_at
            ))
            request_id = cursor.lastrowid

            # ── flight_segments INSERT ─────────────────────────────────────
            # Gidiş segmenti
            if from_iata or from_city or dep_date:
                cursor.execute("""
                    INSERT INTO flight_segments (
                        request_id, `order`,
                        from_iata, from_city,
                        to_iata, to_city,
                        departure_date, departure_time,
                        created_at, updated_at
                    ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                """, (
                    request_id, 1,
                    from_iata or '', from_city or '',
                    to_iata   or '', to_city   or '',
                    dep_date, dep_time,
                    created_at, updated_at
                ))

            # Dönüş segmenti (varsa)
            if ret_date:
                ret_from_iata, ret_from_city = parse_iata(row['donuskalkishavalimani'])
                ret_to_iata,   ret_to_city   = parse_iata(row['donusvarishavalimani'])
                ret_time = clean_time(row['donussaat1'], row['donusdakika1'])

                cursor.execute("""
                    INSERT INTO flight_segments (
                        request_id, `order`,
                        from_iata, from_city,
                        to_iata, to_city,
                        departure_date, departure_time,
                        created_at, updated_at
                    ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                """, (
                    request_id, 2,
                    ret_from_iata or to_iata   or '',
                    ret_from_city or to_city   or '',
                    ret_to_iata   or from_iata or '',
                    ret_to_city   or from_city or '',
                    ret_date, ret_time,
                    created_at, updated_at
                ))

            # ── offers INSERT (fiyatlı kayıtlar veya opsiyonlu kayıtlar) ──
            opsiyon_tarih = clean_date(row.get('opsiyontarihi'))
            opsiyon_saat  = row.get('opsiyonsaati', '').strip() or None
            if (fiyat and fiyat > 0 and currency) or opsiyon_tarih:
                total = fiyat * pax
                dep_rate  = clean_decimal(row.get('depozitorani'))  or 0
                dep_tutar = clean_decimal(row.get('depozitotutari')) or 0
                kazanc    = clean_decimal(row.get('kazanc'))         or 0
                toplam_od = clean_decimal(row.get('toplamodeme'))    or 0

                profit_pct = round((kazanc / total * 100), 2) if total > 0 and kazanc else 0
                cost_price = total - kazanc if kazanc else None

                cursor.execute("""
                    INSERT INTO offers (
                        request_id, airline, currency,
                        price_per_pax, total_price, cost_price,
                        profit_amount, profit_percent,
                        deposit_rate, deposit_amount,
                        option_date, option_time,
                        offer_text, created_by,
                        is_visible,
                        created_at, updated_at
                    ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
                """, (
                    request_id, airline, currency,
                    fiyat, total, cost_price,
                    kazanc if kazanc else None, profit_pct,
                    dep_rate, dep_tutar,
                    opsiyon_tarih, opsiyon_saat,
                    row.get('cevapmetni') or None,
                    row.get('mesajiyazan') or None,
                    1,
                    created_at, updated_at
                ))

            # ── request_log ───────────────────────────────────────────────
            cursor.execute("""
                INSERT INTO request_logs (request_id, action, description, user_id, created_at, updated_at)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (
                request_id,
                'migration',
                f'Eski sistemden taşındı. Eski ID: {old_id}, Eski GTPNR: {old_gtpnr}',
                MIGRATION_USER_ID,
                created_at, updated_at
            ))

            ok += 1

        except Exception as e:
            error += 1
            print(f"  HATA - Satır {old_id}: {e}")
            if not DRY_RUN:
                conn.rollback()
            continue

    # Commit
    if not DRY_RUN and conn:
        conn.commit()
        cursor.close()
        conn.close()

    print()
    print("=" * 50)
    print(f"  ✅ Başarılı : {ok}")
    print(f"  ⏭  Atlanan  : {skip}")
    print(f"  ❌ Hatalı   : {error}")
    print("=" * 50)
    if DRY_RUN:
        print("  DRY-RUN tamamlandı. Gerçek migration için --dry-run'ı kaldırın.")


if __name__ == '__main__':
    migrate()
