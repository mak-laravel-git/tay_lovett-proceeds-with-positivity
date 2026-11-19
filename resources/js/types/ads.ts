/** Laravel's length-aware paginator as serialised by Inertia. */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type SelectOption = { value: string; label: string };

export type PersonRef = { id: number; name: string; email: string };
