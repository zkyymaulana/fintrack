<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import AuthLayout from '../../Layouts/AuthLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Daftar" />
    <AuthLayout eyebrow="Mulai di sini" title="Buat akun FinTrack" description="Siapkan ruang pribadi untuk mencatat dan memahami keuanganmu.">
        <form class="mt-9 space-y-4" @submit.prevent="submit">
            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-[#29433a]">Nama lengkap</label>
                <input id="name" v-model="form.name" class="field" type="text" name="name" autocomplete="name" placeholder="Nama kamu" required :aria-invalid="Boolean(form.errors.name)" aria-describedby="register-name-error" />
                <p v-if="form.errors.name" id="register-name-error" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-[#29433a]">Email</label>
                <input id="email" v-model="form.email" class="field" type="email" name="email" autocomplete="email" inputmode="email" placeholder="nama@email.com" required :aria-invalid="Boolean(form.errors.email)" aria-describedby="register-email-error" />
                <p v-if="form.errors.email" id="register-email-error" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-[#29433a]">Kata sandi</label>
                <input id="password" v-model="form.password" class="field" type="password" name="password" autocomplete="new-password" minlength="8" placeholder="Minimal 8 karakter" required :aria-invalid="Boolean(form.errors.password)" aria-describedby="register-password-error" />
                <p v-if="form.errors.password" id="register-password-error" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#29433a]">Ulangi kata sandi</label>
                <input id="password_confirmation" v-model="form.password_confirmation" class="field" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" placeholder="Ulangi kata sandi" required />
            </div>

            <button class="primary-button mt-2 w-full" type="submit" :disabled="form.processing">
                <span>{{ form.processing ? 'Membuat akun...' : 'Buat akun' }}</span>
                <ArrowRight :size="18" aria-hidden="true" />
            </button>
        </form>

        <div class="mt-8 rounded-2xl border border-[#e0e9e2] bg-white/70 px-5 py-4 text-sm text-[#5a6d62]">
            Sudah punya akun?
            <Link href="/login" class="ml-1 font-bold text-[#256958] hover:underline">Masuk</Link>
        </div>
    </AuthLayout>
</template>
