<script setup lang="ts">
import { computed, onMounted, onUpdated, ref } from 'vue';
const props = defineProps<{ label: string; id: string; error?: string; hint?: string }>();
const describedBy = computed(() => [props.error ? `${props.id}-error` : '', props.hint ? `${props.id}-hint` : ''].filter(Boolean).join(' ') || undefined);
// Tanda * (mockup .req) mengikuti atribut required pada kontrol di dalamnya, jadi tidak perlu prop terpisah.
const root = ref<HTMLElement | null>(null);
const required = ref(false);
const sync = () => { required.value = !!root.value?.querySelector('[required]'); };
onMounted(sync);
onUpdated(sync);
</script>

<template>
    <div ref="root" class="field">
        <label :for="id"><span>{{ label }}</span><span v-if="required" class="req" aria-hidden="true"> *</span></label>
        <slot :described-by="describedBy" :invalid="!!error" />
        <small v-if="hint" :id="`${id}-hint`">{{ hint }}</small>
        <p v-if="error" :id="`${id}-error`" class="error" role="alert">{{ error }}</p>
    </div>
</template>
