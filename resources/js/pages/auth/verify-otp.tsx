import { Head, router, setLayoutProps } from '@inertiajs/react';
import { CodeEntry } from '@/components/auth/code-entry';
import { logout } from '@/routes';

export default function VerifyOtp({
    email,
    cooldown,
    hasActiveCode,
    expiresInMinutes,
    status,
}: {
    email: string;
    cooldown: number;
    hasActiveCode: boolean;
    expiresInMinutes: number;
    status?: string;
}) {
    setLayoutProps({
        title: 'Check your email',
        description: hasActiveCode
            ? `We sent a 6-digit code to ${email}. Enter it to finish creating your account.`
            : `Press Resend to get a 6-digit code at ${email}.`,
    });

    return (
        <>
            <Head title="Verify your email" />
            <CodeEntry
                verifyUrl="/email/verify"
                resendUrl="/email/verification-notification"
                cooldown={hasActiveCode ? cooldown : 0}
                expiresInMinutes={expiresInMinutes}
                status={status}
                submitLabel="Verify email"
                footer={
                    <button
                        type="button"
                        className="font-semibold text-brand-deep underline underline-offset-4"
                        onClick={() => router.post(logout())}
                    >
                        Log out
                    </button>
                }
            />
        </>
    );
}
