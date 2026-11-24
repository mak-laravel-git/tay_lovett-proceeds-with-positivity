<?php

namespace App\Services\Ads\Contracts;

use App\Services\Ads\Data\ProcessedCallback;
use Symfony\Component\HttpFoundation\Response;

/**
 * Providers that require a non-JSON acknowledgement body (e.g. Wannads expects exact "OK").
 */
interface AcknowledgesCallback
{
    public function acknowledge(ProcessedCallback $processed): Response;
}
