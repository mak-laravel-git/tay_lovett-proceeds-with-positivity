<?php

namespace App\Enums;

/**
 * What happens to a quote when the last workflow step is done.
 */
enum PublishMode: string
{
    case Auto = 'auto';
    case Manual = 'manual';
}
