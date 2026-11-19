import { Notice } from '@/components/ui-brand';
import type { PresenterProps } from '@/components/ads/survey-presenter';

/**
 * Placeholder for Google AdSense. The server never selects this provider yet (it is never configured), so this
 * is only the interface a real display / video presenter will fill in later.
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars
export function GoogleAdSensePresenter(_props: PresenterProps) {
    return (
        <Notice>
            Google AdSense is not available yet. Another provider will be used
            instead.
        </Notice>
    );
}
