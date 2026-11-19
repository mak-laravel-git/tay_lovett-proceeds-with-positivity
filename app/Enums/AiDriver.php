<?php

namespace App\Enums;

enum AiDriver: string
{
    case OpenAi = 'openai';
    case Anthropic = 'anthropic';
    case Gemini = 'gemini';
    case DeepSeek = 'deepseek';
    case Groq = 'groq';
    case Mistral = 'mistral';
    case Xai = 'xai';
    case Cohere = 'cohere';
    case OpenRouter = 'openrouter';
    case Ollama = 'ollama';

    /**
     * Human readable provider name shown in the admin panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::OpenAi => 'OpenAI (GPT)',
            self::Anthropic => 'Anthropic (Claude)',
            self::Gemini => 'Google (Gemini)',
            self::DeepSeek => 'DeepSeek',
            self::Groq => 'Groq',
            self::Mistral => 'Mistral AI',
            self::Xai => 'xAI (Grok)',
            self::Cohere => 'Cohere',
            self::OpenRouter => 'OpenRouter',
            self::Ollama => 'Ollama (self-hosted)',
        };
    }

    /**
     * Example model identifiers offered as quick picks. Admins can still type any model id.
     *
     * @return list<string>
     */
    public function suggestedModels(): array
    {
        return match ($this) {
            self::OpenAi => ['gpt-4o-mini', 'gpt-4o', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano', 'gpt-5', 'gpt-5-mini', 'gpt-5-nano', 'o4-mini', 'o3'],
            self::Anthropic => ['claude-haiku-5', 'claude-sonnet-5', 'claude-opus-5', 'claude-haiku-5-5', 'claude-sonnet-5-5', 'claude-opus-5-5', 'claude-fable-5-1', 'claude-haiku-4-5', 'claude-sonnet-4-5', 'claude-opus-4-5'],
            self::Gemini => ['gemini-2.5-pro', 'gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-2.0-flash', 'gemini-2.0-flash-lite'],
            self::DeepSeek => ['deepseek-chat', 'deepseek-reasoner'],
            self::Groq => ['llama-3.3-70b-versatile', 'llama-3.1-8b-instant', 'openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'qwen/qwen3-32b'],
            self::Mistral => ['mistral-small-latest', 'mistral-medium-latest', 'mistral-large-latest', 'open-mistral-nemo'],
            self::Xai => ['grok-4', 'grok-3', 'grok-3-mini', 'grok-3-fast'],
            self::Cohere => ['command-r', 'command-r-plus', 'command-a-03-2025', 'command-r7b-12-2024'],
            self::OpenRouter => ['openai/gpt-4o-mini', 'anthropic/claude-3.5-haiku', 'google/gemini-2.0-flash-001', 'meta-llama/llama-3.3-70b-instruct', 'deepseek/deepseek-chat'],
            self::Ollama => ['llama3.2', 'llama3.1', 'mistral', 'qwen2.5', 'gemma2', 'phi3'],
        };
    }

    /**
     * Whether the provider needs a base URL because it is not hosted by a fixed vendor.
     */
    public function requiresUrl(): bool
    {
        return $this === self::Ollama;
    }

    /**
     * Option list for the admin forms.
     *
     * @return list<array{value: string, label: string, requires_url: bool, suggested_models: list<string>}>
     */
    public static function options(): array
    {
        return array_map(fn (self $driver): array => [
            'value' => $driver->value,
            'label' => $driver->label(),
            'requires_url' => $driver->requiresUrl(),
            'suggested_models' => $driver->suggestedModels(),
        ], self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $driver): string => $driver->value, self::cases());
    }

    public static function labelFor(string $driver): string
    {
        return self::tryFrom($driver)?->label() ?? $driver;
    }
}
