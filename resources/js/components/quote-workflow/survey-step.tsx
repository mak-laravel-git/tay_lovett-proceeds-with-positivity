import { useCallback, useState } from 'react';
import { presenterFor } from '@/components/ads/providers';
import { SurveyFrame } from '@/components/ads/survey-frame';
import { Button } from '@/components/ui/button';
import { Notice } from '@/components/ui-brand';
import { useSurveyFlow } from '@/hooks/use-survey-flow';
import { fetchWorkflow, fetchWorkflowOffers } from '@/lib/workflow-api';
import type { Workflow } from '@/lib/workflow-api';

type Props = {
    workflow: Workflow;
    onChange: (workflow: Workflow) => void;
};

/**
 * The survey step. Surveys are listed, opened and polled exactly as everywhere else; only the list comes from
 * the run, which also decides what happens when there is nothing to show.
 */
export function SurveyStep({ workflow, onChange }: Props) {
    const id = workflow.id;
    const [message, setMessage] = useState<string | null>(null);
    const [checking, setChecking] = useState(false);

    const fetchList = useCallback(async () => {
        const response = await fetchWorkflowOffers(id);
        setMessage(response.message);
        onChange(response.workflow);

        return response;
    }, [id, onChange]);

    const refresh = useCallback(async () => {
        setChecking(true);

        try {
            onChange((await fetchWorkflow(id)).workflow);
        } catch {
            // The next list refresh brings the state up to date.
        } finally {
            setChecking(false);
        }
    }, [id, onChange]);

    const flow = useSurveyFlow({
        purpose: 'quote_generation',
        fetchList,
        onApproved: () => void refresh(),
    });
    const Presenter = presenterFor(flow.list?.provider);

    return (
        <div className="grid gap-3">
            {!workflow.survey.awards_points ? (
                <Notice>
                    Completing this survey unlocks your quote. It does not earn
                    points.
                </Notice>
            ) : null}
            {workflow.notice || message ? (
                <p className="text-sm text-brand-deep" role="alert">
                    {message ?? workflow.notice}
                </p>
            ) : null}
            <Presenter flow={flow} />
            {workflow.survey.pending ? (
                <Button
                    className="w-fit"
                    variant="outline"
                    disabled={checking}
                    onClick={() => void refresh()}
                >
                    {checking
                        ? 'Checking…'
                        : 'I finished a survey: check again'}
                </Button>
            ) : null}
            <SurveyFrame flow={flow} />
        </div>
    );
}
