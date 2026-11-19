import { Form, Head, router } from '@inertiajs/react';
import {
    Loader2,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { AiDriverSelect } from '@/components/admin/ai-driver-select';
import { ModelCombobox } from '@/components/admin/model-combobox';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
    formatCents,
} from '@/components/admin/admin-page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AiDriverOption } from '@/types/ai';

type ModelRow = {
    id: number;
    driver: string;
    driver_label: string;
    name: string;
    max_request_cents: number | null;
    is_enabled: boolean;
    structured_output_tested: boolean;
    has_enabled_provider: boolean;
};

const primary = 'bg-[#ed2948] text-white hover:bg-[#d61f3d]';

function ModelFields({
    drivers,
    model,
    errors,
}: {
    drivers: AiDriverOption[];
    model?: ModelRow;
    errors: Record<string, string>;
}) {
    const [driver, setDriver] = useState(
        model?.driver ?? drivers[0]?.value ?? '',
    );
    const [name, setName] = useState(model?.name ?? '');
    const [cents, setCents] = useState(String(model?.max_request_cents ?? 5));
    const suggestions =
        drivers.find((item) => item.value === driver)?.suggested_models ?? [];

    return (
        <>
            <div className="grid gap-2">
                <Label>Provider type</Label>
                <AiDriverSelect
                    drivers={drivers}
                    value={driver}
                    onValueChange={(value) => {
                        setDriver(value);
                        setName('');
                    }}
                    disabled={model !== undefined}
                />
                {errors.driver ? (
                    <p className="text-sm text-[#ed2948]">{errors.driver}</p>
                ) : null}
            </div>
            <div className="grid gap-2">
                <Label htmlFor="model-name">Model</Label>
                <input type="hidden" name="name" value={name} />
                <ModelCombobox
                    id="model-name"
                    options={suggestions}
                    value={name}
                    onValueChange={setName}
                />
                {errors.name ? (
                    <p className="text-sm text-[#ed2948]">{errors.name}</p>
                ) : null}
            </div>
            <div className="grid gap-2">
                <Label htmlFor="model-ceiling">
                    Max cost per request (cents)
                </Label>
                <Input
                    id="model-ceiling"
                    name="max_request_cents"
                    type="number"
                    min={1}
                    value={cents}
                    onChange={(event) => setCents(event.target.value)}
                    className="bg-white"
                />
                <p className="text-xs text-muted-foreground">
                    = {formatCents(Number(cents) || 0)}. Set aside from the
                    daily budget on every quote and counted as spent. One quote
                    usually costs far less than 1 cent, so 5 is a safe cap.
                </p>
                {errors.max_request_cents ? (
                    <p className="text-sm text-[#ed2948]">
                        {errors.max_request_cents}
                    </p>
                ) : null}
            </div>
        </>
    );
}

export default function Models({
    models,
    drivers,
    providerDrivers,
}: {
    models: ModelRow[];
    drivers: AiDriverOption[];
    providerDrivers: string[];
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<ModelRow | null>(null);
    const [deleting, setDeleting] = useState<ModelRow | null>(null);
    const [testingId, setTestingId] = useState<number | null>(null);

    const runTest = (model: ModelRow) => {
        router.post(
            `/admin/ai/models/${model.id}/test`,
            {},
            {
                preserveScroll: true,
                onStart: () => setTestingId(model.id),
                onFinish: () => setTestingId(null),
            },
        );
    };

    const toggle = (model: ModelRow) => {
        router.put(
            `/admin/ai/models/${model.id}`,
            {
                name: model.name,
                max_request_cents: model.max_request_cents ?? 5,
                is_enabled: !model.is_enabled,
            },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="AI models" />
            <AdminPage
                title="AI models"
                description="A model is the specific AI brain a provider runs, such as gpt-4o-mini. The provider holds your key, the model decides which brain writes the quote."
                actions={
                    <Button
                        type="button"
                        className={primary}
                        onClick={() => setCreateOpen(true)}
                    >
                        <Plus className="size-4" />
                        Add model
                    </Button>
                }
            >
                <AdminNotice>
                    Every provider needs at least one enabled model, otherwise
                    it is skipped. In Priority routing the first enabled model
                    of a provider is used. In Manual routing you choose the
                    model for each step.
                </AdminNotice>

                <AdminSectionCard
                    title="Catalog"
                    description={`${models.length} model${models.length === 1 ? '' : 's'}`}
                >
                    {models.length === 0 ? (
                        <AdminEmpty>
                            No models yet. Add one for each provider you
                            created.
                        </AdminEmpty>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[820px] text-left text-sm">
                                <thead className="border-b text-muted-foreground">
                                    <tr>
                                        <th className="px-2 py-2.5 font-medium">
                                            Model
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Provider type
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Max cost / request
                                        </th>
                                        <th className="px-2 py-2.5 font-medium">
                                            Status
                                        </th>
                                        <th className="w-32 px-2 py-2.5" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {models.map((model) => (
                                        <tr
                                            key={model.id}
                                            className="border-b border-[#eef5f8] last:border-0"
                                        >
                                            <td className="px-2 py-3 font-medium text-[#0a1018]">
                                                {model.name}
                                            </td>
                                            <td className="px-2 py-3">
                                                <div className="flex flex-wrap items-center gap-1.5">
                                                    {model.driver_label}
                                                    {!providerDrivers.includes(
                                                        model.driver,
                                                    ) ? (
                                                        <Badge
                                                            variant="outline"
                                                            className="border-amber-200 bg-amber-50 text-amber-800"
                                                        >
                                                            no provider yet
                                                        </Badge>
                                                    ) : null}
                                                </div>
                                            </td>
                                            <td className="px-2 py-3 tabular-nums">
                                                {model.max_request_cents
                                                    ? formatCents(
                                                          model.max_request_cents,
                                                      )
                                                    : '-'}
                                            </td>
                                            <td className="px-2 py-3">
                                                <div className="flex flex-wrap gap-1.5">
                                                    <StatusBadge
                                                        value={
                                                            model.is_enabled
                                                                ? 'approved'
                                                                : 'draft'
                                                        }
                                                        label={
                                                            model.is_enabled
                                                                ? 'enabled'
                                                                : 'disabled'
                                                        }
                                                    />
                                                    <StatusBadge
                                                        value={
                                                            model.structured_output_tested
                                                                ? 'healthy'
                                                                : 'unknown'
                                                        }
                                                        label={
                                                            model.structured_output_tested
                                                                ? 'verified'
                                                                : 'not tested'
                                                        }
                                                    />
                                                </div>
                                            </td>
                                            <td className="px-2 py-3">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={
                                                            testingId !==
                                                                null ||
                                                            !model.has_enabled_provider
                                                        }
                                                        title={
                                                            model.has_enabled_provider
                                                                ? undefined
                                                                : 'No enabled provider for this driver yet.'
                                                        }
                                                        onClick={() =>
                                                            runTest(model)
                                                        }
                                                        className="border-[#76d8f8]/50 bg-white text-[#0f6f8f]"
                                                    >
                                                        {testingId ===
                                                        model.id ? (
                                                            <Loader2 className="size-3.5 animate-spin" />
                                                        ) : (
                                                            <ShieldCheck className="size-3.5" />
                                                        )}
                                                        Test
                                                    </Button>
                                                    <DropdownMenu>
                                                    <DropdownMenuTrigger
                                                        asChild
                                                    >
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            className="size-8"
                                                            aria-label={`Actions for ${model.name}`}
                                                        >
                                                            <MoreHorizontal className="size-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        <DropdownMenuItem
                                                            onSelect={() =>
                                                                setEditing(
                                                                    model,
                                                                )
                                                            }
                                                        >
                                                            <Pencil className="size-4" />
                                                            Edit
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onSelect={() =>
                                                                toggle(model)
                                                            }
                                                        >
                                                            <Power className="size-4" />
                                                            {model.is_enabled
                                                                ? 'Disable'
                                                                : 'Enable'}
                                                        </DropdownMenuItem>
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            variant="destructive"
                                                            onSelect={() =>
                                                                setDeleting(
                                                                    model,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="size-4" />
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </AdminSectionCard>
                <p className="text-xs text-muted-foreground">
                    Press Test on a row to call that exact model through its
                    first enabled provider. 'Verified' appears after it
                    succeeds, from either a provider's Test connection or a
                    model's own Test button.
                </p>
            </AdminPage>

            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Add model</DialogTitle>
                        <DialogDescription>
                            Use the exact model id from the provider's
                            documentation.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action="/admin/ai/models"
                        method="post"
                        className="grid gap-3"
                        onSuccess={() => setCreateOpen(false)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <ModelFields
                                    drivers={drivers}
                                    errors={errors}
                                />
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setCreateOpen(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className={primary}
                                    >
                                        Save model
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit model</DialogTitle>
                        <DialogDescription>
                            Changing the model id resets its verified status.
                        </DialogDescription>
                    </DialogHeader>
                    {editing ? (
                        <Form
                            key={editing.id}
                            action={`/admin/ai/models/${editing.id}`}
                            method="put"
                            transform={(data) => ({
                                ...data,
                                is_enabled: editing.is_enabled,
                            })}
                            className="grid gap-3"
                            onSuccess={() => setEditing(null)}
                        >
                            {({ processing, errors }) => (
                                <>
                                    <ModelFields
                                        drivers={drivers}
                                        model={editing}
                                        errors={errors}
                                    />
                                    <DialogFooter>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setEditing(null)}
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className={primary}
                                        >
                                            Save changes
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    ) : null}
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Delete this model?</DialogTitle>
                        <DialogDescription>
                            {deleting?.name} and any routing step that uses it
                            will be removed.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setDeleting(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() =>
                                deleting &&
                                router.delete(
                                    `/admin/ai/models/${deleting.id}`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setDeleting(null),
                                    },
                                )
                            }
                        >
                            Delete model
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Models.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Models', href: '/admin/ai/models' },
    ],
};
