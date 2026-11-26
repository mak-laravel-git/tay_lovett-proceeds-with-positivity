<?php

namespace App\Enums;

/**
 * What the workflow does when a step cannot be completed: move on anyway, or keep the user on the step.
 */
enum FallbackAction: string
{
    case Continue = 'continue';
    case Block = 'block';
}
