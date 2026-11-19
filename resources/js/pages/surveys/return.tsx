import { Head } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Where the survey provider sends the browser when a survey ends. It only tells the survey page (or the popup's
 * opener) that the user is back, so the frame can close. It can never approve a survey or award points: that
 * happens only when the provider's own server confirms the completion.
 */
export default function SurveyReturn({
    state,
    activityId,
}: {
    state: 'returned' | 'unverified';
    activityId: string | null;
}) {
    useEffect(() => {
        const message = { type: 'survey-returned', activityId };

        if (window.parent !== window) {
            window.parent.postMessage(message, window.location.origin);
        }

        if (window.opener) {
            (window.opener as Window).postMessage(
                message,
                window.location.origin,
            );
            const timer = window.setTimeout(() => window.close(), 1500);

            return () => window.clearTimeout(timer);
        }
    }, [activityId]);

    return (
        <>
            <Head title="Survey finished" />
            <main className="grid min-h-dvh place-items-center bg-background p-6 text-center">
                <div className="max-w-sm">
                    <h1 className="text-xl font-extrabold">
                        Thanks for taking the survey
                    </h1>
                    <p className="mt-2 text-sm text-muted-foreground">
                        {state === 'returned'
                            ? 'We are waiting for the survey provider to confirm it. Your points are added as soon as they do — this can take a few minutes.'
                            : 'You can close this window. Points are added only after the survey provider confirms your survey.'}
                    </p>
                </div>
            </main>
        </>
    );
}
