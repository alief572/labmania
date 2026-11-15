@php
    $dates = new \App\Support\QuotationPdf;
    $start = \Carbon\Carbon::parse($program['tgl_mulai'])->locale('id');
    $end = \Carbon\Carbon::parse($program['tgl_selesai'])->locale('id');
    $variant = collect($produk)->pluck('varian')->filter()->unique()->implode(' + ');
    $title = collect($produk)->pluck('judul')->implode(' + ');
@endphp
<h1 class="title">SURAT TUGAS</h1><p align="center">No. {{ $surat->nomor }}</p>
@if($surat->status === 'diganti')<h2 align="center">DIGANTI</h2>@endif
<p>Menindaklanjuti kegiatan {{ $variant }} "{{ $title }}", saya yang bertanda tangan di bawah ini:</p>
<table class="form"><tr><td>Nama</td><td>: ........................................................</td></tr><tr><td>Jabatan</td><td>: Operational Manager</td></tr></table>
<p>Dengan ini menugaskan kepada:</p>
<table width="100%" style="border-collapse:collapse"> <thead><tr>@foreach(['No.', 'Nama', 'NIK', 'Jabatan'] as $h)<th style="border:0.3mm solid #111;padding:2mm">{{ $h }}</th>@endforeach</tr></thead><tbody>
@for($i=1;$i<=10;$i++)<tr><td style="border:0.3mm solid #111;padding:2mm" width="8%">{{ $i <= 2 ? $i : '' }}</td><td style="border:0.3mm solid #111;padding:2mm" width="40%">{{ $i === 1 ? $trainer['nama'] : ($i === 2 ? $asisten['nama'] : '') }}</td><td style="border:0.3mm solid #111;padding:2mm" width="25%">{{ $i <= 2 ? '........................' : '' }}</td><td style="border:0.3mm solid #111;padding:2mm">{{ $i === 1 ? 'Trainer' : ($i === 2 ? 'Asisten Trainer' : '') }}</td></tr>@endfor
</tbody></table>
<p>Sebagaimana ditugaskan sebagai <b>Trainer / Asisten Trainer</b> dalam acara {{ $variant }} "{{ $title }}" bertempat di {{ $program['lokasi'] }} yang dilaksanakan pada:</p>
<table class="form"><tr><td>Hari / Tanggal</td><td>: {{ $start->translatedFormat('l, j F Y') }}@if($program['tgl_selesai'] !== $program['tgl_mulai']) s.d. {{ $end->translatedFormat('l, j F Y') }}@endif</td></tr><tr><td>Waktu</td><td>: {{ $program['waktu'] }}</td></tr></table>
<p>Demikian Surat Tugas ini dibuat agar yang bersangkutan dapat melaksanakan dengan baik dan penuh tanggung jawab.</p>
<p align="right">Bekasi, {{ $dates->tgl(today()->toDateString()) }}<br>Operational Manager<br><br><br><br>(........................................)</p>
