<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import PageHead from '../../components/PageHead.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import MoneyInput from '../../components/MoneyInput.vue';
import { rupiah } from '../../composables/costing';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Item = { id: number; nomor: string; judul: string; nama_produk: string | null; nama_varian: string | null; nama_sertifikasi: string | null; lingkup: string[]; harga_mulai: string | number | null };
type Group = { id: number; nama: string; items: Item[] };
type Opt = { id: number; nama: string; n: number };
const props = defineProps<{ groups: Group[]; filters: { bidang: string | number; sert: string | number; q: string }; options: { bidang: Opt[]; sert: Opt[] }; can: { edit: boolean; print: boolean }; lingkupMax: { max_items: number; max_length: number } }>();

// Filter langsung berlaku (mockup tanpa tombol Terapkan): pilihan segera, kata kunci ditunda 300 ms.
const filter = ref({ ...props.filters });
const query = () => Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '' && v !== null));
const apply = () => router.get(appUrl('/master/katalog'), query(), { preserveScroll: true, preserveState: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
const flush = () => { clearTimeout(timer); apply(); };
watch(() => [filter.value.bidang, filter.value.sert], flush);
watch(() => filter.value.q, () => { clearTimeout(timer); timer = setTimeout(apply, 300); });
onBeforeUnmount(() => clearTimeout(timer));
const pdfUrl = () => appUrl(`/master/katalog/pdf?${new URLSearchParams(query() as Record<string, string>).toString()}`);

const total = computed(() => props.groups.reduce((n, g) => n + g.items.length, 0));
const base = computed(() => props.options.bidang.reduce((n, o) => n + o.n, 0));
const nameOf = (list: Opt[], id: string | number) => list.find(o => String(o.id) === String(id))?.nama ?? '—';
const filterText = computed(() => `Bidang: ${filter.value.bidang === '' ? 'Semua' : nameOf(props.options.bidang, filter.value.bidang)} · Sertifikat: ${filter.value.sert === '' ? 'Semua' : nameOf(props.options.sert, filter.value.sert)}${filter.value.q ? ` · Kata kunci: "${filter.value.q}"` : ''}`);

// Lingkup: satu baris per item, Enter menambah baris (mockup ktLingkup).
const editing = ref<{ item: Item; bidang: string } | null>(null);
const form = useForm({ items: [''] as string[], harga_mulai: '' as string | number });
const max = computed(() => props.lingkupMax.max_items);
function open(item: Item, group: Group) {
    editing.value = { item, bidang: group.nama };
    form.clearErrors();
    form.items = item.lingkup.length ? [...item.lingkup] : [''];
    form.harga_mulai = item.harga_mulai === null || item.harga_mulai === undefined ? '' : String(Math.round(Number(item.harga_mulai)));
}
const focusRow = async (i: number) => { await nextTick(); (document.getElementById(`lk-${i}`) ?? document.getElementById('lk-add'))?.focus(); };
function addRow(after = form.items.length - 1) {
    if (form.items.length >= max.value) return;
    form.items.splice(after + 1, 0, '');
    focusRow(after + 1);
}
function delRow(i: number) {
    form.items.splice(i, 1);
    if (!form.items.length) form.items.push('');
    focusRow(Math.min(i, form.items.length - 1));
}
const save = () => confirmation.visit('put', `/master/product/${editing.value!.item.id}/lingkup`, { items: form.items, harga_mulai: String(form.harga_mulai ?? '') }, {
    title: `Simpan lingkup dan harga ${editing.value!.item.judul} (${editing.value!.item.nomor})?`,
}, { preserveScroll: true, onSuccess: () => { editing.value = null; }, onError: (e) => { form.setError('items', e.lingkup); form.setError('harga_mulai', e.harga_mulai); } });
</script>

<template>
    <Head title="Katalog Produk" />
    <AppLayout title="Katalog Produk" full-width>
        <PageHead title="Katalog Produk">
            <a v-if="can.print" class="primary" :href="pdfUrl()" target="_blank" rel="noopener">Cetak Katalog (PDF)</a>
        </PageHead>
        <p v-if="($page.props.errors as Record<string, string>).pdf" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).pdf }}</p>

        <div class="kt-box">
            <div class="kt-toolbar">
                <input v-model="filter.q" type="search" maxlength="100" placeholder="Cari judul, nomor, atau atribut…" aria-label="Cari katalog" @keydown.enter.prevent="flush" />
                <select v-model="filter.bidang" aria-label="Filter bidang"><option value="">Semua bidang</option><option v-for="o in options.bidang" :key="o.id" :value="o.id">{{ o.nama }} ({{ o.n }})</option></select>
                <select v-model="filter.sert" aria-label="Filter sertifikat"><option value="">Semua sertifikat</option><option v-for="o in options.sert" :key="o.id" :value="o.id">{{ o.nama }} ({{ o.n }})</option></select>
            </div>

            <div v-if="!total" class="kt-empty">
                <h2>Tidak ada paket produk</h2>
                <p>{{ base ? 'Tidak ada Paket Produk aktif yang cocok dengan filter. Ubah filter Bidang/Sertifikat atau kata kunci.' : 'Belum ada Paket Produk berstatus Aktif. Tambahkan atau aktifkan paket di menu Product.' }}</p>
            </div>
            <template v-else>
                <div class="kt-sum"><span><b>{{ total }}</b> paket produk · <b>{{ groups.length }}</b> bidang</span><span class="muted">{{ filterText }}</span></div>
                <section v-for="group in groups" :key="group.id" class="kt-group" :aria-label="group.nama">
                    <h3>{{ group.nama }}<span>{{ group.items.length }} paket</span></h3>
                    <div class="kt-grid">
                        <article v-for="item in group.items" :key="item.id" class="kt-card">
                            <div class="kt-top"><span class="kt-tag">{{ item.nama_produk }}</span><span class="kt-code">{{ item.nomor }}</span></div>
                            <h4>{{ item.judul }}</h4>
                            <p class="kt-var"><span>Varian</span> {{ item.nama_varian ?? '—' }}</p>
                            <div class="kt-cert"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="9" r="6" /><path d="m8.5 13.5-1.5 8 5-3 5 3-1.5-8" /></svg><span>{{ item.nama_sertifikasi }}</span></div>
                            <p v-if="item.harga_mulai !== null && item.harga_mulai !== undefined && Number(item.harga_mulai) > 0" class="kt-price"><b>Start from {{ rupiah(item.harga_mulai) }}</b><small>perkiraan, belum harga final</small></p>
                            <p v-else class="kt-price muted">Harga start from belum diisi</p>
                            <div class="kt-lk">
                                <template v-if="item.lingkup.length"><p class="kt-lk-h">Lingkup</p><ul><li v-for="l in item.lingkup" :key="l">{{ l }}</li></ul></template>
                                <p v-else class="muted kt-lk-empty">Lingkup belum diisi.</p>
                                <button v-if="can.edit" type="button" class="kt-btn" :disabled="confirming" :aria-label="`${item.lingkup.length ? 'Ubah' : 'Isi'} lingkup dan harga ${item.judul} (${item.nomor})`" @click="open(item, group)">{{ item.lingkup.length ? `Ubah lingkup & harga (${item.lingkup.length})` : 'Isi lingkup & harga' }}</button>
                            </div>
                        </article>
                    </div>
                </section>
            </template>
        </div>

        <ConfirmDialog v-if="editing" size="wide" :title="`Lingkup & Harga: ${editing.item.judul}`" confirm-label="Simpan" :busy="form.processing || confirming" @confirm="save" @cancel="editing = null">
            <p class="kt-hint"><span class="kt-code">{{ editing.item.nomor }}</span> · {{ [editing.item.nama_produk, editing.bidang, editing.item.nama_varian, editing.item.nama_sertifikasi].filter(Boolean).join(' · ') }}. Lingkup tampil di kartu Katalog Produk dan di PDF katalog. Satu baris untuk satu item; tekan Enter untuk menambah baris.</p>
            <div class="field"><label for="hm">Harga start from (Rp) <span class="muted">(opsional)</span></label><MoneyInput id="hm" v-model="form.harga_mulai" :aria-describedby="form.errors.harga_mulai ? 'hm-err' : 'hm-hint'" /><small id="hm-hint">Perkiraan untuk promosi, bukan harga final. Tidak dipakai di Master Program, Penjualan Program, quotation, atau invoice.</small><p v-if="form.errors.harga_mulai" id="hm-err" class="error" role="alert">{{ form.errors.harga_mulai }}</p></div>
            <ol class="ling-list">
                <li v-for="(_, i) in form.items" :key="i" class="ling-row">
                    <label class="sr-only" :for="`lk-${i}`">Item lingkup {{ i + 1 }}</label>
                    <span class="ling-no" aria-hidden="true">{{ i + 1 }}.</span>
                    <input :id="`lk-${i}`" v-model="form.items[i]" :maxlength="lingkupMax.max_length" placeholder="Tulis satu item lingkup" @keydown.enter.prevent="addRow(i)" />
                    <button type="button" :aria-label="`Hapus item lingkup ${i + 1}`" @click="delRow(i)">Hapus</button>
                </li>
            </ol>
            <div class="ling-tools">
                <button id="lk-add" type="button" :disabled="form.items.length >= max" @click="addRow()">+ Tambah item lingkup</button>
                <span class="muted">{{ form.items.length }} dari maks. {{ max }} item</span>
            </div>
            <p v-if="form.errors.items" class="error" role="alert">{{ form.errors.items }}</p>
        </ConfirmDialog>
    </AppLayout>
</template>
