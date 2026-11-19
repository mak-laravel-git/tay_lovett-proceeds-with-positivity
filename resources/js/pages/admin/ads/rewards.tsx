import { Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
} from '@/components/admin/admin-page';
import { FilterField } from '@/components/admin/ads/filters';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { SelectOption } from '@/types/ads';

type Rule = {
    id: number;
    provider_key: string | null;
    activity_type: string;
    points: number;
    is_enabled: boolean;
};

type Props = {
    rules: Rule[];
    providers: { key: string; label: string }[];
    activity_types: SelectOption[];
    settings: {
        settlement_timezone: string;
        point_usd_value: string;
        reconciliation_tolerance_usd: string;
        reconciliation_recheck_days: number;
        offers_cache_seconds: number;
        gate_quote_generation: boolean;
    };
    timezones: string[];
};

const selectClass =
    'h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

export default function AdsRewards({
    rules,
    providers,
    activity_types,
    settings,
    timezones,
}: Props) {
    const label = (key: string | null) =>
        key === null
            ? 'All providers (global rule)'
            : (providers.find((provider) => provider.key === key)?.label ??
              key);

    return (
        <>
            <Head title="Rewards" />
            <AdminPage
                title="Rewards"
                description="How many points a user receives for each verified completion."
            >
                <AdminNotice>
                    Points are awarded only after the provider’s verified
                    confirmation arrives, and always use the rule below — never
                    the reward amount a survey advertises. A provider-specific
                    rule overrides the global one. A disabled rule is ignored,
                    so a disabled provider rule falls back to the global rule.
                </AdminNotice>

                <AdminSectionCard
                    title="Reward rules"
                    description="Fixed points per successful completion."
                >
                    {rules.length === 0 ? (
                        <AdminEmpty>
                            No rules yet. Until you add one, completed surveys
                            are approved but earn no points.
                        </AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Applies to</TableHead>
                                    <TableHead>Activity</TableHead>
                                    <TableHead>Points</TableHead>
                                    <TableHead>Enabled</TableHead>
                                    <TableHead className="w-10" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rules.map((rule) => (
                                    <RuleRow
                                        key={rule.id}
                                        rule={rule}
                                        label={label(rule.provider_key)}
                                    />
                                ))}
                            </TableBody>
                        </Table>
                    )}
                    <NewRuleForm
                        providers={providers}
                        activityTypes={activity_types}
                    />
                </AdminSectionCard>

                <SettingsForm settings={settings} timezones={timezones} />
            </AdminPage>
        </>
    );
}

function RuleRow({ rule, label }: { rule: Rule; label: string }) {
    const [points, setPoints] = useState(String(rule.points));

    const save = (data: Partial<{ points: number; is_enabled: boolean }>) =>
        router.put(`/admin/ads/rewards/${rule.id}`, data, {
            preserveScroll: true,
            onError: (errors) => toast.error(Object.values(errors)[0]),
        });

    return (
        <TableRow>
            <TableCell className="font-medium">{label}</TableCell>
            <TableCell className="capitalize">{rule.activity_type}</TableCell>
            <TableCell>
                <div className="flex items-center gap-2">
                    <Input
                        type="number"
                        min={0}
                        value={points}
                        className="w-28"
                        aria-label={`Points for ${label}`}
                        onChange={(event) => setPoints(event.target.value)}
                    />
                    <Button
                        size="sm"
                        variant="outline"
                        disabled={points === String(rule.points)}
                        onClick={() => save({ points: Number(points) })}
                    >
                        Save
                    </Button>
                </div>
            </TableCell>
            <TableCell>
                <Switch
                    checked={rule.is_enabled}
                    aria-label={`Enable rule for ${label}`}
                    onCheckedChange={(checked) => save({ is_enabled: checked })}
                />
            </TableCell>
            <TableCell>
                <Button
                    size="icon"
                    variant="ghost"
                    aria-label={`Delete rule for ${label}`}
                    onClick={() => {
                        if (confirm('Remove this reward rule?')) {
                            router.delete(`/admin/ads/rewards/${rule.id}`, {
                                preserveScroll: true,
                            });
                        }
                    }}
                >
                    <Trash2 />
                </Button>
            </TableCell>
        </TableRow>
    );
}

function NewRuleForm({
    providers,
    activityTypes,
}: {
    providers: { key: string; label: string }[];
    activityTypes: SelectOption[];
}) {
    const [provider, setProvider] = useState('');
    const [type, setType] = useState(activityTypes[0]?.value ?? 'survey');
    const [points, setPoints] = useState('');
    const [processing, setProcessing] = useState(false);

    return (
        <form
            className="mt-4 grid items-end gap-3 rounded-xl border border-dashed border-[#d4e7ed] bg-[#f8fbfe] p-3 sm:grid-cols-[1fr_1fr_8rem_auto]"
            onSubmit={(event) => {
                event.preventDefault();
                router.post(
                    '/admin/ads/rewards',
                    {
                        provider_key: provider === '' ? null : provider,
                        activity_type: type,
                        points: Number(points),
                        is_enabled: true,
                    },
                    {
                        preserveScroll: true,
                        onStart: () => setProcessing(true),
                        onFinish: () => setProcessing(false),
                        onSuccess: () => setPoints(''),
                        onError: (errors) =>
                            toast.error(Object.values(errors)[0]),
                    },
                );
            }}
        >
            <FilterField label="Applies to">
                <select
                    className={selectClass}
                    value={provider}
                    onChange={(event) => setProvider(event.target.value)}
                >
                    <option value="">All providers (global rule)</option>
                    {providers.map((item) => (
                        <option key={item.key} value={item.key}>
                            {item.label}
                        </option>
                    ))}
                </select>
            </FilterField>
            <FilterField label="Activity type">
                <select
                    className={selectClass}
                    value={type}
                    onChange={(event) => setType(event.target.value)}
                >
                    {activityTypes.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </select>
            </FilterField>
            <FilterField label="Points">
                <Input
                    type="number"
                    min={0}
                    required
                    value={points}
                    onChange={(event) => setPoints(event.target.value)}
                />
            </FilterField>
            <Button type="submit" disabled={processing || points === ''}>
                Add / replace rule
            </Button>
        </form>
    );
}

function SettingsForm({
    settings,
    timezones,
}: {
    settings: Props['settings'];
    timezones: string[];
}) {
    const [values, setValues] = useState({
        settlement_timezone: settings.settlement_timezone,
        point_usd_value: settings.point_usd_value,
        reconciliation_tolerance_usd: settings.reconciliation_tolerance_usd,
        reconciliation_recheck_days: String(
            settings.reconciliation_recheck_days,
        ),
        offers_cache_seconds: String(settings.offers_cache_seconds),
        gate_quote_generation: settings.gate_quote_generation,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const set = (
        key: Exclude<keyof typeof values, 'gate_quote_generation'>,
        value: string,
    ) =>
        setValues((current) => ({ ...current, [key]: value }));

    return (
        <AdminSectionCard
            title="Settings"
            description="Day boundaries, the value of a point, and reconciliation tuning."
        >
            <form
                className="grid gap-4 sm:grid-cols-2"
                onSubmit={(event) => {
                    event.preventDefault();
                    router.put('/admin/ads/rewards/settings', values, {
                        preserveScroll: true,
                        onStart: () => setProcessing(true),
                        onFinish: () => setProcessing(false),
                        onSuccess: () => setErrors({}),
                        onError: (bag) => setErrors(bag),
                    });
                }}
            >
                <FilterField label="Settlement timezone (day boundary & midnight reconciliation)">
                    <select
                        className={selectClass}
                        value={values.settlement_timezone}
                        onChange={(event) =>
                            set('settlement_timezone', event.target.value)
                        }
                    >
                        {timezones.map((zone) => (
                            <option key={zone} value={zone}>
                                {zone}
                            </option>
                        ))}
                    </select>
                    <FieldError message={errors.settlement_timezone} />
                </FilterField>
                <FilterField label="Value of one point in USD (optional, estimates only)">
                    <Input
                        inputMode="decimal"
                        placeholder="e.g. 0.01"
                        value={values.point_usd_value}
                        onChange={(event) =>
                            set('point_usd_value', event.target.value)
                        }
                    />
                    <FieldError message={errors.point_usd_value} />
                </FilterField>
                <FilterField label="Reconciliation rounding tolerance (USD per row)">
                    <Input
                        inputMode="decimal"
                        value={values.reconciliation_tolerance_usd}
                        onChange={(event) =>
                            set(
                                'reconciliation_tolerance_usd',
                                event.target.value,
                            )
                        }
                    />
                    <FieldError message={errors.reconciliation_tolerance_usd} />
                </FilterField>
                <FilterField label="Re-check previous days (delayed reports)">
                    <Input
                        type="number"
                        min={0}
                        max={14}
                        value={values.reconciliation_recheck_days}
                        onChange={(event) =>
                            set(
                                'reconciliation_recheck_days',
                                event.target.value,
                            )
                        }
                    />
                    <FieldError message={errors.reconciliation_recheck_days} />
                </FilterField>
                <FilterField label="Survey list cache (seconds)">
                    <Input
                        type="number"
                        min={30}
                        max={3600}
                        value={values.offers_cache_seconds}
                        onChange={(event) =>
                            set('offers_cache_seconds', event.target.value)
                        }
                    />
                    <FieldError message={errors.offers_cache_seconds} />
                </FilterField>
                <FilterField label="Quote generation needs a completed survey">
                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            checked={values.gate_quote_generation}
                            onChange={(event) =>
                                setValues((current) => ({
                                    ...current,
                                    gate_quote_generation: event.target.checked,
                                }))
                            }
                        />
                        Require a survey before each quote (turn off if no
                        provider is configured yet)
                    </label>
                </FilterField>
                <div className="flex items-end">
                    <Button type="submit" disabled={processing}>
                        Save settings
                    </Button>
                </div>
            </form>
        </AdminSectionCard>
    );
}

function FieldError({ message }: { message?: string }) {
    return message ? (
        <span className="text-xs text-destructive">{message}</span>
    ) : null;
}

AdsRewards.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Advertising', href: '/admin/ads' },
        { title: 'Rewards', href: '/admin/ads/rewards' },
    ],
};
