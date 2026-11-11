import type { LucideIcon } from 'lucide-vue-next';

export type UserRole = 'admin' | 'notulis' | 'member' | 'reader';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon;
    isActive?: boolean;
    badge?: number;
}

export interface SharedData {
    [key: string]: unknown;
    name: string;
    auth: Auth;
    notifications: { unread: number };
    flash: { success?: string; error?: string };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface Division {
    id: number;
    name: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    role_label: string;
    job_title: string | null;
    division_id: number | null;
    division?: Division | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Meeting {
    id: number;
    title: string;
    document_type: string;
    meeting_type: string;
    status: 'draft' | 'final' | 'locked';
    confidentiality: 'open' | 'division' | 'restricted' | 'secret';
    scheduled_at: string | null;
    location: string | null;
    meeting_link: string | null;
    agenda: string | null;
    content: Record<string, unknown> | null;
    division?: Division | null;
    creator?: { id: number; name: string } | null;
    created_by: number;
    finalized_at: string | null;
}

export interface Tag {
    id: number;
    type: 'person' | 'division' | 'topic';
    target_id: number | null;
    target_name: string | null;
}

export interface Participant {
    id: number;
    user_id: number | null;
    name: string | null;
    email: string | null;
    attendance: 'invited' | 'present' | 'absent' | 'excused';
    user?: { id: number; name: string; email: string } | null;
}

export interface Decision {
    id: number;
    content: string;
    decided_at: string | null;
    topic?: { id: number; name: string; slug: string } | null;
}

export interface ActionItemFeedback {
    id: number;
    action_item_id: number;
    user_id: number | null;
    body: string;
    response_status: 'acknowledged' | 'scheduled' | 'in_progress' | 'blocked' | 'done' | 'need_help';
    response_status_label: string;
    created_at: string;
    user?: { id: number; name: string } | null;
}

export interface ActionItem {
    id: number;
    title: string;
    description: string | null;
    due_date: string | null;
    status: 'new' | 'in_progress' | 'done' | 'cancelled';
    is_overdue?: boolean;
    is_owned?: boolean;
    response_time_hours?: number | null;
    overdue_hours?: number | null;
    first_responded_at?: string | null;
    source: string;
    completed_at: string | null;
    meeting?: { id: number; title: string; scheduled_at?: string | null } | null;
    pic?: { id: number; name: string } | null;
    pic_name: string | null;
    division?: Division | null;
    feedback?: ActionItemFeedback[];
}

export type BreadcrumbItemType = BreadcrumbItem;
