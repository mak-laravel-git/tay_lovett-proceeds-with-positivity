import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    AdminEmpty,
    AdminNotice,
    AdminPage,
    AdminSectionCard,
    StatusBadge,
} from '@/components/admin/admin-page';
import {
    Field,
    SelectField,
    SwitchField,
} from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { SelectOption } from '@/types/ads';

type Placement = {
    id: number;
    key: string;
    name: string;
    kind: string;
    network: string;
    location: string | null;
    ad_unit_path: string | null;
    adsense_slot_id: string | null;
    sizes: string;
    devices: string;
    min_height: number;
    every_n: number | null;
    load_timeout_seconds: number;
    min_watch_seconds: number;
    is_enabled: boolean;
    sort_order: number;
};

type Options = {
    kinds: SelectOption[];
    networks: SelectOption[];
    locations: SelectOption[];
    devices: SelectOption[];
};

type Props = {
    placements: Placement[];
    options: Options;
    switches: {
        rewarded_enabled: boolean;
        display_enabled: boolean;
        has_adsense_client: boolean;
    };
};

export default function GoogleAdPlacements({
    placements,
    options,
    switches,
}: Props) {
    // `null` is closed, `'new'` creates, otherwise the placement being edited.
    const [editing, setEditing] = useState<Placement | 'new' | null>(null);
    const label = (list: SelectOption[], value: string | null) =>
        list.find((option) => option.value === value)?.label ?? value ?? '—';

    const remove = (placement: Placement) => {
        if (window.confirm(`Delete the ad unit “${placement.name}”?`)) {
            router.delete(`/admin/google-ads/placements/${placement.id}`, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Ad units" />
            <AdminPage
                title="Ad units"
                description="Rewarded units used by the quote workflow, and regular units shown on pages."
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus /> Add ad unit
                    </Button>
                }
            >
                {!switches.rewarded_enabled || !switches.display_enabled ? (
                    <AdminNotice tone="warn">
                        {!switches.rewarded_enabled
                            ? 'Rewarded ads are switched off. '
                            : ''}
                        {!switches.display_enabled
                            ? 'Regular ads are switched off. '
                            : ''}
                        Change this under{' '}
                        <Link href="/admin/google-ads" className="underline">
                            Google ads
                        </Link>
                        .
                    </AdminNotice>
                ) : null}

                <AdminSectionCard
                    title="Configured units"
                    description="Regular ads never appear on admin or sign-in pages, in dialogs or beside an open survey."
                >
                    {placements.length === 0 ? (
                        <AdminEmpty>No ad units yet.</AdminEmpty>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {placements.map((placement) => (
                                    <TableRow key={placement.id}>
                                        <TableCell>
                                            <b>{placement.name}</b>
                                            <div className="text-xs text-muted-foreground">
                                                {placement.key}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {label(
                                                options.kinds,
                                                placement.kind,
                                            )}
                                            <div className="text-xs text-muted-foreground">
                                                {label(
                                                    options.networks,
                                                    placement.network,
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {placement.location
                                                ? label(
                                                      options.locations,
                                                      placement.location,
                                                  )
                                                : 'Quote workflow'}
                                        </TableCell>
                                        <TableCell className="font-mono text-xs">
                                            {placement.ad_unit_path ??
                                                placement.adsense_slot_id}
                                        </TableCell>
                                        <TableCell>
                                            <StatusBadge
                                                value={
                                                    placement.is_enabled
                                                        ? 'success'
                                                        : 'skipped'
                                                }
                                                label={
                                                    placement.is_enabled
                                                        ? 'enabled'
                                                        : 'disabled'
                                                }
                                            />
                                        </TableCell>
                                        <TableCell className="text-right whitespace-nowrap">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    setEditing(placement)
                                                }
                                            >
                                                <Pencil /> Edit
                                            </Button>{' '}
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                aria-label={`Delete ${placement.name}`}
                                                onClick={() =>
                                                    remove(placement)
                                                }
                                            >
                                                <Trash2 />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </AdminSectionCard>
            </AdminPage>

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setEditing(null);
                    }
                }}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>
                            {editing === 'new' ? 'Add ad unit' : 'Edit ad unit'}
                        </DialogTitle>
                        <DialogDescription>
                            Copy the unit path or slot ID from your Google
                            account.
                        </DialogDescription>
                    </DialogHeader>
                    {editing !== null ? (
                        <PlacementForm
                            key={editing === 'new' ? 'new' : editing.id}
                            placement={editing === 'new' ? null : editing}
                            options={options}
                            hasAdSenseClient={switches.has_adsense_client}
                            onDone={() => setEditing(null)}
                        />
                    ) : null}
                </DialogContent>
            </Dialog>
        </>
    );
}

function PlacementForm({
    placement,
    options,
    hasAdSenseClient,
    onDone,
}: {
    placement: Placement | null;
    options: Options;
    hasAdSenseClient: boolean;
    onDone: () => void;
}) {
    const form = useForm({
        key: placement?.key ?? '',
        name: placement?.name ?? '',
        kind: placement?.kind ?? 'rewarded',
        network: placement?.network ?? 'ad_manager',
        location: placement?.location ?? 'feed_top',
        ad_unit_path: placement?.ad_unit_path ?? '',
        adsense_slot_id: placement?.adsense_slot_id ?? '',
        sizes: placement?.sizes ?? '',
        devices: placement?.devices ?? 'all',
        min_height: placement?.min_height ?? 0,
        every_n: placement?.every_n ?? 4,
        load_timeout_seconds: placement?.load_timeout_seconds ?? 10,
        min_watch_seconds: placement?.min_watch_seconds ?? 5,
        is_enabled: placement?.is_enabled ?? true,
        sort_order: placement?.sort_order ?? 10,
    });
    const { data, setData, errors } = form;
    const rewarded = data.kind === 'rewarded';
    const adManager = data.network === 'ad_manager';

    const numberField = (
        name:
            | 'min_height'
            | 'every_n'
            | 'load_timeout_seconds'
            | 'min_watch_seconds'
            | 'sort_order',
        label: string,
        hint?: string,
    ) => (
        <Field label={label} hint={hint} error={errors[name]}>
            <Input
                type="number"
                min={0}
                value={data[name]}
                onChange={(event) => setData(name, Number(event.target.value))}
            />
        </Field>
    );

    const textField = (
        name: 'key' | 'name' | 'ad_unit_path' | 'adsense_slot_id' | 'sizes',
        label: string,
        placeholder: string,
        hint?: string,
    ) => (
        <Field label={label} hint={hint} error={errors[name]}>
            <Input
                value={data[name]}
                placeholder={placeholder}
                onChange={(event) => setData(name, event.target.value)}
            />
        </Field>
    );

    return (
        <form
            className="grid gap-4 sm:grid-cols-2"
            onSubmit={(event) => {
                event.preventDefault();
                const visit = { preserveScroll: true, onSuccess: onDone };

                if (placement) {
                    form.put(
                        `/admin/google-ads/placements/${placement.id}`,
                        visit,
                    );
                } else {
                    form.post('/admin/google-ads/placements', visit);
                }
            }}
        >
            {textField('name', 'Name', 'Feed banner')}
            {textField(
                'key',
                'Key',
                'feed-banner',
                'Unique; letters, numbers, dashes.',
            )}
            <SelectField
                label="Type"
                error={errors.kind}
                value={data.kind}
                options={options.kinds}
                onChange={(value) => {
                    setData((current) => ({
                        ...current,
                        kind: value,
                        // Rewarded web ads only exist in Ad Manager.
                        network:
                            value === 'rewarded'
                                ? 'ad_manager'
                                : current.network,
                    }));
                }}
            />
            <SelectField
                label="Google product"
                error={errors.network}
                value={data.network}
                disabled={rewarded}
                options={options.networks}
                onChange={(value) => setData('network', value)}
            />
            {adManager ? (
                textField(
                    'ad_unit_path',
                    'Ad Manager unit path',
                    '/1234567/rewarded',
                )
            ) : (
                <>
                    {textField(
                        'adsense_slot_id',
                        'AdSense slot ID',
                        '1234567890',
                        hasAdSenseClient
                            ? undefined
                            : 'Also add the AdSense client ID under Google ads.',
                    )}
                </>
            )}
            {rewarded ? (
                <>
                    {numberField(
                        'load_timeout_seconds',
                        'Load timeout (seconds)',
                        'After this the ad counts as failed.',
                    )}
                    {numberField(
                        'min_watch_seconds',
                        'Minimum watch time (seconds)',
                        'A reward reported sooner is refused.',
                    )}
                </>
            ) : (
                <>
                    <SelectField
                        label="Page location"
                        error={errors.location}
                        value={data.location}
                        options={options.locations}
                        onChange={(value) => setData('location', value)}
                    />
                    <SelectField
                        label="Devices"
                        error={errors.devices}
                        value={data.devices}
                        options={options.devices}
                        onChange={(value) => setData('devices', value)}
                    />
                    {adManager
                        ? textField(
                              'sizes',
                              'Sizes',
                              '300x250, 336x280',
                              'Empty uses 300x250.',
                          )
                        : null}
                    {numberField(
                        'min_height',
                        'Reserved height (pixels)',
                        'Stops the page jumping when the ad loads.',
                    )}
                    {data.location === 'feed_between'
                        ? numberField('every_n', 'Show after every N quotes')
                        : null}
                </>
            )}
            {numberField(
                'sort_order',
                'Order',
                'Lower numbers are used first.',
            )}
            <SwitchField
                id="is_enabled"
                label="Enabled"
                checked={data.is_enabled}
                onChange={(checked) => setData('is_enabled', checked)}
            />
            <div className="flex justify-end gap-2 sm:col-span-2">
                <Button type="button" variant="outline" onClick={onDone}>
                    Cancel
                </Button>
                <Button type="submit" disabled={form.processing}>
                    Save ad unit
                </Button>
            </div>
        </form>
    );
}

GoogleAdPlacements.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Google ads', href: '/admin/google-ads' },
        { title: 'Ad units', href: '/admin/google-ads/placements' },
    ],
};
