export type AccountStatus = 'active' | 'suspended' | 'banned' | 'deleted';

export type LedgerRow = {
    id: number;
    entry_type: string;
    amount_cents: number;
    description: string | null;
    created_at: string | null;
};

export type WalletSummary = {
    pending_cents: number;
    available_cents: number;
    reserved_cents: number;
    lifetime_credited_cents: number;
    recent_ledger: LedgerRow[];
};

export type MonthTotals = {
    credited_cents: number;
    debited_cents: number;
    net_cents: number;
    entries: number;
};

export type EarningsMonth = {
    month: string;
    label: string;
    previous_month: string;
    previous_label: string;
    available_months: { value: string; label: string }[];
    current: MonthTotals;
    previous: MonthTotals;
    change: {
        credited_percent: number | null;
        debited_percent: number | null;
        net_percent: number | null;
        entries_percent: number | null;
    };
    month_ledger: LedgerRow[];
};

export type PeopleAccount = {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    role: 'user' | 'administrator';
    verified: boolean;
    two_factor_enabled?: boolean;
    banned_at: string | null;
    ban_reason: string | null;
    suspended_until: string | null;
    suspension_reason: string | null;
    is_banned: boolean;
    is_suspended: boolean;
    deleted_at: string | null;
    created_at: string | null;
    updated_at?: string | null;
    status: AccountStatus;
    wallet?: WalletSummary;
    earnings?: EarningsMonth;
};

export type PeopleCapabilities = {
    can_moderate: boolean;
    show_wallet: boolean;
};

export type PeoplePageProps = {
    title: string;
    role: 'user' | 'administrator';
    status: AccountStatus;
    search: string;
    accounts: PeopleAccount[];
    capabilities: PeopleCapabilities;
};

export type PeopleDetailRoutes = {
    index: string;
    show: string;
    update: string;
    destroy: string;
    restore: string;
    ban?: string;
    unban?: string;
    suspend?: string;
    unsuspend?: string;
};

export type PeopleDetailProps = {
    account: PeopleAccount;
    capabilities: PeopleCapabilities;
    routes: PeopleDetailRoutes;
    filters?: {
        month: string;
    };
};
