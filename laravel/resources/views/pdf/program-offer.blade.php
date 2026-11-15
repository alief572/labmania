<h1>SURAT PENAWARAN</h1>
<p>No. {{ $p->nomor }}</p>
<p>Kepada:<br>
@if ($company)
    <b>{{ $company->name_customer }}</b><br>
    u.p. {{ $company->pic_name }}{{ $company->pic_jabatan ? ', '.$company->pic_jabatan : '' }}<br>
    {{ $company->address_office }}{{ $company->kota ? ', '.$company->kota : '' }}
@else
    calon peserta
@endif
</p>
<p>Dengan hormat, bersama ini kami menawarkan program berikut:</p>
<table class="tbl"><tbody>
    <tr><th>Program</th><td>{{ $title }}</td></tr>
    <tr><th>Pelaksanaan</th><td>{{ $p->tgl_mulai }} s.d. {{ $p->tgl_selesai }} · {{ $p->waktu }} · {{ $p->lokasi }}</td></tr>
    <tr><th>Durasi</th><td>{{ $p->durasi_hari }} hari</td></tr>
    <tr><th>Jam Pembelajaran</th><td>{{ $p->jam_pembelajaran }} JP</td></tr>
    <tr><th>Trainer</th><td>{{ $trainer }}</td></tr>
    <tr><th>Harga / peserta</th><td>Rp {{ number_format($p->harga_jual_peserta, 0, ',', '.') }}</td></tr>
    <tr><th>Jumlah peserta</th><td>{{ $p->target_peserta }} peserta</td></tr>
    <tr><th>Total</th><td><b>Rp {{ number_format($p->harga_jual_peserta * $p->target_peserta, 0, ',', '.') }}</b></td></tr>
</tbody></table>
<p>Angka diambil dari Penjualan Program Final dan tidak dapat diubah di sini.</p>
