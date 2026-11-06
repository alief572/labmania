"""Bangun ulang SATU paket final dari file kerja terbaru.

Pakai:  py -3 final/build_final.py "Master - Quotation"
        py -3 final/build_final.py "Invoice - Pengajuan"

Hanya folder final/<paket>/ yang ditulis; paket lain tidak disentuh. Isi tiap paket diatur di PAKET
di bawah. Menambah paket baru = menambah satu entri PAKET (dan, untuk mockup, satu entri PAKET_MENU di
ui-mockup/produk/core.js dengan kunci yang sama dengan 'mockup').
"""
import os, re, shutil, sys, datetime
import xml.dom.minidom

import openpyxl

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))  # specs/
MOCK = os.path.join(ROOT, 'ui-mockup', 'produk')

PAKET = {
    'Master - Quotation': {
        'fr': ['fr-nfr.md', 'fr-nfr-master-program.md', 'fr-nfr-penjualan-program.md', 'fr-nfr-quotation.md', 'fr-nfr-activity-log.md'],
        'catatan_fr': 'Bagian 1 (Master Data) juga memuat menu umum yang dipakai semua paket: Master User, Master Role, Role & Permission, sidebar.',
        'erd': ['erd_master_data', 'erd_master_program', 'erd_penjualan_program', 'erd_master_trainer', 'erd_quotation', 'erd_activity_log', 'erd_matrix_akses'],
        'flow': ['level0-lanskap', 'level1-master-program', 'level1-penjualan-program', 'level1-quotation', 'level1-activity-log', 'combine'],
        'uc': {'Master Data': None, 'Master Program': None, 'Penjualan Program': None, 'Quotation': None, 'Activity Log': None},
        'mockup': 'master-quotation',
    },
    'Invoice - Pengajuan': {
        'fr': ['fr-nfr-invoice.md', 'fr-nfr-manage-peserta.md', 'fr-nfr-pengajuan-biaya.md', 'fr-nfr-activity-log.md'],
        'catatan_fr': ('Menu umum Master User, Master Role, Role & Permission (FR-36 – FR-39, FR-54 – FR-58, FR-64, FR-65) ada di '
                       '`docs/fr-nfr.md`, Bagian 1 paket **Master - Quotation**; tidak disalin ke paket ini. '
                       'Invoice belum punya halaman ERD (menunggu Q-IVC-01) dan use case-nya belum masuk workbook. '
                       'Halaman ERD Penjualan Program ikut sebagai rujukan kolom Close Program (Manage Peserta).'),
        'erd': ['erd_penjualan_program', 'erd_pengajuan_biaya', 'erd_activity_log', 'erd_matrix_akses'],
        'flow': ['level0-lanskap', 'level1-pengajuan-biaya', 'level1-activity-log'],
        'uc': {'Invoice': None, 'Manage Peserta': None, 'Pengajuan Biaya': None, 'Activity Log': None, 'Master Data': ['Pengguna']},
        'mockup': 'invoice-pengajuan',
    },
}


def build_fr(nama, cfg, out):
    rows, bodies = [], []
    for i, f in enumerate(cfg['fr'], 1):
        s = open(os.path.join(ROOT, 'docs', f), encoding='utf-8').read().rstrip('\n')
        first, rest = s.split('\n', 1)
        m = re.match(r'# Kebutuhan Fungsional & Non-Fungsional — (.+)$', first)
        assert m, (f, first)
        ver = re.search(r'^- \*\*Versi:\*\* (.+)$', rest, re.M).group(1).strip()
        rows.append(f'| Bagian {i} | {m.group(1).replace(" Labmania", "")} | {ver} | `docs/{f}` |')
        bodies.append(f'# Bagian {i} — {m.group(1)}\n' + rest)
    today = datetime.date.today().strftime('%d-%m-%Y')
    head = (f'# FR & NFR {nama} — Labmania\n\n'
            f'- **Isi:** {len(bodies)} dokumen FR & NFR paket {nama}\n'
            f'- **Tanggal salinan:** {today}\n'
            f'- **Sumber kerja (yang tetap diperbarui):** ' + ', '.join(f'`specs/docs/{f}`' for f in cfg['fr']) +
            '. Dokumen ini salinan final; perubahan dibuat di file sumber lalu paket dibangun ulang dengan `final/build_final.py`.\n'
            f'- **Pasangan final:** `ERD {nama}.drawio`, `Flow Process {nama}.drawio`, `Use Case {nama}.xlsx`, `Mock Up {nama}/index.html`\n'
            f'- **Catatan:** {cfg["catatan_fr"]}\n\n'
            '## Daftar isi\n\n| Bagian | Modul | Versi | File sumber |\n|---|---|---|---|\n' + '\n'.join(rows) +
            '\n\nPenomoran FR/NFR, UC, dan pertanyaan terbuka (Q-…) tetap milik masing-masing bagian. Penanda sumber (`[…]`) dan '
            'rujukan `input/…` dibaca relatif terhadap folder `specs/`.\n\n---\n\n')
    open(os.path.join(out, f'FR & NFR {nama}.md'), 'w', encoding='utf-8', newline='').write(head + '\n\n---\n\n'.join(bodies) + '\n')


def build_drawio(src, ids, dst):
    s = open(src, encoding='utf-8').read()
    blocks = list(re.finditer(r'[ \t]*<diagram\b[^>]*\bid="([^"]+)"[^>]*>.*?</diagram>\n?', s, re.S))
    have = [b.group(1) for b in blocks]
    miss = [i for i in ids if i not in have]
    assert not miss, f'halaman tidak ada di {src}: {miss}'
    keep = ''.join(b.group(0) for b in blocks if b.group(1) in ids)
    out = s[:blocks[0].start()] + keep + s[blocks[-1].end():]
    xml.dom.minidom.parseString(out.encode('utf-8'))
    assert '<!--' not in out
    open(dst, 'w', encoding='utf-8', newline='').write(out)


def build_uc(filt, dst):
    shutil.copyfile(os.path.join(ROOT, 'docs', 'workbook.xlsx'), dst)
    wb = openpyxl.load_workbook(dst)
    cols = {'Modules': (1, 2), 'UC BPMN': (4, 5), 'UC Non-BPMN': (4, 5), 'Entities': (2, 3)}  # (kolom Modul, kolom Sub Modul)
    for ws in wb:
        cm, cs = cols[ws.title]
        for r in range(ws.max_row, 1, -1):
            mod, sub = ws.cell(r, cm).value, ws.cell(r, cs).value
            if mod is None and all(c.value is None for c in ws[r]):
                continue
            ok = mod in filt and (filt[mod] is None or sub in filt[mod])
            if not ok:
                ws.delete_rows(r)
    wb.save(dst)


def build_mockup(key, nama, dst):
    if os.path.isdir(dst):
        shutil.rmtree(dst)
    os.makedirs(dst)
    html = open(os.path.join(MOCK, 'Labmania.html'), encoding='utf-8').read()
    assert html.count('<html lang="id">') == 1
    html = html.replace('<html lang="id">', f'<html lang="id" data-paket="{key}">')
    html = re.sub(r'<title>.*?</title>', f'<title>Labmania — {nama} (Mockup)</title>', html, count=1)
    open(os.path.join(dst, 'index.html'), 'w', encoding='utf-8', newline='').write(html)
    for f in re.findall(r'<(?:script|link)[^>]+(?:src|href)="([^"]+)"', html):
        shutil.copyfile(os.path.join(MOCK, f), os.path.join(dst, f))
    core = open(os.path.join(MOCK, 'core.js'), encoding='utf-8').read()
    assert f"'{key}':" in core, f'PAKET_MENU di core.js belum punya {key}'


def main(nama):
    cfg = PAKET[nama]
    out = os.path.join(ROOT, 'final', nama)
    os.makedirs(out, exist_ok=True)
    build_fr(nama, cfg, out)
    build_drawio(os.path.join(ROOT, 'docs', 'diagrams', 'erd.drawio'), cfg['erd'], os.path.join(out, f'ERD {nama}.drawio'))
    build_drawio(os.path.join(ROOT, 'docs', 'diagrams', 'bpmn-to-be.drawio'), cfg['flow'], os.path.join(out, f'Flow Process {nama}.drawio'))
    build_uc(cfg['uc'], os.path.join(out, f'Use Case {nama}.xlsx'))
    build_mockup(cfg['mockup'], nama, os.path.join(out, f'Mock Up {nama}'))
    print('ok', out)


if __name__ == '__main__':
    if len(sys.argv) != 2 or sys.argv[1] not in PAKET:
        sys.exit('Sebutkan satu paket: ' + ' | '.join(f'"{k}"' for k in PAKET))
    main(sys.argv[1])
