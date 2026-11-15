<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class Survey
{
    public static function enabled(): bool
    {
        return config('labmania.operasional.survey_public_enabled', false) === true && DB::table('survey_pertanyaan')->where('aktif', 1)->exists();
    }

    public static function questions()
    {
        return DB::table('survey_pertanyaan')->where('aktif', 1)->orderBy('urutan')->orderBy('id')->get()->map(function ($q) {
            $q->opsi = $q->opsi ? json_decode($q->opsi, true, 512, JSON_THROW_ON_ERROR) : [];

            return $q;
        });
    }

    public static function summary(int $program): array
    {
        $s = DB::table('program_survey')->where('id_penjualan', $program)->first();
        $answers = $s ? DB::table('survey_jawaban as a')->join('quotation_peserta as x', 'x.id', '=', 'a.id_quotation_peserta')->join('quotation as q', 'q.id', '=', 'x.id_quotation')->where('a.id_program_survey', $s->id)->get(['a.id', 'x.id as peserta_id', 'x.nama', 'x.instansi', 'q.nomor', 'q.revisi', 'a.tgl_isi']) : collect();
        $details = $answers->isEmpty() ? collect() : DB::table('survey_jawaban_detail as d')->join('survey_jawaban as a', 'a.id', '=', 'd.id_survey_jawaban')->join('quotation_peserta as x', 'x.id', '=', 'a.id_quotation_peserta')->whereIn('d.id_survey_jawaban', $answers->pluck('id'))->get(['d.*', 'x.nama']);
        $recap = [];
        $choices = DB::table('survey_pertanyaan')->where('tipe', 'pilihan')->pluck('id');
        foreach ($details->groupBy(fn ($d) => $d->id_survey_pertanyaan.'|'.$d->pertanyaan_teks) as $group) {
            $scale = $group->whereNotNull('nilai');
            $recap[] = ['pertanyaan' => $group->first()->pertanyaan_teks, 'rata' => $scale->isNotEmpty() ? round($scale->avg('nilai'), 1) : null,
                'nilai' => $scale->countBy('nilai'), 'pilihan' => $choices->contains($group->first()->id_survey_pertanyaan) ? $group->whereNotNull('jawaban')->countBy('jawaban') : null, 'jawaban' => $group->whereNotNull('jawaban')->values()];
        }

        return ['enabled' => self::enabled(), 'status' => $s?->status ?? 'belum', 'url' => $s && self::enabled() ? url('/survey/'.$s->token) : null,
            'answers' => $answers, 'details' => $details, 'recap' => $recap, 'total' => Participants::query($program)->count()];
    }

    public static function toggle(int $id, bool $open): void
    {
        Sales::transaction(function () use ($id, $open) {
            $p = Sales::program($id, true);
            Sales::open($p);
            if ($p->status !== 'final' || ($open && (! self::enabled() || ! Participants::query($id)->exists()))) {
                throw ValidationException::withMessages(['survey' => 'Survei publik menunggu pengesahan BA dan peserta Deal.']);
            }
            $s = DB::table('program_survey')->where('id_penjualan', $id)->lockForUpdate()->first();
            $new = ['status' => $open ? 'buka' : 'tutup', $open ? 'dibuka_by' : 'ditutup_by' => Auth::id(), $open ? 'dibuka_date' : 'ditutup_date' => now(), 'updated_by' => Auth::id(), 'updated_date' => now()];
            if ($s) {
                DB::table('program_survey')->where('id', $s->id)->update($new);
            } else {
                if (! $open) {
                    throw ValidationException::withMessages(['survey' => 'Survei belum pernah dibuka.']);
                }
                DB::table('program_survey')->insert(['id_penjualan' => $id, 'token' => rtrim(strtr(base64_encode(random_bytes(15)), '+/', '-_'), '='), ...$new, ...Invoice::audit()]);
            }
            write_log('Manage Peserta', $open ? 'Buka Survey' : 'Tutup Survey', 'Survey '.Sales::display($p), ['old' => ['status' => $s?->status ?? 'belum'], 'new' => ['status' => $new['status']]], 'penjualan_program', $id, Sales::display($p));
        });
    }

    public static function row(string $token): object
    {
        abort_unless(self::enabled(), 503, 'Survei publik belum diaktifkan; menunggu pengesahan BA.');

        return DB::table('program_survey')->where('token', $token)->first() ?? abort(404);
    }

    public static function submit(string $token, array $input): void
    {
        $read = self::row($token);
        Sales::transaction(function () use ($read, $input) {
            $p = Sales::program($read->id_penjualan, true);
            Sales::open($p);
            $s = DB::table('program_survey')->where('id', $read->id)->lockForUpdate()->first();
            if ($s->status !== 'buka' || $p->status !== 'final') {
                throw ValidationException::withMessages(['survey' => 'Survei sudah ditutup.']);
            }
            $x = Participants::query($p->id)->where('x.id', $input['id_quotation_peserta'])->first();
            if (! $x || DB::table('survey_jawaban')->where('id_program_survey', $s->id)->where('id_quotation_peserta', $x->id)->exists()) {
                throw ValidationException::withMessages(['id_quotation_peserta' => 'Peserta tidak tersedia atau sudah mengisi.']);
            }
            $rules = [];
            $questions = self::questions();
            foreach ($questions as $q) {
                $rules['jawaban.'.$q->id] = [$q->wajib ? 'required' : 'nullable', ...match ($q->tipe) {
                    'skala' => ['integer', 'between:1,5'], 'pilihan' => ['string', Rule::in($q->opsi)], 'teks' => ['string', 'max:500'],
                }];
            }
            $valid = validator($input, $rules)->validate();
            $id = DB::table('survey_jawaban')->insertGetId(['id_program_survey' => $s->id, 'id_quotation_peserta' => $x->id, 'tgl_isi' => now(), 'created_date' => now()]);
            $details = [];
            foreach ($questions as $q) {
                $value = $valid['jawaban'][$q->id] ?? null;
                $details[] = ['id_survey_jawaban' => $id, 'id_survey_pertanyaan' => $q->id, 'pertanyaan_teks' => $q->teks,
                    'nilai' => $q->tipe === 'skala' ? $value : null, 'jawaban' => $q->tipe === 'skala' ? null : $value, 'created_date' => now()];
            }
            DB::table('survey_jawaban_detail')->insert($details);
            Audit::publicSurvey($p->id, Sales::display($p), $x->nama, $id);
        });
    }
}
