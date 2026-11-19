import { router, useForm } from '@inertiajs/react';
import { Clock, RefreshCw } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { AuthNotice, authButton } from '@/components/auth/auth-parts';
import { OtpField } from '@/components/auth/otp-field';
import { useCountdown } from '@/components/auth/use-countdown';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

/**
 * The shared "type the 6-digit code from your email" block used for email verification and password reset.
 */
export function CodeEntry({
    verifyUrl,
    resendUrl,
    cooldown,
    expiresInMinutes,
    status,
    submitLabel,
    footer,
}: {
    verifyUrl: string;
    resendUrl: string;
    cooldown: number;
    expiresInMinutes: number;
    status?: string | null;
    submitLabel: string;
    footer?: ReactNode;
}) {
    const form = useForm({ code: '' });
    const [seconds, restart] = useCountdown(cooldown);
    const [resending, setResending] = useState(false);

    const submit = (code: string) => {
        if (code.length !== 6 || form.processing) {
            return;
        }

        form.transform(() => ({ code }));
        form.post(verifyUrl, {
            preserveScroll: true,
            onError: () => form.setData('code', ''),
        });
    };

    const resend = () => {
        router.post(
            resendUrl,
            {},
            {
                preserveScroll: true,
                onStart: () => setResending(true),
                onFinish: () => setResending(false),
                onSuccess: (page) => {
                    form.setData('code', '');
                    form.clearErrors();
                    restart(Number(page.props.cooldown ?? 60));
                },
            },
        );
    };

    return (
        <form
            className="grid gap-5"
            onSubmit={(event) => {
                event.preventDefault();
                submit(form.data.code);
            }}
        >
            {status ? <AuthNotice>{status}</AuthNotice> : null}

            <div className="grid gap-3">
                <OtpField
                    value={form.data.code}
                    onChange={(value) => {
                        form.setData('code', value);
                        form.clearErrors();
                    }}
                    onComplete={submit}
                    disabled={form.processing}
                    invalid={Boolean(form.errors.code)}
                />
                {form.errors.code ? (
                    <p role="alert" className="text-center text-sm font-medium text-brand-deep">
                        {form.errors.code}
                    </p>
                ) : (
                    <p className="flex items-center justify-center gap-1.5 text-center text-xs text-muted-ink">
                        <Clock className="size-3.5" aria-hidden />
                        The code works for {expiresInMinutes} minutes.
                    </p>
                )}
            </div>

            <Button
                type="submit"
                disabled={form.processing || form.data.code.length !== 6}
                className={authButton}
            >
                {form.processing && <Spinner />}
                {submitLabel}
            </Button>

            <div className="flex flex-col items-center gap-2 text-sm text-muted-ink">
                <span>Did not get the email? Check your spam folder or</span>
                <Button
                    type="button"
                    variant="outline"
                    onClick={resend}
                    disabled={seconds > 0 || resending}
                    className="h-10 rounded-xl border-line bg-white"
                >
                    {resending ? <Spinner /> : <RefreshCw className="size-4" />}
                    {seconds > 0 ? `Resend code in ${seconds}s` : 'Resend code'}
                </Button>
            </div>

            {footer ? <div className="text-center text-sm">{footer}</div> : null}
        </form>
    );
}
