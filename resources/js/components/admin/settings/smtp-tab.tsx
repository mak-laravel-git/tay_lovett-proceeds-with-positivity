import { router, useForm } from '@inertiajs/react';
import { Loader2, Send } from 'lucide-react';
import { useState } from 'react';
import { AdminNotice } from '@/components/admin/admin-page';
import {
    SettingField,
    SettingsForm,
    SwitchField,
    boolOf,
    textOf,
    type SettingsGroup,
} from '@/components/admin/settings/shared';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export function SmtpTab({
    group,
    activeMailer,
    adminEmail,
}: {
    group: SettingsGroup;
    activeMailer: string;
    adminEmail: string;
}) {
    const form = useForm({
        enabled: boolOf(group, 'enabled'),
        host: textOf(group, 'host'),
        port: textOf(group, 'port'),
        encryption: textOf(group, 'encryption') || 'none',
        username: textOf(group, 'username'),
        password: '',
        clear_password: false,
        from_address: textOf(group, 'from_address'),
        from_name: textOf(group, 'from_name'),
        timeout: textOf(group, 'timeout'),
    });
    const [to, setTo] = useState(adminEmail);
    const [testing, setTesting] = useState(false);

    const sendTest = () => {
        router.post(
            '/admin/settings/smtp/test',
            { ...form.data, to },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setTesting(true),
                onFinish: () => setTesting(false),
            },
        );
    };

    return (
        <div className="grid gap-4">
            {activeMailer !== 'smtp' ? (
                <AdminNotice tone="warn">
                    Emails are not sent anywhere right now. The active mailer
                    is "{activeMailer}", so verification codes only land in the
                    server log and users cannot confirm their email. Turn SMTP
                    on below before going live.
                </AdminNotice>
            ) : null}

            <SettingsForm
                group="smtp"
                title="SMTP"
                description="The mail server used for verification codes, password resets and notices."
                processing={form.processing}
                onSubmit={() =>
                    form.post('/admin/settings/smtp', {
                        preserveScroll: true,
                        onSuccess: () => form.setData('password', ''),
                    })
                }
                extra={
                    <span className="text-xs text-muted-foreground">
                        Test email below uses the values in this form, even
                        before you save.
                    </span>
                }
            >
                <SwitchField
                    id="smtp_enabled"
                    label="Send email through this SMTP server"
                    help="When off, the mailer from the .env file is used."
                    source={group.enabled.source}
                    checked={form.data.enabled}
                    onCheckedChange={(checked) =>
                        form.setData('enabled', checked)
                    }
                />

                <div className="grid gap-5 sm:grid-cols-[1fr_9rem_11rem]">
                    <SettingField
                        id="smtp_host"
                        label="Host"
                        source={group.host.source}
                        error={form.errors.host}
                    >
                        <Input
                            id="smtp_host"
                            value={form.data.host}
                            onChange={(event) =>
                                form.setData('host', event.target.value)
                            }
                            placeholder="smtp.example.com"
                            className="bg-white"
                        />
                    </SettingField>
                    <SettingField
                        id="smtp_port"
                        label="Port"
                        source={group.port.source}
                        error={form.errors.port}
                    >
                        <Input
                            id="smtp_port"
                            type="number"
                            min={1}
                            max={65535}
                            value={form.data.port}
                            onChange={(event) =>
                                form.setData('port', event.target.value)
                            }
                            className="bg-white"
                        />
                    </SettingField>
                    <SettingField
                        id="smtp_encryption"
                        label="Encryption"
                        source={group.encryption.source}
                        error={form.errors.encryption}
                    >
                        <Select
                            value={form.data.encryption}
                            onValueChange={(value) =>
                                form.setData('encryption', value)
                            }
                        >
                            <SelectTrigger
                                id="smtp_encryption"
                                className="w-full bg-white"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                <SelectItem value="tls">
                                    TLS (STARTTLS)
                                </SelectItem>
                                <SelectItem value="ssl">SSL</SelectItem>
                            </SelectContent>
                        </Select>
                    </SettingField>
                </div>
                <p className="-mt-3 text-xs text-muted-foreground">
                    TLS usually uses port 587. SSL usually uses port 465.
                </p>

                <div className="grid gap-5 sm:grid-cols-2">
                    <SettingField
                        id="smtp_username"
                        label="Username"
                        source={group.username.source}
                        error={form.errors.username}
                    >
                        <Input
                            id="smtp_username"
                            autoComplete="off"
                            value={form.data.username}
                            onChange={(event) =>
                                form.setData('username', event.target.value)
                            }
                            className="bg-white"
                        />
                    </SettingField>
                    <SettingField
                        id="smtp_password"
                        label="Password"
                        help={
                            group.password.has_value
                                ? 'A password is stored. Leave empty to keep it, or type a new one to replace it.'
                                : 'No password stored.'
                        }
                        source={group.password.source}
                        error={form.errors.password}
                    >
                        <Input
                            id="smtp_password"
                            type="password"
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            placeholder={
                                group.password.has_value ? '••••••••' : ''
                            }
                            className="bg-white"
                        />
                        {group.password.source === 'database' ? (
                            <label className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Checkbox
                                    checked={form.data.clear_password}
                                    onCheckedChange={(checked) =>
                                        form.setData(
                                            'clear_password',
                                            checked === true,
                                        )
                                    }
                                />
                                Remove the saved password and use the .env one
                            </label>
                        ) : null}
                    </SettingField>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <SettingField
                        id="smtp_from_address"
                        label="From address"
                        source={group.from_address.source}
                        error={form.errors.from_address}
                    >
                        <Input
                            id="smtp_from_address"
                            type="email"
                            value={form.data.from_address}
                            onChange={(event) =>
                                form.setData('from_address', event.target.value)
                            }
                            className="bg-white"
                        />
                    </SettingField>
                    <SettingField
                        id="smtp_from_name"
                        label="From name"
                        source={group.from_name.source}
                        error={form.errors.from_name}
                    >
                        <Input
                            id="smtp_from_name"
                            value={form.data.from_name}
                            onChange={(event) =>
                                form.setData('from_name', event.target.value)
                            }
                            className="bg-white"
                        />
                    </SettingField>
                </div>

                <SettingField
                    id="smtp_timeout"
                    label="Timeout (seconds)"
                    help="How long to wait for the mail server (5 to 120)."
                    source={group.timeout.source}
                    error={form.errors.timeout}
                    className="sm:max-w-[12rem]"
                >
                    <Input
                        id="smtp_timeout"
                        type="number"
                        min={5}
                        max={120}
                        value={form.data.timeout}
                        onChange={(event) =>
                            form.setData('timeout', event.target.value)
                        }
                        className="bg-white"
                    />
                </SettingField>

                <div className="grid gap-2 rounded-xl border border-dashed border-[#d4e7ed] p-3.5">
                    <p className="text-sm font-medium">Send a test email</p>
                    <div className="flex flex-col gap-2 sm:flex-row">
                        <Input
                            type="email"
                            value={to}
                            onChange={(event) => setTo(event.target.value)}
                            aria-label="Send the test email to"
                            className="bg-white"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            disabled={testing || to === ''}
                            onClick={sendTest}
                        >
                            {testing ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Send className="size-4" />
                            )}
                            Send test
                        </Button>
                    </div>
                </div>
            </SettingsForm>
        </div>
    );
}
