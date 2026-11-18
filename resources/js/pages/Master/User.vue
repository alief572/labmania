<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import type { Menu } from '../../types';
import PageHead from '../../components/PageHead.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; nama: string; nik: string | null; tmp_lahir: string | null; tgl_lahir: string | null; id_group: number | null; nm_group: string | null; gender: string | null; agama: string | null; telp: string | null; email: string | null; alamat: string | null; npwp: string | null; tgl_masuk: string | null; tipe: string | null; jobdesc: string | null; aktif: number; id_user: number | null; username: string | null; n_override: number };
const props = defineProps<{
    filters: { q: string; role: string | number; status: string }; roles: { id_group: number; nm_group: string }[]; superadmin: number; me: number;
    options: { gender: string[]; agama: string[]; tipe: string[] };
    can: { add: boolean; manage: boolean };
}>();
const filter = ref({ ...props.filters });
watch(() => [filter.value.role, filter.value.status], () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
const blank = () => ({ nama: '', id_group: '' as string | number, nik: '', tmp_lahir: '', tgl_lahir: '', gender: '', agama: '', telp: '', email: '', alamat: '', npwp: '', tgl_masuk: '', tipe: '', jobdesc: '', akun: false, username: '', password: '', password_confirmation: '' });
const form = useForm({ ...blank(), override: undefined as string[] | undefined });
// Override Permission (FR-65): matriks dimuat dari /permissions/users/{id} (JSON) hanya untuk user ber-akun yang sedang diedit.
type Ovr = { menus: Menu[]; selected: string[]; inherited: string[]; editable: boolean };
const ovr = ref<Ovr | null>(null);
const ovrError = ref('');
const ovrOpen = ref(false);
const ACTS = ['View', 'Add', 'Manage', 'Delete', 'Approve'];
const ovrChosen = computed(() => new Set(form.override ?? []));
const ovrInherited = computed(() => new Set(ovr.value?.inherited ?? []));
const ovrMenus = computed(() => (ovr.value?.menus ?? []).filter((m) => m.actions.length));
const ovrGroups = computed(() => {
    const out: { modul: string; items: Menu[] }[] = [];
    for (const m of ovrMenus.value) {
        let g = out.find((x) => x.modul === (m.modul ?? 'Umum'));
        if (!g) out.push(g = { modul: m.modul ?? 'Umum', items: [] });
        g.items.push(m);
    }
    return out;
});
const ovrKey = (m: Menu, a: string) => `${m.id}:${a}`;
const ovrExtra = computed(() => [...ovrChosen.value].filter((k) => !ovrInherited.value.has(k)).length);
function ovrToggle(m: Menu, a: string, checked: boolean) {
    const v = new Set(form.override ?? []);
    if (checked) { v.add(ovrKey(m, a)); if (a !== 'View') v.add(ovrKey(m, 'View')); }
    else { v.delete(ovrKey(m, a)); if (a === 'View') ACTS.forEach((x) => v.delete(ovrKey(m, x))); }
    form.override = [...v];
}
async function loadOverride(row: Row) {
    ovr.value = null; ovrError.value = ''; ovrOpen.value = false; form.override = undefined;
    if (!row.id_user || row.id_group === props.superadmin || !props.can.manage) return;
    try {
        const response = await fetch(appUrl(`/permissions/users/${row.id_user}`), { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) throw new Error('Override Permission gagal dimuat.');
        const json = await response.json();
        ovr.value = { menus: json.menus, selected: json.selected, inherited: json.inherited, editable: json.editable };
        // Hak dari role tercentang dan terkunci; yang dikirim hanya himpunan lengkap yang dilihat user.
        form.override = [...new Set([...json.selected, ...json.inherited])];
        ovrOpen.value = (json.selected as string[]).some((k) => !(json.inherited as string[]).includes(k));
    } catch (failure) { ovrError.value = failure instanceof Error ? failure.message : 'Override Permission gagal dimuat.'; }
}
const mode = ref<'add' | 'edit' | null>(null);
const current = ref<Row | null>(null);
const str = (v: unknown) => (v === null || v === undefined ? '' : String(v));
const dateOnly = (v: unknown) => str(v).slice(0, 10);
function openAdd() { form.reset(); form.clearErrors(); Object.assign(form, blank()); current.value = null; mode.value = 'add'; ovr.value = null; ovrError.value = ''; form.override = undefined; }
function openEdit(row: Row) {
    form.clearErrors(); current.value = row; mode.value = 'edit'; loadOverride(row);
    Object.assign(form, { ...blank(), nama: row.nama, id_group: row.id_group ?? '', nik: str(row.nik), tmp_lahir: str(row.tmp_lahir), tgl_lahir: dateOnly(row.tgl_lahir), gender: str(row.gender), agama: str(row.agama), telp: str(row.telp), email: str(row.email), alamat: str(row.alamat), npwp: str(row.npwp), tgl_masuk: dateOnly(row.tgl_masuk), tipe: str(row.tipe), jobdesc: str(row.jobdesc), akun: !!row.id_user, username: str(row.username) });
}
const close = () => { mode.value = null; };
function save() {
    return confirmation.form(form, mode.value === 'edit' ? 'put' : 'post', mode.value === 'edit' ? `/master/user/${current.value!.id}` : '/master/user', { title: `${mode.value === 'edit' ? 'Simpan perubahan' : 'Tambahkan'} ${form.nama}${mode.value === 'edit' ? ` (${current.value!.kode})` : ''}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/user/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.nama} (${row.kode})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });
const resetting = ref<Row | null>(null);
const resetForm = useForm({ password: '', password_confirmation: '' });
const doReset = () => confirmation.form(resetForm, 'post', `/master/user/${resetting.value!.id}/reset-password`, {
    title: `Ganti password ${resetting.value!.username} (${resetting.value!.kode})?`,
    text: 'Password akun akan diganti.', confirmLabel: 'Ya, ganti password', danger: true,
}, { preserveScroll: true, onSuccess: () => { resetting.value = null; resetForm.reset(); reload(); } });
const locked = () => current.value?.id_group === props.superadmin;
const err = (k: string) => (form.errors as Record<string, string>)[k];

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Employee ID', orderable: true }, { title: 'Nama', orderable: true }, { title: 'Role', orderable: true }, { title: 'Akun login', orderable: true }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Master User" />
    <AppLayout title="Master User" full-width>
        <PageHead title="Master User" description="Data karyawan Labmania dan akun login ke sistem. Role dipilih dari Master Role; hak aksesnya diatur di Role & Permission. Override Permission memberi satu user hak menu tambahan di luar role-nya. User nonaktif tidak dapat login; data tidak dihapus."><button v-if="can.add" type="button" class="primary" @click="openAdd" :disabled="confirming">+ Tambah User</button></PageHead>
        <p v-if="($page.props.errors as Record<string, string>).status" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).status }}</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari ID, nama, username, email, atau role…" /></label>
            <label>Role<select v-model="filter.role"><option value="">Semua role</option><option v-for="r in roles" :key="r.id_group" :value="r.id_group">{{ r.nm_group }}</option></select></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
        </div>
        <ServerTable ref="table" :url="appUrl('/master/user/data')" :columns="columns" :filters="appliedFilters" :keyword="filter.q" :label="'User'">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }"><span class="code">{{ row.kode }}</span></template>
            <template #cell-2="{ row }">{{ row.nama }}<small v-if="row.email || row.telp">{{ row.email || row.telp }}</small></template>
            <template #cell-3="{ row }">{{ row.nm_group ?? '—' }}<small v-if="row.tipe">{{ row.tipe }}</small></template>
            <template #cell-4="{ row }"><span v-if="row.username" class="code">{{ row.username }}</span><span v-else class="muted">Tanpa akun</span><small v-if="row.n_override" title="Hak menu tambahan di luar role">Override {{ row.n_override }} hak</small></template>
            <template #cell-5="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-6="{ row }"><ActionMenu v-if="can.manage" >
                        <button type="button" @click="openEdit(row)" :disabled="confirming">Edit</button>
                        <button v-if="row.id_user && row.id_user !== me" type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        <button v-else-if="!row.id_user" type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        <button v-if="row.id_user" type="button" @click="resetting = row" :disabled="confirming">Reset Password</button>
                        <Link v-if="row.id_user && row.id_group !== superadmin && row.id_user !== me" :href="appUrl(`/permissions/users/${row.id_user}`)">Override</Link>
                    </ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="mode" :title="mode === 'edit' ? `Edit User ${current?.kode}` : 'Add New User'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="grid2">
                <div class="stack">
                    <FormField id="ukode" label="Employee ID" hint="Dibuat otomatis oleh sistem; tidak dapat diubah." :error="undefined" v-slot="{ describedBy, invalid }"><input id="ukode" :value="current?.kode ?? ''" placeholder="Dibuat otomatis saat disimpan" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                    <FormField id="unama" label="Employee Name" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="unama" v-model="form.nama" maxlength="100" placeholder="Employee Name" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="unik" label="Personal ID Number" :error="form.errors.nik" v-slot="{ describedBy, invalid }"><input id="unik" v-model="form.nik" maxlength="16" inputmode="numeric" placeholder="NIK" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="utl" label="Birth Place" :error="form.errors.tmp_lahir" v-slot="{ describedBy, invalid }"><input id="utl" v-model="form.tmp_lahir" maxlength="50" placeholder="Birth Place" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="utg" label="Birth Date" :error="form.errors.tgl_lahir" v-slot="{ describedBy, invalid }"><input id="utg" v-model="form.tgl_lahir" type="date" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="urole" label="Role" hint="Dari Master Role; hak akses diatur di Role &amp; Permission" :error="form.errors.id_group" v-slot="{ describedBy, invalid }"><select id="urole" v-model="form.id_group" required :disabled="locked()" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Choose one</option><option v-for="r in roles.filter((x) => x.id_group !== superadmin || locked())" :key="r.id_group" :value="r.id_group">{{ r.nm_group }}</option></select></FormField>
                    <FormField id="ugen" label="Gender" :error="form.errors.gender" v-slot="{ describedBy, invalid }"><select id="ugen" v-model="form.gender"  :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Choose one</option><option v-for="g in options.gender" :key="g" :value="g">{{ g }}</option></select></FormField>
                    <FormField id="uag" label="Religion" :error="form.errors.agama" v-slot="{ describedBy, invalid }"><select id="uag" v-model="form.agama"  :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Choose one</option><option v-for="g in options.agama" :key="g" :value="g">{{ g }}</option></select></FormField>
                </div>
                <div class="stack">
                    <FormField id="utel" label="Phone Number" :error="form.errors.telp" v-slot="{ describedBy, invalid }"><input id="utel" v-model="form.telp" inputmode="tel" placeholder="+62..." :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="umail" label="Email" :error="form.errors.email" v-slot="{ describedBy, invalid }"><input id="umail" v-model="form.email" type="email" placeholder="email@domain.com" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="ualm" label="Address" :error="form.errors.alamat" v-slot="{ describedBy, invalid }"><textarea id="ualm" v-model="form.alamat" rows="2" maxlength="255" placeholder="Address" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                    <FormField id="unp" label="NPWP" :error="form.errors.npwp" v-slot="{ describedBy, invalid }"><input id="unp" v-model="form.npwp" maxlength="25" inputmode="numeric" placeholder="NPWP Number" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="ujd" label="Join Date" :error="form.errors.tgl_masuk" v-slot="{ describedBy, invalid }"><input id="ujd" v-model="form.tgl_masuk" type="date" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="uti" label="Employee Type" :error="form.errors.tipe" v-slot="{ describedBy, invalid }"><select id="uti" v-model="form.tipe"  :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Choose one</option><option v-for="g in options.tipe" :key="g" :value="g">{{ g }}</option></select></FormField>
                    <FormField id="ujob" label="Job Desc." :error="form.errors.jobdesc" v-slot="{ describedBy, invalid }"><textarea id="ujob" v-model="form.jobdesc" rows="2" maxlength="500" placeholder="Job Description" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                    <div class="field"><span class="static-label">Status</span><p class="static" id="ustatus">{{ !current || current.aktif ? 'Active' : 'Non Active' }}</p><small>Ubah lewat menu Aktifkan / Nonaktifkan di kolom Action.</small></div>
                </div>
            </div>
            <fieldset class="stack"><legend>Akun Login <span class="muted">— tambahan di luar form acuan</span></legend>
                <label><input v-model="form.akun" type="checkbox" :disabled="!!current?.id_user" /> User ini memiliki akun login</label>
                <template v-if="form.akun">
                    <FormField id="uusr" label="Username" hint="Huruf kecil, angka, titik, garis bawah; 3–45 karakter." :error="form.errors.username" v-slot="{ describedBy, invalid }"><input id="uusr" v-model="form.username" maxlength="45" autocomplete="off" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="upw" :label="current?.id_user ? 'Password baru (kosongkan bila tidak diganti)' : 'Password'" hint="Minimal 8 karakter." :error="form.errors.password" v-slot="{ describedBy, invalid }"><input id="upw" v-model="form.password" type="password" autocomplete="new-password" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="upw2" label="Konfirmasi password" :error="err('password_confirmation')" v-slot="{ describedBy, invalid }"><input id="upw2" v-model="form.password_confirmation" type="password" autocomplete="new-password" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                </template>
                <p v-if="ovrError" class="error" role="alert">{{ ovrError }}</p>
                <details v-if="ovr" class="ovr-box" :open="ovrOpen" @toggle="ovrOpen = ($event.target as HTMLDetailsElement).open">
                    <summary><b>Override Permission</b> <span class="muted">{{ ovrExtra ? `${ovrExtra} hak tambahan` : 'Belum ada hak tambahan' }}</span></summary>
                    <div class="ovr-body">
                        <p class="hint-line top">Hak menu tambahan khusus untuk user ini, di luar hak role-nya. Hak yang sudah dimiliki role tercentang dan terkunci. Override hanya menambah hak; mencentang Add, Manage, atau Delete otomatis mencentang View.</p>
                        <p v-if="form.errors.override" class="error" role="alert">{{ form.errors.override }}</p>
                        <div class="table-wrap ak-wrap"><table class="ak-tbl"><caption class="sr-only">Hak tambahan per menu</caption>
                            <thead><tr><th scope="col">Modul / Menu</th><th scope="col">Hak akses</th></tr></thead>
                            <tbody><template v-for="g in ovrGroups" :key="g.modul"><tr class="ak-mod"><td colspan="2">{{ g.modul }}</td></tr>
                                <tr v-for="m in g.items" :key="m.id"><th scope="row">{{ m.judul }}<small class="code">{{ m.kode }}</small></th>
                                    <td><div class="ak-chips"><label v-for="a in ACTS.filter((x) => m.actions.includes(x))" :key="a" class="ak-chip" :class="{ on: ovrChosen.has(ovrKey(m, a)), role: ovrInherited.has(ovrKey(m, a)) }"><input type="checkbox" :aria-label="`${m.judul}: ${a}`" :checked="ovrChosen.has(ovrKey(m, a))" :disabled="!ovr.editable || ovrInherited.has(ovrKey(m, a))" @change="ovrToggle(m, a, ($event.target as HTMLInputElement).checked)" />{{ a }}<span v-if="ovrInherited.has(ovrKey(m, a))" class="code">role</span></label></div></td></tr></template></tbody></table></div>
                    </div>
                </details>
            </fieldset>
        </ConfirmDialog>

        <ConfirmDialog v-if="resetting" :title="`Reset password ${resetting.username}`" :busy="resetForm.processing || confirming" confirm-label="Ganti password" @confirm="doReset" @cancel="resetting = null">
            <div class="stack">
                <FormField id="rp" label="Password baru" hint="Minimal 8 karakter." :error="resetForm.errors.password" v-slot="{ describedBy, invalid }"><input id="rp" v-model="resetForm.password" type="password" autocomplete="new-password" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="rp2" label="Konfirmasi password" v-slot="{ describedBy }"><input id="rp2" v-model="resetForm.password_confirmation" type="password" autocomplete="new-password" :aria-describedby="describedBy" /></FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
