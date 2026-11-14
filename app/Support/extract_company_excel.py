import sys
import os
import argparse
import json
import time
import datetime
import openpyxl

def parse_num(v):
    if not v or v == '-' or str(v).strip() in ['-', '', '#REF!', 'None', 'none']:
        return 0.0
    try:
        return float(str(v).replace(',', ''))
    except Exception:
        return 0.0

def parse_date(v, fallback_date='2024-01-01'):
    if isinstance(v, (datetime.datetime, datetime.date)):
        return v.strftime('%Y-%m-%d')
    if isinstance(v, (int, float)) and v > 20000:
        return (datetime.datetime(1899, 12, 30) + datetime.timedelta(days=int(v))).strftime('%Y-%m-%d')
    if isinstance(v, str):
        v = v.strip()
        for fmt in [
            '%Y-%m-%d', '%d/%m/%Y', '%d-%m-%Y', '%Y/%m/%d',
            '%d/%m/%y', '%d-%m-%y', '%y-%m-%d',
            '%Y-%m-%d %H:%M:%S', '%d/%m/%Y %H:%M:%S'
        ]:
            try:
                return datetime.datetime.strptime(v[:19] if ' ' in v else v[:10], fmt).strftime('%Y-%m-%d')
            except Exception:
                pass
    return fallback_date

def map_house_code(raw):
    if not raw or str(raw).strip() in ['-', '', 'None', 'none']:
        return 'VIL-INFRA'
    s = str(raw).strip().upper()
    if s in ['A5/A6', 'A6/A5']:
        return 'VIL-A5-A6'
    return f'VIL-{s}'

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--file', required=True, help='Path to Excel file')
    parser.add_argument('--out-dir', required=True, help='Path to directory for output JSON files')
    args = parser.parse_args()

    excel_path = args.file
    out_dir = args.out_dir
    os.makedirs(out_dir, exist_ok=True)

    t0 = time.time()
    wb = openpyxl.load_workbook(excel_path, read_only=True, data_only=True)

    # 1. KONTROL BAHAN + Mandor
    ws_k = wb['KONTROL BAHAN']
    houses_dict = {}
    for r_idx, row in enumerate(ws_k.iter_rows(values_only=True), 1):
        if r_idx < 5:
            continue
        blok = row[2]
        if not blok or str(blok).strip() in ['-', '', 'None']:
            continue
        blok_str = str(blok).strip()
        code = f'VIL-{blok_str.upper()}'
        tipe = row[3]
        total_m2 = row[5]
        total_bahan = row[8]
        bobot = row[9]
        houses_dict[code] = {
            'house_code': code,
            'name': f'Blok {blok_str}',
            'type': f'Tipe {tipe}' if tipe else 'Tipe Standar',
            'budget': parse_num(total_m2),
            'material_budget': parse_num(total_bahan),
            'physical_progress': round(parse_num(bobot) * 100, 2),
            'mandor_name': None,
            'category': 'house'
        }

    # Mandor lookup from LAPORAN LOGISTIK 2026
    if 'LAPORAN LOGISTIK 2026 ' in wb.sheetnames:
        ws_l = wb['LAPORAN LOGISTIK 2026 ']
        for r_idx, row in enumerate(ws_l.iter_rows(values_only=True), 1):
            if r_idx < 10:
                continue
            m = row[2]
            b = row[3]
            if b and m:
                code = f'VIL-{str(b).strip().upper()}'
                if code in houses_dict:
                    houses_dict[code]['mandor_name'] = str(m).strip()

    # Pre-defined non-house / combination units
    extra_houses = [
        {'house_code': 'VIL-INFRA', 'name': 'Infrastruktur & Fasilitas Umum', 'type': 'Infrastruktur', 'category': 'public_work'},
        {'house_code': 'VIL-POLSEK', 'name': 'Fasilitas Polsek', 'type': 'Fasilitas Umum', 'category': 'public_work'},
        {'house_code': 'VIL-POS', 'name': 'Pos Keamanan', 'type': 'Fasilitas Umum', 'category': 'public_work'},
        {'house_code': 'VIL-PUING', 'name': 'Pekerjaan Puing / Urug', 'type': 'Pekerjaan Tanah', 'category': 'public_work'},
        {'house_code': 'VIL-TANAH', 'name': 'Pematangan Lahan / Tanah', 'type': 'Pekerjaan Tanah', 'category': 'public_work'},
        {'house_code': 'VIL-A5-A6', 'name': 'Blok A5/A6 (Kombinasi)', 'type': 'Tipe Standar', 'category': 'house'},
    ]
    for eh in extra_houses:
        if eh['house_code'] not in houses_dict:
            houses_dict[eh['house_code']] = {
                'house_code': eh['house_code'],
                'name': eh['name'],
                'type': eh['type'],
                'budget': 0.0,
                'material_budget': 0.0,
                'physical_progress': 0.0,
                'mandor_name': None,
                'category': eh['category']
            }

    # 2. Stok Tersedia
    ws_s = wb['Stok Tersedia']
    stok_map = {}
    for r_idx, row in enumerate(ws_s.iter_rows(values_only=True), 1):
        if r_idx < 4:
            continue
        code = row[0]
        if not code:
            continue
        code_str = str(code).strip().upper()
        stok_map[code_str] = {
            'keluar': parse_num(row[3]),
            'datang': parse_num(row[4]),
            'rusak': parse_num(row[5]),
            'return': parse_num(row[6]),
            'ready': parse_num(row[7]),
            'nilai_stok': parse_num(row[9]),
        }

    # 3. Katalog Barang
    ws_c = wb['Katalog Barang']
    current_cat = 'Material Umum'
    categories = set([current_cat])
    suppliers = set()
    materials = []
    mat_code_map = {}

    for r_idx, row in enumerate(ws_c.iter_rows(values_only=True), 1):
        if r_idx < 3:
            continue
        code = row[0]
        name = row[1]
        if not code and name:
            name_str = str(name).strip()
            if len(name_str) > 2 and not name_str.startswith('*'):
                current_cat = name_str
                categories.add(current_cat)
            continue
        if not code or not name:
            continue
        code_str = str(code).strip().upper()
        name_str = str(name).strip()
        unit = str(row[2]).strip() if row[2] else 'pcs'
        price = parse_num(row[3])
        supplier = str(row[4]).strip() if row[4] else None
        if supplier and supplier not in ['-', '0']:
            suppliers.add(supplier)
        else:
            supplier = None

        stok_info = stok_map.get(code_str, {
            'keluar': 0.0, 'datang': 0.0, 'rusak': 0.0, 'return': 0.0, 'ready': 0.0, 'nilai_stok': 0.0
        })

        mat_item = {
            'code': code_str,
            'name': name_str,
            'category': current_cat,
            'unit': unit,
            'unit_price': price,
            'supplier': supplier,
            'stock': stok_info['ready'],
            'datang': stok_info['datang'],
            'keluar': stok_info['keluar'],
            'rusak': stok_info['rusak'],
            'return': stok_info['return'],
        }
        materials.append(mat_item)
        mat_code_map[code_str] = mat_item

    # 4. BARANG KELUAR -> write JSONL directly to minimize memory
    ws_b = wb['BARANG KELUAR']
    usages_file_path = os.path.join(out_dir, 'usages.jsonl')
    tx_count = 0
    last_valid_date = '2024-01-01'

    with open(usages_file_path, 'w', encoding='utf-8') as f_out:
        for r_idx, row in enumerate(ws_b.iter_rows(values_only=True), 1):
            if r_idx < 3:
                continue
            name = row[9] # NAMA BARANG
            if not name:
                continue
            code = row[8] # KODE BARANG
            code_str = str(code).strip().upper() if code else None

            # Date parsing
            raw_date = row[0]
            tx_date = parse_date(raw_date, fallback_date=last_valid_date)
            if raw_date:
                last_valid_date = tx_date

            admin = str(row[3]).strip() if row[3] else None
            pengambil = str(row[4]).strip() if row[4] else None
            if pengambil in ['-', '0']:
                pengambil = None

            house_code = map_house_code(row[5])
            notes = str(row[7]).strip() if row[7] else None # KETERANGAN PEKERJAAN
            vol = parse_num(row[10]) # VOLUME
            unit = str(row[11]).strip() if row[11] else 'pcs'
            price = parse_num(row[12]) # HARGA SATUAN
            total = parse_num(row[13]) # JUMLAH
            if total == 0.0 and vol > 0 and price > 0:
                total = round(vol * price, 2)

            tx_item = {
                'row': r_idx,
                'date': tx_date,
                'admin': admin,
                'taken_by': pengambil,
                'house_code': house_code,
                'material_code': code_str,
                'material_name': str(name).strip(),
                'quantity': vol,
                'unit': unit,
                'unit_price': price,
                'total_cost': total,
                'notes': notes,
            }
            f_out.write(json.dumps(tx_item, ensure_ascii=False) + '\n')
            tx_count += 1

    wb.close()

    # Write metadata, houses, categories, suppliers, materials JSON files
    with open(os.path.join(out_dir, 'meta.json'), 'w', encoding='utf-8') as f:
        json.dump({
            'cluster': 'VILLAGIO',
            'warehouse': 'Gudang Villagio',
            'total_houses': len(houses_dict),
            'total_materials': len(materials),
            'total_transactions': tx_count,
            'extracted_at': datetime.datetime.now().isoformat(),
        }, f, indent=2)

    with open(os.path.join(out_dir, 'houses.json'), 'w', encoding='utf-8') as f:
        json.dump(list(houses_dict.values()), f, indent=2)

    with open(os.path.join(out_dir, 'categories.json'), 'w', encoding='utf-8') as f:
        json.dump(sorted(list(categories)), f, indent=2)

    with open(os.path.join(out_dir, 'suppliers.json'), 'w', encoding='utf-8') as f:
        json.dump(sorted(list(suppliers)), f, indent=2)

    with open(os.path.join(out_dir, 'materials.json'), 'w', encoding='utf-8') as f:
        json.dump(materials, f, indent=2)

    elapsed = round(time.time() - t0, 2)
    print(json.dumps({
        'status': 'success',
        'houses': len(houses_dict),
        'materials': len(materials),
        'transactions': tx_count,
        'elapsed_seconds': elapsed,
    }))

if __name__ == '__main__':
    main()
