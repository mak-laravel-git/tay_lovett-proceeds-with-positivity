import { Head, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AiReadiness } from '@/components/admin/ai-readiness';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
} from '@/components/admin/admin-page';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import type { ReadinessCheck } from '@/types/ai';

type Step = {
    id: number;
    credential_label: string;
    driver_label: string;
    model_name: string;
    usable: boolean;
};

type CredentialOption = {
    id: number;
    label: string;
    driver: string;
    driver_label: string;
};

type ModelOption = { id: number; driver: string; name: string };

type LiveStep = {
    label: string;
    driver: string;
    model: string;
    priority: number;
};

const primary = 'bg-[#ed2948] text-white hover:bg-[#d61f3d]';

export default function Routing({
    mode,
    steps,
    credentials,
    models,
    simulation,
    readiness,
}: {
    mode: 'priority' | 'manual';
    steps: Step[];
    credentials: CredentialOption[];
    models: ModelOption[];
    simulation: LiveStep[];
    readiness: ReadinessCheck[];
}) {
    const [credentialId, setCredentialId] = useState('');
    const [modelId, setModelId] = useState('');
    const [processing, setProcessing] = useState(false);

    const credential = credentials.find(
        (item) => String(item.id) === credentialId,
    );
    const modelOptions = models.filter(
        (item) => item.driver === credential?.driver,
    );

    const addStep = () => {
        router.post(
            '/admin/ai/routing/steps',
            { ai_provider_credential_id: credentialId, ai_model_id: modelId },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onSuccess: () => {
                    setCredentialId('');
                    setModelId('');
                },
            },
        );
    };

    return (
        <>
            <Head title="Routing" />
            <AdminPage
                title="Routing"
                description="Routing decides which provider and model write a quote, and who takes over if the first one fails."
            >
                <AiReadiness checks={readiness} />

                <AdminSectionCard
                    title="Routing mode"
                    description="Pick how the order is decided."
                >
                    <ToggleGroup
                        type="single"
                        variant="outline"
                        value={mode}
                        onValueChange={(value) =>
                            value &&
                            router.put(
                                '/admin/ai/routing/mode',
                                { routing_mode: value },
                                { preserveScroll: true },
                            )
                        }
                        className="w-full sm:w-fit"
                    >
                        <ToggleGroupItem value="priority" className="px-5">
                            Priority (automatic)
                        </ToggleGroupItem>
                        <ToggleGroupItem value="manual" className="px-5">
                            Manual (you choose)
                        </ToggleGroupItem>
                    </ToggleGroup>
                    <p className="mt-3 text-sm text-muted-foreground">
                        {mode === 'priority'
                            ? 'Providers are tried in priority order (1 first), each with its first enabled model. Change the order by editing the priority number on a provider.'
                            : 'Only the steps you add below are used, in the order shown. Use this to pair a provider with an exact model.'}
                    </p>
                </AdminSectionCard>

                {mode === 'manual' ? (
                    <AdminSectionCard
                        title="Manual steps"
                        description="Step 1 is tried first. If it fails (rate limit, no credit, outage) the next step runs."
                    >
                        {steps.length === 0 ? (
                            <AdminEmpty>
                                No steps yet. Add a provider and model below,
                                otherwise generation has no route.
                            </AdminEmpty>
                        ) : (
                            <ol className="grid gap-2">
                                {steps.map((step, index) => (
                                    <li
                                        key={step.id}
                                        className="flex items-center gap-3 rounded-2xl border border-[#d4e7ed] bg-[#f8fbfe] p-3"
                                    >
                                        <span className="flex size-8 shrink-0 items-center justify-center rounded-full bg-[#0a1018] text-sm font-semibold text-[#76d8f8]">
                                            {index + 1}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-semibold text-[#0a1018]">
                                                {step.credential_label}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {step.driver_label} ·{' '}
                                                {step.model_name}
                                            </p>
                                        </div>
                                        {!step.usable ? (
                                            <StatusBadge
                                                value="unhealthy"
                                                label="skipped"
                                            />
                                        ) : null}
                                        <div className="flex gap-1">
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="outline"
                                                className="size-8 bg-white"
                                                aria-label="Move up"
                                                disabled={index === 0}
                                                onClick={() =>
                                                    router.post(
                                                        `/admin/ai/routing/steps/${step.id}/move`,
                                                        { direction: 'up' },
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                <ArrowUp className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="outline"
                                                className="size-8 bg-white"
                                                aria-label="Move down"
                                                disabled={
                                                    index === steps.length - 1
                                                }
                                                onClick={() =>
                                                    router.post(
                                                        `/admin/ai/routing/steps/${step.id}/move`,
                                                        { direction: 'down' },
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                <ArrowDown className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="outline"
                                                className="size-8 bg-white text-[#ed2948]"
                                                aria-label="Remove step"
                                                onClick={() =>
                                                    router.delete(
                                                        `/admin/ai/routing/steps/${step.id}`,
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        )}

                        <div className="mt-4 grid gap-3 rounded-2xl border border-dashed border-[#d4e7ed] p-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                            <div className="grid gap-1.5">
                                <span className="text-sm font-medium">
                                    Provider
                                </span>
                                <Select
                                    value={credentialId}
                                    onValueChange={(value) => {
                                        setCredentialId(value);
                                        setModelId('');
                                    }}
                                    disabled={credentials.length === 0}
                                >
                                    <SelectTrigger
                                        className="w-full bg-white"
                                        aria-label="Provider"
                                    >
                                        <SelectValue placeholder="Choose provider" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {credentials.map((item) => (
                                            <SelectItem
                                                key={item.id}
                                                value={String(item.id)}
                                            >
                                                {item.label} ·{' '}
                                                {item.driver_label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-1.5">
                                <span className="text-sm font-medium">
                                    Model
                                </span>
                                <Select
                                    value={modelId}
                                    onValueChange={setModelId}
                                    disabled={modelOptions.length === 0}
                                >
                                    <SelectTrigger
                                        className="w-full bg-white"
                                        aria-label="Model"
                                    >
                                        <SelectValue
                                            placeholder={
                                                credential
                                                    ? 'Choose model'
                                                    : 'Pick a provider first'
                                            }
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {modelOptions.map((item) => (
                                            <SelectItem
                                                key={item.id}
                                                value={String(item.id)}
                                            >
                                                {item.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button
                                type="button"
                                className={primary}
                                disabled={!credentialId || !modelId || processing}
                                onClick={addStep}
                            >
                                <Plus className="size-4" />
                                Add step
                            </Button>
                        </div>
                        {credential && modelOptions.length === 0 ? (
                            <p className="mt-2 text-xs text-[#a1283c]">
                                No enabled model for {credential.driver_label}.
                                Add one under Models.
                            </p>
                        ) : null}
                    </AdminSectionCard>
                ) : null}

                <AdminSectionCard
                    title="Live order"
                    description={`${simulation.length} step${simulation.length === 1 ? '' : 's'} that quote generation will really try`}
                >
                    {simulation.length === 0 ? (
                        <AdminEmpty>
                            Nothing is ready. A provider needs an enabled model
                            {mode === 'manual'
                                ? ' and a manual step'
                                : ''}
                            .
                        </AdminEmpty>
                    ) : (
                        <ol className="grid gap-2">
                            {simulation.map((step, index) => (
                                <li
                                    key={`${step.label}-${step.model}-${index}`}
                                    className="flex items-center gap-3 rounded-2xl border border-[#d4e7ed] bg-[#f8fbfe] p-3"
                                >
                                    <span className="flex size-8 items-center justify-center rounded-full bg-[#0a1018] text-sm font-semibold text-[#76d8f8]">
                                        {index + 1}
                                    </span>
                                    <div>
                                        <p className="font-semibold text-[#0a1018]">
                                            {step.label}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {step.model}
                                            {index === 0
                                                ? ' · first choice'
                                                : ' · fallback'}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    )}
                    <div className="mt-3">
                        <AdminNotice>
                            This list is computed from your settings and makes
                            no provider call. Use Test connection on a provider to
                            prove it really answers.
                        </AdminNotice>
                    </div>
                </AdminSectionCard>
            </AdminPage>
        </>
    );
}

Routing.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Routing', href: '/admin/ai/routing' },
    ],
};
