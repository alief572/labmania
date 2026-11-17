<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
const visible = ref(false);
const form = useForm({ username: '', password: '' });
const submit = () => form.post(appUrl('/login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="Masuk" />
    <main class="auth-page login-page">
        <section class="auth-card" aria-labelledby="title">
            <div class="auth-brand"><span class="brand-mark">L</span><h1 id="title">Labmania Indonesia</h1><p class="muted">Enterprise Resource Planning</p></div>
            <form @submit.prevent="submit">
                <label for="username">Username</label><div class="auth-input"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3"/></svg><input id="username" v-model="form.username" autocomplete="username" placeholder="Masukkan username Anda" required maxlength="45" autofocus :aria-invalid="!!form.errors.username" aria-describedby="login-error" /></div>
                <label for="password">Password</label><div class="auth-input"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="password" v-model="form.password" :type="visible ? 'text' : 'password'" autocomplete="current-password" placeholder="Masukkan password Anda" required :aria-invalid="!!form.errors.password" aria-describedby="login-error" /><button type="button" class="password-toggle" :aria-label="visible ? 'Sembunyikan password' : 'Tampilkan password'" :aria-pressed="visible" @click="visible = !visible"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12"/><circle cx="12" cy="12" r="3"/><path v-if="visible" d="m3 3 18 18"/></svg></button></div>
                <p id="login-error" class="error" role="alert">{{ form.errors.username || form.errors.password }}</p>
                <button class="primary" :disabled="form.processing">{{ form.processing ? 'Memproses…' : 'Masuk ke Sistem' }}</button>
            </form>
        </section>
        <footer class="login-footer">© {{ new Date().getFullYear() }} Labmania Indonesia. Seluruh hak cipta dilindungi.</footer>
    </main>
</template>
