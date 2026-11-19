<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class QuoteAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(public string $category = 'Hope') {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'Write one original, kind, and specific quote about '.$this->category.'. Do not include advice about money, advertising, or providers.';
    }

    /**
     * Get the agent's structured output schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required(),
            'category' => $schema->string()->required(),
        ];
    }
}
