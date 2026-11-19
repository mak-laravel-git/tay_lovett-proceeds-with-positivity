<?php

namespace App\Http\Controllers\Admin\Ads;

use App\Http\Controllers\Controller;
use App\Services\Ads\AdsReport;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __invoke(AdsReport $report): Response
    {
        return Inertia::render('admin/ads/index', $report->overview());
    }
}
