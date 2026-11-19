import { router } from '@inertiajs/react';
import { RotateCcw } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { AdminSectionCard } from '@/components/admin/admin-page';
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
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';

export type SettingValue = {
    value: string | number | boolean | null;
    source: 'database' | 'env';
    secret: boolean;
    has_value: boolean;
};

export type SettingsGroup = Record<string, SettingValue>;

export function textOf(group: SettingsGroup, key: string): string {
    const value = group[key]?.value;

    return value === null || value === undefined ? '' : String(value);
}

export function boolOf(group: SettingsGroup, key: string): boolean {
    return group[key]?.value === true;
}

export const primaryButton = 'bg-[#ed2948] text-white hover:bg-[#d61f3d]';

export function SourceBadge({ source }: { source: 'database' | 'env' }) {
    return (
        <Badge
            variant="outline"
            className={cn(
                'h-5 px-1.5 text-[10px] font-medium',
                source === 'database'
                    ? 'border-[#76d8f8]/60 bg-[#e7f7ff] text-[#0f6f8f]'
                    : 'border-[#d4e7ed] bg-white text-[#526475]',
            )}
            title={
                source === 'database'
                    ? 'Saved in the database'
                    : 'Not saved yet. The value comes from the .env file.'
            }
        >
            {source === 'database' ? 'Database' : 'From .env'}
        </Badge>
    );
}

export function SettingField({
    id,
    label,
    help,
    source,
    error,
    className,
    children,
}: {
    id: string;
    label: string;
    help?: string;
    source?: 'database' | 'env';
    error?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('grid content-start gap-2', className)}>
            <div className="flex items-center gap-2">
                <Label htmlFor={id}>{label}</Label>
                {source ? <SourceBadge source={source} /> : null}
            </div>
            {children}
            {help ? (
                <p className="text-xs text-muted-foreground">{help}</p>
            ) : null}
            {error ? <p className="text-sm text-[#ed2948]">{error}</p> : null}
        </div>
    );
}

export function SwitchField({
    id,
    label,
    help,
    source,
    checked,
    onCheckedChange,
}: {
    id: string;
    label: string;
    help?: string;
    source?: 'database' | 'env';
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
}) {
    return (
        <div className="flex items-start justify-between gap-4 rounded-xl border border-[#d4e7ed] bg-[#f8fbfe] p-3.5">
            <div className="grid gap-1">
                <div className="flex items-center gap-2">
                    <Label htmlFor={id}>{label}</Label>
                    {source ? <SourceBadge source={source} /> : null}
                </div>
                {help ? (
                    <p className="text-xs text-muted-foreground">{help}</p>
                ) : null}
            </div>
            <Switch
                id={id}
                checked={checked}
                onCheckedChange={onCheckedChange}
            />
        </div>
    );
}

/**
 * Card with a form, a Save button and a confirmed "Reset to defaults" for one settings group.
 */
export function SettingsForm({
    group,
    title,
    description,
    processing,
    onSubmit,
    extra,
    children,
}: {
    group: string;
    title: string;
    description: string;
    processing: boolean;
    onSubmit: () => void;
    extra?: ReactNode;
    children: ReactNode;
}) {
    const [resetOpen, setResetOpen] = useState(false);
    const [resetting, setResetting] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        onSubmit();
    };

    return (
        <>
            <AdminSectionCard title={title} description={description} className="max-w-3xl">
                <form onSubmit={submit} className="grid max-w-3xl gap-5">
                    {children}
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="submit"
                            disabled={processing}
                            className={primaryButton}
                        >
                            Save changes
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setResetOpen(true)}
                        >
                            <RotateCcw className="size-4" />
                            Reset to defaults
                        </Button>
                        {extra}
                    </div>
                </form>
            </AdminSectionCard>

            <Dialog open={resetOpen} onOpenChange={setResetOpen}>
                <DialogContent className="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Reset these settings?</DialogTitle>
                        <DialogDescription>
                            Everything saved in this tab is removed from the
                            database. The site goes back to the values in the
                            .env file.
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
                                    `/admin/settings/${group}/reset`,
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
