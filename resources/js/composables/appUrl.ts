/** Prefix subpath deployment (mis. /labmania), dibaca dari <meta name="app-base"> yang diisi server. */
export function appBase(): string {
    if (typeof document === 'undefined') return '';
    return (document.querySelector('meta[name="app-base"]')?.getAttribute('content') ?? '').replace(/\/+$/, '');
}

/** Tambah prefix ke path root-absolut (/x). Idempoten; URL absolut, //host, #hash dan relatif dibiarkan. */
export function appUrl(path: string, base: string = appBase()): string {
    if (!base || !path.startsWith('/') || path.startsWith('//')) return path;
    if (path === base || path.startsWith(base + '/') || path.startsWith(base + '?')) return path;
    return base + path;
}

/** Kebalikan appUrl: buang prefix agar bisa dibandingkan dengan URL navigasi server. */
export function stripBase(path: string, base: string = appBase()): string {
    if (!base) return path;
    if (path === base) return '/';
    if (path.startsWith(base + '?')) return '/' + path.slice(base.length);
    return path.startsWith(base + '/') ? path.slice(base.length) : path;
}
