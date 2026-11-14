<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';

type Item = { id: number; nomor: string; judul: string; nama_produk: string | null; nama_varian: string | null; nama_sertifikasi: string | null; lingkup: string[] };
type Group = { id: number; nama: string; items: Item[] };
type Opt = { id: number; nama: string; n: number };
const props = defineProps<{ groups: Group[]; filters: { bidang: string | number; sert: string | number; q: string }; options: { bidang: Opt[]; sert: Opt[] }; can: { edit: boolean; print: boolean }; lingkupMax: { max_items: number; max_length: number } }>();
const filter = ref({ ...props.filters });
const query = () => Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '' && v !== null));
const apply = () => router.get('/master/katalog', query(), { preserveScroll: true });
const pdfUrl = () => `/master/katalog/pdf?${new URLSearchParams(query() as Record<string, string>).toString()}`;
const editing = ref<Item | null>(null);
const form = useForm({ text: '' });
function open(item: Item) { editing.value = item; form.clearErrors(); form.text = item.lingkup.join('\n'); }
const save = () => router.put(`/master/product/${editing.value!.id}/lingkup`, { items: form.text.split(/\r?\n/) }, { preserveScroll: true, onSuccess: () => { editing.value = null; }, onError: (e) => form.setError('text', e.lingkup) });
</script>

<template>
    <Head title="Katalog Produk" />
    <AppLayout title="Katalog Produk">
        <div class="page-head"><h1>Katalog Produk</h1><a v-if="can.print" class="primary" :href="pdfUrl()" target="_blank" rel="noopener">Cetak Katalog (PDF)</a></div>
        <p v-if="($page.props.errors as Record<string, string>).pdf" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).pdf }}</p>
        <form class="filters" @submit.prevent="apply">
            <label>Bidang<select v-model="filter.bidang"><option value="">Semua</option><option v-for="o in options.bidang" :key="o.id" :value="o.id">{{ o.nama }} ({{ o.n }})</option></select></label>
            <label>Sertifikat<select v-model="filter.sert"><option value="">Semua</option><option v-for="o in options.sert" :key="o.id" :value="o.id">{{ o.nama }} ({{ o.n }})</option></select></label>
            <label>Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Nomor, judul, atau lingkup" /></label>
            <button class="primary">Terapkan</button>
        </form>
        <p v-if="!groups.length" class="empty">Tidak ada Product aktif sesuai filter.</p>
        <section v-for="group in groups" :key="group.id" class="stack" :aria-label="group.nama">
            <h2>{{ group.nama }}</h2>
            <div class="card-grid">
                <article v-for="item in group.items" :key="item.id" class="card">
                    <span class="badge warn">{{ item.nama_produk }}</span>
                    <h3>{{ item.judul }}</h3><small>{{ item.nomor }} · {{ item.nama_varian ?? 'Varian belum diisi' }} · {{ item.nama_sertifikasi }}</small>
                    <ol v-if="item.lingkup.length"><li v-for="l in item.lingkup" :key="l">{{ l }}</li></ol>
                    <p v-else class="muted">Lingkup belum diisi.</p>
                    <button v-if="can.edit" type="button" @click="open(item)">{{ item.lingkup.length ? `Ubah lingkup (${item.lingkup.length})` : 'Isi lingkup' }}</button>
                </article>
            </div>
        </section>
        <ConfirmDialog v-if="editing" :title="`Lingkup ${editing.nomor}`" :busy="form.processing" @confirm="save" @cancel="editing = null">
            <FormField id="kl" label="Satu item per baris" :hint="`Maksimal ${lingkupMax.max_items} item, ${lingkupMax.max_length} karakter per item.`" :error="form.errors.text" v-slot="{ describedBy, invalid }">
                <textarea id="kl" v-model="form.text" rows="8" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea>
            </FormField>
        </ConfirmDialog>
    </AppLayout>
</template>
