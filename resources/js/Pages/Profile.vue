<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Camera, KeyRound, LogOut, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';

const props = defineProps<{ profile: { name: string; email: string } }>();
const page = usePage();
const avatarUrl = computed(() => (page.props.auth as { user?: { avatar_url?: string | null } })?.user?.avatar_url ?? null);
const initials = computed(() => props.profile.name.trim().slice(0, 1).toUpperCase() || 'F');
const details = useForm({ name: props.profile.name, email: props.profile.email });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });
const avatar = useForm<{ avatar: File | null }>({ avatar: null });
const avatarInput = ref<HTMLInputElement | null>(null);

function saveDetails() {
    details.patch('/profile');
}
function savePassword() {
    password.put('/profile/password', { onSuccess: () => password.reset() });
}
function chooseAvatar(event: Event) {
    avatar.avatar = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function saveAvatar() {
    avatar.post('/profile/avatar', { forceFormData: true, onSuccess: () => { avatar.reset(); if (avatarInput.value) avatarInput.value.value = ''; } });
}
function deleteAvatar() {
    avatar.delete('/profile/avatar');
}
</script>

<template>
    <Head title="Profil" />
    <AppLayout title="Profil" subtitle="Kelola informasi akun dan keamanan masukmu.">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(280px,0.8fr)]">
            <div class="space-y-6">
                <section class="rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-[#294538]"><UserRound :size="20" aria-hidden="true" /> Informasi akun</h2>
                    <form class="mt-5 space-y-4" @submit.prevent="saveDetails">
                        <div><label for="profile-name" class="mb-2 block text-sm font-semibold text-[#294538]">Nama</label><input id="profile-name" v-model="details.name" class="field w-full" autocomplete="name" required maxlength="255" /><p v-if="details.errors.name" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ details.errors.name }}</p></div>
                        <div><label for="profile-email" class="mb-2 block text-sm font-semibold text-[#294538]">Email</label><input id="profile-email" v-model="details.email" class="field w-full" type="email" autocomplete="email" required maxlength="255" /><p v-if="details.errors.email" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ details.errors.email }}</p></div>
                        <button class="primary-button" type="submit" :disabled="details.processing">{{ details.processing ? 'Menyimpan…' : 'Simpan profil' }}</button>
                    </form>
                </section>

                <section class="rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-[#294538]"><KeyRound :size="20" aria-hidden="true" /> Ubah kata sandi</h2>
                    <form class="mt-5 space-y-4" @submit.prevent="savePassword">
                        <div><label for="current-password" class="mb-2 block text-sm font-semibold text-[#294538]">Kata sandi saat ini</label><input id="current-password" v-model="password.current_password" class="field w-full" type="password" autocomplete="current-password" required /><p v-if="password.errors.current_password" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ password.errors.current_password }}</p></div>
                        <div><label for="new-password" class="mb-2 block text-sm font-semibold text-[#294538]">Kata sandi baru</label><input id="new-password" v-model="password.password" class="field w-full" type="password" autocomplete="new-password" minlength="8" required /><p v-if="password.errors.password" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ password.errors.password }}</p></div>
                        <div><label for="confirm-password" class="mb-2 block text-sm font-semibold text-[#294538]">Ulangi kata sandi baru</label><input id="confirm-password" v-model="password.password_confirmation" class="field w-full" type="password" autocomplete="new-password" required /></div>
                        <button class="primary-button" type="submit" :disabled="password.processing">{{ password.processing ? 'Menyimpan…' : 'Perbarui kata sandi' }}</button>
                    </form>
                </section>
            </div>

            <div class="space-y-6">
                <section class="rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-[#294538]"><Camera :size="20" aria-hidden="true" /> Foto profil</h2>
                    <div class="mt-5 flex items-center gap-4"><span class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#d9eadc] text-2xl font-bold text-[#26694f]"><img v-if="avatarUrl" :src="avatarUrl" alt="Foto profil saat ini" class="h-full w-full object-cover" />{{ avatarUrl ? '' : initials }}</span><p class="text-sm leading-relaxed text-[#728478]">JPG atau PNG, maksimal 2 MB.</p></div>
                    <form class="mt-5 space-y-4" @submit.prevent="saveAvatar"><div><label for="profile-avatar" class="mb-2 block text-sm font-semibold text-[#294538]">Pilih foto</label><input id="profile-avatar" ref="avatarInput" class="block w-full text-sm text-[#61766b] file:mr-3 file:rounded-xl file:border-0 file:bg-[#e9f4eb] file:px-4 file:py-3 file:font-semibold file:text-[#256958]" type="file" accept="image/jpeg,image/png" @change="chooseAvatar" /><p v-if="avatar.errors.avatar" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ avatar.errors.avatar }}</p></div><button class="primary-button" type="submit" :disabled="!avatar.avatar || avatar.processing">{{ avatar.processing ? 'Mengunggah…' : 'Unggah foto' }}</button></form>
                    <button v-if="avatarUrl" type="button" class="mt-4 inline-flex min-h-11 items-center text-sm font-bold text-[#a9443c] hover:underline" :disabled="avatar.processing" @click="deleteAvatar">Hapus foto</button>
                </section>

                <section class="rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7"><h2 class="text-lg font-bold text-[#294538]">Akun dan laporan</h2><p class="mt-2 text-sm leading-relaxed text-[#728478]">Unduh catatan keuanganmu atau akhiri sesi di perangkat ini.</p><div class="mt-4 flex flex-wrap gap-3"><Link href="/reports" class="inline-flex min-h-11 items-center rounded-xl border border-[#bfd8c6] px-4 text-sm font-bold text-[#256958]">Buka laporan</Link><Link href="/analytics" class="inline-flex min-h-11 items-center rounded-xl border border-[#bfd8c6] px-4 text-sm font-bold text-[#256958]">Buka analitik</Link><Link href="/logout" method="post" as="button" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-[#e9d1c9] px-4 text-sm font-bold text-[#a9443c]"><LogOut :size="16" aria-hidden="true" /> Keluar</Link></div></section>
            </div>
        </div>
    </AppLayout>
</template>
