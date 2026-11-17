import { appUrl } from './appUrl';
export async function postPdf(url: string, data: object = {}): Promise<void> {
    const tab = window.open('about:blank', '_blank');
    if (tab) tab.opener = null;
    try {
        const csrf = document.cookie.split('; ').find((v) => v.startsWith('XSRF-TOKEN='))?.slice(11);
        const response = await fetch(appUrl(url), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(csrf ?? '') },
            body: JSON.stringify(data),
        });
        if (!response.ok) {
            const result = await response.json().catch(() => null);
            throw new Error(result?.errors ? Object.values(result.errors).flat().join('\n') : (result?.message || `Permintaan gagal (${response.status}).`));
        }
        if (!response.headers.get('Content-Type')?.includes('application/pdf')) throw new Error('Sesi berakhir. Muat ulang halaman dan masuk kembali.');
        const href = URL.createObjectURL(await response.blob());
        if (tab) tab.location.href = href;
        else { const a = document.createElement('a'); a.href = href; a.download = 'Labmania.pdf'; a.click(); }
        setTimeout(() => URL.revokeObjectURL(href), 60_000);
    } catch (e) { tab?.close(); throw e; }
}
