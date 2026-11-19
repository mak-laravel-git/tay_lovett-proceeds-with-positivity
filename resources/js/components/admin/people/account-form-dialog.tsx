import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { FileUploadAvatar } from '@/components/file-upload-avatar';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import type { PeopleAccount } from '@/components/admin/people/types';

type AccountFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    mode: 'create' | 'edit';
    role: 'user' | 'administrator';
    account?: PeopleAccount | null;
    storeUrl: string;
    updateUrl?: string;
};

export function AccountFormDialog({
    open,
    onOpenChange,
    mode,
    role,
    account,
    storeUrl,
    updateUrl,
}: AccountFormDialogProps) {
    const [removeAvatar, setRemoveAvatar] = useState(false);

    const form = useForm<{
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        email_verified: boolean;
        avatar: File | null;
        remove_avatar: boolean;
    }>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        email_verified: true,
        avatar: null,
        remove_avatar: false,
    });

    useEffect(() => {
        if (!open) {
            return;
        }

        setRemoveAvatar(false);

        if (mode === 'edit' && account) {
            form.setData({
                name: account.name,
                email: account.email,
                password: '',
                password_confirmation: '',
                email_verified: account.verified,
                avatar: null,
                remove_avatar: false,
            });
            form.clearErrors();

            return;
        }

        form.reset();
        form.setData('email_verified', true);
        form.clearErrors();
        // eslint-disable-next-line react-hooks/exhaustive-deps -- reset only when dialog opens / target changes
    }, [open, mode, account?.id]);

    const noun = role === 'administrator' ? 'administrator' : 'user';

    function submit(event: React.FormEvent) {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            remove_avatar: removeAvatar,
        }));

        if (mode === 'create') {
            form.post(storeUrl, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(
                        `${noun.charAt(0).toUpperCase()}${noun.slice(1)} created.`,
                    );
                    onOpenChange(false);
                    form.reset();
                },
            });

            return;
        }

        if (!updateUrl) {
            return;
        }

        form.post(updateUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Account updated.');
                onOpenChange(false);
            },
        });
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>
                        {mode === 'create'
                            ? `Create ${noun}`
                            : `Edit ${noun}`}
                    </DialogTitle>
                    <DialogDescription>
                        {mode === 'create'
                            ? `Add a new ${noun} account to the platform.`
                            : `Update profile details for ${account?.name ?? 'this account'}.`}
                    </DialogDescription>
                </DialogHeader>

                <form className="grid gap-5" onSubmit={submit}>
                    <FileUploadAvatar
                        value={form.data.avatar}
                        onChange={(file) => {
                            setRemoveAvatar(false);
                            form.setData(
                                'avatar',
                                Array.isArray(file) ? (file[0] ?? null) : file,
                            );
                        }}
                        existingUrl={
                            removeAvatar ? null : (account?.avatar ?? null)
                        }
                        onClearExisting={() => {
                            setRemoveAvatar(true);
                            form.setData('avatar', null);
                        }}
                        uploadProgress={form.progress?.percentage ?? null}
                        isUploading={form.processing}
                        error={form.errors.avatar}
                        avatarFallback={account?.name?.slice(0, 1) ?? '?'}
                        showCaption
                        placeholder="Profile photo"
                        hint="PNG, JPG or WebP up to 2MB"
                    />

                    <div className="grid gap-2">
                        <Label htmlFor="person-name">Name</Label>
                        <Input
                            id="person-name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="person-email">Email</Label>
                        <Input
                            id="person-email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            required
                        />
                        <InputError message={form.errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="person-password">
                            Password
                            {mode === 'edit' ? ' (optional)' : ''}
                        </Label>
                        <PasswordInput
                            id="person-password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            required={mode === 'create'}
                            autoComplete="new-password"
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="person-password-confirmation">
                            Confirm password
                        </Label>
                        <PasswordInput
                            id="person-password-confirmation"
                            value={form.data.password_confirmation}
                            onChange={(event) =>
                                form.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            required={mode === 'create' || form.data.password !== ''}
                            autoComplete="new-password"
                        />
                    </div>

                    <label className="flex items-center gap-2 text-sm">
                        <Checkbox
                            checked={form.data.email_verified}
                            onCheckedChange={(checked) =>
                                form.setData('email_verified', checked === true)
                            }
                        />
                        Mark email as verified
                    </label>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {mode === 'create' ? 'Create' : 'Save changes'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
