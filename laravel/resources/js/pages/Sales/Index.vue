<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import Pagination from '../../components/Pagination.vue';
import { rupiah } from '../../composables/costing';
type Row = { id: number; reschedule_count: number; nomor: string; master_nomor: string; jenis: string; judul: string; name_customer: string | null; tgl_mulai: string; tgl_selesai: string; lokasi: string; jam_pembelajaran: number | null; trainer_nama: string | null; asisten_nama: string | null; status: string; peserta_deal: number; target_peserta: number; harga_jual_peserta: string };
const props = defineProps<{ rows: { data: Row[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }; filters: Record<string, string>; can: { add: boolean }; years: number[] }>();
const f = reactive({ q: '', jenis: '', status: '', tahun: '', bulan: '', ...props.filters });
const search = () => router.get('/penjualan-program', f);
</script>
<template>
    <Head title="Penjualan Program" /><AppLayout title="Penjualan Program">
        <div class="page-head"><h1>Penjualan Program</h1><Link v-if="can.add" href="/penjualan-program/create">Buat Penjualan Program</Link></div>
        <form class="filters" @submit.prevent="search">
            <label>Cari<input v-model="f.q" type="search" placeholder="Nomor, Company, lokasi" maxlength="100" /></label>
            <label>Jenis<select v-model="f.jenis"><option value="">Semua</option><option value="public">Public</option><option value="b2b">B2B</option></select></label>
            <label>Status<select v-model="f.status"><option value="">Semua</option><option value="draft">Draft</option><option value="final">Final</option><option value="batal">Batal</option></select></label>
            <label>Tahun acara<select v-model="f.tahun"><option value="">Semua</option><option v-for="y in years" :key="y" :value="y">{{ y }}</option></select></label>
            <label>Bulan acara<select v-model="f.bulan"><option value="">Semua</option><option v-for="n in 12" :key="n" :value="n">{{ new Date(2026, n - 1).toLocaleString('id-ID', { month: 'long' }) }}</option></select></label>
            <button class="primary">Terapkan</button><Link href="/penjualan-program">Reset</Link>
        </form>
        <p>{{ rows.total }} penjualan</p><div class="table-wrap"><table><caption class="sr-only">Daftar Penjualan Program</caption><thead><tr><th>Program</th><th>Jadwal</th><th>Peserta Deal / target</th><th>Harga / peserta</th><th>Status</th></tr></thead><tbody>
            <tr v-for="p in rows.data" :key="p.id"><td><Link :href="`/penjualan-program/${p.id}`">{{ p.nomor }}</Link><small>{{ p.jenis.toUpperCase() }} · {{ p.judul }}</small><small>{{ p.master_nomor }} · {{ p.name_customer }}</small></td><td>{{ p.tgl_mulai }} s.d. {{ p.tgl_selesai }}<small>{{ p.lokasi }} · {{ p.jam_pembelajaran ?? 'Belum diisi' }} JP</small><small>Trainer: {{ p.trainer_nama || 'Belum diisi' }}</small><small>Asisten: {{ p.asisten_nama || 'Belum diisi' }}</small></td><td>{{ p.peserta_deal }} / {{ p.target_peserta }}</td><td>{{ rupiah(p.harga_jual_peserta) }}</td><td>{{ p.status }}<small v-if="p.reschedule_count">Reschedule {{p.reschedule_count}} kali</small><Link v-if="p.status === 'final'" :href="`/penjualan-program/${p.id}/reschedule`">Reschedule / Riwayat</Link></td></tr>
            <tr v-if="!rows.data.length"><td colspan="5" class="empty">Belum ada penjualan sesuai filter.</td></tr>
        </tbody></table></div><Pagination :page="rows" label="Halaman Penjualan Program" />
    </AppLayout>
</template>
