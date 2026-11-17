import { appUrl } from './appUrl';
import { onScopeDispose, ref } from 'vue';
import { router, type InertiaForm } from '@inertiajs/vue3';
import type { Method, RequestPayload, UseFormSubmitOptions } from '@inertiajs/core';
import type { SweetAlertOptions } from 'sweetalert2';
import Swal from 'sweetalert2/dist/sweetalert2.js';
import 'sweetalert2/dist/sweetalert2.css';

type Confirmation = { title: string; text?: string; confirmLabel?: string; danger?: boolean; successText?: string };
type WriteMethod = Exclude<Method, 'get'>;

// Clone reactive form arrays/objects, retaining immutable File/Blob attachments.
function snapshot<T>(value: T): T {
    if (Array.isArray(value)) return value.map(snapshot) as T;
    if (value instanceof Date) return new Date(value) as T;
    if (value && Object.getPrototypeOf(value) === Object.prototype) {
        return Object.fromEntries(Object.entries(value).map(([key, item]) => [key, snapshot(item)])) as T;
    }
    return value;
}

export function useConfirmedMutation() {
    const pending = ref(false);
    let disposed = false;
    let asking = false;

    function feedback(icon: 'success' | 'error', text: string) {
        void Swal.fire({ icon, titleText: icon === 'success' ? 'Berhasil' : 'Proses gagal', text,
            confirmButtonText: 'Tutup', confirmButtonColor: '#14304F', heightAuto: false, returnFocus: true });
    }

    async function run(confirmation: Confirmation, send: (finish: () => void) => void | Promise<void>) {
        if (pending.value || disposed) return false;
        pending.value = true;
        try {
            if (typeof document !== 'undefined') document.querySelectorAll<HTMLElement>('[popover]:popover-open').forEach(el => el.hidePopover());
            asking = true;
            const options: SweetAlertOptions = {
                titleText: confirmation.title, text: confirmation.text,
                icon: confirmation.danger ? 'warning' : 'question', showCancelButton: true,
                confirmButtonText: confirmation.confirmLabel || 'Ya, simpan', cancelButtonText: 'Batal',
                confirmButtonColor: confirmation.danger ? '#9C2636' : '#14304F', cancelButtonColor: '#6E7390',
                reverseButtons: true, focusCancel: true, returnFocus: true, heightAuto: false,
                allowOutsideClick: false, allowEscapeKey: true, customClass: { popup: 'master-confirmation' },
            };
            const result = await Swal.fire(options);
            asking = false;
            if (!result.isConfirmed || disposed) { pending.value = false; return false; }
            await send(() => { pending.value = false; });
            return true;
        } catch (error) {
            asking = false;
            pending.value = false;
            feedback('error', error instanceof Error ? error.message : 'Permintaan tidak dapat diproses. Coba lagi.');
            return false;
        }
    }

    function callbacks(confirmation: Confirmation, options: UseFormSubmitOptions, finish: () => void): UseFormSubmitOptions {
        return { ...options,
            onSuccess: page => {
                const flash = page.props.flash as { success?: string } | undefined;
                feedback('success', flash?.success || confirmation.successText || 'Perubahan berhasil disimpan.');
                return options.onSuccess?.(page);
            },
            onError: errors => {
                feedback('error', Object.values(errors).flat().join('\n') || 'Periksa isian dan coba lagi.');
                return options.onError?.(errors);
            },
            onHttpException: response => {
                feedback('error', [401, 419].includes(response.status) ? 'Sesi berakhir. Muat ulang halaman dan masuk kembali.'
                    : response.status === 403 ? 'Anda tidak memiliki akses untuk proses ini.' : 'Permintaan gagal. Muat ulang data sebelum mencoba kembali.');
                options.onHttpException?.(response);
                return false;
            },
            onNetworkError: error => {
                feedback('error', 'Koneksi terputus. Periksa data terbaru sebelum mencoba kembali.');
                options.onNetworkError?.(error);
                return false;
            },
            onFinish: visit => { try { options.onFinish?.(visit); } finally { finish(); } },
        };
    }

    function form<T extends object>(target: InertiaForm<T>, method: WriteMethod, url: string, confirmation: Confirmation, options: UseFormSubmitOptions = {}) {
        if (target.processing) return Promise.resolve(false);
        const data = snapshot(target.data());
        return run(confirmation, finish => {
            target.transform(() => data);
            try { target.submit(method, appUrl(url), callbacks(confirmation, options, finish)); }
            finally { target.transform(value => value); }
        });
    }

    function visit(method: WriteMethod, url: string, data: RequestPayload, confirmation: Confirmation, options: UseFormSubmitOptions = {}) {
        const payload = snapshot(data);
        return run(confirmation, finish => router.visit(appUrl(url), { ...callbacks(confirmation, options, finish), method, data: payload }));
    }

    function request<T>(data: T, confirmation: Confirmation, send: (payload: T) => Promise<void>, options: { onSuccess?: () => void; onError?: (message: string) => void } = {}) {
        const payload = snapshot(data);
        return run(confirmation, async finish => {
            let saved = false;
            try {
                await send(payload);
                saved = true;
                if (disposed) return;
                options.onSuccess?.();
                if (!disposed) feedback('success', confirmation.successText || 'Perubahan berhasil disimpan.');
            } catch (error) {
                if (disposed) return;
                const message = saved ? 'Perubahan tersimpan, tetapi pembaruan halaman gagal. Muat ulang halaman untuk melihat hasilnya.'
                    : error instanceof Error ? error.message : 'Permintaan gagal. Coba lagi.';
                feedback('error', message);
                options.onError?.(message);
            } finally { finish(); }
        });
    }

    onScopeDispose(() => { disposed = true; if (asking) Swal.close(); pending.value = false; });
    return { pending, form, visit, request };
}
