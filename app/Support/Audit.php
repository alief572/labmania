<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\NumberSyntax;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LogicException;

class Audit
{
    public static function publicSurvey(int $program, string $number, string $name, int $answer): void
    {
        Cache::forget('logs.filters');
        DB::table('logs')->insert(['module' => 'Manage Peserta', 'sub_module' => 'Survey', 'action' => 'Isi Survey', 'description' => 'Survey '.$number,
            'ref_table' => 'penjualan_program', 'ref_id' => $program, 'ref_no' => $number,
            'data' => json_encode(['actor_type' => 'publik', 'peserta' => $name, 'id_jawaban' => $answer], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_by' => null, 'created_date' => now(), 'ip_address' => request()->ip()]);
    }

    private const METADATA = ['created_at', 'created_date', 'created_by', 'updated_at', 'updated_date', 'updated_by', 'deleted_at', 'deleted_date', 'deleted_by'];

    public static function normalize(array $data): array
    {
        if (isset($data['old'], $data['new']) && is_array($data['old']) && is_array($data['new'])) {
            foreach (array_unique([...array_keys($data['old']), ...array_keys($data['new'])]) as $field) {
                if (in_array(strtolower((string) $field), self::METADATA, true)
                    || self::equal($data['old'][$field] ?? null, $data['new'][$field] ?? null, (string) $field)) {
                    unset($data['old'][$field], $data['new'][$field]);
                }
            }
        }
        foreach ($data['detail_changes'] ?? [] as $field => $change) {
            if (is_array($change) && (in_array(strtolower((string) $field), self::METADATA, true)
                || self::equal($change['old'] ?? null, $change['new'] ?? null, (string) $field))) {
                unset($data['detail_changes'][$field]);
            }
        }

        return $data;
    }

    private static function equal(mixed $old, mixed $new, string $field): bool
    {
        if ($old === $new) {
            return true;
        }
        if (preg_match('/diskon|harga|total|qty|ppn|kurs|cost|biaya|profit|target|tarif/i', $field)) {
            $old = $old === null || $old === '' ? 0 : $old;
            $new = $new === null || $new === '' ? 0 : $new;
        }
        // ponytail: hanya angka polos tanpa leading zero dianggap nominal; kode seperti 007 atau 0812345 dibandingkan sebagai string. Notasi ilmiah tidak lagi dianggap sama.
        if (self::plainNumber($old) && self::plainNumber($new)) {
            try {
                // ponytail: 256 digits cover MySQL DECIMAL; raise only with a schema change.
                return BigDecimal::parse(trim((string) $old), NumberSyntax::SCIENTIFIC, 256)
                    ->minus(BigDecimal::parse(trim((string) $new), NumberSyntax::SCIENTIFIC, 256))->abs()->isLessThan('0.0001');
            } catch (NumberFormatException) {
                return false;
            }
        }

        return false;
    }

    private static function plainNumber(mixed $value): bool
    {
        return (is_int($value) || is_float($value) || is_string($value))
            && preg_match('/^[+-]?(0|[1-9]\d*)(\.\d+)?$/', trim((string) $value)) === 1;
    }

    public static function redact(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $item) {
            $value[$key] = preg_match('/password|secret|token|recovery|authorization/i', (string) $key)
                ? '[REDACTED]' : self::redact($item);
        }

        return $value;
    }

    public static function write($module, $action, $description, $data, $table, $id, $number): int
    {
        if (! Auth::id()) {
            throw new LogicException('Audit internal membutuhkan aktor autentikasi.');
        }

        Cache::forget('logs.filters');

        return DB::table('logs')->insertGetId([
            'module' => $module, 'sub_module' => is_array($data) ? ($data['sub_module'] ?? null) : null,
            'action' => $action, 'description' => $description,
            'ref_table' => $table, 'ref_id' => $id, 'ref_no' => $number,
            'data' => $data === null ? null : json_encode(self::redact(is_array($data) ? self::normalize($data) : $data), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_by' => Auth::id(), 'created_date' => now(), 'ip_address' => request()->ip(),
        ]);
    }

    /** Khusus isian link: actor_type ditetapkan server, tidak diambil dari request atau memakai identitas admin. */
    public static function publicConfirmation(string $action, string $description, array $data, int $id, string $number): int
    {
        Cache::forget('logs.filters');

        return DB::table('logs')->insertGetId([
            'module' => 'Quotation', 'sub_module' => 'Data Peserta', 'action' => $action, 'description' => $description,
            'ref_table' => 'quotation', 'ref_id' => $id, 'ref_no' => $number,
            'data' => json_encode(self::redact(['actor_type' => 'publik'] + $data), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'created_by' => null, 'created_date' => now(), 'ip_address' => request()->ip(),
        ]);
    }
}
