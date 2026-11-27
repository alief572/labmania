export type Change = { field: string; label: string; old: unknown; new: unknown };

const AKSI: Record<string, string> = { lihat: 'View', tambah: 'Add', ubah: 'Manage', aktif: 'Manage', hapus: 'Delete', approve: 'Approve' };

export const printable = (value: unknown) => value === null || value === undefined ? '—' : typeof value === 'object' ? JSON.stringify(value) : String(value);

/** Untuk kolom daftar (LOG-FR-03: ringkas). Teks lengkap tetap ada di popup Detail. */
export const brief = (value: unknown, max = 120) => {
    const text = printable(value);
    return text.length > max ? `${text.slice(0, max)}…` : text;
};

export function changes(data: Record<string, unknown>, menus: Record<string, string> = {}): Change[] {
    const old = (data.old || {}) as Record<string, unknown>;
    const current = (data.new || {}) as Record<string, unknown>;
    const details = Object.entries((data.detail_changes || {}) as Record<string, { old: unknown; new: unknown }>);
    // Log hak akses menyimpan snapshot `grants` mentah; yang ditampilkan cukup perubahannya per menu.
    const hasGrants = 'grants' in old || 'grants' in current;
    const rows = Object.keys({ ...old, ...current })
        .filter(field => !(field === 'grants' && details.length) && JSON.stringify(old[field]) !== JSON.stringify(current[field]))
        .map(field => ({ field, label: field, old: old[field], new: current[field] }));

    return [...rows, ...details.map(([field, change]) => {
        const grant = hasGrants ? /^(.+) - (\w+)$/.exec(field) : null;
        if (!grant) return { field, label: field, old: change.old, new: change.new };
        const [, code, action] = grant;
        const ya = (value: unknown) => value ? 'Ya' : 'Tidak';
        return { field, label: `${menus[code] ?? code} · ${AKSI[action] ?? action}`, old: ya(change.old), new: ya(change.new) };
    })];
}
