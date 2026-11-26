import { Head, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ChevronRight,
    Copy,
    KeyRound,
    PlugZap,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
} from '@/components/admin/admin-page';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { formatDateTime } from '@/lib/format';

type Provider = {
    id: number;
    key: string;
    label: string;
    is_enabled: boolean;
    priority: number;
    health: string;
    registered: boolean;
    configured: boolean;
    supports_reconciliation: boolean;
    integration_type: string;
    hints: {
        api_token: string | null;
        api_secret: string | null;
        reporting_access_token: string | null;
    };
    compliance: {
        approval_status: string;
        production_enabled: boolean;
        reward_cashout_permission: boolean;
        concurrent_provider_permission: boolean;
        sandbox_mode: boolean;
        partner_id: string | null;
        currency_name_plural: string | null;
        exchange_rate: string | null;
        notes: string | null;
    };
    /** Editable endpoint settings; null for providers with a fixed address. */
    connection: {
        base_url: string | null;
        timeout_seconds: number | null;
        default_base_url: string;
        default_timeout_seconds: number;
    } | null;
    last_tested_at: string | null;
    last_success_at: string | null;
    last_error_category: string | null;
    callback_url: string;
    events_url: string | null;
    return_url: string;
};

type Props = {
    providers: Provider[];
    fallback_order: { key: string; label: string; configured: boolean }[];
    audits: {
        id: number;
        action: string;
        provider: string | null;
        user: string | null;
        at: string | null;
    }[];
};

export default function AdsProviders({
    providers,
    fallback_order,
    audits,
}: Props) {
    const [editing, setEditing] = useState<Provider | null>(null);

    const toggle = (provider: Provider, enabled: boolean) => {
        router.put(
            `/admin/ads/providers/${provider.id}`,
            { is_enabled: enabled },
            {
                preserveScroll: true,
                onError: (errors) =>
                    toast.error(
                        errors.is_enabled ?? 'Could not change this provider.',
                    ),
            },
        );
    };

    const move = (provider: Provider, direction: 'up' | 'down') =>
        router.post(
            `/admin/ads/providers/${provider.id}/move`,
            { direction },
            { preserveScroll: true },
        );

    const test = (provider: Provider) =>
        router.post(
            `/admin/ads/providers/${provider.id}/test`,
            {},
            { preserveScroll: true },
        );

    const clearCredentials = (provider: Provider) => {
        if (
            window.confirm(
                `Delete the stored credentials of ${provider.label}? It will be switched off.`,
            )
        ) {
            router.delete(
                `/admin/ads/providers/${provider.id}/credentials`,
                { preserveScroll: true },
            );
        }
    };

    return (
        <>
            <Head title="Ad providers" />
            <AdminPage
                title="Ad providers"
                description="Turn providers on or off, set the order they are tried in, and manage their credentials."
            >
                <AdminSectionCard
                    title="Fallback order"
                    description="Enabled providers are asked in this order. If one fails or has nothing to show, the next is tried."
                >
                    {fallback_order.length === 0 ? (
                        <AdminNotice tone="warn">
                            No provider is enabled, so users will not see
                            surveys.
                        </AdminNotice>
                    ) : (
                        <ol className="flex flex-wrap items-center gap-2 text-sm">
                            {fallback_order.map((provider, index) => (
                                <li
                                    key={provider.key}
                                    className="flex items-center gap-2"
                                >
                                    <span className="rounded-full border border-[#76d8f8]/50 bg-[#e7f7ff] px-3 py-1 font-medium text-[#0f6f8f]">
                                        {index + 1}. {provider.label}
                                        {provider.configured
                                            ? ''
                                            : ' (not configured)'}
                                    </span>
                                    {index < fallback_order.length - 1 ? (
                                        <ChevronRight className="size-4 text-muted-foreground" />
                                    ) : null}
                                </li>
                            ))}
                        </ol>
                    )}
                </AdminSectionCard>

                <div className="grid gap-4">
                    {providers.map((provider, index) => (
                        <AdminSectionCard
                            key={provider.id}
                            title={provider.label}
                            description={`Key: ${provider.key} · priority ${provider.priority}`}
                            actions={
                                <div className="flex items-center gap-3">
                                    <StatusBadge value={provider.health} />
                                    <div className="flex items-center gap-2">
                                        <Switch
                                            id={`enabled-${provider.id}`}
                                            checked={provider.is_enabled}
                                            onCheckedChange={(checked) =>
                                                toggle(provider, checked)
                                            }
                                            aria-label={`Enable ${provider.label}`}
                                        />
                                        <Label
                                            htmlFor={`enabled-${provider.id}`}
                                        >
                                            {provider.is_enabled
                                                ? 'Enabled'
                                                : 'Disabled'}
                                        </Label>
                                    </div>
                                </div>
                            }
                        >
                            <div className="grid gap-4 lg:grid-cols-2">
                                <dl className="grid gap-2 text-sm">
                                    <Row label="Type">
                                        {provider.integration_type}
                                    </Row>
                                    <Row label="Credentials">
                                        {provider.configured
                                            ? 'Complete'
                                            : 'Missing required credentials / product settings'}
                                    </Row>
                                    <Row label="API token / public key">
                                        {provider.hints.api_token ?? 'Not set'}
                                    </Row>
                                    <Row label="Signing secret">
                                        {provider.hints.api_secret ?? 'Not set'}
                                    </Row>
                                    {provider.supports_reconciliation ? (
                                        <Row label="Reporting access token">
                                            {provider.hints
                                                .reporting_access_token ??
                                                'Not set (reconciliation unavailable)'}
                                        </Row>
                                    ) : null}
                                    <Row label="Approval">
                                        {provider.compliance.approval_status}
                                    </Row>
                                    <Row label="Sandbox">
                                        {provider.compliance.sandbox_mode
                                            ? 'On'
                                            : 'Off'}
                                    </Row>
                                    <Row label="Production enabled">
                                        {provider.compliance.production_enabled
                                            ? 'Yes'
                                            : 'No'}
                                    </Row>
                                    <Row label="Cashout permission">
                                        {provider.compliance
                                            .reward_cashout_permission
                                            ? 'Yes'
                                            : 'No'}
                                    </Row>
                                    {provider.key === 'theoremreach' ? (
                                        <Row label="Coexistence permission">
                                            {provider.compliance
                                                .concurrent_provider_permission
                                                ? 'Yes'
                                                : 'No (launch blocked)'}
                                        </Row>
                                    ) : null}
                                    <Row label="Last tested">
                                        {formatDateTime(
                                            provider.last_tested_at,
                                        )}
                                    </Row>
                                    <Row label="Last success">
                                        {formatDateTime(
                                            provider.last_success_at,
                                        )}
                                    </Row>
                                    {provider.last_error_category ? (
                                        <Row label="Last error">
                                            {provider.last_error_category.replaceAll(
                                                '_',
                                                ' ',
                                            )}
                                        </Row>
                                    ) : null}
                                </dl>

                                <div className="grid gap-3 text-sm">
                                    <CopyField
                                        label="Postback (reward callback) URL"
                                        value={provider.callback_url}
                                        help="Enter this as the server-to-server reward URL in the provider’s dashboard. The provider appends its parameters and the signature; it must be a public HTTPS address."
                                    />
                                    {provider.events_url ? (
                                        <CopyField
                                            label="Event webhook (reversals / bans)"
                                            value={provider.events_url}
                                            help="TheoremReach reversals are not sent on the reward callback. Configure this Event Webhook URL in the publisher dashboard."
                                        />
                                    ) : null}
                                    <CopyField
                                        label="Post-survey redirect URL"
                                        value={provider.return_url}
                                        help="Where the user’s browser returns after a survey. It never approves a survey or awards points."
                                    />
                                </div>
                            </div>

                            <div className="mt-4 flex flex-wrap items-center gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => setEditing(provider)}
                                >
                                    <KeyRound /> Credentials
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    disabled={!provider.configured}
                                    onClick={() => test(provider)}
                                >
                                    <PlugZap /> Test connection
                                </Button>
                                {Object.values(provider.hints).some(Boolean) ? (
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => clearCredentials(provider)}
                                    >
                                        <Trash2 /> Delete credentials
                                    </Button>
                                ) : null}
                                <div className="ml-auto flex items-center gap-1">
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label="Move earlier in the fallback order"
                                        disabled={index === 0}
                                        onClick={() => move(provider, 'up')}
                                    >
                                        <ArrowUp />
                                    </Button>
                                    <Button
                                        size="icon"
                                        variant="ghost"
                                        aria-label="Move later in the fallback order"
                                        disabled={
                                            index === providers.length - 1
                                        }
                                        onClick={() => move(provider, 'down')}
                                    >
                                        <ArrowDown />
                                    </Button>
                                </div>
                            </div>
                        </AdminSectionCard>
                    ))}
                </div>

                <AdminSectionCard title="Recent changes">
                    {audits.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No changes recorded yet.
                        </p>
                    ) : (
                        <ul className="grid gap-1.5 text-sm">
                            {audits.map((audit) => (
                                <li key={audit.id}>
                                    <span className="font-medium">
                                        {audit.provider ?? 'Provider'}
                                    </span>{' '}
                                    {audit.action} by {audit.user ?? 'system'} ·{' '}
                                    <span className="text-muted-foreground">
                                        {formatDateTime(audit.at)}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    )}
                </AdminSectionCard>
            </AdminPage>

            {editing ? (
                <CredentialsDialog
                    key={editing.id}
                    provider={editing}
                    onClose={() => setEditing(null)}
                />
            ) : null}
        </>
    );
}

function Row({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="flex justify-between gap-4">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="text-right font-medium">{children}</dd>
        </div>
    );
}

function CopyField({
    label,
    value,
    help,
}: {
    label: string;
    value: string;
    help: string;
}) {
    return (
        <div className="grid gap-1">
            <Label>{label}</Label>
            <div className="flex gap-2">
                <Input readOnly value={value} className="font-mono text-xs" />
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    aria-label={`Copy ${label}`}
                    onClick={() => {
                        void navigator.clipboard.writeText(value).then(
                            () => toast.success('Copied.'),
                            () => toast.error('Copy failed.'),
                        );
                    }}
                >
                    <Copy />
                </Button>
            </div>
            <p className="text-xs text-muted-foreground">{help}</p>
        </div>
    );
}

/**
 * Credentials are write-only: the stored values are never sent to the browser. Leave a field empty to keep
 * what is stored.
 */
function CredentialsDialog({
    provider,
    onClose,
}: {
    provider: Provider;
    onClose: () => void;
}) {
    // The caller keys this by provider, so the form always starts from that provider's saved settings and an
    // untouched switch is saved as it was.
    const [values, setValues] = useState({
        api_token: '',
        api_secret: '',
        reporting_access_token: '',
        approval_status: provider.compliance.approval_status,
        partner_id: provider.compliance.partner_id ?? '',
        currency_name_plural:
            provider.compliance.currency_name_plural ?? 'Points',
        exchange_rate: provider.compliance.exchange_rate ?? '',
        notes: provider.compliance.notes ?? '',
        production_enabled: provider.compliance.production_enabled,
        reward_cashout_permission:
            provider.compliance.reward_cashout_permission,
        concurrent_provider_permission:
            provider.compliance.concurrent_provider_permission,
        sandbox_mode: provider.compliance.sandbox_mode,
    });
    const [connection, setConnection] = useState({
        base_url: provider.connection?.base_url ?? '',
        timeout_seconds: String(provider.connection?.timeout_seconds ?? ''),
    });
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        // The connection fields only exist for providers that support them.
        const body = provider.connection
            ? { ...values, ...connection }
            : values;

        router.put(`/admin/ads/providers/${provider.id}`, body, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: (bag) => setErrors(bag),
            onSuccess: onClose,
        });
    };

    return (
        <Dialog
            open
            onOpenChange={(next) => {
                if (!next) {
                    onClose();
                }
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{provider.label} settings</DialogTitle>
                        <DialogDescription>
                            Secrets are write-only and never shown again. Leave
                            a secret field empty to keep the current value.
                            Compliance flags do not equal provider approval.
                        </DialogDescription>
                    </DialogHeader>
                    {(
                        [
                            {
                                name: 'api_token' as const,
                                label:
                                    provider?.key === 'wannads'
                                        ? 'Public API key'
                                        : 'API token / API key',
                                secret: true,
                                show: true,
                            },
                            {
                                name: 'api_secret' as const,
                                label:
                                    provider?.key === 'wannads'
                                        ? 'Postback SECRET (not API SECRET)'
                                        : 'Signing secret',
                                secret: true,
                                show: true,
                            },
                            {
                                name: 'reporting_access_token' as const,
                                label: 'Reporting access token',
                                secret: true,
                                show:
                                    provider?.supports_reconciliation ?? false,
                            },
                        ] as const
                    )
                        .filter((field) => field.show)
                        .map((field) => (
                            <div key={field.name} className="grid gap-1.5">
                                <Label htmlFor={field.name}>
                                    {field.label}
                                </Label>
                                <Input
                                    id={field.name}
                                    type="password"
                                    autoComplete="off"
                                    value={values[field.name]}
                                    placeholder={
                                        provider?.hints[
                                            field.name as keyof Provider['hints']
                                        ]
                                            ? `Current: ${provider.hints[field.name as keyof Provider['hints']]}`
                                            : 'Not set'
                                    }
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            [field.name]: event.target.value,
                                        })
                                    }
                                />
                                {errors[field.name] ? (
                                    <p className="text-sm text-destructive">
                                        {errors[field.name]}
                                    </p>
                                ) : null}
                            </div>
                        ))}

                    {provider?.key === 'theoremreach' ? (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="partner_id">Partner ID</Label>
                                <Input
                                    id="partner_id"
                                    value={values.partner_id}
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            partner_id: event.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="currency_name_plural">
                                    Currency name (plural)
                                </Label>
                                <Input
                                    id="currency_name_plural"
                                    value={values.currency_name_plural}
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            currency_name_plural:
                                                event.target.value,
                                        })
                                    }
                                />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="exchange_rate">
                                    Exchange rate
                                </Label>
                                <Input
                                    id="exchange_rate"
                                    value={values.exchange_rate}
                                    onChange={(event) =>
                                        setValues({
                                            ...values,
                                            exchange_rate: event.target.value,
                                        })
                                    }
                                />
                            </div>
                        </>
                    ) : null}

                    {provider.connection ? (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="base_url">
                                    Survey feed base URL
                                </Label>
                                <Input
                                    id="base_url"
                                    value={connection.base_url}
                                    placeholder={
                                        provider.connection.default_base_url
                                    }
                                    onChange={(event) =>
                                        setConnection({
                                            ...connection,
                                            base_url: event.target.value,
                                        })
                                    }
                                />
                                <p className="text-xs text-muted-foreground">
                                    Empty uses the default. Only https
                                    addresses on tapresearch.com are accepted.
                                </p>
                                {errors.base_url ? (
                                    <p className="text-sm text-destructive">
                                        {errors.base_url}
                                    </p>
                                ) : null}
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="timeout_seconds">
                                    Request timeout (seconds)
                                </Label>
                                <Input
                                    id="timeout_seconds"
                                    type="number"
                                    min={2}
                                    max={30}
                                    value={connection.timeout_seconds}
                                    placeholder={String(
                                        provider.connection
                                            .default_timeout_seconds,
                                    )}
                                    onChange={(event) =>
                                        setConnection({
                                            ...connection,
                                            timeout_seconds: event.target.value,
                                        })
                                    }
                                />
                                {errors.timeout_seconds ? (
                                    <p className="text-sm text-destructive">
                                        {errors.timeout_seconds}
                                    </p>
                                ) : null}
                            </div>
                        </>
                    ) : null}

                    <div className="grid gap-1.5">
                        <Label htmlFor="approval_status">Approval status</Label>
                        <Input
                            id="approval_status"
                            value={values.approval_status}
                            onChange={(event) =>
                                setValues({
                                    ...values,
                                    approval_status: event.target.value,
                                })
                            }
                        />
                    </div>

                    {(
                        [
                            [
                                'sandbox_mode',
                                'Sandbox / test mode',
                            ],
                            [
                                'production_enabled',
                                'Production enabled flag',
                            ],
                            [
                                'reward_cashout_permission',
                                'Cash-redeemable reward permission',
                            ],
                            [
                                'concurrent_provider_permission',
                                'Concurrent provider permission (TheoremReach)',
                            ],
                        ] as const
                    ).map(([name, label]) => (
                        <div
                            key={name}
                            className="flex items-center justify-between gap-3"
                        >
                            <Label htmlFor={name}>{label}</Label>
                            <Switch
                                id={name}
                                checked={values[name]}
                                onCheckedChange={(checked) =>
                                    setValues({
                                        ...values,
                                        [name]: checked,
                                    })
                                }
                            />
                        </div>
                    ))}

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Save settings
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

AdsProviders.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Providers', href: '/admin/ads/providers' },
    ],
};
