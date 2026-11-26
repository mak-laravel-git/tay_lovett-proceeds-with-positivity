import { Form, Link } from '@inertiajs/react';
import { Check, Circle, Eye, LoaderCircle, Minus, Send, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { AdStep } from '@/components/quote-workflow/ad-step';
import { GenerateStep } from '@/components/quote-workflow/generate-step';
import { SurveyStep } from '@/components/quote-workflow/survey-step';
import { Button } from '@/components/ui/button';
import { cancelWorkflow } from '@/lib/workflow-api';
import type { Workflow, WorkflowStep } from '@/lib/workflow-api';
import { cn } from '@/lib/utils';

type Props = {
    workflow: Workflow;
    onChange: (workflow: Workflow) => void;
    /** Leave the panel: the run is over, or the user cancelled it. */
    onClose: () => void;
};

/** The running "Create quote" flow: progress on top, the current step below. */
export function WorkflowPanel({ workflow, onChange, onClose }: Props) {
    const [cancelling, setCancelling] = useState(false);

    const cancel = async () => {
        setCancelling(true);

        try {
            await cancelWorkflow(workflow.id);
            onClose();
        } catch (caught) {
            toast.error(
                caught instanceof Error ? caught.message : 'Could not cancel.',
            );
        } finally {
            setCancelling(false);
        }
    };

    return (
        <div className="mt-5 grid gap-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-sm font-bold">
                    Creating your {workflow.category.name} quote
                </p>
                {workflow.can_cancel ? (
                    <Button
                        size="sm"
                        variant="ghost"
                        disabled={cancelling}
                        onClick={() => void cancel()}
                    >
                        <X /> Cancel
                    </Button>
                ) : null}
            </div>

            <Steps steps={workflow.steps} />

            <CurrentStep
                workflow={workflow}
                onChange={onChange}
                onClose={onClose}
            />
        </div>
    );
}

function CurrentStep({ workflow, onChange, onClose }: Props) {
    switch (workflow.state) {
        case 'ad_before_survey':
        case 'ad_before_publish':
            // Keyed so each ad step starts fresh.
            return (
                <AdStep
                    key={workflow.state}
                    workflow={workflow}
                    onChange={onChange}
                />
            );
        case 'survey':
            return <SurveyStep workflow={workflow} onChange={onChange} />;
        case 'generate':
        case 'generating':
            return <GenerateStep workflow={workflow} onChange={onChange} />;
        case 'completed':
            return <Result workflow={workflow} onClose={onClose} />;
        default:
            return (
                <div className="grid gap-3">
                    <p className="text-sm text-muted-foreground">
                        This run has ended.
                    </p>
                    <Button className="w-fit" onClick={onClose}>
                        Start again
                    </Button>
                </div>
            );
    }
}

function Result({
    workflow,
    onClose,
}: {
    workflow: Workflow;
    onClose: () => void;
}) {
    const quote = workflow.quote;

    return (
        <div className="grid gap-3" role="status">
            {quote ? (
                <>
                    <blockquote className="rounded-xl border-l-4 border-brand bg-brand-soft/50 p-4 text-base font-bold">
                        {quote.text}
                    </blockquote>
                    <p className="text-sm text-muted-foreground">
                        {quote.status === 'published'
                            ? 'Your quote is published to the feed.'
                            : quote.status === 'approved'
                              ? 'Your quote is ready. Publish it when you want it on the feed.'
                              : quote.status === 'rejected'
                                ? 'This quote was not approved for the feed.'
                                : 'Your quote is waiting for a review before it can be published.'}
                    </p>
                </>
            ) : (
                <p className="text-sm text-muted-foreground">
                    This run is complete.
                </p>
            )}
            <div className="flex flex-wrap gap-2">
                {quote?.status === 'approved' ? (
                    <Form
                        action={`/studio/quotes/${quote.slug}/publish`}
                        method="post"
                        onSuccess={onClose}
                    >
                        {({ processing }) => (
                            <Button type="submit" disabled={processing}>
                                <Send /> Publish
                            </Button>
                        )}
                    </Form>
                ) : null}
                {quote?.status === 'published' ? (
                    <Button asChild>
                        <Link href={`/q/${quote.slug}`}>
                            <Eye /> View
                        </Link>
                    </Button>
                ) : null}
                <Button variant="outline" onClick={onClose}>
                    Create another
                </Button>
            </div>
        </div>
    );
}

const stepIcons = {
    done: Check,
    skipped: Minus,
    current: LoaderCircle,
    upcoming: Circle,
} as const;

function Steps({ steps }: { steps: WorkflowStep[] }) {
    return (
        <ol className="flex flex-wrap gap-2" aria-label="Progress">
            {steps.map((step, index) => {
                const Icon = stepIcons[step.status];

                return (
                    <li
                        key={step.key}
                        aria-current={
                            step.status === 'current' ? 'step' : undefined
                        }
                        className={cn(
                            'flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-bold',
                            step.status === 'current' &&
                                'border-brand bg-brand-soft text-brand-deep',
                            step.status === 'done' &&
                                'border-emerald-200 bg-emerald-50 text-emerald-700',
                            (step.status === 'upcoming' ||
                                step.status === 'skipped') &&
                                'text-muted-foreground',
                        )}
                    >
                        <Icon className="size-3.5" aria-hidden />
                        {index + 1}. {step.label}
                        {step.status === 'skipped' ? ' (skipped)' : ''}
                    </li>
                );
            })}
        </ol>
    );
}
