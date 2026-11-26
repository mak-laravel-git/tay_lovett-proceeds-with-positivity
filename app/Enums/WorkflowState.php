<?php

namespace App\Enums;

/**
 * Where one quote workflow run currently stands. Only the server changes this.
 */
enum WorkflowState: string
{
    case AdBeforeSurvey = 'ad_before_survey';
    case Survey = 'survey';
    case Generate = 'generate';
    case Generating = 'generating';
    case AdBeforePublish = 'ad_before_publish';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::AdBeforeSurvey => 'Ad before survey',
            self::Survey => 'Survey',
            self::Generate => 'Ready to generate',
            self::Generating => 'Generating',
            self::AdBeforePublish => 'Ad before publishing',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Expired => 'Expired',
        };
    }

    public function isActive(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }

    /**
     * A run may only be cancelled or expire while no quote exists yet: a generated quote is never thrown away.
     */
    public function isBeforeGeneration(): bool
    {
        return in_array($this->value, self::beforeGenerationValues(), true);
    }

    /**
     * @return list<string>
     */
    public static function beforeGenerationValues(): array
    {
        return [self::AdBeforeSurvey->value, self::Survey->value, self::Generate->value];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $state): array => ['value' => $state->value, 'label' => $state->label()], self::cases());
    }
}
