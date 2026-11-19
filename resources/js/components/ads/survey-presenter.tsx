import { Clock, Flame, LifeBuoy, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Notice, Pill } from '@/components/ui-brand';
import type { SurveyFlow } from '@/hooks/use-survey-flow';

export type PresenterProps = { flow: SurveyFlow };

/**
 * Presents a survey provider (TapResearch today): a native list of surveys, each opened in a frame, then a
 * wait for the provider's confirmation. Pure presentation, all state lives in the flow. The caller renders
 * <SurveyFrame> next to it, outside any dialog, so the frame can stay open while the list is hidden.
 */
export function SurveyPresenter({ flow }: PresenterProps) {
    const { list, loading, error, open, waiting, starting } = flow;
    const offers = list?.offers ?? [];

    return (
        <div className="grid gap-3">
            {waiting && !open ? (
                <div role="status">
                    <Notice>
                        Checking with the provider for survey {waiting.surveyId}
                        … this can take a few minutes.
                    </Notice>
                </div>
            ) : null}

            <div className="flex items-center justify-between gap-2">
                <h2 className="font-bold">Available surveys</h2>
                <Button
                    size="sm"
                    variant="outline"
                    disabled={loading}
                    onClick={() => void flow.load()}
                >
                    <RefreshCw className={loading ? 'animate-spin' : ''} />
                    Refresh
                </Button>
            </div>

            {error ? (
                <p className="text-sm text-destructive" role="alert">
                    {error}
                </p>
            ) : null}

            {!error && !loading && offers.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No surveys are available right now. Please check back in a
                    few minutes.
                </p>
            ) : null}

            <ul className="grid gap-3">
                {offers.map((offer) => (
                    <li
                        key={offer.identifier}
                        className="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3"
                    >
                        <div className="grid gap-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <b>Survey {offer.identifier}</b>
                                {offer.is_hot ? (
                                    <Pill className="inline-flex items-center gap-1">
                                        <Flame className="size-3" /> Popular
                                    </Pill>
                                ) : null}
                                {offer.category ? (
                                    <Pill>{offer.category}</Pill>
                                ) : null}
                            </div>
                            <span className="flex items-center gap-1 text-xs text-muted-foreground">
                                <Clock className="size-3.5" />
                                {offer.length_minutes
                                    ? `About ${offer.length_minutes} min`
                                    : 'Length not given'}
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            {offer.support_url ? (
                                <a
                                    href={offer.support_url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 text-xs font-bold text-brand-deep hover:underline"
                                >
                                    <LifeBuoy className="size-3.5" /> Get help
                                </a>
                            ) : null}
                            <Button
                                size="sm"
                                disabled={starting !== null || open !== null}
                                onClick={() => void flow.start(offer)}
                            >
                                {starting === offer.identifier
                                    ? 'Opening…'
                                    : 'Start'}
                            </Button>
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    );
}
