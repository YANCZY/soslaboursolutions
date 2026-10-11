export type Site = {
    id: number;
    site_name: string | null;
    company: {
        id: number;
        company_name: string;
    };
    is_active: boolean;
    street: string;
    city: string;
    state: string;
    postcode: string;
    country: string;
};
