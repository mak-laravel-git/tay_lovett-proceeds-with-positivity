import { ExternalLink, FlaskConical, X } from 'lucide-react';
import { createPortal } from 'react-dom';
import { Button } from '@/components/ui/button';
import type { SurveyFlow } from '@/hooks/use-survey-flow';

/**
 * The full-screen frame a survey opens in. It is portalled to <body> so it stays full screen even when the
 * presenter sits inside a dialog (a transformed dialog would otherwise become its containing block).
 */
export function SurveyFrame({ flow }: { flow: SurveyFlow }) {
    const { open } = flow;

    if (!open) {
        return null;
    }

    return createPortal(
        <div
            role="dialog"
            aria-modal="true"
            aria-label={`Survey ${open.surveyId}`}
            className="pointer-events-auto fixed inset-0 z-[100] flex flex-col bg-ink"
        >
            <div className="flex flex-wrap items-center justify-between gap-2 border-b-[3px] border-brand bg-ink px-3 py-2 text-white">
                <span className="text-sm font-bold">
                    Survey {open.surveyId}
                </span>
                <div className="flex flex-wrap items-center gap-2">
                    {flow.canSimulate ? (
                        <Button
                            size="sm"
                            variant="secondary"
                            disabled={flow.simulating}
                            onClick={() => void flow.simulate()}
                            title="Development only: completes this survey as a verified provider callback would"
                        >
                            <FlaskConical /> Simulate completion (dev)
                        </Button>
                    ) : null}
                    <Button
                        size="sm"
                        variant="secondary"
                        onClick={flow.openInWindow}
                        title="Use this if the survey does not appear below"
                    >
                        <ExternalLink /> Not loading? Open in a window
                    </Button>
                    <Button
                        size="sm"
                        variant="secondary"
                        onClick={flow.closeSurvey}
                    >
                        <X /> Close
                    </Button>
                </div>
            </div>
            <iframe
                title={`Survey ${open.surveyId}`}
                src={open.url}
                className="min-h-0 flex-1 border-0 bg-white"
                referrerPolicy="strict-origin-when-cross-origin"
                sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-top-navigation-by-user-activation"
            />
        </div>,
        document.body,
    );
}
