import { Form, Head, router } from '@inertiajs/react';
import {
    Loader2,
    MoreHorizontal,
    Pencil,
    Plus,
    Power,
    ShieldCheck,
    Trash2,
    TriangleAlert,
} from 'lucide-react';
import { useState } from 'react';
import { AiDriverSelect } from '@/components/admin/ai-driver-select';
import {
    AdminEmpty,
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

type Credential = {
    id: number;
    driver: string;
    driver_label: string;
    label: string;
    priority: number;
    is_enabled: boolean;
    health: string;
    key_hint: string | null;
    url: string | null;
    enabled_models: number;
    last_tested_at: string | null;
    last_error_category: string | null;
};

const primary = 'bg-[#ed2948] text-white hover:bg-[#d61f3d]';

function InputError({ message }: { message?: string }) {
    return message ? (
        <p className="text-sm text-[#ed2948]">{message}</p>
    ) : null;
}

function ProviderFields({
    drivers,
    credential,
    errors,
}: {
    drivers: AiDriverOption[];
    credential?: Credential;
    errors: Record<string, string>;
}) {
    const [driver, setDriver] = useState(
        credential?.driver ?? drivers[0]?.value ?? '',
    );
    const needsUrl =
        drivers.find((item) => item.value === driver)?.requires_url ?? false;

    return (
        <>
            <div className="grid gap-2">
                <Label>Provider type</Label>
                <AiDriverSelect
                    drivers={drivers}
                    value={driver}
                    onValueChange={setDriver}
                    disabled={credential !== undefined}
                />
                <InputError message={errors.driver} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="provider-label">Name</Label>
                <Input
                    id="provider-label"
                    name="label"
                    defaultValue={credential?.label}
                    placeholder="e.g. OpenAI production"
                    className="bg-white"
                />
                <p className="text-xs text-muted-foreground">
                    Only for you. Use it to tell two keys of the same provider
                    apart.
                </p>
                <InputError message={errors.label} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor="provider-key">
                    API key{credential ? ' (leave empty to keep current)' : ''}
                </Label>
                <Input
                    id="provider-key"
                    name="key"
                    type="password"
                    autoComplete="off"
                    placeholder={credential?.key_hint ?? 'sk-...'}
                    className="bg-white"
                />
                <InputError message={errors.key} />
            </div>
            {needsUrl ? (
                <div className="grid gap-2">
                    <Label htmlFor="provider-url">Server URL</Label>
                    <Input
                        id="provider-url"
                        name="url"
                        defaultValue={credential?.url ?? ''}
                        placeholder="http://localhost:11434"
                        className="bg-white"
                    />
                    <InputError message={errors.url} />
                </div>
            ) : null}
            <div className="grid gap-2">
                <Label htmlFor="provider-priority">Priority</Label>
                <Input
                    id="provider-priority"
                    name="priority"
                    type="number"
                    defaultValue={credential?.priority ?? 1}
                    min={1}
                    className="bg-white"
                />
                <p className="text-xs text-muted-foreground">
                    1 is tried first. If it fails, the next number is used.
                </p>
                <InputError message={errors.priority} />
            </div>
        </>
    );
}

export default function Providers({
    credentials,
    drivers,
}: {
    credentials: Credential[];
    drivers: AiDriverOption[];
}) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<Credential | null>(null);
    const [deleting, setDeleting] = useState<Credential | null>(null);
    const [testingId, setTestingId] = useState<number | null>(null);

    const runTest = (credential: Credential) => {
        router.post(
            `/admin/ai/providers/${credential.id}/test`,
            {},
            {
                preserveScroll: true,
                onStart: () => setTestingId(credential.id),
                onFinish: () => setTestingId(null),
            },
        );
    };

    const toggle = (credential: Credential) => {
        router.put(
            `/admin/ai/providers/${credential.id}`,
            {
                label: credential.label,
                priority: credential.priority,
                url: credential.url,
                is_enabled: !credential.is_enabled,
            },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="AI providers" />
            <AdminPage
                title="AI providers"
                description="A provider is an account with an AI company (OpenAI, Claude, Gemini...). Add its API key here so the site can ask it to write quotes."
                actions={
                    <Button
                        type="button"
                        className={primary}
                        onClick={() => setCreateOpen(true)}
                    >
                        <Plus className="size-4" />
                        Add provider
                    </Button>
                }
            >
                <AdminNotice>
                    Keys are stored encrypted and cannot be read back. Test
                    connection makes one small real request, so it may cost a
                    fraction of a cent. The result appears as a message in the
                    corner.
                </AdminNotice>

                <AdminSectionCard
                    title="Credentials"
                    description={`${credentials.length} provider${credentials.length === 1 ? '' : 's'}`}
                >
                    {credentials.length === 0 ? (
                        <AdminEmpty>
                            No providers yet. Add one, then add a model for it
                            under Models.
                        </AdminEmpty>
                    ) : (
                        <div className="grid gap-3">
                            {credentials.map((credential) => (
                                <div
                                    key={credential.id}
                                    className="flex flex-col gap-3 rounded-2xl border border-[#d4e7ed] bg-[#f8fbfe] p-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="font-semibold text-[#0a1018]">
                                                {credential.label}
                                            </p>
                                            <StatusBadge
                                                value={credential.health}
                                            />
                                            <StatusBadge
                                                value={
                                                    credential.is_enabled
                                                        ? 'approved'
                                                        : 'draft'
                                                }
                                                label={
                                                    credential.is_enabled
                                                        ? 'enabled'
                                                        : 'disabled'
                                                }
                                            />
                                        </div>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {credential.driver_label} · priority{' '}
                                            {credential.priority}
                                            {credential.key_hint
                                                ? ` · key ${credential.key_hint}`
                                                : ''}
                                        </p>
                                        {credential.last_tested_at ? (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Last tested{' '}
                                                {new Date(
                                                    credential.last_tested_at,
                                                ).toLocaleString()}
                                                {credential.last_error_category
                                                    ? ` · ${credential.last_error_category.replaceAll('_', ' ')}`
                                                    : ''}
                                            </p>
                                        ) : (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Never tested
                                            </p>
                                        )}
                                        {credential.enabled_models === 0 ? (
                                            <p className="mt-1.5 flex items-center gap-1.5 text-xs text-[#a1283c]">
                                                <TriangleAlert className="size-3.5" />
                                                No enabled model for this
                                                provider. Add one under Models
                                                or it will never be used.
                                            </p>
                                        ) : null}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled={testingId !== null}
                                            onClick={() => runTest(credential)}
                                            className="border-[#76d8f8]/50 bg-white text-[#0f6f8f]"
                                        >
                                            {testingId === credential.id ? (
                                                <Loader2 className="size-3.5 animate-spin" />
                                            ) : (
                                                <ShieldCheck className="size-3.5" />
                                            )}
                                            {testingId === credential.id
                                                ? 'Testing...'
                                                : 'Test connection'}
                                        </Button>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button
                                                    type="button"
                                                    size="icon"
                                                    variant="outline"
                                                    className="size-8 bg-white"
                                                    aria-label={`Actions for ${credential.label}`}
                                                >
                                                    <MoreHorizontal className="size-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        setEditing(credential)
                                                    }
                                                >
                                                    <Pencil className="size-4" />
                                                    Edit
                                                </DropdownMenuItem>
                                                <DropdownMenuItem
                                                    onSelect={() =>
                                                        toggle(credential)
                                                    }
                                                >
                                                    <Power className="size-4" />
                                                    {credential.is_enabled
                                                        ? 'Disable'
                                                        : 'Enable'}
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onSelect={() =>
                                                        setDeleting(credential)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                    Delete
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </AdminSectionCard>
            </AdminPage>

            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Add provider</DialogTitle>
                        <DialogDescription>
                            The key is stored encrypted and never shown again.
                        </DialogDescription>
                    </DialogHeader>
                    <Form
                        action="/admin/ai/providers"
                        method="post"
                        className="grid gap-3"
                        onSuccess={() => setCreateOpen(false)}
                    >
                        {({ processing, errors }) => (
                            <>
                                <ProviderFields
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
                                        Save provider
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
                        <DialogTitle>Edit provider</DialogTitle>
                        <DialogDescription>
                            Enter a new API key only if you want to replace the
                            current one.
                        </DialogDescription>
                    </DialogHeader>
                    {editing ? (
                        <Form
                            key={editing.id}
                            action={`/admin/ai/providers/${editing.id}`}
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
                                    <ProviderFields
                                        drivers={drivers}
                                        credential={editing}
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
                        <DialogTitle>Delete this provider?</DialogTitle>
                        <DialogDescription>
                            {deleting?.label} will stop being used and its
                            routing steps are removed. Past usage stays in the
                            log.
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
                                    `/admin/ai/providers/${deleting.id}`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => setDeleting(null),
                                    },
                                )
                            }
                        >
                            Delete provider
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

Providers.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Providers', href: '/admin/ai/providers' },
    ],
};
