<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Services\AppSettings;
use App\Support\AppSettingsRegistry;
use App\Support\LegalDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ApplicationSettingsController extends Controller
{
    public function index(Request $request, AppSettings $settings): Response
    {
        $groups = [];

        foreach (AppSettingsRegistry::groupNames() as $group) {
            $groups[$group] = $settings->describe($group);
        }

        return Inertia::render('admin/settings/index', [
            'groups' => $groups,
            'tab' => in_array($request->query('tab'), [...AppSettingsRegistry::groupNames(), 'legal'], true)
                ? $request->query('tab')
                : 'general',
            'logoUrl' => $settings->logoUrl(),
            'faviconUrl' => $settings->faviconUrl(),
            'activeMailer' => config('mail.default'),
            'adminEmail' => $request->user()->email,
            'maintenanceStatus' => $settings->maintenanceStatus(),
            'legalDocuments' => collect(LegalDefaults::slugs())->map(function (string $slug): array {
                $document = LegalDocument::forSlug($slug);

                return [
                    'slug' => $slug,
                    'title' => $document->title,
                    'is_custom' => $document->isCustom(),
                    'updated_at' => $document->updated_at?->toIso8601String(),
                    'updated_by' => $document->editor?->name,
                ];
            })->all(),
        ]);
    }

    public function update(Request $request, string $group, AppSettings $settings): RedirectResponse
    {
        abort_unless(AppSettingsRegistry::exists($group), 404);

        $validated = $request->validate($this->rules($group, $request));
        $values = collect($validated)
            ->only(AppSettingsRegistry::fieldNames($group))
            ->except($this->secretFields($group))
            ->map(fn (mixed $value): mixed => $value ?? '')
            ->all();

        if ($group === 'general') {
            $values = [...$values, ...$this->storeBranding($request, $settings)];
        }

        $settings->setMany($this->qualify($group, $values), $request->user()->id);

        if ($group === 'smtp') {
            $this->updateSmtpPassword($request, $settings);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Settings saved.']);

        return back();
    }

    public function reset(string $group, AppSettings $settings): RedirectResponse
    {
        abort_unless(AppSettingsRegistry::exists($group), 404);

        if ($group === 'general') {
            $this->deleteBrandingFile($settings->get('general.logo_path'));
            $this->deleteBrandingFile($settings->get('general.favicon_path'));
        }

        $settings->reset($group);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Settings reset. Values now come from the .env file.']);

        return back();
    }

    /**
     * Send one test message with the values currently in the form. Nothing is saved.
     */
    public function testSmtp(Request $request, AppSettings $settings): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules('smtp', $request, forTest: true),
            'to' => ['required', 'email', 'max:255'],
        ]);

        $smtp = array_merge($settings->group('smtp'), collect($validated)->only(AppSettingsRegistry::fieldNames('smtp'))->except('password')->all());

        if (filled($validated['password'] ?? null)) {
            $smtp['password'] = $validated['password'];
        }

        $name = (string) $settings->get('general.site_name');

        try {
            Mail::build($settings->smtpTransportConfig($smtp))->raw(
                "This is a test message from {$name}. If you can read it, your SMTP settings work.",
                function ($message) use ($validated, $smtp, $name): void {
                    $message->to($validated['to'])->subject("SMTP test from {$name}");

                    if (filled($smtp['from_address'] ?? null)) {
                        $message->from($smtp['from_address'], $smtp['from_name'] ?: $name);
                    }
                },
            );

            Inertia::flash('toast', ['type' => 'success', 'message' => 'Test email sent to '.$validated['to'].'.']);
        } catch (Throwable $exception) {
            $message = (string) $exception->getMessage();

            if (filled($smtp['password'] ?? null)) {
                $message = str_replace((string) $smtp['password'], '[password]', $message);
            }

            Inertia::flash('toast', ['type' => 'error', 'message' => 'Test email failed: '.str($message)->limit(160)]);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(string $group, Request $request, bool $forTest = false): array
    {
        return match ($group) {
            'general' => [
                'site_name' => ['required', 'string', 'max:80'],
                'tagline' => ['nullable', 'string', 'max:80'],
                'meta_description' => ['nullable', 'string', 'max:300'],
                'footer_text' => ['nullable', 'string', 'max:200'],
                'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
                'favicon' => ['nullable', 'file', 'mimetypes:image/png,image/x-icon,image/vnd.microsoft.icon,image/webp', 'max:512'],
                'remove_logo' => ['sometimes', 'boolean'],
                'remove_favicon' => ['sometimes', 'boolean'],
            ],
            'smtp' => [
                'enabled' => ['required', 'boolean'],
                'host' => [Rule::requiredIf($request->boolean('enabled') || $forTest), 'nullable', 'string', 'max:255'],
                'port' => [Rule::requiredIf($request->boolean('enabled') || $forTest), 'nullable', 'integer', 'between:1,65535'],
                'encryption' => ['required', Rule::in(['none', 'tls', 'ssl'])],
                'username' => ['nullable', 'string', 'max:255'],
                'password' => ['nullable', 'string', 'max:500'],
                'clear_password' => ['sometimes', 'boolean'],
                'from_address' => [Rule::requiredIf($request->boolean('enabled') || $forTest), 'nullable', 'email', 'max:255'],
                'from_name' => ['nullable', 'string', 'max:120'],
                'timeout' => ['required', 'integer', 'between:5,120'],
            ],
            'authentication' => [
                'registration_open' => ['required', 'boolean'],
                'otp_expiry_minutes' => ['required', 'integer', 'between:3,60'],
                'otp_resend_cooldown_seconds' => ['required', 'integer', 'between:15,600'],
                'otp_max_attempts' => ['required', 'integer', 'between:3,10'],
                'otp_max_sends_per_hour' => ['required', 'integer', 'between:2,20'],
                'confirm_timeout_minutes' => ['required', 'integer', 'between:5,1440'],
            ],
            'contact' => [
                'support_email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:40'],
                'address' => ['nullable', 'string', 'max:200'],
                'facebook_url' => ['nullable', 'url:https', 'max:255'],
                'x_url' => ['nullable', 'url:https', 'max:255'],
                'instagram_url' => ['nullable', 'url:https', 'max:255'],
                'youtube_url' => ['nullable', 'url:https', 'max:255'],
            ],
            // 'maintenance' has no case here: MaintenanceController owns the whole feature, including its
            // own small, separately validated persistence of title/message/expected_back_at.
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function secretFields(string $group): array
    {
        return collect(AppSettingsRegistry::groups()[$group])
            ->filter(fn (array $definition): bool => $definition['secret'] ?? false)
            ->keys()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function qualify(string $group, array $values): array
    {
        $qualified = [];

        foreach ($values as $name => $value) {
            $qualified[$group.'.'.$name] = $value;
        }

        return $qualified;
    }

    /**
     * Save a new SMTP password, or drop the stored one so the .env password applies again. An empty field keeps it.
     */
    private function updateSmtpPassword(Request $request, AppSettings $settings): void
    {
        if ($request->boolean('clear_password')) {
            $settings->forget('smtp.password');

            return;
        }

        if (filled($request->input('password'))) {
            $settings->set('smtp.password', (string) $request->input('password'), $request->user()->id);
        }
    }

    /**
     * Store uploaded branding files and report the new paths. Old files are removed.
     *
     * @return array<string, string|null>
     */
    private function storeBranding(Request $request, AppSettings $settings): array
    {
        $paths = [];

        foreach (['logo' => 'general.logo_path', 'favicon' => 'general.favicon_path'] as $field => $key) {
            $file = $request->file($field);
            $remove = $request->boolean('remove_'.$field);

            if (! $file instanceof UploadedFile && ! $remove) {
                continue;
            }

            $this->deleteBrandingFile($settings->get($key));
            $paths[explode('.', $key, 2)[1]] = $file instanceof UploadedFile ? $file->store('branding', 'public') : '';
        }

        return $paths;
    }

    private function deleteBrandingFile(mixed $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete((string) $path);
        }
    }
}
