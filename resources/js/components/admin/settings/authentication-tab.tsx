import { useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import {
    SettingField,
    SettingsForm,
    SwitchField,
    boolOf,
    textOf,
    type SettingsGroup,
} from '@/components/admin/settings/shared';

type NumericKey =
    | 'otp_expiry_minutes'
    | 'otp_resend_cooldown_seconds'
    | 'otp_max_attempts'
    | 'otp_max_sends_per_hour'
    | 'confirm_timeout_minutes';

const numericFields: Array<{
    key: NumericKey;
    label: string;
    help: string;
    min: number;
    max: number;
}> = [
    {
        key: 'otp_expiry_minutes',
        label: 'Code lifetime (minutes)',
        help: 'How long an emailed 6-digit code works (3 to 60).',
        min: 3,
        max: 60,
    },
    {
        key: 'otp_resend_cooldown_seconds',
        label: 'Resend wait (seconds)',
        help: 'Minimum wait before a new code can be sent (15 to 600).',
        min: 15,
        max: 600,
    },
    {
        key: 'otp_max_attempts',
        label: 'Wrong guesses allowed',
        help: 'After this many wrong codes the code is cancelled (3 to 10).',
        min: 3,
        max: 10,
    },
    {
        key: 'otp_max_sends_per_hour',
        label: 'Codes per hour',
        help: 'How many codes one email address may request each hour (2 to 20).',
        min: 2,
        max: 20,
    },
    {
        key: 'confirm_timeout_minutes',
        label: 'Security re-confirm (minutes)',
        help: 'After this long, opening Security asks the user to confirm again (5 to 1440).',
        min: 5,
        max: 1440,
    },
];

export function AuthenticationTab({ group }: { group: SettingsGroup }) {
    const form = useForm({
        registration_open: boolOf(group, 'registration_open'),
        otp_expiry_minutes: textOf(group, 'otp_expiry_minutes'),
        otp_resend_cooldown_seconds: textOf(
            group,
            'otp_resend_cooldown_seconds',
        ),
        otp_max_attempts: textOf(group, 'otp_max_attempts'),
        otp_max_sends_per_hour: textOf(group, 'otp_max_sends_per_hour'),
        confirm_timeout_minutes: textOf(group, 'confirm_timeout_minutes'),
    });

    return (
        <SettingsForm
            group="authentication"
            title="Authentication"
            description="Sign-up rules and how email codes behave."
            processing={form.processing}
            onSubmit={() =>
                form.post('/admin/settings/authentication', {
                    preserveScroll: true,
                })
            }
        >
            <SwitchField
                id="registration_open"
                label="Allow new registrations"
                help="When off, the Create account page is closed. Existing users can still sign in."
                source={group.registration_open.source}
                checked={form.data.registration_open}
                onCheckedChange={(checked) =>
                    form.setData('registration_open', checked)
                }
            />
            <div className="grid gap-5 sm:grid-cols-2">
                {numericFields.map((field) => (
                    <SettingField
                        key={field.key}
                        id={field.key}
                        label={field.label}
                        help={field.help}
                        source={group[field.key].source}
                        error={form.errors[field.key]}
                    >
                        <Input
                            id={field.key}
                            type="number"
                            min={field.min}
                            max={field.max}
                            value={form.data[field.key]}
                            onChange={(event) =>
                                form.setData(field.key, event.target.value)
                            }
                            className="bg-white"
                        />
                    </SettingField>
                ))}
            </div>
        </SettingsForm>
    );
}
