<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { money, type Order } from '../types/salesOrder';
import { useConfirmedMutation } from '../composables/confirmedMutation';
const props = defineProps<{ so: Order; kepada: string; count: number; total: string }>();
const form = useForm({});
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const save = () => confirmation.form(form, 'post', `/sales-order/${props.so.id}/siap-invoice`, {
    title: `Tandai ${props.so.nomor} Siap invoice?`, text: `${props.kepada}: ${props.count} peserta Deal, ${money(props.total)}. Status Siap invoice tidak dapat dibatalkan.`,
    confirmLabel: 'Ya, siap invoice', danger: true,
}, { preserveScroll: true });
</script>
<template>
    <button type="button" :disabled="confirming || form.processing" @click="save">Siap dibuatkan invoice</button>
    <p v-for="error in form.errors" :key="error" class="error" role="alert">{{ error }}</p>
</template>
