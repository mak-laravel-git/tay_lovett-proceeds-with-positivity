<?php

namespace App\Http\Controllers;

use App\Models\GoogleAdSetting;
use Illuminate\Http\Response;

/**
 * Serves /ads.txt from the admin panel, so authorised sellers can be changed without a deployment.
 */
class AdsTxtController extends Controller
{
    public function __invoke(): Response
    {
        $content = trim((string) GoogleAdSetting::query()->value('ads_txt'));

        abort_if($content === '', 404);

        return response($content."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
