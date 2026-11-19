import { presenterFor } from '@/components/ads/providers';
import { SurveyFrame } from '@/components/ads/survey-frame';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useSurveyFlow } from '@/hooks/use-survey-flow';
import type { GatePurpose } from '@/lib/ads-api';

type Props = {
    purpose: GatePurpose;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    /** Called when the provider has confirmed a completed survey. */
    onUnlocked: () => void;
};

/**
 * The reusable "complete a survey first" dialog. It asks the server which provider has something to show,
 * renders that provider's presenter and reports back once the provider has confirmed completion.
 *
 * Use it through useAdGate() rather than directly.
 */
export function AdGate({ purpose, open, onOpenChange, onUnlocked }: Props) {
    const flow = useSurveyFlow({
        purpose,
        enabled: open,
        onApproved: onUnlocked,
    });
    const Presenter = presenterFor(flow.list?.provider);

    return (
        <>
            {/* Hidden (not closed) while a survey is open, so the frame is never a child of a modal. */}
            <Dialog
                open={open && flow.open === null}
                onOpenChange={onOpenChange}
            >
                <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-xl">
                    <DialogHeader>
                        <DialogTitle>Complete a survey to continue</DialogTitle>
                        <DialogDescription>
                            Finish one survey and we will unlock this for you.
                            Your result is confirmed by the survey provider,
                            which can take a few minutes.
                        </DialogDescription>
                    </DialogHeader>
                    <Presenter flow={flow} />
                </DialogContent>
            </Dialog>
            <SurveyFrame flow={flow} />
        </>
    );
}
