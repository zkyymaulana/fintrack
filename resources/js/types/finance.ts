export type FinanceType = 'income' | 'expense' | 'transfer';

export type Transaction = {
    id: number;
    title: string;
    type: FinanceType;
    amount: string;
    admin_fee: string;
    date: string;
    note: string | null;
    receipt_image_path?: string | null;
    is_ocr?: boolean;
    category_id: number;
    category_name: string | null;
    wallet_id: number;
    wallet_name: string | null;
    to_wallet_id: number | null;
    to_wallet_name: string | null;
};

export type Wallet = {
    id: number;
    name: string;
    wallet_type: 'regular' | 'investment';
    balance: string;
    auto_deduction_enabled?: boolean;
    auto_deduction_type?: 'expense' | 'transfer' | null;
    auto_deduction_to_wallet_id?: number | null;
    auto_deduction_to_wallet_name?: string | null;
    auto_deduction_amount?: string | null;
    auto_deduction_category_id?: number | null;
    auto_deduction_title?: string | null;
    auto_deduction_note?: string | null;
    auto_deduction_frequency?: 'daily' | 'weekly' | 'monthly' | null;
    auto_deduction_next_run_on?: string | null;
    auto_deduction_last_run_on?: string | null;
    auto_deduction_last_status?: 'charged' | 'insufficient_balance' | 'category_missing' | 'destination_missing' | null;
};
export type Option = { id: number; name: string; wallet_type?: 'regular' | 'investment' };
export type Category = Option & { type: 'income' | 'expense' };

export type Budget = {
    id: number;
    category_id: number;
    category_name: string;
    limit_amount: string;
    spent: string;
    remaining: string;
    percentage: number;
    status: 'safe' | 'warning' | 'over';
    inherited: boolean;
};

export type TrendPoint = { month: string; income: string; expense: string };
export type ExpenseCategory = {
    category_id: number;
    category_name: string;
    amount: string;
    count: number;
    percentage: number;
    transactions: { id: number; title: string; note: string | null; date: string; amount: string }[];
};
