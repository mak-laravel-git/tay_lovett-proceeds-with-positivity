import type { ComponentType } from 'react';
import { GoogleAdSensePresenter } from '@/components/ads/google-adsense-presenter';
import { SurveyPresenter } from '@/components/ads/survey-presenter';
import type { PresenterProps } from '@/components/ads/survey-presenter';

/**
 * How each provider is shown to the user, keyed by `ad_providers.key`. A provider whose server adapter returns
 * something other than a survey list (a video, a display unit) gets its own presenter here: one line per provider.
 */
const presenters: Record<string, ComponentType<PresenterProps>> = {
    tapresearch: SurveyPresenter,
    wannads: SurveyPresenter,
    theoremreach: SurveyPresenter,
    google_adsense: GoogleAdSensePresenter,
};

/** The presenter for the provider that won the fallback chain; the survey list is the default. */
export function presenterFor(
    providerKey: string | null | undefined,
): ComponentType<PresenterProps> {
    return (
        (providerKey ? presenters[providerKey] : undefined) ?? SurveyPresenter
    );
}
