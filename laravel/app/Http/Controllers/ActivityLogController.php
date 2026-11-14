<?php

namespace App\Http\Controllers;

use App\Support\Audit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request)
    {
        Gate::authorize('menu', ['activity_log', 'View']);
        $filters = $request->validate([
            'module' => 'nullable|string|max:50', 'action' => 'nullable|string|max:30',
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d'.($request->filled('from') ? '|after_or_equal:from' : ''),
            'keyword' => 'nullable|string|max:100', 'ref_table' => 'nullable|string|max:50', 'ref_no' => 'nullable|string|max:50',
            'ref_id' => 'nullable|integer|min:1', 'user' => 'nullable|integer|min:1',
        ]);
        $query = DB::table('logs as l')->leftJoin('users as u', 'u.id_user', '=', 'l.created_by')
            ->select('l.*', 'u.nm_lengkap', 'u.username');
        foreach (['module', 'action', 'ref_table', 'ref_id', 'ref_no'] as $key) {
            if (($filters[$key] ?? null) !== null) {
                $query->where('l.'.$key, $filters[$key]);
            }
        }
        if (($filters['user'] ?? null) !== null) {
            $query->where('l.created_by', $filters['user']);
        }
        if (! empty($filters['from'])) {
            $query->where('l.created_date', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $query->where('l.created_date', '<', Carbon::parse($filters['to'])->addDay()->format('Y-m-d'));
        }
        if (($filters['keyword'] ?? null) !== null) {
            $query->where(fn ($q) => $q->where('l.description', 'like', '%'.$filters['keyword'].'%')->orWhere('l.ref_no', 'like', '%'.$filters['keyword'].'%')->orWhere('u.nm_lengkap', 'like', '%'.$filters['keyword'].'%')->orWhere('u.username', 'like', '%'.$filters['keyword'].'%'));
        }
        $logs = $query->orderByDesc('l.id')->paginate(50)->withQueryString();
        $logs->through(function ($log) {
            $decoded = json_decode($log->data ?? '', true);
            $log->actor_label = $log->created_by === null && ($decoded['actor_type'] ?? null) === 'publik'
                ? 'Calon peserta / PIC (tanpa login)' : ($log->nm_lengkap ?: ($log->username ?: 'Akun tidak tersedia'));
            $log->data = is_array($decoded) ? Audit::redact(Audit::normalize($decoded)) : null;

            return $log;
        });

        return Inertia::render('ActivityLog', [
            'logs' => $logs, 'filters' => $filters,
            ...Cache::remember('logs.filters', 300, fn () => [
                'modules' => DB::table('logs')->distinct()->orderBy('module')->pluck('module'),
                'actions' => DB::table('logs')->distinct()->orderBy('action')->pluck('action'),
                'users' => DB::table('logs as l')->leftJoin('users as u', 'u.id_user', '=', 'l.created_by')
                    ->whereNotNull('l.created_by')->distinct()->orderBy('u.nm_lengkap')->get(['l.created_by', 'u.nm_lengkap']),
            ]),
        ]);
    }
}
