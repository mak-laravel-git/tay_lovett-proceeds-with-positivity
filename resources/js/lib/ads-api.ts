import { xsrfToken } from '@/lib/csrf';

/** What a completed ad / survey can unlock. Mirrors App\Enums\GatePurpose. */
export type GatePurpose = 'quote_generation';

export type Offer = {
    identifier: string;
    length_minutes: number | null;
    category: string | null;
    support_url: string | null;
    is_hot: boolean;
};

export type OffersResponse = {
    request_id: number;
    /** The provider that won the fallback chain, or null when nobody had anything to show. */
    provider: string | null;
    /** True only in local development, where completion can be simulated. */
    simulate: boolean;
    offers: Offer[];
};

export type StartResponse = { activity_id: string; url: string };

export type ActivityStatusResponse = {
    status: string;
    reward_status: string;
    points: number;
};

export type GateStatus = {
    enabled: boolean;
    available: number;
    unlocked: boolean;
};

const FALLBACK_MESSAGE = 'Something went wrong. Please try again.';

type RequestOptions = Omit<RequestInit, 'headers'> & {
    headers?: Record<string, string>;
};

async function request<T>(url: string, init: RequestOptions): Promise<T> {
    const response = await fetch(url, {
        credentials: 'same-origin',
        ...init,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...init.headers,
        },
    });

    if (!response.ok) {
        const data = (await response.json().catch(() => ({}))) as {
            message?: string;
        };

        throw new Error(data.message ?? FALLBACK_MESSAGE);
    }

    return (await response.json()) as T;
}

export function postJson<T>(url: string, body?: unknown): Promise<T> {
    return request<T>(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });
}

export function getJson<T>(url: string): Promise<T> {
    return request<T>(url, { method: 'GET' });
}

/** Run the provider fallback chain (or one selected provider) and list what it can show. */
export const fetchOffers = (provider?: string): Promise<OffersResponse> =>
    postJson<OffersResponse>(
        '/surveys/offers',
        provider ? { provider } : undefined,
    );

/** The user opened an offer: the server records the attempt and returns the entry URL. */
export const startOffer = (body: {
    request_id: number;
    offer_identifier: string;
    purpose?: GatePurpose;
}): Promise<StartResponse> => postJson<StartResponse>('/surveys/start', body);

/** Read-only: what the provider's verified callback has recorded so far. */
export const fetchActivityStatus = (
    activityId: string,
): Promise<ActivityStatusResponse> =>
    getJson<ActivityStatusResponse>(`/surveys/activities/${activityId}/status`);

/** Whether the user already holds an unspent unlock for this purpose. */
export const fetchGate = (purpose: GatePurpose): Promise<GateStatus> =>
    getJson<GateStatus>(`/ads/gate/${purpose}`);

/** Local development only: complete the user's own survey through the real callback pipeline. */
export const simulateCompletion = (
    activityId: string,
): Promise<{ result: string }> =>
    postJson<{ result: string }>(`/dev/ads/activities/${activityId}/simulate`);
