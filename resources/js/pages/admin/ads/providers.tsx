import { Head, router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ChevronRight,
    Copy,
    KeyRound,
    PlugZap,
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
    hints: {
        api_token: string | null;
        api_secret: string | null;
        reporting_access_token: string | null;
    };
    last_tested_at: string | null;
    last_success_at: string | null;
    last_error_category: string | null;
    callback_url: string;
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
                                    <Row label="Credentials">
                                        {provider.configured
                                            ? 'Complete'
                                            : 'Missing the API token or signing secret'}
                                    </Row>
                                    <Row label="API token">
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

            <CredentialsDialog
                provider={editing}
                onClose={() => setEditing(null)}
            />
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
    provider: Provider | null;
    onClose: () => void;
}) {
    const [values, setValues] = useState({
        api_token: '',
        api_secret: '',
        reporting_access_token: '',
    });
    const [processing, setProcessing] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const close = () => {
        setValues({
            api_token: '',
            api_secret: '',
            reporting_access_token: '',
        });
        setErrors({});
        onClose();
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (!provider) {
            return;
        }

        router.put(`/admin/ads/providers/${provider.id}`, values, {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: (bag) => setErrors(bag),
            onSuccess: close,
        });
    };

    const fields: {
        name: keyof typeof values;
        label: string;
        hint: string | null | undefined;
        show: boolean;
    }[] = [
        {
            name: 'api_token',
            label: 'API token',
            hint: provider?.hints.api_token,
            show: true,
        },
        {
            name: 'api_secret',
            label: 'Signing secret',
            hint: provider?.hints.api_secret,
            show: true,
        },
        {
            name: 'reporting_access_token',
            label: 'Reporting access token',
            hint: provider?.hints.reporting_access_token,
            show: provider?.supports_reconciliation ?? false,
        },
    ];

    return (
        <Dialog
            open={provider !== null}
            onOpenChange={(open) => !open && close()}
        >
            <DialogContent>
                <form onSubmit={submit} className="grid gap-4">
                    <DialogHeader>
                        <DialogTitle>{provider?.label} credentials</DialogTitle>
                        <DialogDescription>
                            Stored encrypted and never shown again. Leave a
                            field empty to keep the current value.
                        </DialogDescription>
                    </DialogHeader>
                    {fields
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
                                        field.hint
                                            ? `Current: ${field.hint}`
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
                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={close}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Save credentials
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
