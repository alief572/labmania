<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
defineProps<{ secret: string | null; setupUrl: string | null; needsToken: boolean; enrollBlocked: boolean }>();
const form = useForm({ code: '', enroll_token: '' });
const submit = () => form.post('/two-factor', { onFinish: () => form.reset('code', 'enroll_token') });
</script>

<template>
    <Head title="Verifikasi akun" />
    <main class="auth-page"><section class="auth-card" aria-labelledby="title">
        <div class="brand auth-brand"><span class="brand-mark">L</span><strong>Labmania</strong></div>
        <h1 id="title">{{ secret ? 'Siapkan autentikator' : 'Verifikasi akun' }}</h1>
        <template v-if="secret"><p>Tambahkan akun di aplikasi autentikator menggunakan kunci ini:</p><code class="setup-key">{{ secret }}</code><p><a v-if="setupUrl" :href="setupUrl">Tambahkan ke autentikator</a></p></template>
        <p v-else-if="enrollBlocked" role="alert" class="notice">Autentikator akun ini belum diaktifkan. Hubungi admin untuk meminta token enroll, lalu muat ulang halaman ini.</p>
        <p v-else>Masukkan kode dari aplikasi autentikator Anda.</p>
        <form v-if="!enrollBlocked" @submit.prevent="submit">
            <template v-if="needsToken">
                <label for="enroll_token">Token enroll dari admin</label><input id="enroll_token" v-model="form.enroll_token" autocomplete="off" required :aria-invalid="!!form.errors.enroll_token" aria-describedby="token-error" />
                <p id="token-error" class="error" role="alert">{{ form.errors.enroll_token }}</p>
            </template>
            <label for="code">Kode 6 digit</label><input id="code" v-model="form.code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus required :aria-invalid="!!form.errors.code" aria-describedby="code-error" />
            <p id="code-error" class="error" role="alert">{{ form.errors.code }}</p>
            <button class="primary" :disabled="form.processing">{{ form.processing ? 'Memverifikasi…' : 'Verifikasi' }}</button>
        </form>
        <Link class="back-link" href="/logout" method="post" as="button">Kembali ke login</Link>
    </section></main>
</template>
