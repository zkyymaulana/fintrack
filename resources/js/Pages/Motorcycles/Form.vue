<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Bike } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Motorcycle } from '../../types/motorcycle';

const props = defineProps<{ motorcycle: Motorcycle | null }>();
const form = useForm({
    name: props.motorcycle?.name ?? '',
    brand: props.motorcycle?.brand ?? '',
    model: props.motorcycle?.model ?? '',
    license_plate: props.motorcycle?.license_plate ?? '',
    current_odometer_km: props.motorcycle?.current_odometer_km ?? 0,
});
function submit() {
    if (props.motorcycle) {
        form.transform((data) => {
            return {
                name: data.name,
                brand: data.brand,
                model: data.model,
                license_plate: data.license_plate,
            };
        }).put(`/motorcycles/${props.motorcycle.id}`);
    } else {
        form.post('/motorcycles');
    }
}
</script>

<template>
    <Head :title="motorcycle ? 'Ubah motor' : 'Tambah motor'" />
    <AppLayout :title="motorcycle ? 'Ubah motor' : 'Tambah motor'" subtitle="Catat identitas motor dan kilometer saat ini sebagai titik awal perawatan.">
        <Link href="/motorcycles" class="mb-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline"><ArrowLeft :size="17" aria-hidden="true" /> Kembali ke motor</Link>
        <form class="surface-shadow max-w-2xl space-y-5 rounded-3xl border border-[#e3ebe3] bg-white p-6 sm:p-9" @submit.prevent="submit">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#256958]"><Bike :size="23" aria-hidden="true" /></span>
            <div><label for="motorcycle-name" class="mb-2 block text-sm font-semibold">Nama motor</label><input id="motorcycle-name" v-model="form.name" class="field" maxlength="100" placeholder="Contoh: Motor harian" required /><p v-if="form.errors.name" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.name }}</p></div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label for="motorcycle-brand" class="mb-2 block text-sm font-semibold">Merek</label><input id="motorcycle-brand" v-model="form.brand" class="field" maxlength="100" placeholder="Contoh: Honda" /></div><div><label for="motorcycle-model" class="mb-2 block text-sm font-semibold">Model</label><input id="motorcycle-model" v-model="form.model" class="field" maxlength="100" placeholder="Contoh: Vario 160" /></div></div>
            <div><label for="motorcycle-plate" class="mb-2 block text-sm font-semibold">Nomor polisi <span class="font-normal text-[#819186]">(opsional)</span></label><input id="motorcycle-plate" v-model="form.license_plate" class="field" maxlength="20" placeholder="Contoh: B 1234 XYZ" /></div>
            <div v-if="!motorcycle"><label for="motorcycle-odometer" class="mb-2 block text-sm font-semibold">Kilometer saat ini</label><div class="relative"><input id="motorcycle-odometer" v-model.number="form.current_odometer_km" class="field pr-14" type="number" min="0" max="2000000" step="1" required /><span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm text-[#819186]">km</span></div><p class="mt-2 text-xs leading-relaxed text-[#748579]">Kilometer ini menjadi dasar untuk menghitung kapan item perawatan berikutnya jatuh tempo.</p><p v-if="form.errors.current_odometer_km" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.current_odometer_km }}</p></div>
            <div v-else class="rounded-2xl bg-[#f7faf6] p-4 text-sm text-[#60766a]">Odometer saat ini {{ motorcycle.current_odometer_km.toLocaleString('id-ID') }} km. Perbarui kilometer dari halaman catatan motor agar riwayat pembacaan tetap tersimpan.</div>
            <div class="flex flex-wrap gap-3 border-t border-[#edf0eb] pt-5"><button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan motor' }}</button><Link href="/motorcycles" class="inline-flex min-h-12 items-center rounded-xl px-5 text-sm font-semibold text-[#61766b] hover:bg-[#f2f6f1]">Batal</Link></div>
        </form>
    </AppLayout>
</template>
