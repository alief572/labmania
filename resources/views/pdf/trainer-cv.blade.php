<style>
  body { font-family: dejavusans, sans-serif; font-size: 10pt; color: #030637; }
  h1 { font-size: 13pt; margin: 0 0 1mm 0; color: #14304F; }
  .kode { font-size: 9pt; color: #6E7390; margin: 0 0 4mm 0; }
  table { border-collapse: collapse; width: 100%; }
  .sum td { padding: 2mm 3mm; border: 0.2mm solid #D3DCE8; font-size: 9.5pt; vertical-align: top; }
  .sum td.lbl { width: 45%; }
  .sum td.val { text-align: right; font-weight: bold; }
  .sum tr.odd td { background-color: #E4EAF3; }
  h3 { font-size: 11pt; color: #14304F; margin: 7mm 0 2mm 0; }
  .lst th { background-color: #14304F; color: #ffffff; font-size: 8.5pt; text-align: left; padding: 2mm 2.5mm; border: 0.2mm solid #14304F; }
  .lst td { font-size: 9pt; padding: 2mm 2.5mm; border: 0.2mm solid #D3DCE8; vertical-align: top; }
  .lst tr.odd td { background-color: #F6F8FB; }
  .none { font-size: 9pt; color: #6E7390; }
</style>
<h1>MASTER TRAINER — IDENTITAS FASILITATOR</h1>
<p class="kode">{{ $t->kode }}</p>
<table class="sum">
  @foreach ($summary as $i => $row)
    <tr class="{{ $i % 2 === 0 ? 'odd' : '' }}"><td class="lbl">{{ $row[0] }}</td><td class="val">{{ trim((string) $row[1]) === '' ? '—' : $row[1] }}</td></tr>
  @endforeach
</table>
@foreach ($lists as $l)
  <h3>{{ $l['title'] }}</h3>
  @if ($l['rows'])
    <table class="lst" repeat_header="1">
      <thead><tr><th style="width: 9mm;">No.</th>@foreach ($l['cols'] as $c)<th>{{ $c[1] }}</th>@endforeach</tr></thead>
      <tbody>
        @foreach ($l['rows'] as $i => $r)
          <tr class="{{ $i % 2 ? 'odd' : '' }}" nobr="true">
            <td>{{ $i + 1 }}</td>
            @foreach ($l['cols'] as $c)
              <td>
                @if ($c[0] === 'file_path')
                  {{ ! empty($r['file_path']) ? 'Terlampir (lihat halaman lampiran)' : '—' }}
                @else
                  {{ trim((string) ($r[$c[0]] ?? '')) === '' ? '—' : $r[$c[0]] }}
                @endif
              </td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p class="none">Belum ada data.</p>
  @endif
@endforeach
