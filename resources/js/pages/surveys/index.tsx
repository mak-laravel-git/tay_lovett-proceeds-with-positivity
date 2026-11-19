import { Head, router } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import { SurveyFrame } from '@/components/ads/survey-frame';
import { SurveyPresenter } from '@/components/ads/survey-presenter';
import { Reveal, Stagger, StaggerItem } from '@/components/motion/reveal';
import { Metric, Notice, PageTitle, Panel } from '@/components/ui-brand';
import { useSurveyFlow } from '@/hooks/use-survey-flow';

export default function Surveys({
    points,
}: {
    points: { balance: number; lifetime_earned: number };
}) {
    const flow = useSurveyFlow({
        onApproved: () => router.reload({ only: ['points'] }),
    });

    return (
        <>
            <Head title="Surveys" />
            <PageTitle
                icon={ClipboardList}
                title="Surveys"
                pill="Earn points"
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
                    Points are added only after the survey provider confirms
                    that you completed a survey — that can take a few minutes.
                    If you do not qualify for a survey, it earns no points.
                </Notice>
            </div>

            <Reveal>
                <Panel className="mt-3 p-4">
                    <SurveyPresenter flow={flow} />
                </Panel>
            </Reveal>

            <SurveyFrame flow={flow} />
        </>
    );
}
