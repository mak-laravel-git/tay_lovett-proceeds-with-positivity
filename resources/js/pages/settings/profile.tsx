import { router, useForm, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { BadgeCheck, Mail, TriangleAlert, UserRound } from 'lucide-react';
import { useState } from 'react';
import { OtpField } from '@/components/auth/otp-field';
import { useCountdown } from '@/components/auth/use-countdown';
import {
    AccountSection,
    FieldRow,
    accountButton,
    accountInput,
} from '@/components/account/account-section';
import { AdActivitySection } from '@/components/ads/ad-activity-section';
import type { AdStats } from '@/components/ads/ad-activity-section';
import { FileUploadAvatar } from '@/components/file-upload-avatar';
import PasswordInput from '@/components/password-input';
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
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type Props = {
    emailVerified: boolean;
    memberSince: string | null;
    pendingEmail: string | null;
    emailCooldown: number;
    expiresInMinutes: number;
    canDelete: boolean;
    adStats: AdStats;
};

export default function Profile({
    emailVerified,
    memberSince,
    pendingEmail,
    emailCooldown,
    expiresInMinutes,
    canDelete,
    adStats,
}: Props) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Profile" />

            <PhotoSection />
            <NameSection />
            <EmailSection
                emailVerified={emailVerified}
                memberSince={memberSince}
                pendingEmail={pendingEmail}
                emailCooldown={emailCooldown}
                expiresInMinutes={expiresInMinutes}
                role={auth.is_administrator ? 'Administrator' : 'Member'}
            />
            <AdActivitySection stats={adStats} />
            {canDelete ? <DeleteSection /> : null}
        </>
    );
}

function PhotoSection() {
    const { auth } = usePage().props;
    const form = useForm<{ avatar: File | null }>({ avatar: null });

    const upload = (file: File | null) => {
        form.setData('avatar', file);

        if (file) {
            form.transform(() => ({ avatar: file }));
            form.post('/settings/profile/avatar', {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => form.reset(),
            });
        }
    };

    return (
        <AccountSection
            title="Profile photo"
            description="Shown next to your name around the site."
            icon={<UserRound className="size-5" />}
        >
            <FileUploadAvatar
                value={form.data.avatar}
                onChange={(file) =>
                    upload(Array.isArray(file) ? (file[0] ?? null) : file)
                }
                existingUrl={auth.user.avatar ?? null}
                onClearExisting={() =>
                    router.delete('/settings/profile/avatar', {
                        preserveScroll: true,
                    })
                }
                uploadProgress={form.progress?.percentage ?? null}
                isUploading={form.processing}
                error={form.errors.avatar}
                avatarFallback={auth.user.name.slice(0, 1).toUpperCase()}
                placeholder="Your photo"
                hint="PNG, JPG or WebP · 64 to 4000 px · up to 2 MB"
            />
        </AccountSection>
    );
}

function NameSection() {
    const { auth } = usePage().props;
    const form = useForm({ name: auth.user.name });

    return (
        <AccountSection
            title="Name"
            description="This is how you appear to others."
            icon={<UserRound className="size-5" />}
        >
            <form
                className="grid max-w-md gap-4"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.patch('/settings/profile', { preserveScroll: true });
                }}
            >
                <FieldRow label="Full name" htmlFor="name" error={form.errors.name}>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(event) => form.setData('name', event.target.value)}
                        autoComplete="name"
                        required
                        className={accountInput}
                    />
                </FieldRow>
                <div>
                    <Button
                        type="submit"
                        disabled={form.processing || form.data.name === auth.user.name}
                        className={accountButton}
                        data-test="update-profile-button"
                    >
                        {form.processing && <Spinner />}
                        Save name
                    </Button>
                </div>
            </form>
        </AccountSection>
    );
}

function EmailSection({
    emailVerified,
    memberSince,
    pendingEmail,
    emailCooldown,
    expiresInMinutes,
    role,
}: {
    emailVerified: boolean;
    memberSince: string | null;
    pendingEmail: string | null;
    emailCooldown: number;
    expiresInMinutes: number;
    role: string;
}) {
    const { auth } = usePage().props;
    const request = useForm({ new_email: '', current_password: '' });
    const confirm = useForm({ code: '' });
    const [seconds, restart] = useCountdown(emailCooldown);
    const [resending, setResending] = useState(false);

    const submitCode = (code: string) => {
        if (code.length !== 6 || confirm.processing) {
            return;
        }

        confirm.transform(() => ({ code }));
        confirm.post('/settings/profile/email/verify', {
            preserveScroll: true,
            onError: () => confirm.setData('code', ''),
            onSuccess: () => confirm.reset(),
        });
    };

    return (
        <AccountSection
            title="Email address"
            description="Used to sign in and to send you codes."
            icon={<Mail className="size-5" />}
        >
            <div className="grid gap-5">
                <div className="flex flex-wrap items-center gap-2 rounded-xl border border-line bg-sky-wash px-3.5 py-3">
                    <span className="font-bold text-ink">{auth.user.email}</span>
                    {emailVerified ? (
                        <Badge variant="outline" className="border-success/40 bg-success/10 text-ink">
                            <BadgeCheck className="size-3.5" />
                            Verified
                        </Badge>
                    ) : (
                        <Badge variant="outline" className="border-brand/40 bg-brand-soft text-brand-deep">
                            <TriangleAlert className="size-3.5" />
                            Not verified
                        </Badge>
                    )}
                    <span className="ml-auto text-xs text-muted-ink">
                        {role}
                        {memberSince
                            ? ` · member since ${new Date(memberSince).toLocaleDateString()}`
                            : ''}
                    </span>
                </div>

                {pendingEmail ? (
                    <div className="grid gap-4 rounded-xl border border-line p-4">
                        <div>
                            <p className="font-bold text-ink">Confirm your new email</p>
                            <p className="text-sm text-muted-ink">
                                We sent a 6-digit code to{' '}
                                <b className="text-ink">{pendingEmail}</b>. Your
                                current email stays active until you enter it. The
                                code works for {expiresInMinutes} minutes.
                            </p>
                        </div>
                        <OtpField
                            value={confirm.data.code}
                            onChange={(value) => {
                                confirm.setData('code', value);
                                confirm.clearErrors();
                            }}
                            onComplete={submitCode}
                            disabled={confirm.processing}
                            invalid={Boolean(confirm.errors.code)}
                            autoFocus={false}
                        />
                        {confirm.errors.code ? (
                            <p role="alert" className="text-center text-sm font-medium text-brand-deep">
                                {confirm.errors.code}
                            </p>
                        ) : null}
                        <div className="flex flex-wrap items-center justify-center gap-2">
                            <Button
                                type="button"
                                disabled={confirm.processing || confirm.data.code.length !== 6}
                                className={accountButton}
                                onClick={() => submitCode(confirm.data.code)}
                            >
                                {confirm.processing && <Spinner />}
                                Confirm email
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                className="h-10 rounded-xl border-line bg-white"
                                disabled={seconds > 0 || resending}
                                onClick={() =>
                                    router.post(
                                        '/settings/profile/email/resend',
                                        {},
                                        {
                                            preserveScroll: true,
                                            onStart: () => setResending(true),
                                            onFinish: () => setResending(false),
                                            onSuccess: (page) =>
                                                restart(Number(page.props.emailCooldown ?? 60)),
                                        },
                                    )
                                }
                            >
                                {seconds > 0 ? `Resend in ${seconds}s` : 'Resend code'}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                className="h-10 rounded-xl"
                                onClick={() =>
                                    router.delete('/settings/profile/email', {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Cancel change
                            </Button>
                        </div>
                    </div>
                ) : (
                    <form
                        className="grid max-w-md gap-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            request.post('/settings/profile/email', {
                                preserveScroll: true,
                                onSuccess: () => request.reset(),
                                onError: () => request.reset('current_password'),
                            });
                        }}
                    >
                        <p className="text-sm font-bold text-ink">Change email</p>
                        <FieldRow
                            label="New email address"
                            htmlFor="new_email"
                            error={request.errors.new_email}
                        >
                            <Input
                                id="new_email"
                                type="email"
                                value={request.data.new_email}
                                onChange={(event) =>
                                    request.setData('new_email', event.target.value)
                                }
                                autoComplete="off"
                                required
                                placeholder="new@example.com"
                                className={accountInput}
                            />
                        </FieldRow>
                        <FieldRow
                            label="Current password"
                            htmlFor="email_current_password"
                            error={request.errors.current_password ?? (request.errors as Record<string, string>).code}
                            hint="We ask for it so nobody else can redirect your account."
                        >
                            <PasswordInput
                                id="email_current_password"
                                value={request.data.current_password}
                                onChange={(event) =>
                                    request.setData('current_password', event.target.value)
                                }
                                autoComplete="current-password"
                                required
                                className={accountInput}
                            />
                        </FieldRow>
                        <div>
                            <Button
                                type="submit"
                                disabled={request.processing}
                                className={accountButton}
                            >
                                {request.processing && <Spinner />}
                                Send code to new email
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </AccountSection>
    );
}

function DeleteSection() {
    const [open, setOpen] = useState(false);
    const form = useForm({ password: '' });

    return (
        <>
            <AccountSection
                title="Delete account"
                description="Your account is removed and you are signed out. This cannot be undone."
                icon={<TriangleAlert className="size-5" />}
                tone="danger"
            >
                <Button
                    type="button"
                    variant="destructive"
                    className="h-10 rounded-xl"
                    onClick={() => setOpen(true)}
                    data-test="delete-user-button"
                >
                    Delete my account
                </Button>
            </AccountSection>

            <Dialog
                open={open}
                onOpenChange={(next) => {
                    setOpen(next);

                    if (!next) {
                        form.reset();
                        form.clearErrors();
                    }
                }}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Delete your account?</DialogTitle>
                        <DialogDescription>
                            Enter your password to confirm. You will lose access
                            straight away.
                        </DialogDescription>
                    </DialogHeader>
                    <form
                        className="grid gap-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.delete('/settings/profile', { preserveScroll: true });
                        }}
                    >
                        <FieldRow
                            label="Password"
                            htmlFor="delete_password"
                            error={form.errors.password}
                        >
                            <PasswordInput
                                id="delete_password"
                                value={form.data.password}
                                onChange={(event) =>
                                    form.setData('password', event.target.value)
                                }
                                autoComplete="current-password"
                                autoFocus
                                className={accountInput}
                            />
                        </FieldRow>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={form.processing || form.data.password === ''}
                            >
                                {form.processing && <Spinner />}
                                Delete account
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
