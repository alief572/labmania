<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { useConfirmedMutation } from "../composables/confirmedMutation";
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';
import PageHead from '../components/PageHead.vue';
import ServerTable from '../components/ServerTable.vue';
import ActionMenu from '../components/ActionMenu.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import type { Menu } from '../types';

type Row = { id_group: number; nm_group: string; n_user: number; n_override: number; n_hak: number; hak: string[]; sistem: boolean; total: number };
type Detail = { menus: Menu[]; selected: string[]; editable: boolean; n_user: number };
defineProps<{ kpi: { roles: number; users: number; menus: number; hak: number }; superadmin: number; can: { manage: boolean } }>();

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const table = ref<InstanceType<typeof ServerTable> | null>(null);
const keyword = ref('');
const columns = [{ title: 'No', orderable: false }, { title: 'Role', orderable: true }, { title: 'User terdaftar', orderable: true }, { title: 'Total hak', orderable: true }, { title: 'Cakupan (preview)', orderable: false }, { title: 'Action', orderable: false }];

// Urutan aksi mengikuti mockup (AK_ACTS).
const actions = ['View', 'Add', 'Manage', 'Delete', 'Approve'];
const editing = ref<Row | null>(null);
const detail = ref<Detail | null>(null);
const loadError = ref('');
const search = ref('');
const form = useForm({ grants: [] as string[] });
const key = (m: Menu, a: string) => `${m.id}:${a}`;
const code = (m: Menu, a: string) => `${m.kode}.${a.toLowerCase()}`;

async function open(row: Row) {
    loadError.value = ''; detail.value = null; search.value = ''; editing.value = row; form.clearErrors();
    try {
        const response = await fetch(appUrl(`/permissions/roles/${row.id_group}`), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Data hak akses gagal dimuat.');
        const json = await response.json();
        detail.value = { menus: json.menus, selected: json.selected, editable: json.editable, n_user: json.n_user };
        form.grants = [...json.selected];
    } catch (failure) { loadError.value = failure instanceof Error ? failure.message : 'Data hak akses gagal dimuat.'; }
}
const close = () => { editing.value = null; detail.value = null; };

const grantMenus = computed(() => (detail.value?.menus ?? []).filter((m) => m.actions.length));
const total = computed(() => grantMenus.value.reduce((n, m) => n + m.actions.length, 0));
const chosen = computed(() => new Set(form.grants));
const locked = computed(() => !detail.value?.editable);
const on = (m: Menu, a: string) => (editing.value?.sistem ?? false) || chosen.value.has(key(m, a));
const count = computed(() => (editing.value?.sistem ? total.value : form.grants.length));
const groups = computed(() => {
    const s = search.value.trim().toLowerCase();
    const out: { modul: string; items: Menu[] }[] = [];
    for (const m of grantMenus.value) {
        if (s && ![m.modul ?? '', m.judul, m.kode, ...m.actions.map((a) => code(m, a))].some((t) => t.toLowerCase().includes(s))) continue;
        let g = out.find((x) => x.modul === (m.modul ?? 'Umum'));
        if (!g) out.push(g = { modul: m.modul ?? 'Umum', items: [] });
        g.items.push(m);
    }
    return out;
});
const set = (values: Set<string>) => { form.grants = [...values]; };
function toggle(m: Menu, a: string, checked: boolean) {
    const v = new Set(form.grants);
    if (checked) { v.add(key(m, a)); if (a !== 'View') v.add(key(m, 'View')); }
    else { v.delete(key(m, a)); if (a === 'View') actions.forEach((x) => v.delete(key(m, x))); }
    set(v);
}
const rowFull = (m: Menu) => m.actions.every((a) => chosen.value.has(key(m, a)));
function toggleRow(m: Menu) {
    const v = new Set(form.grants);
    const full = rowFull(m);
    m.actions.forEach((a) => (full ? v.delete(key(m, a)) : v.add(key(m, a))));
    set(v);
}
const all = (checked: boolean) => set(new Set(checked ? grantMenus.value.flatMap((m) => m.actions.map((a) => key(m, a))) : []));
const save = () => confirmation.form(form, 'put', `/permissions/roles/${editing.value!.id_group}`, {
    title: `Simpan hak akses ${editing.value!.nm_group}?`, text: 'Hak akses role akan diperbarui.', danger: true,
}, { preserveScroll: true, onSuccess: () => { close(); table.value?.reload(); } });
</script>

<template>
    <Head title="Role & Permission" />
    <AppLayout title="Role & Permission" full-width>
        <PageHead title="Role & Permission" />
        <div class="kpis">
            <div class="kpi"><div class="kpi-l">Total Role</div><div class="kpi-v">{{ kpi.roles }}</div><div class="kpi-s">dari Master Role</div></div>
            <div class="kpi"><div class="kpi-l">User berakun</div><div class="kpi-v">{{ kpi.users }}</div><div class="kpi-s">tersebar di semua role</div></div>
            <div class="kpi"><div class="kpi-l">Menu diatur</div><div class="kpi-v">{{ kpi.menus }}</div><div class="kpi-s">{{ kpi.hak }} hak (menu × aksi)</div></div>
        </div>
        <div class="filters"><label class="grow">Cari<input v-model="keyword" type="search" maxlength="100" placeholder="Cari nama role…" /></label></div>
        <ServerTable ref="table" :url="appUrl('/permissions/data')" :columns="columns" :keyword="keyword" label="Role & Permission">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }">
                <template v-if="row.sistem"><b>{{ row.nm_group }}</b> <span class="tag">Full Access</span><small>Role bawaan sistem (akun developer), dibuat dari awal, tidak dapat ditambah lagi</small></template>
                <template v-else><b>{{ row.nm_group }}</b><small>Master Role</small></template>
            </template>
            <template #cell-2="{ row }">{{ row.n_user }} user<small v-if="row.n_override">{{ row.n_override }} dengan override</small></template>
            <template #cell-3="{ row }">
                <span v-if="row.sistem" class="badge on">Semua hak aktif</span>
                <span v-else-if="row.n_hak" class="badge on">{{ row.n_hak }} hak diberikan</span>
                <span v-else class="badge off">Belum diatur</span>
            </template>
            <template #cell-4="{ row }">
                <i v-if="row.sistem" class="muted">Otomatis memiliki semua hak sistem</i>
                <span v-else-if="!row.hak.length" class="muted">—</span>
                <div v-else class="chips"><span v-for="c in row.hak.slice(0, 3)" :key="c" class="chip code">{{ c }}</span><span v-if="row.hak.length > 3" class="chip">+{{ row.hak.length - 3 }} lagi</span></div>
            </template>
            <template #cell-5="{ row }"><ActionMenu><button type="button" :disabled="confirming" @click="open(row)">{{ row.sistem || !can.manage ? 'Lihat' : 'Atur Akses' }}</button></ActionMenu></template>
        </ServerTable>
        <p class="hint-line">Tambah, ubah nama, dan hapus role dilakukan di <a :href="appUrl('/master/role')">Master Role</a>; role baru belum punya hak sampai diatur di sini. <b>Superadmin</b> dibuat dari awal (bawaan sistem) dan tidak dapat ditambah lagi.</p>

        <ConfirmDialog v-if="editing" :title="locked ? `Role ${editing.nm_group}` : `Atur Akses Role ${editing.nm_group}`" confirm-label="Simpan Role" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <p v-if="loadError" class="error" role="alert">{{ loadError }}</p>
            <p v-else-if="!detail" class="muted">Memuat hak akses…</p>
            <template v-else>
                <div class="field"><span class="static-label">Nama Role</span><p class="static">{{ editing.nm_group }}</p><small>Dari Master Role; nama diubah di sana. {{ detail.n_user }} user memakai role ini.</small></div>
                <p v-if="editing.sistem" class="notice"><b>Superadmin</b> (akun developer) — semua akses dan semua data; tidak dapat diubah.</p>
                <p v-else-if="locked" class="notice">Hak akses ini hanya dapat dilihat.</p>
                <p v-if="form.errors.grants" class="error" role="alert">{{ form.errors.grants }}</p>
                <div class="ak-bar">
                    <b>Matrix Hak Akses</b> <span class="tag">{{ count }} dari {{ total }} dipilih</span>
                    <span class="ak-bar-r">
                        <template v-if="!locked"><button type="button" class="btn btn-outline sm" @click="all(true)">Pilih Semua Modul</button><button type="button" class="btn btn-ghost sm" @click="all(false)">Kosongkan</button></template>
                        <input v-model="search" type="search" placeholder="Cari menu / hak…" aria-label="Cari menu atau hak" />
                    </span>
                </div>
                <div class="table-wrap ak-wrap"><table class="ak-tbl">
                    <caption class="sr-only">Matrix hak akses per menu</caption>
                    <thead><tr><th scope="col">Modul / Menu</th><th scope="col">Hak akses / aksi</th><th scope="col" class="ak-c">Per menu</th><th scope="col" class="ak-c">Scope</th></tr></thead>
                    <tbody>
                        <template v-for="g in groups" :key="g.modul">
                            <tr class="ak-mod"><td colspan="4">{{ g.modul }}</td></tr>
                            <tr v-for="m in g.items" :key="m.id">
                                <td><b>{{ m.judul }}</b><small class="code">{{ m.kode }} · {{ m.actions.length }} hak</small></td>
                                <td><div class="ak-chips">
                                    <label v-for="a in m.actions" :key="a" class="ak-chip" :class="{ on: on(m, a) }"><input type="checkbox" :checked="on(m, a)" :disabled="locked" @change="toggle(m, a, ($event.target as HTMLInputElement).checked)" />{{ a }} <span class="code">{{ code(m, a) }}</span></label>
                                </div></td>
                                <td class="ak-c"><button v-if="!locked" type="button" class="btn btn-ghost sm" @click="toggleRow(m)">{{ rowFull(m) ? 'Batal Semua' : 'Pilih Semua' }}</button></td>
                                <td class="ak-c"><span class="ak-scope" :class="{ on: m.actions.some((a) => on(m, a)) }">{{ m.actions.filter((a) => on(m, a)).length }} / {{ m.actions.length }}</span></td>
                            </tr>
                        </template>
                        <tr v-if="!groups.length"><td colspan="4" class="empty">Menu tidak ditemukan.</td></tr>
                    </tbody>
                </table></div>
                <p class="hint-line">Total: <b>{{ count }}</b> hak dipilih. Mencentang Add / Manage / Delete otomatis mencentang View; melepas View melepas semua hak menu itu.</p>
            </template>
        </ConfirmDialog>
    </AppLayout>
</template>
