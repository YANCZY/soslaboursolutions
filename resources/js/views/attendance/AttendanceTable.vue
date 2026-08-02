<script setup lang="ts">
import { ChevronLeft, ChevronRight, EllipsisVertical, FileText, Info, Pencil, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ExportPdfDialog from '@/components/ExportPdfDialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';


// ========
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { exportBrandedPdf } from '@/lib/exportBrandedPdf';
import EditAttendance from '@/views/attendance/EditAttendance.vue';

type AttendanceWorkDetail = {
    client_id: number;
    start_shift: string | null;
    end_shift: string | null;
};

type AttendanceUser = {
    first_name: string;
    last_name: string;
    company_work_details?: AttendanceWorkDetail[];
};

type AttendanceCompany = {
    id: number;
    company_name: string;
};

type Attendance = {
    id: number;
    check_in_date: string;
    check_in_time: string | null;
    check_out_time: string | null;
    lunch_start_time: string | null;
    lunch_end_time: string | null;
    total_working_seconds: number | null;
    approval_status?: 'pending' | 'approved' | 'rejected' | null;
    user?: AttendanceUser | null;
    client?: AttendanceCompany | null;
};

const formatCompanyName = (record: Attendance) => {
    return record.client?.company_name ?? '-';
};

const companyShiftLabel = (record: Attendance) => {
    const startShift = shiftStartTime(record);
    const endShift = shiftEndTime(record);

    if (!startShift || !endShift) {
        return 'No shift assigned for this company.';
    }

    return `${formatTime(startShift)} - ${formatTime(endShift)}`;
};

const assignedWorkDetail = (record: Attendance) => {
    if (!record.client?.id) {
        return null;
    }

    return record.user?.company_work_details?.find(
        (detail) => detail.client_id === record.client?.id,
    ) ?? null;
};

const props = defineProps<{
    attendanceRecords: Attendance[];
}>();

const emit = defineEmits<{
    'attendance-updated': [];
}>();


const editDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const submitApprovalDialogOpen = ref(false);
const exportDialogOpen = ref(false);
const selectedRecord = ref<Attendance | null>(null);
const selectedWeekRecords = ref<Attendance[]>([]);
const formError = ref('');

const editForm = ref({
    check_in_date: '',
    check_in_time: '',
    check_out_time: '',
    lunch_start_time: '',
    lunch_end_time: '',
});

const search = ref('');
const rowsPerPage = ref('10');
const currentPage = ref(1);
const selectedAttendanceIds = ref<number[]>([]);

const isAttendanceSelected = (attendanceId: number) => {
    return selectedAttendanceIds.value.includes(attendanceId);
};

const toggleAttendanceSelection = (
    record: Attendance,
    selected: boolean | 'indeterminate',
) => {
    if (!isSubmittable(record.approval_status)) {
        toast.error(approvalLockedMessage(record.approval_status));

        return;
    }

    if (selected === true) {
        if (!selectedAttendanceIds.value.includes(record.id)) {
            selectedAttendanceIds.value = [
                ...selectedAttendanceIds.value,
                record.id,
            ];
        }

        return;
    }

    selectedAttendanceIds.value = selectedAttendanceIds.value.filter(
        (id) => id !== record.id,
    );
};

const groupSelectionState = (
    records: Attendance[],
): boolean | 'indeterminate' => {
    const selectableRecords = records.filter((record) =>
        isSubmittable(record.approval_status),
    );

    if (selectableRecords.length === 0) {
        return false;
    }

    const selectedRecordCount = selectableRecords.filter((record) =>
        selectedAttendanceIds.value.includes(record.id),
    ).length;

    if (selectedRecordCount === 0) {
        return false;
    }

    if (selectedRecordCount === selectableRecords.length) {
        return true;
    }

    return 'indeterminate';
};

const selectedGroupRecords = (records: Attendance[]) => {
    return records.filter(
        (record) =>
            selectedAttendanceIds.value.includes(record.id) &&
            isSubmittable(record.approval_status),
    );
};

const hasSelectedGroupRecords = (records: Attendance[]) => {
    return selectedGroupRecords(records).length > 0;
};

const openSubmitApprovalDialog = (records: Attendance[]) => {
    const recordsToSubmit = selectedGroupRecords(records);

    if (recordsToSubmit.length === 0) {
        toast.error('Please select at least one attendance record to submit.');

        return;
    }

    selectedWeekRecords.value = recordsToSubmit;
    submitApprovalDialogOpen.value = true;
};

const submitGroupForApproval = async () => {
    const response = await fetch('/attendance/submit-for-approval', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN':
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
        body: JSON.stringify({
            attendance_ids: selectedWeekRecords.value.map((record) => record.id),
        }),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        toast.error(data?.message ?? 'Unable to submit timesheet for approval.');

        return;
    }

    submitApprovalDialogOpen.value = false;
    selectedWeekRecords.value = [];
    toast.success('Timesheet has been sent for approval.');
    emit('attendance-updated');
};

const formatUserName = (user?: AttendanceUser | null) => {
    if (!user) {
        return '-';
    }

    return [user.first_name, user.last_name].filter(Boolean).join(' ') || '-';
};

const formatDate = (date: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    }).format(new Date(`${date}T00:00:00`));
};

const startOfWeek = (date: string) => {
    const value = new Date(`${date}T00:00:00`);
    const day = value.getDay(); // Sunday = 0, Monday = 1
    const diff = day === 0 ? -6 : 1 - day;

    value.setDate(value.getDate() + diff);

    return value;
};

const formatWeekRange = (date: string) => {
    const monday = startOfWeek(date);
    const sunday = new Date(monday);

    sunday.setDate(monday.getDate() + 6);

    const monthLabel = monday.toLocaleDateString('en-US', {
        month: 'short',
    });

    const startDay = monday.toLocaleDateString('en-US', {
        day: '2-digit',
    });

    const endDay = sunday.toLocaleDateString('en-US', {
        day: '2-digit',
    });

    const yearLabel = sunday.toLocaleDateString('en-US', {
        year: 'numeric',
    });

    return `${monthLabel} ${startDay} - ${monthLabel} ${endDay} ${yearLabel}`;
};

const formatApprovalStatus = (status?: string | null) => {
    if (!status) {
        return '-';
    }

    if (status === 'pending') {
        return 'Pending';
    }

    if (status === 'approved') {
        return 'Approved';
    }

    if (status === 'rejected') {
        return 'Rejected';
    }

    return '-';
};

const isApprovalLocked = (status?: string | null) => {
    return status === 'pending' || status === 'approved' || status === 'rejected';
};

const isSubmittable = (status?: string | null) => {
    return !status;
};

const approvalLockedMessage = (status?: string | null) => {
    if (status === 'pending') {
        return 'This attendance is already for approval.';
    }

    if (status === 'approved') {
        return 'This attendance has already been approved.';
    }

    if (status === 'rejected') {
        return 'This attendance has already been rejected.';
    }

    return '';
};

const approvalStatusClass = (status?: string | null) => {
    if (status === 'pending') {
        return 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/50 dark:bg-orange-950/30 dark:text-orange-300';
    }

    if (status === 'approved') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300';
    }

    if (status === 'rejected') {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300';
    }

    return 'border-border bg-muted/40 text-muted-foreground';
};

const formatTime = (time: string | null) => {
    if (!time) {
        return '-';
    }

    const [hours, minutes] = time.split(':');

    return new Date(2000, 0, 1, Number(hours), Number(minutes))
        .toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
};

const toTimeInput = (time: string | null) => {
    return time ? time.slice(0, 5) : '';
};



const formatDuration = (seconds: number | null | undefined) => {
    if (!seconds || seconds <= 0) {
        return '-';
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    return `${hours}h ${minutes}m`;
};

const secondsBetweenTimes = (start: string | null, end: string | null) => {
    if (!start || !end) {
        return 0;
    }

    const [startHours, startMinutes, startSeconds = '0'] = start.split(':');
    const [endHours, endMinutes, endSeconds = '0'] = end.split(':');

    const startTotal =
        Number(startHours) * 3600 +
        Number(startMinutes) * 60 +
        Number(startSeconds);

    const endTotal =
        Number(endHours) * 3600 +
        Number(endMinutes) * 60 +
        Number(endSeconds);

    return Math.max(0, endTotal - startTotal);
};

const timeToSeconds = (time: string | null) => {
    if (!time) {
        return null;
    }

    const [hours, minutes, seconds = '0'] = time.split(':');

    return Number(hours) * 3600 + Number(minutes) * 60 + Number(seconds);
};

const overlapSeconds = (
    start: number,
    end: number,
    windowStart: number,
    windowEnd: number,
) => {
    return Math.max(0, Math.min(end, windowEnd) - Math.max(start, windowStart));
};

const totalLunchBreakSeconds = (record: Attendance) => {
    return secondsBetweenTimes(record.lunch_start_time, record.lunch_end_time);
};

const shiftStartTime = (record: Attendance) => {
    return assignedWorkDetail(record)?.start_shift ?? null;
};

const shiftEndTime = (record: Attendance) => {
    return assignedWorkDetail(record)?.end_shift ?? null;
};

const checkInIndicator = (record: Attendance) => {
    const startShift = shiftStartTime(record);

    if (!record.check_in_time || !startShift) {
        return null;
    }

    const difference = secondsBetweenTimes(startShift, record.check_in_time);

    if (difference > 0) {
        return 'Late';
    }

    if (secondsBetweenTimes(record.check_in_time, startShift) > 0) {
        return 'Early';
    }

    return null;
};

const totalWorkSeconds = (record: Attendance) => {
    const checkIn = timeToSeconds(record.check_in_time);
    const checkOut = timeToSeconds(record.check_out_time);
    const startShift = timeToSeconds(shiftStartTime(record));
    const endShift = timeToSeconds(shiftEndTime(record));

    if (checkIn === null || checkOut === null) {
        return 0;
    }

    if (startShift === null || endShift === null) {
        return Math.max(
            0,
            secondsBetweenTimes(record.check_in_time, record.check_out_time) -
                totalLunchBreakSeconds(record),
        );
    }

    const regularSeconds = overlapSeconds(checkIn, checkOut, startShift, endShift);

    const lunchStart = timeToSeconds(record.lunch_start_time);
    const lunchEnd = timeToSeconds(record.lunch_end_time);

    const lunchDuringRegular =
        lunchStart !== null && lunchEnd !== null
            ? overlapSeconds(lunchStart, lunchEnd, startShift, endShift)
            : 0;

    return Math.max(0, regularSeconds - lunchDuringRegular);
};

const totalOvertimeSeconds = (record: Attendance) => {
    const checkIn = timeToSeconds(record.check_in_time);
    const checkOut = timeToSeconds(record.check_out_time);
    const endShift = timeToSeconds(shiftEndTime(record));

    if (checkIn === null || checkOut === null || endShift === null) {
        return 0;
    }

    return Math.max(0, checkOut - Math.max(checkIn, endShift));
};

const lunchBreakDetails = (record: Attendance) => {
    if (!record.lunch_start_time || !record.lunch_end_time) {
        return [
            {
                label: 'Lunch Break',
                value: 'No lunch break recorded',
            },
        ];
    }

    return [
        {
            label: 'Lunch Break Start',
            value: formatTime(record.lunch_start_time),
        },
        {
            label: 'Lunch Break End',
            value: formatTime(record.lunch_end_time),
        },
    ];
};

const overtimeStartTime = (record: Attendance) => {
    const checkIn = timeToSeconds(record.check_in_time);
    const endShift = timeToSeconds(shiftEndTime(record));

    if (checkIn === null || endShift === null) {
        return null;
    }

    return checkIn > endShift ? record.check_in_time : shiftEndTime(record);
};

const overtimeDetails = (record: Attendance) => {
    if (totalOvertimeSeconds(record) <= 0 || !record.check_out_time) {
        return [
            {
                label: 'Overtime',
                value: 'No overtime recorded',
            },
        ];
    }

    return [
        {
            label: 'Overtime Start',
            value: formatTime(overtimeStartTime(record)),
        },
        {
            label: 'Overtime End',
            value: formatTime(record.check_out_time),
        },
    ];
};

const openEditDialog = (record: Attendance) => {
    selectedRecord.value = record;
    formError.value = '';

    editForm.value = {
        check_in_date: record.check_in_date,
        check_in_time: toTimeInput(record.check_in_time),
        check_out_time: toTimeInput(record.check_out_time),
        lunch_start_time: toTimeInput(record.lunch_start_time),
        lunch_end_time: toTimeInput(record.lunch_end_time),
    };

    editDialogOpen.value = true;
};

const openDeleteDialog = (record: Attendance) => {
    selectedRecord.value = record;
    formError.value = '';
    deleteDialogOpen.value = true;
};

const saveAttendance = async () => {
    if (!selectedRecord.value) {

        return;

    }

    const response = await fetch(`/attendance/${selectedRecord.value.id}`, {
        method: 'PUT',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
        body: JSON.stringify(editForm.value),
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        formError.value = data?.message ?? 'Unable to update attendance.';

        return;
    }

    editDialogOpen.value = false;
    selectedRecord.value = null;
    emit('attendance-updated');
};

const deleteAttendance = async () => {
    if (!selectedRecord.value){

        return;

    }

    const response = await fetch(`/attendance/${selectedRecord.value.id}`, {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
    });

    if (!response.ok) {
        const data = await response.json().catch(() => null);
        formError.value = data?.message ?? 'Unable to delete attendance.';

        return;
    }

    deleteDialogOpen.value = false;
    selectedRecord.value = null;
    emit('attendance-updated');
};



const filteredAttendanceRecords = computed(() => {
    const value = search.value.trim().toLowerCase();

    const sortedRecords = [...props.attendanceRecords].sort((a, b) => {
        const first = `${a.check_in_date} ${a.check_in_time ?? '00:00:00'}`;
        const second = `${b.check_in_date} ${b.check_in_time ?? '00:00:00'}`;

        return second.localeCompare(first);
    });

    if (!value) {
        return sortedRecords;
    }

    return sortedRecords.filter((record) => {
        const userName = formatUserName(record.user).toLowerCase();
        const date = formatDate(record.check_in_date).toLowerCase();
        const checkIn = formatTime(record.check_in_time).toLowerCase();
        const checkOut = formatTime(record.check_out_time).toLowerCase();
        const totalWorkHours = formatDuration(totalWorkSeconds(record)).toLowerCase();
        const totalLunchBreak = formatDuration(totalLunchBreakSeconds(record)).toLowerCase();
        const totalOvertime = formatDuration(totalOvertimeSeconds(record)).toLowerCase();

        return [
            userName,
            date,
            checkIn,
            checkOut,
            totalWorkHours,
            totalLunchBreak,
            totalOvertime,
        ].some((field) => field.includes(value));
    });
});

const openExportDialog = () => {
    exportDialogOpen.value = true;
};

const exportAttendanceAsPdf = (range: { startDate: string; endDate: string }) => {
    const records = filteredAttendanceRecords.value.filter((record) =>
        record.check_in_date >= range.startDate &&
        record.check_in_date <= range.endDate,
    );

    if (records.length === 0) {
        toast.error('No attendance records found for the selected date range.');

        return;
    }

    const result = exportBrandedPdf({
        title: 'Attendance Report',
        records,
        columns: [
            { header: 'Name', value: (record) => formatUserName(record.user) },
            { header: 'Company', value: (record) => formatCompanyName(record) },
            { header: 'Date', value: (record) => formatDate(record.check_in_date) },
            { header: 'Check In', value: (record) => formatTime(record.check_in_time) },
            { header: 'Check Out', value: (record) => formatTime(record.check_out_time) },
            { header: 'Total Work Hours', value: (record) => formatDuration(totalWorkSeconds(record)) },
            { header: 'Total Lunch Break', value: (record) => formatDuration(totalLunchBreakSeconds(record)) },
            { header: 'Total Overtime', value: (record) => formatDuration(totalOvertimeSeconds(record)) },
            { header: 'Status', value: (record) => formatApprovalStatus(record.approval_status) },
        ],
    });

    if (result === 'blocked') {
        toast.error('Please allow pop-ups to export the attendance PDF.');

        return;
    }

    exportDialogOpen.value = false;
};

const totalPages = computed(() => {
    return Math.max(1, Math.ceil(filteredAttendanceRecords.value.length / Number(rowsPerPage.value)));
});

const paginatedAttendanceRecords = computed(() => {
    const start = (currentPage.value - 1) * Number(rowsPerPage.value);
    const end = start + Number(rowsPerPage.value);

    return filteredAttendanceRecords.value.slice(start, end);
});

const paginationStart = computed(() => {
    if (filteredAttendanceRecords.value.length === 0) {
        return 0;
    }

    return (currentPage.value - 1) * Number(rowsPerPage.value) + 1;
});

const paginationEnd = computed(() => {
    return Math.min(
        currentPage.value * Number(rowsPerPage.value),
        filteredAttendanceRecords.value.length,
    );
});

const goToPreviousPage = () => {
    currentPage.value = Math.max(1, currentPage.value - 1);
};

const goToNextPage = () => {
    currentPage.value = Math.min(totalPages.value, currentPage.value + 1);
};

watch([search, rowsPerPage], () => {
    currentPage.value = 1;
});


const groupedAttendanceRecords = computed(() => {
    const groups = new Map<
        string,
        {
            weekStart: string;
            records: Attendance[];
        }
    >();

    for (const record of paginatedAttendanceRecords.value) {
        const monday = startOfWeek(record.check_in_date);
        const weekStart = monday.toISOString().slice(0, 10);

        if (!groups.has(weekStart)) {
            groups.set(weekStart, {
                weekStart,
                records: [],
            });
        }

        groups.get(weekStart)?.records.push(record);
    }

    return Array.from(groups.values());
});

const visibleTableRowCount = computed(() => {
    return groupedAttendanceRecords.value.reduce(
        (total, group) => total + 1 + group.records.length,
        0,
    );
});

</script>

<template>
    <div class="w-full space-y-3 px-4 pt-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-lg font-semibold tracking-tight">
                    Attendance
                </h2>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <Select v-model="rowsPerPage">
                    <SelectTrigger class="h-8 w-24">
                        <SelectValue />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectItem value="10">10</SelectItem>
                        <SelectItem value="20">20</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                    </SelectContent>
                </Select>

                <div
class="relative w-full sm:w-56 lg:w-72"
                >
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-3.5 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        placeholder="Search attendance"
                        class="h-8 pl-8 text-sm"
                    />
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger :as-child="true">
                        <Button type="button" variant="outline" size="icon-sm" aria-label="Attendance actions">
                            <EllipsisVertical class="size-4" />
                        </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" class="w-44">
                        <DropdownMenuItem class="cursor-pointer" @click="openExportDialog">
                            <FileText class="size-4" />
                            Export as PDF
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <div class="overflow-hidden rounded-md border border-border bg-card">
            <div
class="overflow-x-auto overscroll-x-contain"
                :class="visibleTableRowCount > 7 ? 'max-h-[30rem] overflow-y-auto' : ''"
            >
                <table class="w-max min-w-full text-sm">
                    <thead class="sticky top-0 z-10 bg-muted text-left text-muted-foreground">
                        <tr>
                            <th class="w-12 px-4 py-3"><span class="sr-only">Select attendance</span></th>
                            <th class="min-w-[160px] px-4 py-3 font-weight-bold">Name</th>
                            <th class="min-w-[190px] px-4 py-3 font-weight-bold">Company</th>
                            <th class="min-w-[130px] px-4 py-3 font-weight-bold">Date</th>
                            <th class="min-w-[140px] px-4 py-3 font-weight-bold">Check In</th>
                            <th class="min-w-[140px] px-4 py-3 font-weight-bold">Check Out</th>
                            <th class="min-w-[160px] px-4 py-3 font-weight-bold">Total Work Hours</th>
                            <th class="min-w-[160px] px-4 py-3 font-weight-bold">Total Lunch Break</th>
                            <th class="min-w-[150px] px-4 py-3 font-weight-bold">Total Overtime</th>
                            <th class="min-w-[120px] px-4 py-3 font-weight-bold">Status</th>
                            <th class="min-w-[110px] px-4 py-3 text-right font-weight-bold">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template
                            v-for="group in groupedAttendanceRecords"
                            :key="group.weekStart"
                        >
                            <tr class="border-t border-border bg-muted/30">
                                <td class="w-12 px-4 py-2">
                                    <Checkbox
                                        :id="`attendance-group-${group.weekStart}`"
                                        :model-value="groupSelectionState(group.records)"
                                        :aria-label="`Select all attendance for week of ${formatWeekRange(group.weekStart)}`"
                                        @update:model-value="
                                            toggleAttendanceSelection(record, $event)
                                        "
                                    />
                                </td>

                                <td colspan="10" class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold">
                                            {{ formatWeekRange(group.weekStart) }}
                                        </span>

                                        <Button
                                            v-if="hasSelectedGroupRecords(group.records)"
                                            type="button"
                                            size="sm"
                                            @click="openSubmitApprovalDialog(group.records)"
                                        >
                                            Submit For Approval
                                        </Button>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="record in group.records"
                                :key="record.id"
                                class="border-t border-border"
                            >
                                <td class="w-12 px-4 py-3">
                                    <Checkbox
                                        :id="`attendance-record-${record.id}`"
                                        :model-value="isAttendanceSelected(record.id)"
                                        :aria-label="`Select attendance record for ${formatUserName(record.user)}`"
                                        @update:model-value="
                                            toggleAttendanceSelection(record, $event)
                                        "
                                    />
                                </td>
                                <td class="px-4 py-3 font-medium whitespace-nowrap">
                                    {{ formatUserName(record.user) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <TooltipProvider :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <button
                                                        type="button"
                                                        class="inline-flex size-6 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                        :aria-label="`View shift for ${formatCompanyName(record)}`"
                                                    >
                                                        <Info class="size-4" />
                                                    </button>
                                                </TooltipTrigger>

                                                <TooltipContent
                                                    side="top"
                                                    align="center"
                                                    class="border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md"
                                                >
                                                    <div class="space-y-1 text-sm">
                                                        <div class="font-medium">{{ formatCompanyName(record) }}</div>
                                                        <div class="text-muted-foreground">
                                                            Shift: {{ companyShiftLabel(record) }}
                                                        </div>
                                                    </div>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>

                                        <span>{{ formatCompanyName(record) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ formatDate(record.check_in_date) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span>{{ formatTime(record.check_in_time) }}</span>

                                        <span
                                            v-if="checkInIndicator(record)"
                                            class="rounded-md border px-1.5 py-0.5 text-xs font-medium"
                                            :class="
                                                checkInIndicator(record) === 'Late'
                                                    ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300'
                                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300'
                                            "
                                        >
                                            {{ checkInIndicator(record) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ formatTime(record.check_out_time) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ formatDuration(totalWorkSeconds(record)) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <TooltipProvider :delay-duration="100">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-h-8 max-w-full items-center rounded-md px-2 text-left transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                >
                                                    {{ formatDuration(totalLunchBreakSeconds(record)) }}
                                                </button>
                                            </TooltipTrigger>

                                            <TooltipContent
                                                side="top"
                                                align="center"
                                                class="max-w-[calc(100vw-2rem)] border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md"
                                            >
                                                <div class="space-y-1">
                                                    <div
                                                        v-for="detail in lunchBreakDetails(record)"
                                                        :key="detail.label"
                                                        class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2"
                                                    >
                                                        <span class="font-medium">{{ detail.label }}:</span>
                                                        <span class="text-muted-foreground">{{ detail.value }}</span>
                                                    </div>
                                                </div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <TooltipProvider :delay-duration="100">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <button
                                                    type="button"
                                                    class="inline-flex min-h-8 max-w-full items-center rounded-md px-2 text-left transition-colors hover:bg-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                                >
                                                    {{ formatDuration(totalOvertimeSeconds(record)) }}
                                                </button>
                                            </TooltipTrigger>

                                            <TooltipContent
                                                side="top"
                                                align="center"
                                                class="max-w-[calc(100vw-2rem)] border border-border bg-popover px-3 py-2 text-popover-foreground shadow-md"
                                            >
                                                <div class="space-y-1">
                                                    <div
                                                        v-for="detail in overtimeDetails(record)"
                                                        :key="detail.label"
                                                        class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2"
                                                    >
                                                        <span class="font-medium">{{ detail.label }}:</span>
                                                        <span class="text-muted-foreground">{{ detail.value }}</span>
                                                    </div>
                                                </div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-medium"
                                        :class="approvalStatusClass(record.approval_status)">
                                        {{ formatApprovalStatus(record.approval_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex justify-end gap-1">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            aria-label="Edit attendance"
                                            :disabled="isApprovalLocked(record.approval_status)"
                                            @click="openEditDialog(record)"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>

                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-destructive hover:text-destructive disabled:text-muted-foreground"
                                            aria-label="Delete attendance"
                                            :disabled="isApprovalLocked(record.approval_status)"
                                            @click="openDeleteDialog(record)"
                                        >
                                            <Trash2 class="size-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr v-if="filteredAttendanceRecords.length === 0">
                            <td
colspan="11"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No attendance records found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
                <!-- pagination footer -->

                <div
                    class="flex flex-col gap-3 border-t border-border px-4 py-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
                >
                    <p>
                        Showing {{ paginationStart }} to {{ paginationEnd }} of
                        {{ filteredAttendanceRecords.length }} records
                    </p>

                    <div class="flex items-center justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon-sm"
                            :disabled="currentPage === 1"
                            @click="goToPreviousPage"
                        >
                            <ChevronLeft class="size-4" />
                        </Button>

                        <span class="min-w-20 text-center">
                            Page {{ currentPage }} of {{ totalPages }}
                        </span>

                        <Button
                            type="button"
                            variant="outline"
                            size="icon-sm"
                            :disabled="currentPage === totalPages"
                            @click="goToNextPage"
                        >
                            <ChevronRight class="size-4" />
                        </Button>
                    </div>
                </div>

        </div>
        <EditAttendance
            v-model:open="editDialogOpen"
            :attendance="selectedRecord"
            :form="editForm"
            :error="formError"
            @update:form="editForm = $event"
            @save="saveAttendance"
        />
        <!-- ========================== Delete Dialog-->
        <Dialog v-model:open="deleteDialogOpen">
            <DialogContent class="w-full max-w-[calc(100%-2rem)] sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete attendance record?</DialogTitle>
                    <DialogDescription>
                        This action cannot be undone. Are you sure you want to delete this record?
                    </DialogDescription>
                </DialogHeader>

                <p v-if="formError" class="text-sm text-destructive">
                    {{ formError }}
                </p>

                <DialogFooter>
                    <Button type="button" variant="destructive" @click="deleteAttendance">
                        Yes, delete
                    </Button>

                    <Button type="button" variant="outline" @click="deleteDialogOpen = false">
                        Cancel
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <!-- Timesheet For Approval Dialog -->
        <Dialog v-model:open="submitApprovalDialogOpen">
            <DialogContent class="w-full max-w-[calc(100%-2rem)] sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Send Timesheet For Approval</DialogTitle>
                    <DialogDescription>
                        Please make sure your timesheets are correct before sending them for approval.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <Button type="button" @click="submitGroupForApproval">
                        Confirm
                    </Button>

                    <Button type="button" variant="outline" @click="submitApprovalDialogOpen = false">
                        Cancel
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
        <!-- EXPORT MODAL DATE RANGE -->
        <ExportPdfDialog v-model:open="exportDialogOpen" title="Export Attendance PDF"
            description="Select the attendance date range you want to include in the report."
            @export="exportAttendanceAsPdf" />
    </div>
</template>
