import type { PageProps } from '@inertiajs/core';

export interface SharedProps extends PageProps {
    auth: { id_user: number; username: string; nm_lengkap: string } | null;
    navigation: { label: string; url: string; menu: string | null; group: string | null; count?: number }[];
    permissions: Record<string, string[]>;
    flash: { success?: string; enroll_token?: string };
}

export interface Menu {
    id: number;
    kode: string;
    judul: string;
    actions: string[];
}
