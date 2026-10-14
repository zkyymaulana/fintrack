export type FinanceType = 'income' | 'expense' | 'transfer';

export type Transaction = {
    id: number;
    title: string;
    type: FinanceType;
    amount: string;
    admin_fee: string;
    date: string;
    note: string | null;
    category_id: number;
    category_name: string | null;
    wallet_id: number;
    wallet_name: string | null;
    to_wallet_id: number | null;
    to_wallet_name: string | null;
};

export type Wallet = { id: number; name: string; balance: string };
export type Option = { id: number; name: string };
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
export type ExpenseCategory = { category_id: number; category_name: string; amount: string; count: number; percentage: number };
