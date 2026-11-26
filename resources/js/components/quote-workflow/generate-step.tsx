import { RotateCw } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { fetchWorkflow, generateQuote } from '@/lib/workflow-api';
import type { Workflow } from '@/lib/workflow-api';

// How often to look again while another tab (or an interrupted request) is generating.
const RECHECK_MS = 5_000;

type Props = {
    workflow: Workflow;
    onChange: (workflow: Workflow) => void;
};

/** Creates the quote as soon as the run is ready for it, and offers a retry if that fails. */
export function GenerateStep({ workflow, onChange }: Props) {
    const id = workflow.id;
    const ready = workflow.state === 'generate';
    const [error, setError] = useState<string | null>(null);

    const generate = useCallback(async () => {
        setError(null);

        try {
            onChange((await generateQuote(id)).workflow);
        } catch (caught) {
            setError(
                caught instanceof Error
                    ? caught.message
                    : 'A quote could not be generated.',
            );

            // The run may have gone back a step (for example the survey was already used).
            fetchWorkflow(id)
                .then((response) => onChange(response.workflow))
                .catch(() => undefined);
        }
    }, [id, onChange]);

    useEffect(() => {
        if (!ready) {
            const poll = window.setInterval(
                () =>
                    void fetchWorkflow(id)
                        .then((response) => onChange(response.workflow))
                        .catch(() => undefined),
                RECHECK_MS,
            );

            return () => window.clearInterval(poll);
        }
    }, [ready, id, onChange]);

    // Once per mount; deferred so React strict mode does not send it twice. A failure waits for the button.
    const [started, setStarted] = useState(false);

    useEffect(() => {
        if (!ready || started) {
            return;
        }

        const timer = window.setTimeout(() => {
            setStarted(true);
            void generate();
        }, 0);

        return () => window.clearTimeout(timer);
    }, [ready, started, generate]);

    if (error) {
        return (
            <div className="grid gap-3">
                <p className="text-sm text-brand-deep" role="alert">
                    {error}
                </p>
                <Button className="w-fit" onClick={() => void generate()}>
                    <RotateCw /> Try again
                </Button>
            </div>
        );
    }

    return (
        <p
            className="flex items-center gap-2 text-sm text-muted-foreground"
            role="status"
        >
            <Spinner /> Generating your quote…
        </p>
    );
}
