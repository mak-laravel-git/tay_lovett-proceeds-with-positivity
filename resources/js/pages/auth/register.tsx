import { Form, Head } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import {
    AuthField,
    AuthNotice,
    authButton,
    authInput,
} from '@/components/auth/auth-parts';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

export default function Register({
    passwordRules,
    registrationOpen,
}: {
    passwordRules: string;
    registrationOpen: boolean;
}) {
    if (!registrationOpen) {
        return (
            <>
                <Head title="Create account" />
                <div className="grid gap-5 text-center">
                    <span className="mx-auto grid size-14 place-items-center rounded-2xl bg-sky-pale text-ink">
                        <Lock className="size-6" aria-hidden />
                    </span>
                    <AuthNotice tone="error">
                        New accounts cannot be created right now. Please check
                        back later.
                    </AuthNotice>
                    <TextLink
                        href={login()}
                        className="text-sm font-semibold text-brand-deep"
                    >
                        Already have an account? Log in
                    </TextLink>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title="Create account" />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="grid gap-4"
            >
                {({ processing, errors }) => (
                    <>
                        <AuthField id="name" label="Full name" error={errors.name}>
                            <Input
                                id="name"
                                type="text"
                                name="name"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="name"
                                placeholder="Your full name"
                                className={authInput}
                            />
                        </AuthField>

                        <AuthField id="email" label="Email address" error={errors.email}>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                tabIndex={2}
                                autoComplete="email"
                                placeholder="email@example.com"
                                className={authInput}
                            />
                        </AuthField>

                        <AuthField
                            id="password"
                            label="Password"
                            error={errors.password}
                            hint="Use a long, unique password."
                        >
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={3}
                                autoComplete="new-password"
                                passwordrules={passwordRules}
                                placeholder="Create a password"
                                className="h-11 rounded-xl border-line bg-white"
                            />
                        </AuthField>

                        <AuthField
                            id="password_confirmation"
                            label="Confirm password"
                            error={errors.password_confirmation}
                        >
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                tabIndex={4}
                                autoComplete="new-password"
                                placeholder="Repeat the password"
                                className="h-11 rounded-xl border-line bg-white"
                            />
                        </AuthField>

                        <Button
                            type="submit"
                            className={`${authButton} mt-1 w-full`}
                            tabIndex={5}
                            data-test="register-user-button"
                        >
                            {processing && <Spinner />}
                            Create account
                        </Button>

                        <p className="text-center text-xs text-muted-ink">
                            By creating an account you agree to our{' '}
                            <a
                                href="/terms"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-semibold text-brand-deep underline underline-offset-4"
                            >
                                Terms of Service
                            </a>{' '}
                            and{' '}
                            <a
                                href="/privacy"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-semibold text-brand-deep underline underline-offset-4"
                            >
                                Privacy Policy
                            </a>
                            . We will email you a code to confirm your address.
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create your account',
    description: 'Join in a minute. We will email you a code to confirm it is you.',
};
