<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';

type Row = { id: number; parent_id: number; kode: string; judul: string; link: string; ikon: string | null; kelompok: number; aktif: number; sistem: number; aksi: string[]; level: number; tipe: 'modul' | 'menu' };
const props = defineProps<{ rows: Row[]; moduls: { id: number; judul: string }[]; icons: string[]; aksi: Record<string, string>; can: { add: boolean; manage: boolean } }>();
const errors = () => usePage().props.errors as Record<string, string>;
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ tipe: 'menu' as 'modul' | 'menu', judul: '', link: '', ikon: '', parent_id: 0, kelompok: 1, aksi: ['lihat'] as string[] });
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { tipe: row.tipe, judul: row.judul, link: row.tipe === 'modul' ? '' : row.link, ikon: row.ikon ?? '', parent_id: row.parent_id, kelompok: row.kelompok, aksi: [...row.aksi] });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/pengaturan/menu/${editing.value.id}`, done);
    else form.post('/pengaturan/menu', done);
}
const toggle = (r: Row) => router.post(`/pengaturan/menu/${r.id}/toggle`, {}, { preserveScroll: true });
const move = (r: Row, dir: 'up' | 'down') => router.post(`/pengaturan/menu/${r.id}/pindah/${dir}`, {}, { preserveScroll: true });
const isMenu = () => form.tipe === 'menu';
</script>

<template>
    <Head title="Menu Sidebar" />
    <AppLayout title="Menu Sidebar">
        <div class="page-head"><h1>Menu Sidebar</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah Menu</button></div>
        <p class="muted">Struktur menu dan aksi yang dapat diberi hak di Role &amp; Permission. Menu sistem tidak dapat dinonaktifkan dan linknya terkunci.</p>
        <p v-if="errors().aktif" class="error" role="alert">{{ errors().aktif }}</p>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar menu sidebar menurut urutan tampil</caption>
            <thead><tr><th scope="col">Menu</th><th scope="col">Link</th><th scope="col">Kelompok</th><th scope="col">Aksi</th><th scope="col">Status</th><th scope="col">Opsi</th></tr></thead>
            <tbody>
                <tr v-for="r in rows" :key="r.id" :class="{ 'is-off': !r.aktif }">
                    <td :style="{ paddingLeft: r.level ? '28px' : undefined }"><b>{{ r.judul }}</b> <span v-if="r.sistem" class="badge">Sistem</span><small>{{ r.kode }}</small></td>
                    <td>{{ r.tipe === 'modul' ? 'Modul' : r.link }}</td>
                    <td>{{ r.tipe === 'modul' ? '—' : r.kelompok }}</td>
                    <td><template v-if="r.tipe === 'menu'"><span v-for="a in r.aksi" :key="a" class="badge">{{ aksi[a] ?? a }}</span></template><template v-else>—</template></td>
                    <td><span class="badge">{{ r.aktif ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td><ActionMenu v-if="can.manage" >
                        <button type="button" @click="open(r)">Edit</button>
                        <button type="button" :aria-label="`Naikkan ${r.judul}`" @click="move(r, 'up')">Naik</button>
                        <button type="button" :aria-label="`Turunkan ${r.judul}`" @click="move(r, 'down')">Turun</button>
                        <button v-if="!r.sistem" type="button" @click="toggle(r)">{{ r.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </ActionMenu></td>
                </tr>
            </tbody>
        </table></div>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Menu ${editing.judul}` : 'Tambah Menu'" :busy="form.processing" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField v-if="adding" id="mtipe" label="Tipe" v-slot="{ describedBy }"><select id="mtipe" v-model="form.tipe" :aria-describedby="describedBy"><option value="menu">Menu</option><option value="modul">Modul (induk)</option></select></FormField>
                <FormField id="mjudul" label="Judul" :error="form.errors.judul" v-slot="{ describedBy, invalid }"><input id="mjudul" v-model="form.judul" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <template v-if="isMenu()">
                    <FormField id="mlink" label="Link" hint="Huruf, angka, _ - dan /; tanpa awalan /." :error="form.errors.link" v-slot="{ describedBy, invalid }"><input id="mlink" v-model="form.link" maxlength="150" required :readonly="!!editing?.sistem" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="mparent" label="Modul induk" :error="form.errors.parent_id" v-slot="{ describedBy, invalid }"><select id="mparent" v-model.number="form.parent_id" :aria-invalid="invalid" :aria-describedby="describedBy"><option :value="0">Tingkat atas</option><option v-for="m in moduls" :key="m.id" :value="m.id">{{ m.judul }}</option></select></FormField>
                    <FormField id="mkel" label="Kelompok" v-slot="{ describedBy }"><input id="mkel" v-model.number="form.kelompok" type="number" min="1" max="99" :aria-describedby="describedBy" /></FormField>
                    <fieldset><legend>Aksi yang berlaku</legend>
                        <label v-for="(label, key) in aksi" :key="key" class="check"><input v-model="form.aksi" type="checkbox" :value="key" :disabled="key === 'lihat'" /> {{ label }}</label>
                        <p class="muted">Aksi yang dihapus mencabut izin terkait dari semua role dan user.</p>
                        <p v-if="form.errors.aksi" class="error" role="alert">{{ form.errors.aksi }}</p>
                    </fieldset>
                </template>
                <FormField v-if="isMenu() ? form.parent_id === 0 : true" id="mikon" label="Ikon" :error="form.errors.ikon" v-slot="{ describedBy, invalid }"><select id="mikon" v-model="form.ikon" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Tanpa ikon</option><option v-for="i in icons" :key="i" :value="i">{{ i }}</option></select></FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
