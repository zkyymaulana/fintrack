<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Target } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Option } from '../../types/finance';

const props = defineProps<{ month: string; budget: { id: number; category_id: number; category_name: string; limit_amount: string } | null; categoryId: number | null; categories: Option[] }>();
const form = useForm({ month: props.month, category_id: String(props.budget?.category_id ?? props.categoryId ?? ''), limit_amount: props.budget?.limit_amount ?? '' });
function submit() {
    if (props.budget) form.put(`/budgets/${props.budget.id}`);
    else form.post('/budgets');
}
</script>

<template>
    <Head :title="budget ? 'Ubah anggaran' : 'Atur anggaran'" />
    <AppLayout :title="budget ? 'Ubah anggaran' : 'Atur anggaran'" subtitle="Tetapkan batas pengeluaran untuk kategori dan periode yang kamu pilih.">
        <Link :href="`/budgets?month=${month}`" class="mb-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline"><ArrowLeft :size="17" aria-hidden="true" /> Kembali ke anggaran</Link>
        <div v-if="!categories.length" class="mb-5 rounded-2xl border border-[#f0d7aa] bg-[#fff9e9] px-5 py-4 text-sm text-[#795b25]">Kategori pengeluaran belum tersedia. Hubungi pengelola aplikasi.</div>
        <form class="surface-shadow max-w-2xl space-y-6 rounded-3xl border border-[#e3ebe3] bg-white p-6 sm:p-9" @submit.prevent="submit">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#2d765c]"><Target :size="23" aria-hidden="true" /></span>
            <div><label for="budget-month" class="mb-2 block text-sm font-semibold">Bulan</label><input id="budget-month" v-model="form.month" class="field" type="month" required :disabled="Boolean(budget)" :aria-invalid="Boolean(form.errors.month)" /><p v-if="form.errors.month" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.month }}</p></div>
            <div v-if="budget"><p class="mb-2 text-sm font-semibold">Kategori pengeluaran</p><p class="field flex items-center bg-[#f7faf7]">{{ budget.category_name }}</p></div>
            <div v-else><label for="budget-category" class="mb-2 block text-sm font-semibold">Kategori pengeluaran</label><select id="budget-category" v-model="form.category_id" class="field" required :aria-invalid="Boolean(form.errors.category_id)"><option value="" disabled>Pilih kategori</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select><p v-if="form.errors.category_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.category_id }}</p></div>
            <div><label for="budget-limit" class="mb-2 block text-sm font-semibold">Batas pengeluaran (Rp)</label><input id="budget-limit" v-model="form.limit_amount" class="field" type="number" inputmode="decimal" min="0" step="0.01" required :aria-invalid="Boolean(form.errors.limit_amount)" /><p v-if="form.errors.limit_amount" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.limit_amount }}</p></div>
            <p class="rounded-2xl bg-[#f1f6ee] p-4 text-sm leading-relaxed text-[#567163]">Pengeluaran dalam kategori ini, termasuk biaya admin, akan dihitung terhadap batas anggaran. Anggaran yang disimpan untuk bulan ini akan menggantikan nilai bawaan dari periode lain.</p>
            <div class="flex flex-wrap gap-3 border-t border-[#edf0eb] pt-6"><button class="primary-button" type="submit" :disabled="form.processing || !categories.length">{{ form.processing ? 'Menyimpan...' : 'Simpan anggaran' }}</button><Link :href="`/budgets?month=${month}`" class="inline-flex min-h-12 items-center rounded-xl px-5 text-sm font-semibold text-[#61766b] hover:bg-[#f2f6f1]">Batal</Link></div>
        </form>
    </AppLayout>
</template>
