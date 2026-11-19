import { Head, setLayoutProps } from '@inertiajs/react';
import { CodeEntry } from '@/components/auth/code-entry';
import TextLink from '@/components/text-link';

export default function VerifyResetCode({
    email,
    cooldown,
    expiresInMinutes,
    status,
}: {
    email: string;
    cooldown: number;
    expiresInMinutes: number;
    status?: string;
}) {
    setLayoutProps({
        title: 'Enter your code',
        description: `If ${email} has an account, we sent it a 6-digit code.`,
    });

    return (
        <>
            <Head title="Enter reset code" />
            <CodeEntry
                verifyUrl="/forgot-password/verify"
                resendUrl="/forgot-password/resend"
                cooldown={cooldown}
                expiresInMinutes={expiresInMinutes}
                status={status}
                submitLabel="Continue"
                footer={
                    <TextLink
                        href="/forgot-password"
                        className="font-semibold text-brand-deep"
                    >
                        Use a different email
                    </TextLink>
                }
            />
        </>
    );
}
