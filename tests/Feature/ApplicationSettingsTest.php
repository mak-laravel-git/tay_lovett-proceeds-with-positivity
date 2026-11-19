<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Services\AppSettings;
use App\Support\AppSettingsRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    app(AppSettings::class)->refresh();
});

function appSettings(): AppSettings
{
    return app(AppSettings::class);
}

test('values fall back to env config until an administrator saves them', function () {
    config(['app.name' => 'Env Name']);
    appSettings()->captureBaseline();

    expect(appSettings()->get('general.site_name'))->toBe('Env Name')
        ->and(appSettings()->source('general.site_name'))->toBe('env');

    appSettings()->set('general.site_name', 'Database Name');

    expect(appSettings()->get('general.site_name'))->toBe('Database Name')
        ->and(appSettings()->source('general.site_name'))->toBe('database')
        ->and(config('app.name'))->toBe('Database Name');
});

test('a saved empty value differs from a missing one', function () {
    appSettings()->set('contact.phone', '');

    expect(appSettings()->isOverridden('contact.phone'))->toBeTrue()
        ->and(appSettings()->get('contact.phone'))->toBe('')
        ->and(appSettings()->isOverridden('contact.address'))->toBeFalse();
});

test('reset removes only that group and returns to env values', function () {
    config(['app.name' => 'Env Name']);
    appSettings()->captureBaseline();
    appSettings()->set('general.site_name', 'Changed');
    appSettings()->set('contact.phone', '123');

    appSettings()->reset('general');

    expect(appSettings()->get('general.site_name'))->toBe('Env Name')
        ->and(appSettings()->get('contact.phone'))->toBe('123');
});

test('settings are read from the cache without further database queries', function () {
    appSettings()->set('general.tagline', 'Cached line');
    appSettings()->refresh();
    appSettings()->get('general.tagline');

    DB::enableQueryLog();
    appSettings()->get('general.tagline');
    appSettings()->get('smtp.host');
    expect(DB::getQueryLog())->toBe([]);

    $fresh = new AppSettings;
    DB::flushQueryLog();
    expect($fresh->get('general.tagline'))->toBe('Cached line')
        ->and(DB::getQueryLog())->toBe([]);
});

test('saving clears the cache so the next read sees the new value', function () {
    appSettings()->set('general.tagline', 'One');
    expect((new AppSettings)->get('general.tagline'))->toBe('One');

    appSettings()->set('general.tagline', 'Two');

    expect((new AppSettings)->get('general.tagline'))->toBe('Two');
});

test('the site keeps working on env values when the table is missing', function () {
    config(['app.name' => 'Env Name']);
    appSettings()->captureBaseline();
    Cache::flush();
    Schema::drop('app_settings');

    $settings = new AppSettings;

    expect($settings->get('general.site_name'))->toBe('Env Name')
        ->and($settings->site()['name'])->toBe('Env Name');
});

test('secrets are encrypted at rest and never sent to the browser', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->post(route('admin.settings.update', 'smtp'), [
        'enabled' => true,
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'mailer',
        'password' => 'super-secret-password',
        'from_address' => 'hello@example.com',
        'from_name' => 'Site',
        'timeout' => 30,
    ])->assertRedirect();

    $row = AppSetting::query()->where('key', 'smtp.password')->first();
    expect($row->is_secret)->toBeTrue()
        ->and($row->value)->not->toContain('super-secret-password')
        ->and(appSettings()->get('smtp.password'))->toBe('super-secret-password');

    $this->actingAs($admin)->get(route('admin.settings'))
        ->assertOk()
        ->assertDontSee('super-secret-password')
        ->assertInertia(fn ($page) => $page
            ->component('admin/settings/index')
            ->where('groups.smtp.password.value', null)
            ->where('groups.smtp.password.has_value', true)
            ->where('groups.smtp.host.value', 'smtp.example.com')
            ->where('groups.smtp.host.source', 'database'));
});

test('leaving the smtp password empty keeps it and clearing returns to env', function () {
    $admin = User::factory()->administrator()->create();
    appSettings()->set('smtp.password', 'kept');

    $payload = [
        'enabled' => false, 'host' => '', 'port' => '', 'encryption' => 'none',
        'username' => '', 'password' => '', 'from_address' => '', 'from_name' => '', 'timeout' => 30,
    ];

    $this->actingAs($admin)->post(route('admin.settings.update', 'smtp'), $payload)->assertRedirect();
    expect(appSettings()->get('smtp.password'))->toBe('kept');

    $this->actingAs($admin)->post(route('admin.settings.update', 'smtp'), [...$payload, 'clear_password' => true])->assertRedirect();
    expect(appSettings()->isOverridden('smtp.password'))->toBeFalse();
});

test('smtp settings are applied to the mailer config when enabled', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->post(route('admin.settings.update', 'smtp'), [
        'enabled' => true, 'host' => 'mail.example.org', 'port' => 465, 'encryption' => 'ssl',
        'username' => 'u', 'password' => 'p', 'from_address' => 'noreply@example.org', 'from_name' => 'Brand', 'timeout' => 20,
    ])->assertRedirect();

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('mail.example.org')
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtps')
        ->and(config('mail.from.address'))->toBe('noreply@example.org');

    $this->actingAs($admin)->post(route('admin.settings.reset', 'smtp'))->assertRedirect();

    expect(config('mail.default'))->not->toBe('smtp');
});

test('smtp needs a host port and from address only when enabled', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->post(route('admin.settings.update', 'smtp'), [
        'enabled' => true, 'host' => '', 'port' => '', 'encryption' => 'tls', 'timeout' => 30,
    ])->assertSessionHasErrors(['host', 'port', 'from_address']);
});

test('a test email is sent with the form values and failures hide the password', function () {
    $admin = User::factory()->administrator()->create();
    Mail::fake();

    $this->actingAs($admin)->post(route('admin.settings.smtp.test'), [
        'enabled' => true, 'host' => '127.0.0.1', 'port' => 1, 'encryption' => 'none',
        'username' => 'u', 'password' => 'leaky-password', 'from_address' => 'a@example.com',
        'from_name' => 'A', 'timeout' => 5, 'to' => 'me@example.com',
    ])->assertRedirect();

    expect(AppSetting::query()->count())->toBe(0);
});

test('general settings store a logo and remove the old file on replacement', function () {
    Storage::fake('public');
    $admin = User::factory()->administrator()->create();
    $payload = ['site_name' => 'Brand', 'tagline' => '', 'meta_description' => '', 'footer_text' => ''];

    $this->actingAs($admin)->post(route('admin.settings.update', 'general'), [
        ...$payload, 'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
    ])->assertRedirect();

    $first = appSettings()->get('general.logo_path');
    Storage::disk('public')->assertExists($first);
    expect(appSettings()->logoUrl())->not->toBeNull();

    $this->actingAs($admin)->post(route('admin.settings.update', 'general'), [
        ...$payload, 'logo' => UploadedFile::fake()->image('new.png', 200, 200),
    ])->assertRedirect();

    Storage::disk('public')->assertMissing($first);

    $this->actingAs($admin)->post(route('admin.settings.update', 'general'), [
        ...$payload, 'remove_logo' => true,
    ])->assertRedirect();

    expect(appSettings()->logoUrl())->toBeNull();
});

test('svg and oversized logos are rejected', function () {
    Storage::fake('public');
    $admin = User::factory()->administrator()->create();
    $payload = ['site_name' => 'Brand'];

    $this->actingAs($admin)->post(route('admin.settings.update', 'general'), [
        ...$payload, 'logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    ])->assertSessionHasErrors('logo');

    $this->actingAs($admin)->post(route('admin.settings.update', 'general'), [
        ...$payload, 'logo' => UploadedFile::fake()->image('big.png')->size(3000),
    ])->assertSessionHasErrors('logo');
});

test('authentication and contact values are validated', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->post(route('admin.settings.update', 'authentication'), [
        'registration_open' => true, 'otp_expiry_minutes' => 1, 'otp_resend_cooldown_seconds' => 60,
        'otp_max_attempts' => 5, 'otp_max_sends_per_hour' => 5, 'confirm_timeout_minutes' => 30,
    ])->assertSessionHasErrors('otp_expiry_minutes');

    $this->actingAs($admin)->post(route('admin.settings.update', 'contact'), [
        'facebook_url' => 'http://insecure.example.com', 'support_email' => 'nope',
    ])->assertSessionHasErrors(['facebook_url', 'support_email']);

    $this->actingAs($admin)->post(route('admin.settings.update', 'unknown'), [])->assertNotFound();
});

test('the site name from settings reaches every page', function () {
    $user = User::factory()->create();
    appSettings()->set('general.site_name', 'Brand New Name');

    $this->actingAs($user)->get(route('feed'))->assertInertia(fn ($page) => $page->where('site.name', 'Brand New Name')->where('name', 'Brand New Name'));
});

test('only administrators can manage application settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.settings'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.settings.update', 'general'), ['site_name' => 'x'])->assertForbidden();
    $this->actingAs($user)->post(route('admin.settings.reset', 'general'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.settings.smtp.test'), [])->assertForbidden();
});

test('every registry field resolves and an unknown key is rejected', function () {
    foreach (AppSettingsRegistry::groups() as $group => $fields) {
        expect(array_keys(appSettings()->group($group)))->toBe(array_keys($fields));
    }

    expect(fn () => appSettings()->get('nope.key'))->toThrow(InvalidArgumentException::class);
});
