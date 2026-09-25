export type AdminUser = {
    id: number;
    name: string;
    email: string;
    avatar: string;
    is_admin: boolean;
    deactivated_at: string | null;
    last_login_at: string | null;
    created_at: string | null;
};

export type AdminUserDetail = AdminUser & {
    is_environment_admin: boolean;
    email_verified_at: string | null;
};

export type AbilityCheck = {
    allowed: boolean;
    reason: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};
