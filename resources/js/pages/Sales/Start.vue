<script setup lang="ts">
import TutorialButton from "../../components/TutorialButton.vue";
import { appUrl } from '../../composables/appUrl';
import { rupiah } from '../../composables/costing';
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

type Master = { id: number; nomor: string; judul: string; durasi_hari: number; target_peserta: number; harga_jual_peserta: string; hpp: number };
const props = defineProps<{ masters: Master[]; preset: number | null; minB2b: number }>();
const selected = ref<number | ''>(props.preset ?? '');
const master = computed(() => props.masters.find((m) => m.id === Number(selected.value)));
const target = (jenis: 'public' | 'b2b') => appUrl(`/penjualan-program/create?master=${selected.value}&jenis=${jenis}`);
</script>

<template>
    <Head title="Buat Penjualan Program" /><AppLayout title="Penjualan Program">
        <div class="pf-formbar"><Link class="kt-btn" :href="appUrl('/penjualan-program')">← Kembali</Link><h1>Buat Penjualan Program</h1><span class="ref">PJ-FR-02 · PJ-FR-03</span><TutorialButton /></div>
        <section class="panel" aria-labelledby="start-master"><h2 id="start-master"><span class="section-number">1</span> Pilih Master Program <span class="muted">hanya yang berstatus Final</span></h2>
            <label>Master Program
                <select v-model="selected" data-tour="qt-mp" required>
                    <option value="">— Pilih Master Program Final —</option>
                    <option v-for="m in masters" :key="m.id" :value="m.id">{{ m.nomor }} — {{ m.judul }}</option>
                </select>
            </label>
            <p v-if="master" class="hint">Master: {{ master.durasi_hari }} hari · target {{ master.target_peserta }} peserta · harga standar {{ rupiah(Number(master.harga_jual_peserta)) }} · HPP {{ rupiah(master.hpp) }}</p>
            <p v-else-if="!masters.length" class="notice">Belum ada Master Program Final. Finalkan Master Program lebih dulu.</p>
        </section>
        <section class="panel" aria-labelledby="start-jenis"><h2 id="start-jenis"><span class="section-number">2</span> Pilih jenis penjualan <span class="muted">form dan simulasi sama; jenis hanya menentukan jalur setelah Final dan tidak dapat diubah setelah disimpan</span></h2>
            <div class="choice-grid">
                <component :is="master ? Link : 'button'" class="choice" data-tour="jenis-public" data-tour-next="qtForm" v-bind="master ? { href: target('public') } : { type: 'button', disabled: true }">
                    <span class="badge">Public</span><h3>Penjualan Public</h3><p>Acara terbuka, tidak terikat company.</p>
                    <ul><li>Tanggal, lokasi, target peserta, harga per peserta</li><li>Simulasi pendapatan, HPP, profit, titik impas</li><li>Final → <b>Quotation</b> ke Company atau Personal</li></ul>
                </component>
                <component :is="master ? Link : 'button'" class="choice" data-tour="jenis-b2b" v-bind="master ? { href: target('b2b') } : { type: 'button', disabled: true }">
                    <span class="badge">B2B</span><h3>Penjualan B2B</h3><p>Acara untuk satu company (in-house).</p>
                    <ul><li>Company wajib; target peserta <b>minimal {{ minB2b }}</b></li><li>Simulasi pendapatan, HPP, profit, titik impas</li><li>Final → <b>satu Quotation</b> ke Company penjualan, tanpa Formulir Konfirmasi</li></ul>
                </component>
            </div>
            <p v-if="!master" class="hint">Pilih Master Program dahulu.</p>
        </section>
    </AppLayout>
</template>
