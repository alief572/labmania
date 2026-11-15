<script setup lang="ts">
import ServerTable from '../../../components/ServerTable.vue';
import ActionMenu from "../../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import { rupiah } from '../../../composables/costing';
import { useConfirmedMutation } from '../../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; nomor: string; judul: string; nomor_product: string; durasi_hari: number; target_peserta: number; harga_jual_peserta: number; hpp: number; status: 'draft' | 'final' | 'batal' };
type Summary = { id: number; nomor: string; status: string; judul: string; durasi: number; target: number; harga: number; jp: number | null; hpp: number; profit: number; bep_peserta: number | null; warnings: string[]; jumlah_biaya: number; n_penjualan: number };
const props = defineProps<{
    filters: { q: string; status: string };
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    can: { add: boolean; manage: boolean; approve: boolean };
}>();
const filter = ref({ ...props.filters });
const apply = () => router.get('/program', Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '')), { preserveScroll: true });
const label = { draft: 'Draft', final: 'Final', batal: 'Batal' } as const;
const busy = ref(false);
const summaryError = ref('');
async function ask(row: Row, action: 'finalkan' | 'batalkan') {
    if (busy.value || confirming.value) return;
    const id = row.id;
    busy.value = true;
    summaryError.value = '';
    try {
        const res = await fetch(`/program/${id}/ringkasan`, { headers: { Accept: 'application/json' } });
        if (!res.ok) throw new Error('Ringkasan program gagal dimuat. Muat ulang data dan coba lagi.');
        const summary: Summary = await res.json();
        await confirmation.visit('post', `/program/${id}/${action}`, {}, {
            title: `${action === 'finalkan' ? 'Finalkan' : 'Batalkan'} ${summary.nomor}?`,
            text: `${summary.judul}\n${summary.durasi} hari / ${summary.jp ?? 'belum diisi'} JP; target ${summary.target} peserta. Harga ${rupiah(summary.harga)}, HPP ${rupiah(summary.hpp)}, profit ${rupiah(summary.profit)}, BEP ${summary.bep_peserta ?? 'tidak tercapai'}.\n${summary.warnings.join('\n')}\n${action === 'finalkan' ? 'Setelah Final, program terkunci dan tersedia di Penjualan Program.' : 'Program yang dibatalkan tidak dapat dibuka kembali.'}`,
            confirmLabel: action === 'finalkan' ? 'Ya, finalkan' : 'Ya, batalkan', danger: action === 'batalkan',
        }, { preserveScroll: true });
    } catch (error) { summaryError.value = error instanceof Error ? error.message : 'Ringkasan program gagal dimuat.'; }
    finally { busy.value = false; }
}
</script>

<template>
    <Head title="Daftar Master Program" />
    <AppLayout title="Daftar Master Program" full-width>
        <div class="page-head"><h1>Daftar Master Program</h1><Link v-if="can.add" class="primary" href="/program/create">+ Buat Master Program</Link></div>
        <p v-if="($page.props.errors as Record<string, string>).program" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).program }}</p>
        <p v-if="summaryError" class="error" role="alert">{{ summaryError }}</p>
        <form class="filters" @submit.prevent="apply">
            
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="draft">Draft</option><option value="final">Final</option><option value="batal">Batal</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><ServerTable url="/program/data" label="Daftar Master Program" :columns='[{"title":"No","orderable":false},{"title":"Nomor","orderable":true},{"title":"Judul","orderable":false},{"title":"Durasi","orderable":true},{"title":"Target peserta","orderable":true,"className":"dt-body-right"},{"title":"Harga jual standar","orderable":true,"className":"dt-body-right"},{"title":"HPP","orderable":false,"className":"dt-body-right"},{"title":"Status","orderable":true},{"title":"Aksi","orderable":false}]' :filters="{ ...filters }" refresh-on-visit><template #cell-0="{ row: row, number }">{{ number }}</template><template #cell-1="{ row: row, number }">{{ row.nomor }}</template><template #cell-2="{ row: row, number }">{{ row.judul || '—' }}<small>{{ row.nomor_product }}</small></template><template #cell-3="{ row: row, number }">{{ row.durasi_hari }} hari</template><template #cell-4="{ row: row, number }">{{ row.target_peserta }}</template><template #cell-5="{ row: row, number }">{{ rupiah(row.harga_jual_peserta) }}</template><template #cell-6="{ row: row, number }">{{ rupiah(row.hpp) }}</template><template #cell-7="{ row: row, number }"><span class="badge" :class="`st-${row.status}`">{{ label[row.status as keyof typeof label] }}</span></template><template #cell-8="{ row: row, number }"><ActionMenu >
                        <Link :href="`/program/${row.id}`">{{ row.status === 'draft' && can.manage ? 'Edit' : 'Lihat' }}</Link>
                        <template v-if="row.status === 'draft'">
                            <button v-if="can.approve" type="button" :disabled="busy || confirming" @click="ask(row, 'finalkan')">Finalkan</button>
                            <button v-if="can.manage" type="button" class="danger" :disabled="busy || confirming" @click="ask(row, 'batalkan')">Batalkan</button>
                        </template>
                    </ActionMenu></template></ServerTable></div>

    </AppLayout>
</template>
