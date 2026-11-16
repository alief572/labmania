export function tableQuery(data: object, filters: Record<string, string | number> = {}): URLSearchParams {
    const query = new URLSearchParams();
    function append(key: string, value: unknown) {
        if (value === undefined || value === null) return;
        if (typeof value === 'object') Object.entries(value).forEach(([child, item]) => append(`${key}[${child}]`, item));
        else query.append(key, String(value));
    }
    Object.entries(data).forEach(([key, value]) => append(key, value));
    Object.entries(filters).forEach(([key, value]) => { if (key !== 'q' && value !== '') query.set(key, String(value)); });
    return query;
}

export function tableError(status: number): string {
    if (status === 401 || status === 419) return 'Sesi berakhir. Silakan login kembali.';
    if (status === 403) return 'Tidak memiliki akses untuk melihat data ini.';
    if (status === 422) return 'Filter atau permintaan tabel tidak valid. Periksa isian lalu coba lagi.';
    return 'Data gagal dimuat. Silakan coba lagi.';
}

export function lastTablePage(total: number, length: number): number {
    return Math.max(0, Math.ceil(total / length) - 1);
}
