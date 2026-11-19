<?php

namespace App\Support;

/**
 * Starter text for the two public legal pages, shown until an administrator publishes their own.
 * It is a plain outline and not legal advice. The site owner must replace it before launch.
 */
class LegalDefaults
{
    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return ['privacy', 'terms'];
    }

    public static function exists(string $slug): bool
    {
        return in_array($slug, self::slugs(), true);
    }

    public static function title(string $slug): string
    {
        return match ($slug) {
            'privacy' => 'Privacy Policy',
            'terms' => 'Terms of Service',
            default => ucfirst($slug),
        };
    }

    public static function html(string $slug): string
    {
        return match ($slug) {
            'privacy' => <<<'HTML'
<p>This is a starter outline of the Privacy Policy. It has not been reviewed by a lawyer. An administrator should replace it with the final text.</p>
<h2>What we collect</h2>
<ul><li>Account details such as your name and email address.</li><li>Quotes you save and actions you take in the app.</li><li>Basic technical data needed to keep the service secure.</li></ul>
<h2>How we use it</h2>
<p>We use this information to run the service, keep accounts safe and contact you about your account.</p>
<h2>Your choices</h2>
<p>You can update your profile and ask us to delete your account at any time from your account settings.</p>
HTML,
            'terms' => <<<'HTML'
<p>This is a starter outline of the Terms of Service. It has not been reviewed by a lawyer. An administrator should replace it with the final text.</p>
<h2>Using the service</h2>
<p>You must give accurate information and keep your sign-in details private.</p>
<h2>Content</h2>
<p>Quotes in the feed are provided for inspiration. We may remove content that breaks these terms.</p>
<h2>Changes</h2>
<p>We may update these terms. Continuing to use the service means you accept the updated terms.</p>
HTML,
            default => '',
        };
    }
}
