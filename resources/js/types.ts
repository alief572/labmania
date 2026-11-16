import type { PageProps } from '@inertiajs/core';

export interface SharedProps extends PageProps {
    auth: { id_user: number; username: string; nm_lengkap: string } | null;
    navigation: { label: string; url: string; menu: string; group: string | null; sectionKey: string; subgroupKey: string; subgroupLabel: string; icon: string | null; count?: number }[];
    permissions: Record<string, string[]>;
    flash: { success?: string };
}

export interface Menu {
    modul?: string;
    id: number;
    kode: string;
    judul: string;
    actions: string[];
}
