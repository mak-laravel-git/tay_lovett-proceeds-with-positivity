import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    AdminNotice,
    AdminPage,
    AdminSectionCard,
} from '@/components/admin/admin-page';
import {
    Field,
    SelectField,
    SwitchField,
} from '@/components/admin/form-fields';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

type Action = 'continue' | 'block';

type Settings = {
    enabled: boolean;
    ad1_enabled: boolean;
    ad1_on_unavailable: Action;
    ad1_on_error: Action;
    ad1_on_dismissed: Action;
    ad1_placement_id: number | null;
    survey_enabled: boolean;
    survey_on_no_surveys: Action;
    survey_on_api_error: Action;
    survey_on_disqualified: Action;
    survey_provider_keys: string[];
    award_survey_points: boolean;
    ad2_enabled: boolean;
    ad2_on_unavailable: Action;
    ad2_on_error: Action;
    ad2_on_dismissed: Action;
    ad2_placement_id: number | null;
    publish_mode: 'auto' | 'manual';
    max_fallback_skips_per_day: number;
    disclosure_text: string | null;
    expires_after_hours: number;
    log_retention_days: number;
};

type Props = {
    settings: Settings;
    providers: {
        key: string;
        label: string;
        usable: boolean;
        status: string;
    }[];
    placements: { id: number; name: string; is_enabled: boolean }[];
    warnings: string[];
    default_disclosure: string;
};

const actions = (block: string) => [
    { value: 'continue', label: 'Let the user continue' },
    { value: 'block', label: block },
];

export default function WorkflowSettings({
    settings,
    providers,
    placements,
    warnings,
    default_disclosure,
}: Props) {
    const form = useForm({
        ...settings,
        disclosure_text: settings.disclosure_text ?? '',
    });
    const { data, setData, errors } = form;

    const action = (
        name:
            | 'ad1_on_unavailable'
            | 'ad1_on_error'
            | 'ad1_on_dismissed'
            | 'survey_on_no_surveys'
            | 'survey_on_api_error'
            | 'survey_on_disqualified'
            | 'ad2_on_unavailable'
            | 'ad2_on_error'
            | 'ad2_on_dismissed',
        label: string,
        block = 'Keep the user on this step',
        hint?: string,
    ) => (
        <SelectField
            label={label}
            hint={hint}
            error={errors[name]}
            value={data[name]}
            options={actions(block)}
            onChange={(value) => setData(name, value as Action)}
        />
    );

    const adStep = (prefix: 'ad1' | 'ad2', title: string, hint: string) => (
        <AdminSectionCard title={title} description={hint}>
            <div className="grid gap-4 sm:grid-cols-2">
                <SwitchField
                    id={`${prefix}_enabled`}
                    label="Show a rewarded ad at this step"
                    checked={data[`${prefix}_enabled`]}
                    onChange={(checked) =>
                        setData(`${prefix}_enabled`, checked)
                    }
                />
                <SelectField
                    label="Ad unit"
                    error={errors[`${prefix}_placement_id`]}
                    value={String(data[`${prefix}_placement_id`] ?? '')}
                    options={[
                        { value: '', label: 'First enabled rewarded unit' },
                        ...placements.map((placement) => ({
                            value: String(placement.id),
                            label: placement.is_enabled
                                ? placement.name
                                : `${placement.name} (disabled)`,
                        })),
                    ]}
                    onChange={(value) =>
                        setData(
                            `${prefix}_placement_id`,
                            value === '' ? null : Number(value),
                        )
                    }
                />
                {action(
                    `${prefix}_on_unavailable`,
                    'No ad available',
                    undefined,
                    'No fill, unsupported device, or no unit configured.',
                )}
                {action(
                    `${prefix}_on_error`,
                    'Ad failed to load',
                    undefined,
                    'Blocked by an ad blocker, timed out or errored.',
                )}
                {action(
                    `${prefix}_on_dismissed`,
                    'User closed or declined the ad',
                    undefined,
                    '“Continue” makes the ad optional.',
                )}
            </div>
        </AdminSectionCard>
    );

    return (
        <>
            <Head title="Quote workflow" />
            <AdminPage
                title="Quote workflow"
                description="Rewarded ad, survey, quote, second rewarded ad, publish. Changes apply to runs started after saving."
                actions={
                    <Button variant="outline" asChild>
                        <Link href="/admin/workflow/runs">View runs</Link>
                    </Button>
                }
            >
                {warnings.map((warning) => (
                    <AdminNotice key={warning} tone="warn">
                        {warning}
                    </AdminNotice>
                ))}

                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.put('/admin/workflow', { preserveScroll: true });
                    }}
                >
                    <AdminSectionCard
                        title="General"
                        description="The switch for the whole flow and its limits."
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <SwitchField
                                id="enabled"
                                label="Use the step-by-step flow"
                                hint="Off: one press creates a quote, with no ad and no survey."
                                checked={data.enabled}
                                onChange={(checked) =>
                                    setData('enabled', checked)
                                }
                            />
                            <Field
                                label="Skips allowed per user per day"
                                hint="Applies to every “continue” rule. 0 means no limit."
                                error={errors.max_fallback_skips_per_day}
                            >
                                <Input
                                    type="number"
                                    min={0}
                                    max={1000}
                                    value={data.max_fallback_skips_per_day}
                                    onChange={(event) =>
                                        setData(
                                            'max_fallback_skips_per_day',
                                            Number(event.target.value),
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Unfinished runs expire after (hours)"
                                hint="A run that already has a quote never expires."
                                error={errors.expires_after_hours}
                            >
                                <Input
                                    type="number"
                                    min={1}
                                    max={720}
                                    value={data.expires_after_hours}
                                    onChange={(event) =>
                                        setData(
                                            'expires_after_hours',
                                            Number(event.target.value),
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Keep logs for (days)"
                                error={errors.log_retention_days}
                            >
                                <Input
                                    type="number"
                                    min={7}
                                    max={730}
                                    value={data.log_retention_days}
                                    onChange={(event) =>
                                        setData(
                                            'log_retention_days',
                                            Number(event.target.value),
                                        )
                                    }
                                />
                            </Field>
                            <Field
                                label="Text shown before a user starts (optional)"
                                hint={`Leave empty to use: “${default_disclosure}” Google requires that users are told what they must do before an ad.`}
                                error={errors.disclosure_text}
                                className="sm:col-span-2"
                            >
                                <Textarea
                                    rows={3}
                                    maxLength={1000}
                                    value={data.disclosure_text}
                                    onChange={(event) =>
                                        setData(
                                            'disclosure_text',
                                            event.target.value,
                                        )
                                    }
                                />
                            </Field>
                        </div>
                    </AdminSectionCard>

                    {adStep(
                        'ad1',
                        'Step 1: rewarded ad before the survey',
                        'Runs when a user presses Create quote.',
                    )}

                    <AdminSectionCard
                        title="Step 2: survey"
                        description="Only a verified provider callback completes a survey."
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <SwitchField
                                id="survey_enabled"
                                label="Require a survey"
                                checked={data.survey_enabled}
                                onChange={(checked) =>
                                    setData('survey_enabled', checked)
                                }
                            />
                            <SwitchField
                                id="award_survey_points"
                                label="Also award points for these surveys"
                                hint="Check Google’s rewarded ad policy first: with this on, watching an ad leads to cash-out points."
                                checked={data.award_survey_points}
                                onChange={(checked) =>
                                    setData('award_survey_points', checked)
                                }
                            />
                            {action(
                                'survey_on_no_surveys',
                                'No survey available',
                            )}
                            {action(
                                'survey_on_api_error',
                                'Survey API failed',
                                undefined,
                                'Errors, or no selected provider can be used.',
                            )}
                            {action(
                                'survey_on_disqualified',
                                'User was screened out',
                                'Ask the user to try another survey',
                            )}
                            <fieldset className="grid content-start gap-2 text-sm">
                                <legend className="mb-1 font-medium">
                                    Providers that can unlock a quote
                                </legend>
                                {providers.map((provider) => (
                                    <label
                                        key={provider.key}
                                        className="flex items-center gap-2"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={data.survey_provider_keys.includes(
                                                provider.key,
                                            )}
                                            onChange={(event) =>
                                                setData(
                                                    'survey_provider_keys',
                                                    event.target.checked
                                                        ? [
                                                              ...data.survey_provider_keys,
                                                              provider.key,
                                                          ]
                                                        : data.survey_provider_keys.filter(
                                                              (key) =>
                                                                  key !==
                                                                  provider.key,
                                                          ),
                                                )
                                            }
                                        />
                                        <span>
                                            {provider.label}{' '}
                                            <span className="text-xs text-muted-foreground">
                                                (
                                                {provider.usable
                                                    ? 'usable'
                                                    : provider.status}
                                                )
                                            </span>
                                        </span>
                                    </label>
                                ))}
                                <p className="text-xs text-muted-foreground">
                                    Order, credentials and on/off are set on{' '}
                                    <Link
                                        href="/admin/ads/providers"
                                        className="underline"
                                    >
                                        Providers
                                    </Link>
                                    .
                                </p>
                            </fieldset>
                        </div>
                    </AdminSectionCard>

                    {adStep(
                        'ad2',
                        'Step 4: rewarded ad before publishing',
                        'The quote stays hidden until this step is done.',
                    )}

                    <AdminSectionCard
                        title="Publishing"
                        description="What happens when the last step is done."
                    >
                        <div className="grid gap-4 sm:grid-cols-2">
                            <SelectField
                                label="Publish mode"
                                error={errors.publish_mode}
                                value={data.publish_mode}
                                options={[
                                    {
                                        value: 'auto',
                                        label: 'Publish to the feed automatically',
                                    },
                                    {
                                        value: 'manual',
                                        label: 'Unlock only; the user presses Publish',
                                    },
                                ]}
                                onChange={(value) =>
                                    setData(
                                        'publish_mode',
                                        value as 'auto' | 'manual',
                                    )
                                }
                            />
                        </div>
                    </AdminSectionCard>

                    <div className="flex flex-wrap gap-2">
                        <Button type="submit" disabled={form.processing}>
                            Save workflow
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => {
                                if (
                                    window.confirm(
                                        'Return every workflow setting to its default?',
                                    )
                                ) {
                                    router.post(
                                        '/admin/workflow/reset',
                                        {},
                                        // Remount so the form shows the restored values.
                                        { preserveState: false },
                                    );
                                }
                            }}
                        >
                            Reset to defaults
                        </Button>
                    </div>
                </form>
            </AdminPage>
        </>
    );
}

WorkflowSettings.layout = {
    breadcrumbs: [
        { title: 'Administration', href: '/admin' },
        { title: 'Quote workflow', href: '/admin/workflow' },
    ],
};
