export type User = {
    id: number;
    first_name: string;
    last_name: string;
    user_type_id: number;
    email: string;
    phone?: string | null;
    mobile?: string | null;
    status: 'active' | 'inactive' | 'pending';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type AppNotification = {
    id: string;
    title: string;
    message: string;
    week_label?: string | null;
    url: string;
    created_at: string | null;
    is_read: boolean;
};

export type Auth = {
    user: User;
    user_type: string | null;
    notifications: AppNotification[];
    unread_notifications_count: number;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
