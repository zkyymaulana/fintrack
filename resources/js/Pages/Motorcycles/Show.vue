<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Bike, Gauge, Pencil, Plus, Trash2, Wrench } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Motorcycle, MotorcycleMaintenanceItem, MotorcycleMaintenanceRecord, MotorcycleOdometerReading } from '../../types/motorcycle';
import { rupiah, tanggal } from '../../utils/finance';

const props = defineProps<{
    motorcycle: Motorcycle;
    maintenanceItems: MotorcycleMaintenanceItem[];
    maintenanceRecords: MotorcycleMaintenanceRecord[];
    odometerReadings: MotorcycleOdometerReading[];
}>();
const odometerForm = useForm({ odometer_km: props.motorcycle.current_odometer_km, note: '' });
function saveOdometer() {
    odometerForm.post(`/motorcycles/${props.motorcycle.id}/odometer-readings`, {
        onSuccess: () => {
            odometerForm.odometer_km = props.motorcycle.current_odometer_km;
            odometerForm.note = '';
        },
    });
}
function removeItem(item: MotorcycleMaintenanceItem) {
    if (!window.confirm(`Hapus jadwal ${item.name}? Riwayat servis yang sudah dicatat tetap disimpan.`)) return;
    router.delete(`/motorcycles/${props.motorcycle.id}/maintenance-items/${item.id}`);
}
function archiveMotorcycle() {
    if (!window.confirm(`Arsipkan ${props.motorcycle.name}? Riwayat kilometer dan servis tetap disimpan.`)) return;
    router.delete(`/motorcycles/${props.motorcycle.id}`);
}
function kindLabel(kind: 'part' | 'service') {
    return kind === 'part' ? 'Sparepart' : 'Perawatan';
}
</script>

<template>
    <Head :title="motorcycle.name" />
    <AppLayout :title="motorcycle.name" subtitle="Riwayat odometer dan perawatan motor.">
        <Link href="/motorcycles" class="mb-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline"><ArrowLeft :size="17" aria-hidden="true" /> Semua motor</Link>

        <section class="rounded-3xl bg-[#194b40] p-5 text-white sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-4"><div class="flex min-w-0 items-center gap-4"><span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-[#d3efda]"><Bike :size="24" aria-hidden="true" /></span><div class="min-w-0"><h2 class="truncate text-xl font-bold">{{ motorcycle.name }}</h2><p class="mt-1 text-sm text-[#c7e4d2]">{{ [motorcycle.brand, motorcycle.model].filter(Boolean).join(' ') || 'Merek dan model belum diisi' }}<span v-if="motorcycle.license_plate"> · {{ motorcycle.license_plate }}</span></p></div></div><div class="flex gap-2"><Link :href="`/motorcycles/${motorcycle.id}/edit`" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-white/10 px-3 text-sm font-semibold text-white hover:bg-white/15"><Pencil :size="15" aria-hidden="true" /> Ubah</Link><button type="button" class="inline-flex min-h-10 items-center gap-2 rounded-xl bg-white/10 px-3 text-sm font-semibold text-white hover:bg-white/15" @click="archiveMotorcycle"><Trash2 :size="15" aria-hidden="true" /> Arsipkan</button></div></div>
            <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end"><div><p class="text-sm text-[#c7e4d2]">Odometer saat ini</p><p class="mt-1 text-3xl font-bold">{{ motorcycle.current_odometer_km.toLocaleString('id-ID') }} <span class="text-base font-semibold text-[#c7e4d2]">km</span></p></div><form class="flex flex-wrap items-end gap-2 rounded-2xl bg-white/10 p-3" @submit.prevent="saveOdometer"><div><label for="new-odometer" class="mb-1.5 block text-xs font-semibold text-[#d5e9dc]">Perbarui kilometer</label><input id="new-odometer" v-model.number="odometerForm.odometer_km" class="field min-h-10 w-36 border-white/30 bg-white text-[#294538]" type="number" min="0" max="2000000" step="1" required /></div><div><label for="odometer-note" class="mb-1.5 block text-xs font-semibold text-[#d5e9dc]">Catatan (opsional)</label><input id="odometer-note" v-model="odometerForm.note" class="field min-h-10 w-44 border-white/30 bg-white text-[#294538]" maxlength="255" placeholder="Contoh: cek odometer di bengkel" /></div><button type="submit" class="inline-flex min-h-10 items-center rounded-xl bg-white px-3 text-sm font-bold text-[#205c4b] disabled:opacity-60" :disabled="odometerForm.processing">Simpan</button></form></div>
            <p v-if="odometerForm.errors.odometer_km" role="alert" class="mt-2 text-sm text-[#ffe1d9]">{{ odometerForm.errors.odometer_km }}</p>
        </section>

        <section class="mt-7 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7" aria-labelledby="maintenance-schedules-heading">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="maintenance-schedules-heading" class="text-lg font-bold text-[#294538]">Jadwal sparepart dan perawatan</h2><p class="mt-1 text-sm text-[#748579]">Pengingat dihitung dari kilometer servis terakhir.</p></div><Link :href="`/motorcycles/${motorcycle.id}/maintenance-items/create`" class="primary-button"><Plus :size="17" aria-hidden="true" /> Tambah item</Link></div>
            <div v-if="maintenanceItems.length" class="mt-5 grid gap-3 md:grid-cols-2">
                <article v-for="item in maintenanceItems" :key="item.id" class="rounded-2xl border p-4" :class="!item.is_active ? 'border-[#e5eae5] bg-[#f8faf8]' : item.status === 'due' ? 'border-[#edc5b9] bg-[#fff8f5]' : item.status === 'soon' ? 'border-[#f0dfb7] bg-[#fffcf4]' : 'border-[#e3ebe3] bg-white'">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="font-bold text-[#294538]">{{ item.name }}</h3><span class="rounded-full bg-[#edf4ee] px-2 py-0.5 text-[10px] font-bold text-[#527762]">{{ kindLabel(item.kind) }}</span></div><p class="mt-1 text-sm text-[#748579]">Setiap {{ item.interval_km.toLocaleString('id-ID') }} km · terakhir {{ item.last_serviced_km.toLocaleString('id-ID') }} km</p></div><span v-if="!item.is_active" class="rounded-full bg-[#edf0eb] px-2 py-1 text-[10px] font-bold text-[#718478]">Nonaktif</span><span v-else-if="item.status === 'due'" class="rounded-full bg-[#fae6df] px-2 py-1 text-[10px] font-bold text-[#a45340]">Jatuh tempo</span><span v-else-if="item.status === 'soon'" class="rounded-full bg-[#fbf0d4] px-2 py-1 text-[10px] font-bold text-[#896722]">Segera</span></div>
                    <p class="mt-3 text-sm font-bold" :class="item.status === 'due' ? 'text-[#a45340]' : 'text-[#294538]'">{{ item.next_due_km.toLocaleString('id-ID') }} km<span v-if="item.is_active"> · {{ item.km_remaining <= 0 ? `lewat ${Math.abs(item.km_remaining).toLocaleString('id-ID')} km` : `${item.km_remaining.toLocaleString('id-ID')} km lagi` }}</span></p>
                    <p v-if="item.note" class="mt-2 whitespace-pre-line text-xs leading-relaxed text-[#819186]">{{ item.note }}</p>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-[#edf0eb] pt-2"><Link :href="`/motorcycles/${motorcycle.id}/maintenance-records/create?item=${item.id}`" class="min-h-9 inline-flex items-center text-xs font-bold text-[#256958] hover:underline">Catat servis</Link><Link :href="`/motorcycles/${motorcycle.id}/maintenance-items/${item.id}/edit`" class="min-h-9 inline-flex items-center text-xs font-semibold text-[#60766a] hover:underline">Ubah</Link><button type="button" class="min-h-9 text-xs font-semibold text-[#a74439] hover:underline" @click="removeItem(item)">Hapus jadwal</button></div>
                </article>
            </div>
            <div v-else class="mt-5 rounded-2xl border border-dashed border-[#d5e2d7] px-5 py-8 text-center"><Wrench :size="26" class="mx-auto text-[#8caf97]" aria-hidden="true" /><p class="mt-3 font-semibold text-[#294538]">Belum ada jadwal servis</p><p class="mt-1 text-sm text-[#748579]">Tambahkan pengingat seperti ganti oli setiap 2.000 km atau ganti vanbelt.</p></div>
        </section>

        <section class="mt-7 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7" aria-labelledby="maintenance-history-heading">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="maintenance-history-heading" class="text-lg font-bold text-[#294538]">Riwayat servis</h2><p class="mt-1 text-sm text-[#748579]">Catatan servis dapat dihubungkan ke pengeluaran dompet.</p></div><Link :href="`/motorcycles/${motorcycle.id}/maintenance-records/create`" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-[#bdd5c5] px-4 text-sm font-bold text-[#256958] hover:bg-[#f3f8f3]"><Plus :size="16" aria-hidden="true" /> Catat perawatan</Link></div>
            <ul v-if="maintenanceRecords.length" class="mt-4 divide-y divide-[#edf0eb]"><li v-for="record in maintenanceRecords" :key="record.id" class="flex flex-wrap items-start justify-between gap-3 py-4"><div class="flex min-w-0 gap-3"><span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#edf4ee] text-[#527762]"><Wrench :size="16" aria-hidden="true" /></span><div class="min-w-0"><h3 class="break-words font-semibold text-[#294538]">{{ record.title }}</h3><p class="mt-1 text-xs text-[#748579]">{{ tanggal(record.performed_on) }} · {{ record.odometer_km.toLocaleString('id-ID') }} km</p><p v-if="record.note" class="mt-1 whitespace-pre-line text-xs leading-relaxed text-[#819186]">{{ record.note }}</p><p v-if="record.wallet_name" class="mt-1 text-xs font-medium text-[#527762]">Dibayar dari {{ record.wallet_name }}</p></div></div><p v-if="record.cost !== null" class="shrink-0 text-sm font-bold text-[#294538]">{{ rupiah(record.cost) }}</p></li></ul>
            <div v-else class="mt-4 rounded-2xl bg-[#f7faf6] px-5 py-8 text-center"><p class="font-semibold text-[#294538]">Belum ada riwayat servis</p><p class="mt-1 text-sm text-[#748579]">Setelah servis, catat item, kilometer, dan biayanya di sini.</p></div>
        </section>

        <section class="mt-7 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7" aria-labelledby="odometer-history-heading"><div class="flex items-center gap-2"><Gauge :size="18" class="text-[#527762]" aria-hidden="true" /><h2 id="odometer-history-heading" class="font-bold text-[#294538]">Riwayat odometer</h2></div><ul v-if="odometerReadings.length" class="mt-3 divide-y divide-[#edf0eb]"><li v-for="reading in odometerReadings" :key="reading.id" class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm"><span><strong class="font-semibold text-[#294538]">{{ reading.odometer_km.toLocaleString('id-ID') }} km</strong><span class="ml-2 text-xs text-[#748579]">{{ reading.source === 'maintenance' ? 'Dari catatan servis' : reading.source === 'initial' ? 'Odometer awal' : 'Pembaruan odometer' }}</span><span v-if="reading.note" class="mt-1 block text-xs text-[#819186]">{{ reading.note }}</span></span><time class="text-xs text-[#748579]" :datetime="reading.recorded_at">{{ tanggal(reading.recorded_at) }}</time></li></ul><p v-else class="mt-3 text-sm text-[#748579]">Belum ada pembaruan odometer.</p></section>
    </AppLayout>
</template>
