import type { FinanceType } from '../types/finance';

const money = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const date = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' });

export function rupiah(value: string | number): string {
    const parts = String(value).match(/^(-?)(\d+)(?:\.(\d{1,2}))?$/);
    if (!parts) return money.format(Number(value));

    const cents = (parts[3] ?? '').padEnd(2, '0');
    return `${parts[1]}${money.format(BigInt(parts[2]))}${Number(cents) ? `,${cents}` : ''}`;
}
export const tanggal = (value: string) => date.format(new Date(`${value.slice(0, 10)}T00:00:00Z`));
export const typeLabel = (type: FinanceType) => ({ income: 'Pemasukan', expense: 'Pengeluaran', transfer: 'Transfer' })[type];
