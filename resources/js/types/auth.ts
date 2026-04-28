export type User = {
    id: number;
    first_name: string;
    last_name: string;
    user_type_id: number;
    email: string;
    phone?: string | null;
    mobile?: string | null;
    status: 'active' | 'inactive';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
