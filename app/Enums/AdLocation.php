<?php

namespace App\Enums;

/**
 * Where a regular (display) ad can be mounted. Each location exists in the front end, so adding a new one needs
 * code. Admin, sign-in, dialog and survey-frame screens deliberately have none.
 */
enum AdLocation: string
{
    case FeedTop = 'feed_top';
    case FeedBetween = 'feed_between';
    case SavedTop = 'saved_top';
    case PublicQuoteBelow = 'public_quote_below';
    case StudioBelow = 'studio_below';
    case SidebarRight = 'sidebar_right';
    case EarnTop = 'earn_top';

    public function label(): string
    {
        return match ($this) {
            self::FeedTop => 'Feed: top of the page',
            self::FeedBetween => 'Feed: between quotes',
            self::SavedTop => 'Saved quotes: top of the page',
            self::PublicQuoteBelow => 'Public quote page: below the quote',
            self::StudioBelow => 'Quote Studio: bottom of the page',
            self::SidebarRight => 'Right sidebar (wide screens)',
            self::EarnTop => 'Earn Rewards: top of the page (check Google policy first)',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $location): array => ['value' => $location->value, 'label' => $location->label()], self::cases());
    }
}
