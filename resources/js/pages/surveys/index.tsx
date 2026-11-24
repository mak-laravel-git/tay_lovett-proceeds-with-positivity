import { Head, router } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { useState } from 'react';
import { SurveyFrame } from '@/components/ads/survey-frame';
import { SurveyPresenter } from '@/components/ads/survey-presenter';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { Button } from '@/components/ui/button';
import { Metric, Notice, PageTitle, Panel } from '@/components/ui-brand';
import { useSurveyFlow } from '@/hooks/use-survey-flow';

type ProviderCard = {
    key: string;
    label: string;
    integration_type: string;
    status: string;
    status_label: string;
    can_launch: boolean;
    cta: string;
};

export default function Surveys({
    points,
    providers = [],
}: {
    points: { balance: number; lifetime_earned: number };
    providers?: ProviderCard[];
}) {
    const [active, setActive] = useState<string | null>(null);

    const flow = useSurveyFlow({
        enabled: active !== null,
        providerKey: active ?? undefined,
        onApproved: () => router.reload({ only: ['points'] }),
    });

    return (
        <>
            <Head title="Earn Rewards" />
            <PageTitle
                icon={ClipboardList}
                title="Earn Rewards"
                pill="Surveys & offers"
            />

            <Stagger className="grid grid-cols-2 gap-3 px-3 min-[761px]:px-0">
                <StaggerItem>
                    <Metric
                        label="My points"
                        value={points.balance.toLocaleString()}
                    />
                </StaggerItem>
                <StaggerItem>
                    <Metric
                        label="Total earned"
                        value={points.lifetime_earned.toLocaleString()}
                    />
                </StaggerItem>
            </Stagger>

            <div className="px-3 pt-3 min-[761px]:px-0">
                <Notice>
                    Earnings are credited only for eligible completed activities
                    confirmed by the selected provider. Simply opening or
                    viewing an offer does not generate a payout.
                </Notice>
            </div>

            {active === null ? (
                <Reveal>
                    <div className="mt-3 grid gap-3 px-3 min-[761px]:px-0">
                        {providers.map((provider) => (
                            <Panel key={provider.key} className="p-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="grid gap-1">
                                        <h2 className="text-lg font-bold">
                                            {provider.label}
                                        </h2>
                                        <p className="text-sm text-muted-foreground">
                                            {provider.integration_type ===
                                            'offerwall'
                                                ? 'Complete offers to earn points.'
                                                : 'Complete surveys to earn points.'}
                                        </p>
                                        <p className="text-xs font-medium text-[#0f6f8f]">
                                            {provider.status_label}
                                        </p>
                                    </div>
                                    <Button
                                        disabled={!provider.can_launch}
                                        onClick={() => setActive(provider.key)}
                                    >
                                        {provider.cta}
                                    </Button>
                                </div>
                            </Panel>
                        ))}
                        {providers.length === 0 ? (
                            <Panel className="p-4">
                                <p className="text-sm text-muted-foreground">
                                    Reward providers are being prepared. Check
                                    back soon.
                                </p>
                            </Panel>
                        ) : null}
                    </div>
                </Reveal>
            ) : (
                <Reveal>
                    <Panel className="mt-3 p-4">
                        <div className="mb-3 flex items-center justify-between gap-2">
                            <h2 className="font-bold">
                                {providers.find((p) => p.key === active)
                                    ?.label ?? 'Provider'}
                            </h2>
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => setActive(null)}
                            >
                                Back to Rewards
                            </Button>
                        </div>
                        <SurveyPresenter flow={flow} />
                    </Panel>
                </Reveal>
            )}

            <SurveyFrame flow={flow} />
        </>
    );
}
