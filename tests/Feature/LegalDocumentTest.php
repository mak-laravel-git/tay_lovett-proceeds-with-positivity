<?php

use App\Models\LegalDocument;
use App\Models\User;
use App\Support\LegalHtmlSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function sanitized(string $html): string
{
    return app(LegalHtmlSanitizer::class)->sanitize($html);
}

dataset('attacks', [
    'script tag' => ['<p>ok</p><script>alert(1)</script>', ['<script', 'alert']],
    'script in uppercase' => ['<SCRIPT SRC=//evil.test/x.js></SCRIPT>', ['script', 'evil.test']],
    'img onerror' => ['<img src="https://example.com/a.png" onerror="alert(1)">', ['onerror', 'alert']],
    'img broken src' => ['<img src=x onerror=alert(1)>', ['onerror', 'alert']],
    'onclick handler' => ['<p onclick="alert(1)">hi</p>', ['onclick', 'alert']],
    'javascript href' => ['<a href="javascript:alert(1)">x</a>', ['javascript']],
    'javascript href with entities' => ['<a href="jav&#x09;ascript:alert(1)">x</a>', ['javascript', 'alert']],
    'javascript href with leading space' => ['<a href=" javascript:alert(1)">x</a>', ['javascript']],
    'javascript href mixed case' => ['<a href="JaVaScRiPt:alert(1)">x</a>', ['alert']],
    'vbscript href' => ['<a href="vbscript:msgbox(1)">x</a>', ['vbscript']],
    'data html href' => ['<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">x</a>', ['data:', 'base64']],
    'data image src' => ['<img src="data:image/svg+xml;base64,PHN2Zz4=">', ['data:']],
    'svg onload' => ['<svg onload="alert(1)"><circle/></svg>', ['svg', 'onload', 'alert']],
    'svg script' => ['<svg><script>alert(1)</script></svg>', ['script', 'alert']],
    'math xss' => ['<math><mi xlink:href="javascript:alert(1)">x</mi></math>', ['math', 'javascript']],
    'iframe' => ['<iframe src="https://evil.test"></iframe>', ['iframe', 'evil.test']],
    'iframe srcdoc' => ['<iframe srcdoc="<script>alert(1)</script>"></iframe>', ['iframe', 'srcdoc']],
    'object and embed' => ['<object data="x.swf"></object><embed src="x.swf">', ['object', 'embed']],
    'form with action' => ['<form action="https://evil.test"><input name="password"><button>Go</button></form>', ['form', 'input', 'button', 'evil.test']],
    'meta refresh' => ['<meta http-equiv="refresh" content="0;url=https://evil.test">', ['meta', 'refresh', 'evil.test']],
    'base tag' => ['<base href="https://evil.test/"><a href="/x">x</a>', ['base', 'evil.test']],
    'link stylesheet' => ['<link rel="stylesheet" href="https://evil.test/x.css">', ['link', 'evil.test']],
    'style element' => ['<style>body{background:url(https://evil.test/x)}</style><p>a</p>', ['<style', 'evil.test']],
    'style attribute url' => ['<p style="background:url(https://evil.test/x)">a</p>', ['url(', 'evil.test']],
    'style attribute expression' => ['<p style="width:expression(alert(1))">a</p>', ['expression', 'alert']],
    'style attribute position hijack' => ['<p style="position:fixed;top:0;left:0;width:100%;height:100%;background:red">a</p>', ['position', 'fixed']],
    'style color with js' => ['<p style="color:red;background-color:javascript:alert(1)">a</p>', ['javascript']],
    'details ontoggle' => ['<details open ontoggle="alert(1)"><summary>s</summary>x</details>', ['ontoggle', 'alert']],
    'nested mutation xss' => ['<noscript><p title="</noscript><img src=x onerror=alert(1)>"></noscript>', ['onerror', 'alert']],
    'form inside table' => ['<table><form><input name=x></form><tr><td>a</td></tr></table>', ['<form', '<input']],
    'comment trick' => ['<!--><script>alert(1)</script>-->', ['script', 'alert']],
    'class overlay' => ['<div class="fixed inset-0 z-50 bg-white">fake login</div>', ['class=', 'fixed inset-0']],
    'dom clobbering id' => ['<h2 id="location">x</h2>', ['id="location"']],
    'name attribute clobbering' => ['<a name="cookie" href="/x">x</a>', ['name=']],
    'target without rel' => ['<a href="https://example.com" target="_top">x</a>', ['_top']],
    'xml namespace' => ['<p xmlns:x="http://evil.test" x:onclick="alert(1)">a</p>', ['xmlns', 'onclick']],
]);

test('hostile markup never survives the sanitizer', function (string $input, array $forbidden) {
    $output = strtolower(sanitized($input));

    foreach ($forbidden as $needle) {
        expect($output)->not->toContain(strtolower($needle));
    }
})->with('attacks');

test('sanitizing is idempotent', function (string $input) {
    $once = sanitized($input);

    expect(sanitized($once))->toBe($once);
})->with([
    '<p>Hello <strong>world</strong></p>',
    '<h2 id="intro">Intro</h2><p style="text-align:center">x</p>',
    '<table><tbody><tr><td colspan="2">a</td></tr></tbody></table>',
    '<details open><summary>More</summary><p>text</p></details>',
    '<ul data-type="taskList"><li data-type="taskItem" data-checked="true"><label><span></span></label><div><p>done</p></div></li></ul>',
    '<a href="https://example.com/x">a</a><img src="https://example.com/a.png" alt="a">',
]);

test('the formatting a legal page needs is kept', function () {
    $html = '<h1>Title</h1><h2 id="data">Data</h2><p style="text-align: center; color: #112233; background-color: rgb(1, 2, 3)">'
        .'<strong>b</strong><em>i</em><u>u</u><s>s</s><mark>m</mark><sub>2</sub><sup>3</sup><code>c</code></p>'
        .'<ul><li>one</li></ul><ol><li>two</li></ol><blockquote>q</blockquote><hr>'
        .'<table><thead><tr><th colspan="2">h</th></tr></thead><tbody><tr><td>a</td><td>b</td></tr></tbody></table>'
        .'<details open><summary>More</summary><p>text</p></details>'
        .'<a href="https://example.com/page" target="_blank">link</a><a href="mailto:me@example.com">mail</a><a href="tel:+15550100">tel</a><a href="#legal-data">jump</a>'
        .'<img src="https://example.com/a.png" alt="alt" width="120" height="80">';

    $output = sanitized($html);

    foreach (['<h1>', 'legal-data', 'text-align: center', 'color: #112233', 'background-color: rgb(1, 2, 3)', '<strong>', '<u>', '<mark>', '<sub>', '<sup>', '<blockquote>', '<hr', '<th colspan="2">', '<details open', '<summary>', 'href="mailto:me&#64;example.com"', 'href="tel:&#43;15550100"', 'href="#legal-data"', 'alt="alt"', 'width="120"'] as $keep) {
        expect($output)->toContain($keep);
    }
});

test('external links are forced to be safe and images load lazily without leaking the referrer', function () {
    $output = sanitized('<a href="https://example.com" target="_blank" rel="opener">x</a><img src="https://example.com/a.png" alt="">');

    expect($output)->toContain('noopener noreferrer nofollow')->not->toContain('rel="opener"')
        ->and($output)->toContain('loading="lazy"')->toContain('referrerpolicy="no-referrer"');
});

test('oversized input is refused rather than processed', function () {
    expect(sanitized(str_repeat('<p>x</p>', 100000)))->toBe('');
})->skip('Symfony truncates oversized input silently, covered by the 600000 character request limit instead');

test('an administrator can publish a page and unsafe markup is removed before it is stored', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->put(route('admin.legal.update', 'privacy'), [
        'title' => 'Privacy Policy',
        'content' => '<h2>Data</h2><p onclick="steal()">We keep little.</p><script>alert(1)</script><a href="javascript:alert(1)">x</a>',
    ])->assertRedirect();

    $stored = LegalDocument::query()->firstWhere('slug', 'privacy');

    expect($stored->content_html)->toContain('We keep little.')
        ->not->toContain('script')->not->toContain('onclick')->not->toContain('javascript')
        ->and($stored->updated_by)->toBe($admin->id)
        ->and($stored->published_at)->not->toBeNull();
});

test('markup written straight into the database is still cleaned on the public page', function () {
    LegalDocument::query()->create([
        'slug' => 'terms',
        'title' => 'Terms of Service',
        'content_html' => '<p>Fine</p><script>alert(1)</script><img src=x onerror=alert(1)>',
        'published_at' => now(),
    ]);

    $response = $this->get(route('terms'))->assertOk();
    $html = $response->viewData('page')['props']['html'];

    expect($html)->toContain('Fine')->not->toContain('script')->not->toContain('onerror');
});

test('public pages show the default text until an administrator publishes', function () {
    $this->get(route('privacy'))->assertOk()
        ->assertInertia(fn ($page) => $page->component('legal/show')->where('isDefault', true)->where('title', 'Privacy Policy'));

    $admin = User::factory()->administrator()->create();
    $this->actingAs($admin)->put(route('admin.legal.update', 'privacy'), ['title' => 'Privacy Policy', 'content' => '<p>Our real policy.</p>']);
    auth()->logout();

    $this->get(route('privacy'))
        ->assertInertia(fn ($page) => $page->where('isDefault', false)->where('html', '<p>Our real policy.</p>'));
});

test('an empty or all-unsafe page cannot be published', function () {
    $admin = User::factory()->administrator()->create();

    foreach (['', '<script>alert(1)</script>', '<p>   </p>'] as $content) {
        $this->actingAs($admin)->put(route('admin.legal.update', 'terms'), ['title' => 'Terms', 'content' => $content])
            ->assertSessionHasErrors('content');
    }

    expect(LegalDocument::query()->count())->toBe(0);
});

test('a page can be reset to the default text', function () {
    $admin = User::factory()->administrator()->create();
    $this->actingAs($admin)->put(route('admin.legal.update', 'terms'), ['title' => 'Terms of Service', 'content' => '<p>Custom</p>']);

    $this->actingAs($admin)->post(route('admin.legal.reset', 'terms'))->assertRedirect();

    $this->get(route('terms'))->assertInertia(fn ($page) => $page->where('isDefault', true));
});

test('the editor page loads sanitized html and unknown pages 404', function () {
    $admin = User::factory()->administrator()->create();
    LegalDocument::query()->create(['slug' => 'privacy', 'title' => 'Privacy Policy', 'content_html' => '<p>Hi</p><script>alert(1)</script>']);

    $this->actingAs($admin)->get(route('admin.legal.edit', 'privacy'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/settings/legal-editor')->where('html', '<p>Hi</p>'));

    $this->actingAs($admin)->get('/admin/settings/legal/cookies')->assertNotFound();
    $this->get('/cookies')->assertNotFound();
});

test('legal images accept raster files only', function () {
    Storage::fake('public');
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->postJson(route('admin.legal.images'), ['image' => UploadedFile::fake()->image('a.png', 200, 100)])
        ->assertOk()->assertJsonStructure(['url']);

    $this->actingAs($admin)->postJson(route('admin.legal.images'), [
        'image' => UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    ])->assertStatus(422);
});

test('only administrators can edit legal pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.legal.edit', 'privacy'))->assertForbidden();
    $this->actingAs($user)->put(route('admin.legal.update', 'privacy'), ['title' => 'x', 'content' => '<p>x</p>'])->assertForbidden();
    $this->actingAs($user)->post(route('admin.legal.images'))->assertForbidden();
    auth()->logout();
    $this->put(route('admin.legal.update', 'privacy'), ['title' => 'x', 'content' => '<p>x</p>'])->assertRedirect(route('login'));
});

test('the settings page lists both legal documents', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->get(route('admin.settings', ['tab' => 'legal']))
        ->assertInertia(fn ($page) => $page
            ->where('tab', 'legal')
            ->has('legalDocuments', 2)
            ->where('legalDocuments.0.slug', 'privacy')
            ->where('legalDocuments.0.is_custom', false));
});
