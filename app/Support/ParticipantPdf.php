<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class ParticipantPdf
{
    public static function make(object $p, $people, string $kind): string
    {
        $level = error_reporting();
        error_reporting($level & ~E_DEPRECATED);
        try {
            $tmp = storage_path('app/mpdf');
            if (! is_dir($tmp)) {
                mkdir($tmp, 0775, true);
            }
            $options = ['format' => 'A4-L', 'tempDir' => $tmp, 'default_font' => 'dejavusans', 'default_font_size' => 10, 'margin_left' => 10, 'margin_right' => 10, 'margin_top' => 10, 'margin_bottom' => 12];
            $fontDir = config('labmania.operasional.font_dir');
            if ($kind === 'name-table') {
                if (! is_file($fontDir.'/times.ttf') || ! is_file($fontDir.'/timesbd.ttf')) {
                    throw ValidationException::withMessages(['dokumen' => 'Font Times New Roman belum tersedia pada server.']);
                }
                $options['fontDir'] = [...(new ConfigVariables)->getDefaults()['fontDir'], $fontDir];
                $options['fontdata'] = (new FontVariables)->getDefaults()['fontdata'] + ['timesnewroman' => ['R' => 'times.ttf', 'B' => 'timesbd.ttf']];
            }
            $m = new Mpdf($options);
            $m->SetTitle($kind.' '.Sales::display($p));
            $title = Sales::title($p->id_master_program);
            $page = function () use ($m) {
                $m->AddPage();
            };
            if ($kind === 'name-table') {
                foreach ($people as $x) {
                    $page();
                    $name = e($x->nama);
                    $company = e($x->instansi);
                    foreach ([true, false] as $flip) {
                        $html = '<table width="100%"><tr><td style="text-align:center;font-family:timesnewroman;font-size:40pt;font-weight:bold;overflow-wrap:break-word">'.$name.'</td></tr><tr><td style="text-align:center;font-family:timesnewroman;font-size:38pt;overflow-wrap:break-word">'.$company.'</td></tr></table>';
                        if ($flip) {
                            $m->Rotate(180, 148.5, 52.5);
                        }
                        $m->WriteFixedPosHTML($html, 10, $flip ? 17 : 121, 277, 70, 'auto');
                        $m->Rotate(0);
                    }
                }
            } elseif ($kind === 'draft') {
                foreach ($people as $x) {
                    $page();
                    $m->SetWatermarkText('DRAFT');
                    $m->showWatermarkText = true;
                    $m->WriteHTML('<div style="text-align:center;padding-top:20mm"><h1>DRAFT SERTIFIKAT PELATIHAN</h1><p>Pratinjau konfirmasi nama; tanpa nomor dan tanda tangan resmi.</p><p>Diberikan kepada</p><h1>'.e($x->nama_sertifikat ?: $x->nama).'</h1><p>'.e($x->instansi).'</p><h2>'.e($title).'</h2><p>'.e($p->tgl_mulai).' s.d. '.e($p->tgl_selesai).' · '.e($p->lokasi).'</p><p>'.e($p->jam_pembelajaran).' Jam Pembelajaran</p><p>Trainer: '.e(DB::table('ms_trainer')->where('id', $p->id_trainer)->value('nama')).'</p></div>');
                }
            } else {
                $variant = DB::table('master_program as a')->join('paket_produk as b', 'b.id', '=', 'a.id_paket_produk')->join('ms_varian_pelaksanaan as v', 'v.id', '=', 'b.id_varian_pelaksanaan')->where('a.id', $p->id_master_program)->pluck('v.nama')->unique()->implode(' + ');
                $chunks = $people->chunk(10)->values();
                for ($day = 0; $day < $p->durasi_hari; $day++) {
                    foreach ($chunks as $i => $chunk) {
                        $page();
                        $date = Carbon::parse($p->tgl_mulai)->addDays($day)->locale('id')->translatedFormat('l, j F Y');
                        $m->SetHTMLFooter('<table width="100%" style="font-size:8pt;border-top:1px solid black"><tr><td>FR/LMI-TR-05/17/Ed.Rev-1.0</td><td align="center">'.($i + 1).' dari '.$chunks->count().'</td><td align="right">Berlaku 01/08/2023</td></tr></table>');
                        $html = '<table width="100%"><tr><td width="35%" style="font-size:24pt;font-weight:bold">LAB<br><span style="font-size:10pt">M A N I A</span></td><td align="right"><b>PT LABMANIA INDONESIA</b></td></tr></table><h2 align="center">DAFTAR HADIR PESERTA</h2>';
                        foreach (['Jenis Kegiatan' => $variant, 'Judul Kegiatan' => $title, 'Hari/Tanggal' => $date, 'Tempat' => $p->lokasi] as $label => $value) {
                            $html .= '<div><b>'.$label.'</b>: '.e($value).'</div>';
                        }
                        $html .= '<br><table width="100%" style="border-collapse:collapse" border="1" cellpadding="5"><thead><tr><th>No.</th><th>Nama</th><th>Instansi</th><th>No. HP</th><th>Email</th><th>Tanda Tangan</th><th>Ket.</th></tr></thead><tbody>';
                        $list = $chunk->values();
                        for ($n = 0; $n < 10; $n++) {
                            $x = $list->get($n);
                            $html .= '<tr><td height="25">'.($i * 10 + $n + 1).'</td><td>'.e($x?->nama).'</td><td>'.e($x?->instansi).'</td><td>'.e($x?->no_hp ?: $x?->no_wa).'</td><td>'.e($x?->email).'</td><td width="20%"></td><td width="8%"></td></tr>';
                        }
                        $m->WriteHTML($html.'</tbody></table>');
                    }
                }
            }

            return $m->Output('', Destination::STRING_RETURN);
        } finally {
            error_reporting($level);
        }
    }
}
