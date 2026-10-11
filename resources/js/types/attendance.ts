export type AttendanceWorkDetail = {
    client_id: number;
    start_shift: string | null;
    end_shift: string | null;
    site_id: number | null;
};

export type AttendanceUser = {
    first_name: string;
    last_name: string;
    company_work_details?: AttendanceWorkDetail[];
};

export type AttendanceCompany = {
    id: number;
    company_name: string;
};

export type AttendanceSelectableCompany = AttendanceCompany & {
    start_shift: string | null;
    end_shift: string | null;
};

export type Attendance = {
    id: number;
    check_in_date: string;
    status: 'checked_in' | 'lunch_break' | 'checked_out';
    approval_status: 'pending' | 'approved' | 'rejected' | null;
    check_in_time: string | null;
    check_out_time: string | null;
    lunch_start_time: string | null;
    lunch_end_time: string | null;
    total_working_seconds: number;
    covering_for?: string | null;
    user?: AttendanceUser | null;
    client?: AttendanceCompany | null;
    site_id?: number | null;
    site?: {
        id: number;
        site_name: string | null;
    } | null;
};
