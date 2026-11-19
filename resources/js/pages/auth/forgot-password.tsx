import { Form, Head } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import {
    AuthField,
    AuthNotice,
    authButton,
    authInput,
} from '@/components/auth/auth-parts';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

export default function ForgotPassword({ status }: { status?: string }) {
    return (
        <>
            <Head title="Forgot password" />

            <Form action="/forgot-password" method="post" className="grid gap-5">
                {({ processing, errors }) => (
                    <>
                        {status ? <AuthNotice>{status}</AuthNotice> : null}

                        <AuthField id="email" label="Email address" error={errors.email}>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                autoFocus
                                required
                                placeholder="email@example.com"
                                className={authInput}
                            />
                        </AuthField>

                        <Button type="submit" disabled={processing} className={authButton}>
                            {processing && <Spinner />}
                            Email me a code
                        </Button>

                        <div className="text-center text-sm">
                            <TextLink
                                href={login()}
                                className="inline-flex items-center gap-1.5 font-semibold text-brand-deep"
                            >
                                <ArrowLeft className="size-4" />
                                Back to log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot your password?',
    description:
        'Enter your email and we will send a 6-digit code to reset it.',
};
