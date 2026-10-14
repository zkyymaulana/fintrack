<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, LockKeyhole } from '@lucide/vue';
import AuthLayout from '../../Layouts/AuthLayout.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Masuk" />
    <AuthLayout eyebrow="Selamat datang kembali" title="Masuk ke akunmu" description="Lanjutkan perjalanan keuanganmu dari perangkat apa pun.">
        <form class="mt-9 space-y-5" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-[#29433a]">Email</label>
                <input id="email" v-model="form.email" class="field" type="email" name="email" autocomplete="username" inputmode="email" placeholder="nama@email.com" required :aria-invalid="Boolean(form.errors.email)" aria-describedby="login-email-error" />
                <p v-if="form.errors.email" id="login-email-error" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-[#29433a]">Kata sandi</label>
                <input id="password" v-model="form.password" class="field" type="password" name="password" autocomplete="current-password" placeholder="Masukkan kata sandi" required :aria-invalid="Boolean(form.errors.password)" aria-describedby="login-password-error" />
                <p v-if="form.errors.password" id="login-password-error" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.password }}</p>
            </div>

            <label class="inline-flex min-h-11 cursor-pointer items-center gap-3 text-sm text-[#526a5e]">
                <input v-model="form.remember" type="checkbox" name="remember" class="h-4 w-4 accent-[#256958]" />
                Ingat saya di perangkat ini
            </label>

            <button class="primary-button w-full" type="submit" :disabled="form.processing">
                <span>{{ form.processing ? 'Sedang masuk...' : 'Masuk ke FinTrack' }}</span>
                <ArrowRight :size="18" aria-hidden="true" />
            </button>
        </form>

        <div class="mt-8 rounded-2xl border border-[#e0e9e2] bg-white/70 px-5 py-4 text-sm text-[#5a6d62]">
            Belum punya akun?
            <Link href="/register" class="ml-1 font-bold text-[#256958] hover:underline">Daftar sekarang</Link>
        </div>

        <p class="mt-6 flex items-center justify-center gap-2 text-center text-xs text-[#809086]">
            <LockKeyhole :size="14" aria-hidden="true" />
            Sesi web pribadi untuk akun FinTrack milikmu.
        </p>
    </AuthLayout>
</template>
