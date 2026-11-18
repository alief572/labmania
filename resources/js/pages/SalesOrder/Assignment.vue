<script setup lang="ts">
import TutorialButton from "../../components/TutorialButton.vue";
import { appUrl } from '../../composables/appUrl';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import type { Program, Product, Company } from '../../types/salesOrder';
defineProps<{ surat: { id: number; nomor: string; tanggal: string; status: string; id_penjualan: number }; program: Program; judul: string; produk: Product[]; trainer: { nama: string; bidang_keahlian: string; no_telp: string; email: string | null; alamat: string }; asisten: { nama: string; jabatan: string; telp: string; email: string }; company: Company | null; jumlah_so: number; peserta_deal: number }>();
</script>
<template>
    <Head :title="surat.nomor" /><AppLayout title="Surat Kerja Trainer"><Link :href="appUrl('/sales-order?tab=surat')">← Surat Kerja Trainer</Link><h1>Surat Tugas {{ surat.nomor }}</h1><TutorialButton /><p :class="surat.status === 'diganti' ? 'notice' : ''">{{ surat.status === 'diganti' ? 'DIGANTI' : 'Berlaku' }} · Tanggal tersimpan {{ surat.tanggal }}</p><div class="row-actions"><a :href="appUrl(`/surat-kerja-trainer/${surat.id}/pdf`)" target="_blank" rel="noopener">Print Out Surat Kerja</a><Link data-tour-next="soProg" :href="appUrl(`/sales-order/program/${surat.id_penjualan}`)">Lihat Sales Order program</Link></div>
        <section class="panel"><h2>Trainer</h2><p>{{ trainer.nama }} · {{ trainer.bidang_keahlian }}</p><p>{{ trainer.no_telp }} · {{ trainer.alamat }}</p><h2>Asisten Trainer</h2><p>{{ asisten.nama }} · {{ asisten.jabatan }}</p><p>{{ asisten.telp }} · {{ asisten.email }}</p></section>
        <section class="panel"><h2>Program yang ditugaskan</h2><p>{{ program.nomor }} · {{ program.jenis.toUpperCase() }} · {{ judul }}</p><p v-for="k in produk" :key="k.nomor">{{ k.nomor }} · {{ k.varian }} · {{ k.sertifikat }}</p><p>{{ program.tgl_mulai }} s.d. {{ program.tgl_selesai }} · {{ program.waktu }} · {{ program.lokasi }}</p><p>{{ program.durasi_hari }} hari · {{ program.jam_pembelajaran }} JP</p><p v-if="company">{{ company.name_customer }}</p><p>{{ peserta_deal }} peserta Deal dari {{ jumlah_so }} SO.</p></section>
    </AppLayout>
</template>
