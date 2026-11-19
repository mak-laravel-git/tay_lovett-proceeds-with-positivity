import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
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

type SuspendDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    name: string;
    suspendUrl: string;
};

export function SuspendDialog({
    open,
    onOpenChange,
    name,
    suspendUrl,
}: SuspendDialogProps) {
    const form = useForm({ days: 7, reason: '' });

    useEffect(() => {
        if (open) {
            form.setData({ days: 7, reason: '' });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Suspend {name}</DialogTitle>
                    <DialogDescription>
                        Temporarily block login for a set number of days.
                    </DialogDescription>
                </DialogHeader>
                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(suspendUrl, {
                            preserveScroll: true,
                            onSuccess: () => {
                                toast.success('User suspended.');
                                onOpenChange(false);
                            },
                        });
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="suspend-days">Days</Label>
                        <Input
                            id="suspend-days"
                            type="number"
                            min={1}
                            max={3650}
                            value={form.data.days}
                            onChange={(event) =>
                                form.setData(
                                    'days',
                                    Number(event.target.value) || 1,
                                )
                            }
                            required
                        />
                        <InputError message={form.errors.days} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="suspend-reason">Reason (optional)</Label>
                        <Input
                            id="suspend-reason"
                            value={form.data.reason}
                            onChange={(event) =>
                                form.setData('reason', event.target.value)
                            }
                            placeholder="Why is this account suspended?"
                        />
                        <InputError message={form.errors.reason} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Suspend
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
