export type Company = {
    id: number;
    company_name: string;
    company_type: string | null;
    trade: string | null;
    industry: string | null;
    industry_description: string | null;
    phone: string | null;
    website: string | null;
    company_address: string | null;
    company_address_2: string | null;
    company_address_city: string | null;
    company_address_state: string | null;
    company_address_country: string | null;
    is_active: boolean;
    can_delete: boolean;
};
