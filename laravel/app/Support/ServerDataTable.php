<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServerDataTable
{
    public static function prepare(Request $request, string $search = 'q'): void
    {
        if ($request->route('datatable')) {
            $request->validate(['search.value' => 'nullable|string|max:100']);
            if ($request->has('search.value')) {
                $request->merge([$search => $request->input('search.value')]);
            }
        }
    }

    public static function scope(Request $request, Builder $query): void
    {
        if ($request->route('datatable')) {
            $request->attributes->set('datatable.base', clone $query);
        }
    }

    public static function page(Request $request, Builder $query, array $columns, string $key): LengthAwarePaginator
    {
        if (! $request->route('datatable')) {
            return $query->paginate(25)->withQueryString();
        }
        $input = self::parameters($request, count($columns));
        $count = (clone $query)->reorder()->getCountForPagination();
        $base = $request->attributes->get('datatable.base');
        $request->attributes->set('datatable.total', $base ? (clone $base)->reorder()->getCountForPagination() : $count);
        if (! empty($input['order'])) {
            $ordered = false;
            foreach ($input['order'] as $order) {
                if ($field = $columns[(int) $order['column']]) {
                    if (! $ordered) {
                        $query->reorder();
                    }
                    $query->orderBy($field, $order['dir']);
                    $ordered = true;
                }
            }
        }
        $start = (int) ($input['start'] ?? 0);
        $length = (int) ($input['length'] ?? 25);
        $rows = $query->orderBy($key)->offset($start)->limit($length)->get();

        return new LengthAwarePaginator($rows, $count, $length, intdiv($start, $length) + 1);
    }

    public static function result(Request $request, LengthAwarePaginator $page, array $meta = [])
    {
        return response()->json(['draw' => (int) $request->input('draw', 0),
            'recordsTotal' => (int) $request->attributes->get('datatable.total', $page->total()),
            'recordsFiltered' => $page->total(), 'data' => $page->items(), 'meta' => $meta]);
    }

    private static function parameters(Request $request, int $columns): array
    {
        return $request->validate([
            'draw' => 'sometimes|integer|min:0|max:2147483647', 'start' => 'sometimes|integer|min:0|max:2147483647',
            'length' => ['sometimes', 'integer', Rule::in([10, 25, 50, 100])],
            'q' => 'nullable|string|max:100',
            'search' => 'sometimes|array', 'search.value' => 'nullable|string|max:100',
            'search.regex' => ['sometimes', Rule::in(['false', '0', false, 0])],
            'order' => 'sometimes|array|max:'.$columns, 'order.*.column' => 'required|integer|min:0|max:'.($columns - 1), 'order.*.dir' => 'required|in:asc,desc',
            'columns' => 'sometimes|array|max:'.$columns,
            'columns.*.search.regex' => ['sometimes', Rule::in(['false', '0', false, 0])], 'columns.*.search.value' => 'nullable|in:',
        ]);
    }

    public static function response(Request $request, Builder $base, Builder $filtered, array $columns, array $fields, string $key, array $search, ?callable $transform = null)
    {
        $input = $request->validate([
            'draw' => 'sometimes|integer|min:0|max:2147483647', 'start' => 'sometimes|integer|min:0|max:2147483647',
            'length' => ['sometimes', 'integer', Rule::in([10, 25, 50, 100])],
            'q' => 'nullable|string|max:100',
            'search' => 'sometimes|array', 'search.value' => 'nullable|string|max:100', 'search.regex' => ['sometimes', Rule::in(['false', '0', false, 0])],
            'order' => 'sometimes|array|max:'.count($columns), 'order.*.column' => 'required|integer|min:0|max:'.(count($columns) - 1), 'order.*.dir' => 'required|in:asc,desc',
            'columns' => 'sometimes|array|max:'.count($columns), 'columns.*.search.regex' => ['sometimes', Rule::in(['false', '0', false, 0])],
            'columns.*.search.value' => 'nullable|in:',
        ]);
        $query = DB::query()->fromSub((clone $filtered)->reorder(), 'datatable');
        $term = (string) ($input['search']['value'] ?? $request->input('q', ''));
        if ($term !== '') {
            $query->where(function ($q) use ($search, $term) {
                foreach ($search as $field) {
                    $q->orWhere($field, 'like', '%'.$term.'%');
                }
            });
        }
        $total = DB::query()->fromSub((clone $base)->reorder(), 'datatable')->count();
        $count = (clone $query)->count();
        $ordered = false;
        foreach ($input['order'] ?? [] as $order) {
            if ($field = $columns[(int) $order['column']]) {
                $query->orderBy($field, $order['dir']);
                $ordered = true;
            }
        }
        if (! $ordered) {
            foreach ($filtered->orders ?? [] as $order) {
                if (($order['type'] ?? '') === 'Raw') {
                    $query->orderByRaw($order['sql'], $filtered->getRawBindings()['order'] ?? []);
                } else {
                    $parts = explode('.', $order['column']);
                    $query->orderBy(end($parts), $order['direction']);
                }
            }
        }
        $query->orderBy($key);
        $rows = $query->offset((int) ($input['start'] ?? 0))->limit((int) ($input['length'] ?? 25))->get()
            ->map(function ($row) use ($fields, $transform) {
                return array_intersect_key((array) ($transform ? $transform($row) : $row), array_fill_keys($fields, true));
            });

        return response()->json(['draw' => (int) ($input['draw'] ?? 0), 'recordsTotal' => $total, 'recordsFiltered' => $count, 'data' => $rows]);
    }
}
