import { Form, Head, Link, router } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { AiReadiness } from '@/components/admin/ai-readiness';
import {
    AdminPage,
    AdminSectionCard,
    formatCents,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { ReadinessCheck } from '@/types/ai';

type Settings = {
    routing_mode: string;
    timeout_seconds: number;
    max_attempts: number;
    daily_budget_cents: number;
    generation_enabled: boolean;
    default_cooldown_seconds: number;
};

function Field({
    id,
    label,
    help,
    error,
    children,
}: {
    id: string;
    label: string;
    help: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid content-start gap-2">
            <Label htmlFor={id}>{label}</Label>
            {children}
            <p className="text-xs text-muted-foreground">{help}</p>
            {error ? <p className="text-sm text-[#ed2948]">{error}</p> : null}
        </div>
    );
}

export default function AiSettings(props: {
    settings: Settings;
    readiness: ReadinessCheck[];
}) {
    // Remount when saved values change so uncontrolled inputs show the new (or reset) values.
    return <AiSettingsPage key={JSON.stringify(props.settings)} {...props} />;
}

function AiSettingsPage({
    settings,
    readiness,
}: {
    settings: Settings;
    readiness: ReadinessCheck[];
}) {
    const [generationEnabled, setGenerationEnabled] = useState(
        settings.generation_enabled ? '1' : '0',
    );
    const [budget, setBudget] = useState(String(settings.daily_budget_cents));
    const [resetOpen, setResetOpen] = useState(false);
    const [resetting, setResetting] = useState(false);

    return (
        <>
            <Head title="AI settings" />
            <AdminPage
                title="AI settings"
                description="Safety limits for AI quote generation. These apply to Quote Studio, not to the admin test chat."
            >
                <AiReadiness checks={readiness} />

                <AdminSectionCard title="Configuration" className="max-w-3xl">
                    <Form
                        action="/admin/ai/settings"
                        method="put"
                        className="grid max-w-3xl gap-5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="routing_mode"
                                    value={settings.routing_mode}
                                />
                                <Field
                                    id="generation_enabled"
                                    label="Quote generation"
                                    help="Master switch. Off means Quote Studio refuses every request. Quotes that already exist are not touched."
                                    error={errors.generation_enabled}
                                >
                                    <input
                                        type="hidden"
                                        name="generation_enabled"
                                        value={generationEnabled}
                                    />
                                    <Select
                                        value={generationEnabled}
                                        onValueChange={setGenerationEnabled}
                                    >
                                        <SelectTrigger
                                            id="generation_enabled"
                                            className="w-full bg-white sm:w-64"
                                        >
                                            <SelectValue placeholder="Generation" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="0">
                                                Off
                                            </SelectItem>
                                            <SelectItem value="1">On</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </Field>

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <Field
                                        id="daily_budget_cents"
                                        label="Daily budget (cents)"
                                        help={`= ${formatCents(Number(budget) || 0)} per day. When used up, generation stops until tomorrow. 0 blocks everything.`}
                                        error={errors.daily_budget_cents}
                                    >
                                        <Input
                                            id="daily_budget_cents"
                                            name="daily_budget_cents"
                                            type="number"
                                            min={0}
                                            value={budget}
                                            onChange={(event) =>
                                                setBudget(event.target.value)
                                            }
                                            className="bg-white"
                                        />
                                    </Field>
                                    <Field
                                        id="max_attempts"
                                        label="Max attempts per quote"
                                        help="How many providers may be tried for one quote before giving up (1 to 5)."
                                        error={errors.max_attempts}
                                    >
                                        <Input
                                            id="max_attempts"
                                            name="max_attempts"
                                            type="number"
                                            min={1}
                                            max={5}
                                            defaultValue={settings.max_attempts}
                                            className="bg-white"
                                        />
                                    </Field>
                                    <Field
                                        id="timeout_seconds"
                                        label="Timeout (seconds)"
                                        help="How long to wait for one provider before treating it as failed (5 to 60)."
                                        error={errors.timeout_seconds}
                                    >
                                        <Input
                                            id="timeout_seconds"
                                            name="timeout_seconds"
                                            type="number"
                                            min={5}
                                            max={60}
                                            defaultValue={
                                                settings.timeout_seconds
                                            }
                                            className="bg-white"
                                        />
                                    </Field>
                                    <Field
                                        id="default_cooldown_seconds"
                                        label="Cooldown (seconds)"
                                        help="After a rate limit or outage, that provider is skipped for this long."
                                        error={errors.default_cooldown_seconds}
                                    >
                                        <Input
                                            id="default_cooldown_seconds"
                                            name="default_cooldown_seconds"
                                            type="number"
                                            min={1}
                                            max={3600}
                                            defaultValue={
                                                settings.default_cooldown_seconds
                                            }
                                            className="bg-white"
                                        />
                                    </Field>
                                </div>

                                <p className="text-xs text-muted-foreground">
                                    Provider order is set in{' '}
                                    <Link
                                        href="/admin/ai/routing"
                                        className="font-medium text-[#0f6f8f] underline"
                                    >
                                        Routing
                                    </Link>
                                    .
                                </p>

                                <div className="flex flex-wrap items-center gap-2">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-[#ed2948] text-white hover:bg-[#d61f3d]"
                                    >
                                        Save settings
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setResetOpen(true)}
                                    >
                                        <RotateCcw className="size-4" />
                                        Reset to defaults
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </AdminSectionCard>
            </AdminPage>

            <Dialog open={resetOpen} onOpenChange={setResetOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Reset AI settings?</DialogTitle>
                        <DialogDescription>
                            Generation goes back to off, budget to $0, and the
                            limits to their factory values. Your providers,
                            models and manual route steps are not touched.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setResetOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={resetting}
                            onClick={() =>
                                router.post(
                                    '/admin/ai/settings/reset',
                                    {},
                                    {
                                        preserveScroll: true,
                                        onStart: () => setResetting(true),
                                        onFinish: () => setResetting(false),
                                        onSuccess: () => setResetOpen(false),
                                    },
                                )
                            }
                        >
                            Reset settings
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

AiSettings.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'AI settings', href: '/admin/ai/settings' },
    ],
};
