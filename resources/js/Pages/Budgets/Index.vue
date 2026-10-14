<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, Pencil, Plus, Target, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Budget } from '../../types/finance';
import { rupiah } from '../../utils/finance';

const props = defineProps<{ month: string; budgets: Budget[]; totalLimit: string; totalSpent: string; totalRemaining: string }>();
const selectedMonth = ref(props.month);
const pending = ref<number | null>(null);
watch(() => props.month, (value) => selectedMonth.value = value);
function filter() { router.get('/budgets', { month: selectedMonth.value }, { preserveState: true, replace: true }); }
function remove(budget: Budget) {
    if (!window.confirm(`Hapus anggaran ${budget.category_name} untuk bulan ini?`)) return;
    pending.value = budget.id;
    router.delete(`/budgets/${budget.id}`, { onFinish: () => pending.value = null });
}
const badge = { safe: 'Terkendali', warning: 'Mendekati batas' };
</script>

<template>
    <Head title="Anggaran" />
    <AppLayout title="Anggaran" subtitle="Pantau batas pengeluaran setiap kategori dan lihat sisa anggaran pada bulan pilihanmu.">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="filter"><div><label for="budget-month" class="mb-2 block text-xs font-bold text-[#61766b]">Periode anggaran</label><input id="budget-month" v-model="selectedMonth" class="field min-w-48" type="month" required /></div><button class="primary-button" type="submit"><CalendarDays :size="17" aria-hidden="true" /> Tampilkan</button></form>
            <Link :href="`/budgets/create?month=${month}`" class="primary-button"><Plus :size="18" aria-hidden="true" /> Atur anggaran</Link>
        </div>
        <section class="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan anggaran">
            <div class="rounded-3xl bg-[#194b40] p-6 text-white"><p class="text-sm text-[#c7e4d2]">Total batas</p><p class="mt-2 break-words text-2xl font-bold">{{ rupiah(totalLimit) }}</p></div>
            <div class="rounded-3xl border border-[#e3ebe3] bg-white p-6"><p class="text-sm text-[#728478]">Sudah terpakai</p><p class="mt-2 break-words text-2xl font-bold text-[#a55b46]">{{ rupiah(totalSpent) }}</p></div>
            <div class="rounded-3xl border border-[#e3ebe3] bg-white p-6"><p class="text-sm text-[#728478]">Sisa anggaran</p><p class="mt-2 break-words text-2xl font-bold" :class="Number(totalRemaining) < 0 ? 'text-[#b5433b]' : 'text-[#256958]'">{{ rupiah(totalRemaining) }}</p></div>
        </section>
        <p v-if="budgets.some((budget) => budget.inherited)" class="mt-5 rounded-2xl border border-[#dae6df] bg-[#f1f7f2] px-5 py-4 text-sm leading-relaxed text-[#597064]">Anggaran bertanda “Mengikuti periode lain” menggunakan batas yang sudah pernah kamu tetapkan. Pilih “Atur bulan ini” untuk membuat batas khusus pada periode ini.</p>
        <section v-if="budgets.length" class="mt-6 grid gap-4 lg:grid-cols-2" aria-label="Daftar anggaran">
            <article v-for="budget in budgets" :key="budget.category_id" class="surface-shadow min-w-0 rounded-3xl border border-[#e3ebe3] bg-white p-6 sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#28785e]"><Target :size="21" aria-hidden="true" /></span><h2 class="mt-4 text-lg font-bold text-[#294538]">{{ budget.category_name }}</h2><p v-if="budget.inherited" class="mt-1 text-xs text-[#7c8d82]">Mengikuti periode lain</p></div><span class="rounded-full px-3 py-1.5 text-xs font-bold" :class="budget.status === 'over' ? 'bg-[#fff0ed] text-[#a64c42]' : budget.status === 'warning' ? 'bg-[#fff5df] text-[#9a6b2b]' : 'bg-[#eaf5ec] text-[#28785e]'">{{ budget.status === 'over' ? (budget.percentage > 100 ? 'Melewati batas' : 'Mencapai batas') : badge[budget.status] }}</span></div>
                <div class="mt-6 flex items-baseline justify-between gap-3"><p class="break-words text-xl font-bold text-[#294538]">{{ rupiah(budget.spent) }}</p><p class="shrink-0 text-sm text-[#77897d]">dari {{ rupiah(budget.limit_amount) }}</p></div>
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-[#edf1eb]" role="progressbar" :aria-valuenow="Math.min(budget.percentage, 100)" aria-valuemin="0" aria-valuemax="100" :aria-label="`${budget.category_name}: ${budget.percentage}% terpakai`"><div class="h-full rounded-full transition-all" :class="budget.status === 'over' ? 'bg-[#d66c5d]' : budget.status === 'warning' ? 'bg-[#e7a44f]' : 'bg-[#34826f]'" :style="{ width: `${Math.min(budget.percentage, 100)}%` }"></div></div>
                <div class="mt-3 flex justify-between text-sm"><span class="text-[#728478]">{{ budget.percentage }}% terpakai</span><span class="font-semibold" :class="budget.remaining.startsWith('-') ? 'text-[#b5433b]' : 'text-[#256958]'">{{ budget.remaining.startsWith('-') ? 'Lebih' : 'Sisa' }} {{ rupiah(budget.remaining.replace(/^-/, '')) }}</span></div>
                <div class="mt-6 flex flex-wrap gap-2 border-t border-[#edf0eb] pt-4"><Link v-if="budget.inherited" :href="`/budgets/create?month=${month}&category_id=${budget.category_id}`" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#edf5ee] px-4 text-sm font-bold text-[#256958] hover:bg-[#dfece0]"><Plus :size="16" aria-hidden="true" /> Atur bulan ini</Link><template v-else><Link :href="`/budgets/${budget.id}/edit`" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#edf5ee] px-4 text-sm font-bold text-[#256958] hover:bg-[#dfece0]"><Pencil :size="16" aria-hidden="true" /> Ubah</Link><button class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-bold text-[#ad5147] hover:bg-[#fff0ed] disabled:opacity-50" type="button" :disabled="pending === budget.id" @click="remove(budget)"><Trash2 :size="16" aria-hidden="true" /> Hapus</button></template></div>
            </article>
        </section>
        <section v-else class="mt-6 rounded-3xl border border-dashed border-[#cbdacf] bg-white p-10 text-center"><Target :size="36" class="mx-auto text-[#8caf97]" aria-hidden="true" /><h2 class="mt-4 text-lg font-bold">Belum ada anggaran</h2><p class="mt-2 text-sm text-[#748579]">Mulai dengan menetapkan batas untuk kategori pengeluaran.</p><Link :href="`/budgets/create?month=${month}`" class="mt-5 inline-flex min-h-11 items-center gap-2 font-bold text-[#256958] hover:underline">Atur anggaran <ArrowRight :size="17" aria-hidden="true" /></Link></section>
    </AppLayout>
</template>
