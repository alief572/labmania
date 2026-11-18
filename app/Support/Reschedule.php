<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Reschedule
{
    public static function reason(object $p): ?string
    {
        if ($p->closed_date) {
            return 'Program sudah Closed.';
        }
        if ($p->status !== 'final') {
            return 'Reschedule hanya untuk program Final.';
        }
        if (! $p->tgl_mulai || today()->gt(Carbon::parse($p->tgl_mulai)->subDays(7))) {
            return 'Batas H-7 dari jadwal lama sudah lewat.';
        }

        return null;
    }

    public static function version(object $p): string
    {
        return hash('sha256', json_encode([$p,
            DB::table('penjualan_program_reschedule')->where('id_penjualan', $p->id)->max('id'),
            DB::table('quotation')->where('id_penjualan', $p->id)->orderBy('id')->get(),
            DB::table('quotation_peserta as x')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('q.id_penjualan', $p->id)->orderBy('x.id')->get(['x.id', 'x.status']),
            DB::table('invoice')->where('id_penjualan', $p->id)->orderBy('id')->get(['id', 'status', 'tanggal_bayar'])], JSON_THROW_ON_ERROR));
    }

    public static function save(int $id, array $input, array $files): int
    {
        $digest = hash('sha256', json_encode([array_diff_key($input, array_flip(['version', 'request_id', 'evidence'])), array_map(fn ($f) => hash_file('sha256', QuotationFiles::storedPath($f['path'])), $files)], JSON_THROW_ON_ERROR));

        return Sales::transaction(function () use ($id, $input, $files, $digest) {
            $p = Sales::program($id, true);
            Sales::open($p);
            $existing = DB::table('penjualan_program_reschedule')->where('request_id', $input['request_id'])->first();
            if ($existing) {
                if ((int) $existing->id_penjualan !== $id || ! hash_equals($existing->request_hash, $digest)) {
                    throw ValidationException::withMessages(['reschedule' => 'Request sudah dipakai dengan data berbeda.']);
                }

                return $existing->id;
            }
            if ($why = self::reason($p)) {
                throw ValidationException::withMessages(['reschedule' => $why]);
            }
            if (! hash_equals(self::version($p), $input['version'])) {
                throw ValidationException::withMessages(['reschedule' => 'Data berubah sejak pratinjau; muat ulang sebelum menyimpan.']);
            }
            $dateChanged = $p->tgl_mulai !== $input['tgl_mulai'];
            if (! $dateChanged && (int) $p->id_trainer === (int) $input['id_trainer'] && (int) $p->id_asisten === (int) $input['id_asisten']) {
                throw ValidationException::withMessages(['reschedule' => 'Minimal tanggal, trainer atau asisten harus berubah.']);
            }
            $oldLetter = DB::table('surat_kerja_trainer')->where('id_penjualan', $id)->where('status', 'berlaku')->lockForUpdate()->first();
            if ($oldLetter) {
                SalesOrder::assignment($oldLetter->id);
            }
            $quotes = DB::table('quotation')->where('id_penjualan', $id)->where('status', '!=', 'batal')->orderBy('id')->lockForUpdate()->get();
            $history = [];
            if ($dateChanged) {
                foreach ($quotes as $q) {
                    $safe = (array) $q;
                    unset($safe['link_token']);
                    $so = DB::table('sales_order')->where('id_quotation', $q->id)->first();
                    $snap = ['quotation' => $safe, 'program' => (array) $p, 'sales_order' => $so,
                        'pdf_quotation' => base64_encode(QuotationPdf::offer($q, $p))];
                    if ($so) {
                        $snap['pdf_so'] = base64_encode(QuotationPdf::document('Sales Order '.Sales::display($so), view('pdf.sales-order', SalesOrder::detail($so))->render()));
                    }
                    $history[] = ['id_quotation' => $q->id, 'revisi' => $q->revisi, 'link_token_hash' => $q->link_token ? hash('sha256', $q->link_token) : null, 'snapshot' => json_encode($snap, JSON_THROW_ON_ERROR)];
                }
            }
            $update = ['tgl_mulai' => $input['tgl_mulai'], 'tgl_selesai' => Carbon::parse($input['tgl_mulai'])->addDays($p->durasi_hari - 1)->toDateString(),
                'id_trainer' => $input['id_trainer'], 'id_asisten' => $input['id_asisten'], 'revisi' => $p->revisi + ($dateChanged ? 1 : 0), 'updated_by' => Auth::id(), 'updated_date' => now()];
            $snap = ['old' => (array) $p, 'new' => array_merge((array) $p, $update), 'quotation' => $dateChanged ? $quotes->count() : 0,
                'pdf_program' => base64_encode(QuotationPdf::program($p)), 'surat_lama' => $oldLetter?->id];
            $trainers = DB::table('ms_trainer')->whereIn('id', [$p->id_trainer, $input['id_trainer']])->pluck('nama', 'id');
            $assistants = DB::table('ms_user_karyawan')->whereIn('id', [$p->id_asisten, $input['id_asisten']])->pluck('nama', 'id');
            $snap['team'] = ['trainer_lama' => $trainers->get($p->id_trainer), 'trainer_baru' => $trainers->get($input['id_trainer']),
                'asisten_lama' => $assistants->get($p->id_asisten), 'asisten_baru' => $assistants->get($input['id_asisten'])];
            $row = ['id_penjualan' => $id, 'tgl_mulai_lama' => $p->tgl_mulai, 'tgl_selesai_lama' => $p->tgl_selesai, 'tgl_mulai_baru' => $update['tgl_mulai'], 'tgl_selesai_baru' => $update['tgl_selesai'],
                'id_trainer_lama' => $p->id_trainer, 'id_trainer_baru' => $input['id_trainer'], 'id_asisten_lama' => $p->id_asisten, 'id_asisten_baru' => $input['id_asisten'],
                'alasan' => $input['alasan'], 'request_id' => $input['request_id'], 'request_hash' => $digest, 'snapshot' => json_encode($snap, JSON_THROW_ON_ERROR), ...Invoice::audit()];
            $rid = DB::table('penjualan_program_reschedule')->insertGetId($row);
            foreach ($files as $f) {
                DB::table('penjualan_program_reschedule_evidence')->insert(['id_reschedule' => $rid, 'nama_file' => $f['name'], 'path_file' => $f['path'], 'tipe_file' => $f['ext'], 'ukuran_byte' => $f['size'], ...Invoice::audit()]);
            }
            foreach ($history as $h) {
                DB::table('quotation_revisi')->insert($h + ['id_reschedule' => $rid, ...Invoice::audit()]);
            }
            DB::table('penjualan_program')->where('id', $id)->update($update);
            if ($dateChanged) {
                foreach ($quotes as $q) {
                    $rev = $q->revisi + 1;
                    DB::table('quotation')->where('id', $q->id)->update(['revisi' => $rev, 'link_token' => bin2hex(random_bytes(16)), 'link_dibuat' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()]);
                    foreach (DB::table('quotation_peserta')->where('id_quotation', $q->id)->whereIn('status', ['menunggu', 'deal'])->get() as $person) {
                        DB::table('quotation_peserta_konfirmasi_ulang')->insert(['id_quotation_peserta' => $person->id, 'id_reschedule' => $rid, 'revisi' => $rev, ...Invoice::audit()]);
                    }
                    // Quotation Deal kembali ke Terkirim dan menunggu Deal ulang (QTN-FR-42); SO berlaku pindah ke SO Batal (SO-FR-21).
                    // Nilai dan jumlah peserta dicatat sebelum status berubah. Invoice tidak dibatalkan, hanya diberi penanda (PJ-FR-27).
                    if ($q->status === 'deal') {
                        $so = DB::table('sales_order')->where('id_quotation', $q->id)->where('status', '!=', 'batal')->first();
                        if ($so) {
                            $count = DB::table('quotation_peserta')->where('id_quotation', $q->id)->where('status', 'deal')->count();
                            DB::table('sales_order')->where('id', $so->id)->update(['status' => 'batal', 'batal_sebab' => 'reschedule', 'batal_alasan' => $input['alasan'], 'batal_waktu' => now(), 'batal_revisi' => $rev,
                                'batal_peserta' => $count, 'batal_nilai' => Quotation::calculate($q, $count)['grand_total'], 'updated_by' => Auth::id(), 'updated_date' => now()]);
                            write_log('Quotation', 'Batal', 'Batal '.$so->nomor.' karena reschedule (rev'.$rev.'): '.$input['alasan'], ['sub_module' => 'Sales Order', 'old' => ['status' => $so->status], 'new' => ['status' => 'batal'], 'alasan' => $input['alasan']], 'sales_order', $so->id, $so->nomor);
                        }
                        DB::table('quotation')->where('id', $q->id)->update(['status' => 'terkirim', 'deal_ulang_revisi' => $rev, 'deal_ulang_waktu' => now(), 'deal_ulang_selesai_revisi' => null, 'deal_ulang_selesai_date' => null]);
                        DB::table('invoice')->where('id_quotation', $q->id)->where('status', 'invoiced')->update(['resched_revisi' => $rev, 'resched_waktu' => now()]);
                    } elseif ($q->deal_ulang_revisi !== null) {
                        DB::table('quotation')->where('id', $q->id)->update(['deal_ulang_revisi' => $rev]);
                    }
                    write_log('Quotation', 'Revisi', 'Revisi '.$q->nomor.'-rev'.$rev, ['old' => ['revisi' => $q->revisi], 'new' => ['revisi' => $rev], 'alasan' => $input['alasan']], 'quotation', $q->id, $q->nomor);
                }
            }
            if ($oldLetter) {
                DB::table('surat_kerja_trainer')->where('id', $oldLetter->id)->update(['status' => 'diganti', 'updated_by' => Auth::id(), 'updated_date' => now()]);
                $number = Sales::number('surat_kerja_trainer', 'SKT-'.now()->format('ym'));
                $newLetter = DB::table('surat_kerja_trainer')->insertGetId(['nomor' => $number, 'id_penjualan' => $id, 'id_trainer' => $input['id_trainer'], 'id_asisten' => $input['id_asisten'],
                    'tanggal' => today()->toDateString(), 'status' => 'berlaku', 'id_reschedule' => $rid, 'snapshot' => json_encode(SalesOrder::programSnapshot(Sales::program($id)), JSON_THROW_ON_ERROR), ...Invoice::audit()]);
                $snap['surat_baru'] = $newLetter;
                DB::table('penjualan_program_reschedule')->where('id', $rid)->update(['snapshot' => json_encode($snap, JSON_THROW_ON_ERROR)]);
                write_log('Quotation', 'Ganti', 'Surat Tugas '.$number.' menggantikan '.$oldLetter->nomor, ['sub_module' => 'Surat Kerja Trainer', 'old' => ['nomor' => $oldLetter->nomor, 'status' => 'berlaku'], 'new' => ['nomor' => $number, 'status' => 'berlaku'], 'alasan' => $input['alasan']], 'surat_kerja_trainer', $newLetter, $number);
            }
            write_log('Penjualan Program', 'Reschedule', 'Reschedule '.Sales::display(Sales::program($id)).': '.$input['alasan'], ['old' => (array) $p, 'new' => array_merge((array) $p, $update),
                'detail_changes' => ['Evidence' => ['old' => [], 'new' => array_column($files, 'name')]]], 'penjualan_program', $id, $p->nomor);

            return $rid;
        });
    }
}
