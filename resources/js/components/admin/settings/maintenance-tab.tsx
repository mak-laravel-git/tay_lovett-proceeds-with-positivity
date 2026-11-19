import { router, useForm } from '@inertiajs/react';
import { Check, Copy, PowerOff, ShieldAlert } from 'lucide-react';
import { useState } from 'react';
import { AdminNotice, AdminSectionCard } from '@/components/admin/admin-page';
import { DateTimePicker } from '@/components/admin/settings/date-time-picker';
import { SettingField } from '@/components/admin/settings/shared';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useClipboard } from '@/hooks/use-clipboard';

export type MaintenanceStatus = {
    active: boolean;
    bypassUrl: string | null;
    retryAfter: string | null;
    title: string;
    message: string;
    expectedBackAt: string;
};

export function MaintenanceTab({ status }: { status: MaintenanceStatus }) {
    const form = useForm({
        title: status.title,
        message: status.message,
        expected_back_at: status.expectedBackAt,
    });
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [copiedText, copy] = useClipboard();
    const [disabling, setDisabling] = useState(false);

    const enable = () => {
        setConfirmOpen(false);
        form.post('/admin/settings/maintenance/enable', { preserveScroll: true });
    };

    const disable = () => {
        router.post(
            '/admin/settings/maintenance/disable',
            {},
            { preserveScroll: true, onStart: () => setDisabling(true), onFinish: () => setDisabling(false) },
        );
    };

    return (
        <div className="grid gap-4">
            {status.active ? (
                <AdminNotice tone="warn">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <span>
                            Maintenance mode is on. Everyone except administrators sees the maintenance page
                            below instead of the site.
                        </span>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            disabled={disabling}
                            onClick={disable}
                        >
                            <PowerOff className="size-4" />
                            Turn off
                        </Button>
                    </div>
                </AdminNotice>
            ) : (
                <AdminNotice>
                    Maintenance mode is off. Turning it on runs the same real{' '}
                    <code className="rounded bg-white px-1 py-0.5">php artisan down</code> Laravel uses,
                    so the whole site is blocked for anyone without the secret link below.
                </AdminNotice>
            )}

            {status.active && status.bypassUrl ? (
                <AdminSectionCard
                    title="Secret bypass link"
                    description="Anyone who opens this link gets full, normal access to the site for as long as their browser keeps the cookie, even while maintenance mode stays on for everyone else."
                    className="max-w-3xl"
                >
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Input readOnly value={status.bypassUrl} className="bg-white font-mono text-sm" />
                        <Button
                            type="button"
                            variant="outline"
                            className="shrink-0 bg-white"
                            onClick={() => copy(status.bypassUrl!)}
                        >
                            {copiedText === status.bypassUrl ? (
                                <Check className="size-4" />
                            ) : (
                                <Copy className="size-4" />
                            )}
                            {copiedText === status.bypassUrl ? 'Copied' : 'Copy link'}
                        </Button>
                    </div>
                </AdminSectionCard>
            ) : null}

            <AdminSectionCard
                title={status.active ? 'Update the maintenance page' : 'Turn on maintenance mode'}
                description="This text is baked into the page shown to visitors the moment maintenance mode turns on."
                className="max-w-3xl"
            >
                <form
                    className="grid max-w-xl gap-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        setConfirmOpen(true);
                    }}
                >
                    <SettingField id="maintenance_title" label="Title" error={form.errors.title}>
                        <Input
                            id="maintenance_title"
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                            className="bg-white"
                        />
                    </SettingField>
                    <SettingField id="maintenance_message" label="Message" error={form.errors.message}>
                        <Textarea
                            id="maintenance_message"
                            rows={3}
                            value={form.data.message}
                            onChange={(event) => form.setData('message', event.target.value)}
                            className="bg-white"
                        />
                    </SettingField>
                    <SettingField
                        id="maintenance_back"
                        label="Expected back (optional)"
                        help="Shown to visitors, and used as the Retry-After hint for browsers and bots."
                        error={form.errors.expected_back_at}
                        className="sm:max-w-xs"
                    >
                        <DateTimePicker
                            value={form.data.expected_back_at}
                            onChange={(value) => form.setData('expected_back_at', value)}
                            minDate={new Date()}
                        />
                    </SettingField>
                    <div>
                        <Button
                            type="submit"
                            disabled={form.processing}
                            className={
                                status.active
                                    ? 'bg-[#ed2948] text-white hover:bg-[#d61f3d]'
                                    : 'bg-[#0a1018] text-white hover:bg-[#182c38]'
                            }
                        >
                            <ShieldAlert className="size-4" />
                            {status.active ? 'Update maintenance page' : 'Turn on maintenance mode'}
                        </Button>
                    </div>
                </form>
            </AdminSectionCard>

            <AlertDialog open={confirmOpen} onOpenChange={setConfirmOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>
                            {status.active ? 'Update the live maintenance page?' : 'Turn on maintenance mode?'}
                        </AlertDialogTitle>
                        <AlertDialogDescription>
                            {status.active
                                ? 'The page every visitor sees is replaced immediately with this new text and a new secret link. The old link stops working.'
                                : 'The whole site becomes unreachable for everyone except administrators and anyone holding the secret link this generates.'}
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={enable}>
                            {status.active ? 'Update page' : 'Turn on'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
