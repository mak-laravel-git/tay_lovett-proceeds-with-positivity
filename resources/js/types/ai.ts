export type AiDriverOption = {
    value: string;
    label: string;
    requires_url: boolean;
    suggested_models: string[];
};

export type ReadinessCheck = {
    key: string;
    label: string;
    ok: boolean;
    hint: string;
};
