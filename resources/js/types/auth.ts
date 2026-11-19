export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role?: 'user' | 'administrator';
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    is_administrator: boolean;
};

export type Site = {
    name: string;
    tagline: string;
    description: string;
    footer_text: string;
    logo_url: string | null;
    favicon_url: string | null;
    registration_open: boolean;
    contact: {
        support_email: string;
        phone: string;
        address: string;
    };
    social: Partial<Record<'facebook' | 'x' | 'instagram' | 'youtube', string>>;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
