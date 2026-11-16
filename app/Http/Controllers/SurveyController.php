<?php

namespace App\Http\Controllers;

use App\Support\Participants;
use App\Support\Sales;
use App\Support\Survey;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SurveyController extends Controller
{
    public function show(string $token)
    {
        $s = Survey::row($token);
        $p = Sales::program($s->id_penjualan);
        $open = $s->status === 'buka' && ! $p->closed_date && $p->status === 'final';

        $answered = $open ? DB::table('survey_jawaban')->where('id_program_survey', $s->id)->pluck('id_quotation_peserta') : collect();

        return Inertia::render('Survey', ['token' => $token, 'open' => $open, 'program' => ['judul' => Sales::title($p->id_master_program), 'tgl_mulai' => $p->tgl_mulai, 'tgl_selesai' => $p->tgl_selesai, 'lokasi' => $p->lokasi, 'trainer' => DB::table('ms_trainer')->where('id', $p->id_trainer)->value('nama')],
            'participants' => $open ? Participants::query($p->id)->get()->map(fn ($x) => ['id' => $x->id, 'nama' => $x->nama, 'instansi' => $x->instansi, 'answered' => $answered->contains($x->id)]) : [], 'questions' => $open ? Survey::questions() : []]);
    }

    public function store(Request $r, string $token)
    {
        Survey::row($token);
        $data = $r->validate(['id_quotation_peserta' => 'required|integer|min:1', 'jawaban' => 'required|array|max:100']);
        Survey::submit($token, $data);

        return back()->with('success', 'Terima kasih. Jawaban survei tersimpan.');
    }

    public function toggle(int $id, string $action)
    {
        Gate::authorize('menu', ['manage_peserta', 'Manage']);
        Survey::toggle($id, $action === 'buka');

        return back()->with('success', 'Status survei tersimpan.');
    }

    public function excel(int $id)
    {
        Gate::authorize('menu', ['manage_peserta', 'View']);
        $s = Survey::summary($id);
        $questions = collect($s['details'])->map(fn ($d) => ['id' => $d->id_survey_pertanyaan, 'teks' => $d->pertanyaan_teks])->unique(fn ($q) => $q['id'].'|'.$q['teks'])->values();
        $rows = [['No', 'Nama', 'Instansi', 'No. Quotation', 'Waktu isi', ...$questions->pluck('teks')->all()]];
        foreach ($s['answers'] as $i => $a) {
            $values = collect($s['details'])->where('id_survey_jawaban', $a->id);
            $rows[] = [$i + 1, $a->nama, $a->instansi, Sales::display($a), $a->tgl_isi, ...$questions->map(fn ($q) => ($values->first(fn ($d) => $d->id_survey_pertanyaan === $q['id'] && $d->pertanyaan_teks === $q['teks'])))->map(fn ($d) => $d ? ($d->nilai ?? $d->jawaban) : '')->all()];
        }
        write_log('Manage Peserta', 'Export Survey', 'Export Survey '.Sales::display(Sales::program($id)), null, 'penjualan_program', $id, Sales::display(Sales::program($id)));

        return Xlsx::response('Survey_'.Sales::display(Sales::program($id)), $rows);
    }
}
