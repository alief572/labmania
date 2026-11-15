<html><head><meta charset="utf-8"><style>
  body { font-family: dejavusans, sans-serif; color: #3C4161; margin: 0; padding: 0; }
  h1, h2, h3 { margin: 0; }
  ul { list-style-type: disc; margin: 1px 0 0 0; padding-left: 12px; }
  .head { border-bottom: 3px solid #D5A320; margin-bottom: 14px; }
  .bidang { background-color: #14304F; color: #FFFFFF; font-size: 11pt; font-weight: bold; padding: 6px 10px; margin: 10px 0 8px 0; }
  .card { border: 1px solid #D3DCE8; padding: 9px 11px; }
  .tag { background-color: #D5A320; color: #030637; font-size: 7pt; font-weight: bold; padding: 1px 6px; }
  .judul { font-size: 10.5pt; font-weight: bold; color: #14304F; margin: 5px 0; }
  .meta { font-size: 8pt; color: #3C4161; }
  .muted { color: #6E7390; }
  .sert { border-top: 1px dashed #D3DCE8; margin-top: 6px; padding-top: 5px; font-size: 8pt; font-weight: bold; color: #7A5A00; }
</style></head><body>

<div style="text-align:center; padding-top: 180px;">
  <div style="font-size: 30pt; font-weight: bold; color: #14304F;">LAB<span style="color:#D5A320;">MANIA</span></div>
  <div style="font-size: 9pt; letter-spacing: 4px; color: #6E7390; margin-top: 8px;">KATALOG PRODUK {{ $tahun }}</div>
  <div style="font-size: 9pt; color: #6E7390; margin-top: 40px;">Dicetak {{ now()->format('d-m-Y') }}</div>
</div>
<pagebreak />

<table width="100%" class="head" cellpadding="0" cellspacing="0"><tr>
  <td style="padding: 6px 0;"><b style="font-size: 14pt; color: #14304F;">LAB<span style="color:#D5A320;">MANIA</span></b></td>
  <td align="right" style="font-size: 8pt; color: #6E7390; letter-spacing: 2px;">KATALOG PRODUK {{ $tahun }}</td>
</tr></table>

@foreach ($groups as $group)
  <div class="bidang">{{ $group['nama'] }}</div>
  @foreach (array_chunk($group['items'], 2) as $pair)
    <table width="100%" cellpadding="0" cellspacing="6" style="margin-bottom: 2px;"><tr>
      @foreach ($pair as $p)
        <td width="50%" valign="top"><div class="card">
          <table width="100%" cellpadding="0" cellspacing="0"><tr>
            <td><span class="tag">{{ mb_strtoupper($p->nama_produk ?? '—') }}</span></td>
            <td align="right" class="muted" style="font-size: 7pt;">{{ $p->nomor }}</td>
          </tr></table>
          <div class="judul">{{ $p->judul }}</div>
          <div class="meta"><span class="muted">Bidang</span> <b>{{ $p->nama_bidang ?? '—' }}</b></div>
          <div class="meta"><span class="muted">Varian</span> <b>{{ $p->nama_varian ?? '—' }}</b></div>
          @if ($p->lingkup)
            <div class="meta" style="margin-top: 4px;"><span class="muted">Lingkup</span>
              <ul>@foreach ($p->lingkup as $t)<li>{{ $t }}</li>@endforeach</ul></div>
          @endif
          <div class="sert">&#9679; {{ $p->nama_sertifikasi ?? '—' }}</div>
        </div></td>
      @endforeach
      @if (count($pair) === 1)<td width="50%"></td>@endif
    </tr></table>
  @endforeach
@endforeach
</body></html>
