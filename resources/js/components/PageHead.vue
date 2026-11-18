<script setup lang="ts">
import TutorialButton from './TutorialButton.vue';
import type { TutorialKey } from '../composables/tutorials';
withDefaults(defineProps<{ title: string; description?: string; reference?: string; tutorial?: TutorialKey | false }>(), { tutorial: undefined });
</script>

<template>
    <div class="page-head">
        <div>
            <h1>{{ title }}<span v-if="reference" class="ref">{{ reference }}</span></h1>
            <p v-if="description" class="page-desc">{{ description }}</p>
        </div>
        <div class="page-head-actions" data-tour="actions"><TutorialButton :tutorial="tutorial" /><slot /></div>
    </div>
</template>
