<script setup lang="ts">
import { appUrl } from '../../../composables/appUrl';
import PageHead from '../../../components/PageHead.vue';
import ServerTable from '../../../components/ServerTable.vue';
import ActionMenu from "../../../components/ActionMenu.vue";
import { ref, onBeforeUnmount, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import { rupiah } from '../../../composables/costing';
import { useConfirmedMutation } from '../../../composables/confirmedMutation';
import { useProgramTransition } from '../../../composables/programTransition';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; nomor: string; judul: string; nomor_product: string; nama_varian: string | null; nama_sertifikasi: string | null; durasi_hari: number; target_peserta: number; harga_jual_peserta: number; hpp: number; status: 'draft' | 'final' | 'batal' };
const props = defineProps<{
    filters: { q: string; status: string };
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    can: { add: boolean; manage: boolean; approve: boolean };
}>();
const filter = ref({ ...props.filters });
const apply = () => router.get(appUrl('/program'), Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '' && v !== 'all')), { preserveScroll: true, preserveState: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => filter.value.status, () => { clearTimeout(timer); apply(); });
watch(() => filter.value.q, () => { clearTimeout(timer); timer = setTimeout(apply, 300); });
onBeforeUnmount(() => clearTimeout(timer));
const label = { draft: 'Draft', final: 'Final', batal: 'Batal' } as const;
const { ask: askTransition, busy, error: summaryError } = useProgramTransition(confirmation);
const ask = (row: Row, action: 'finalkan' | 'batalkan') => askTransition(row.id, action);
</script>

<template>
    <Head title="Daftar Master Program" />
    <AppLayout title="Daftar Master Program" full-width>
        <PageHead title="Daftar Master Program" description="Program siap jual: Paket Produk + costing baseline. Tidak ada Public/B2B, customer, tanggal, atau lokasi acara — semuanya diisi di Penjualan Program."><Link v-if="can.add" class="primary" :href="appUrl('/program/create')">+ Buat Master Program</Link></PageHead>
        <p v-if="($page.props.errors as Record<string, string>).program" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).program }}</p>
        <p v-if="summaryError" class="error" role="alert">{{ summaryError }}</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari nomor atau judul…" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="draft">Draft</option><option value="final">Final</option><option value="batal">Batal</option></select></label>
        </div>
        <div class="table-wrap"><ServerTable :url="appUrl('/program/data')" label="Daftar Master Program" :columns='[{"title":"No","orderable":false},{"title":"Nomor","orderable":true},{"title":"Judul","orderable":false},{"title":"Durasi","orderable":true},{"title":"Target peserta","orderable":true,"className":"dt-body-right"},{"title":"Harga jual standar","orderable":true,"className":"dt-body-right"},{"title":"HPP","orderable":false,"className":"dt-body-right"},{"title":"Status","orderable":true},{"title":"Action","orderable":false}]' :filters="{ ...filters }" refresh-on-visit><template #cell-0="{ row: row, number }">{{ number }}</template><template #cell-1="{ row: row, number }"><span class="code">{{ row.nomor }}</span></template><template #cell-2="{ row: row, number }">{{ row.judul || '—' }}<small>{{ [row.nomor_product, row.nama_varian, row.nama_sertifikasi].filter(Boolean).join(' · ') }}</small></template><template #cell-3="{ row: row, number }">{{ row.durasi_hari }} hari</template><template #cell-4="{ row: row, number }">{{ row.target_peserta }}</template><template #cell-5="{ row: row, number }">{{ rupiah(row.harga_jual_peserta) }}</template><template #cell-6="{ row: row, number }">{{ rupiah(row.hpp) }}</template><template #cell-7="{ row: row, number }"><span class="badge" :class="`st-${row.status}`">{{ label[row.status as keyof typeof label] }}</span></template><template #cell-8="{ row: row, number }"><ActionMenu >
                        <Link :href="appUrl(`/program/${row.id}`)">{{ row.status === 'draft' && can.manage ? 'Edit' : 'Lihat' }}</Link>
                        <template v-if="row.status === 'draft'">
                            <button v-if="can.approve" type="button" :disabled="busy || confirming" @click="ask(row, 'finalkan')">Finalkan</button>
                            <button v-if="can.manage" type="button" class="danger" :disabled="busy || confirming" @click="ask(row, 'batalkan')">Batalkan</button>
                        </template>
                    </ActionMenu></template></ServerTable></div>

    </AppLayout>
</template>
