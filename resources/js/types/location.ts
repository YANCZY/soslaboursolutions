export type LocationOption = {
    id: number;
    client_id: number;
    site_id: number | null;
    label: string;
    job_role: string | null;
    salary: string | number | null;
    travel_allowance: string | number | null;
    travel_allowance_currency: string | null;
    start_shift: string | null;
    end_shift: string | null;
};
