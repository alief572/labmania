<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ label: string; id: string; error?: string; hint?: string }>();
const describedBy = computed(() => [props.error ? `${props.id}-error` : '', props.hint ? `${props.id}-hint` : ''].filter(Boolean).join(' ') || undefined);
</script>

<template>
    <div class="field">
        <label :for="id"><span>{{ label }}</span></label>
        <slot :described-by="describedBy" :invalid="!!error" />
        <small v-if="hint" :id="`${id}-hint`">{{ hint }}</small>
        <p v-if="error" :id="`${id}-error`" class="error" role="alert">{{ error }}</p>
    </div>
</template>
