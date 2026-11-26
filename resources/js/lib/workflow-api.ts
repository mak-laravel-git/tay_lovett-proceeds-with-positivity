import { getJson, postJson } from '@/lib/ads-api';
import type { OffersResponse } from '@/lib/ads-api';

export type WorkflowState =
    | 'ad_before_survey'
    | 'survey'
    | 'generate'
    | 'generating'
    | 'ad_before_publish'
    | 'completed'
    | 'cancelled'
    | 'expired';

export type WorkflowStep = {
    key: string;
    label: string;
    status: 'done' | 'skipped' | 'current' | 'upcoming';
};

/** One run of the quote flow, as the server sees it. Mirrors App\Services\QuoteWorkflow\WorkflowPresenter. */
export type Workflow = {
    id: string;
    state: WorkflowState;
    active: boolean;
    category: { id: number; name: string | null };
    steps: WorkflowStep[];
    survey: { awards_points: boolean; pending: boolean };
    /** Only present once the run is complete. */
    quote: { slug: string; text: string; status: string } | null;
    notice: string | null;
    can_cancel: boolean;
};

export type AdUnit = {
    attempt_id: string;
    ad_unit_path: string;
    load_timeout_seconds: number;
    min_watch_seconds: number;
};

export type AdEventName =
    | 'ready'
    | 'shown'
    | 'granted'
    | 'closed'
    | 'declined'
    | 'unavailable'
    | 'error';

type WorkflowResponse = { workflow: Workflow };

const base = (id: string) => `/studio/workflow/${id}`;

export const startWorkflow = (categoryId: number): Promise<WorkflowResponse> =>
    postJson<WorkflowResponse>('/studio/workflow', {
        quote_category_id: categoryId,
    });

export const fetchWorkflow = (id: string): Promise<WorkflowResponse> =>
    getJson<WorkflowResponse>(base(id));

export const issueAd = (
    id: string,
): Promise<WorkflowResponse & { ad: AdUnit | null; message: string | null }> =>
    postJson(`${base(id)}/ads/attempts`);

export const reportAdEvent = (
    id: string,
    attemptId: string,
    event: AdEventName,
    detail?: string,
): Promise<WorkflowResponse & { message: string | null }> =>
    postJson(`${base(id)}/ads/attempts/${attemptId}/events`, {
        event,
        detail,
    });

export const fetchWorkflowOffers = (
    id: string,
): Promise<OffersResponse & WorkflowResponse & { message: string | null }> =>
    postJson(`${base(id)}/survey/offers`);

export const generateQuote = (id: string): Promise<WorkflowResponse> =>
    postJson<WorkflowResponse>(`${base(id)}/generate`);

export const cancelWorkflow = (id: string): Promise<WorkflowResponse> =>
    postJson<WorkflowResponse>(`${base(id)}/cancel`);
