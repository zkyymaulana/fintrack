<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Wrench } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Bike = { id: number; name: string; current_odometer_km: number };
type Item = { id: number; name: string; kind: 'part' | 'service'; interval_km: number; baseline_odometer_km: number; note: string | null; is_active: boolean };
const props = defineProps<{ motorcycle: Bike; item: Item | null }>();
const form = useForm({
    name: props.item?.name ?? '',
    kind: props.item?.kind ?? 'part',
    interval_km: props.item?.interval_km ?? 2000,
    baseline_odometer_km: props.item?.baseline_odometer_km ?? props.motorcycle.current_odometer_km,
    note: props.item?.note ?? '',
    is_active: props.item?.is_active ?? true,
});
function submit() {
    if (props.item) form.put(`/motorcycles/${props.motorcycle.id}/maintenance-items/${props.item.id}`);
    else form.post(`/motorcycles/${props.motorcycle.id}/maintenance-items`);
}
</script>

<template>
    <Head :title="item ? 'Ubah jadwal perawatan' : 'Tambah jadwal perawatan'" />
    <AppLayout :title="item ? 'Ubah item perawatan' : 'Tambah item perawatan'" :subtitle="`Atur pengingat untuk ${motorcycle.name} berdasarkan jarak tempuh.`">
        <Link :href="`/motorcycles/${motorcycle.id}`" class="mb-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline"><ArrowLeft :size="17" aria-hidden="true" /> Kembali ke motor</Link>
        <form class="surface-shadow max-w-2xl space-y-5 rounded-3xl border border-[#e3ebe3] bg-white p-6 sm:p-9" @submit.prevent="submit">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#256958]"><Wrench :size="22" aria-hidden="true" /></span>
            <div><label for="maintenance-name" class="mb-2 block text-sm font-semibold">Nama sparepart atau perawatan</label><input id="maintenance-name" v-model="form.name" class="field" maxlength="100" placeholder="Contoh: Ganti oli mesin atau ganti vanbelt" required /><p v-if="form.errors.name" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.name }}</p></div>
            <div><label for="maintenance-kind" class="mb-2 block text-sm font-semibold">Jenis item</label><select id="maintenance-kind" v-model="form.kind" class="field" required><option value="part">Sparepart</option><option value="service">Perawatan</option></select><p v-if="form.errors.kind" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.kind }}</p></div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label for="maintenance-interval" class="mb-2 block text-sm font-semibold">Ulangi setiap</label><div class="relative"><input id="maintenance-interval" v-model.number="form.interval_km" class="field pr-14" type="number" min="1" max="2000000" step="1" required /><span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm text-[#819186]">km</span></div><p v-if="form.errors.interval_km" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.interval_km }}</p></div><div><label for="maintenance-baseline" class="mb-2 block text-sm font-semibold">Terakhir dilakukan pada</label><div class="relative"><input id="maintenance-baseline" v-model.number="form.baseline_odometer_km" class="field pr-14" type="number" min="0" :max="motorcycle.current_odometer_km" step="1" required /><span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm text-[#819186]">km</span></div><p class="mt-2 text-xs text-[#748579]">Odometer motor saat ini {{ motorcycle.current_odometer_km.toLocaleString('id-ID') }} km.</p><p v-if="form.errors.baseline_odometer_km" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.baseline_odometer_km }}</p></div></div>
            <div><label for="maintenance-note" class="mb-2 block text-sm font-semibold">Catatan <span class="font-normal text-[#819186]">(opsional)</span></label><textarea id="maintenance-note" v-model="form.note" class="field min-h-24" maxlength="1000" placeholder="Merek oli atau detail yang ingin diingat" /></div>
            <label v-if="item" class="flex min-h-11 items-center gap-3 text-sm font-semibold text-[#294538]"><input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-[#256958]" />Aktifkan pengingat ini</label>
            <div class="rounded-2xl bg-[#f7faf6] p-4 text-sm leading-relaxed text-[#60766a]">Kilometer servis berikutnya dihitung dari catatan servis terakhir ditambah interval. Contoh: oli terakhir di 10.000 km, interval 2.000 km, jatuh tempo di 12.000 km.</div>
            <div class="flex flex-wrap gap-3 border-t border-[#edf0eb] pt-5"><button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan jadwal' }}</button><Link :href="`/motorcycles/${motorcycle.id}`" class="inline-flex min-h-12 items-center rounded-xl px-5 text-sm font-semibold text-[#61766b] hover:bg-[#f2f6f1]">Batal</Link></div>
        </form>
    </AppLayout>
</template>
