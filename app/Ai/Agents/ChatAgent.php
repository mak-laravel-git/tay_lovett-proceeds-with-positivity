<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class ChatAgent implements Agent, Conversational
{
    use Promptable;

    /**
     * @param  list<array{role: string, content: string}>  $history
     */
    public function __construct(public array $history = []) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a friendly assistant inside the '.config('app.name').' admin panel. '
            .'Administrators use this chat to check that an AI provider works. Answer briefly and kindly.';
    }

    /**
     * Previous turns of the test conversation.
     *
     * @return list<Message>
     */
    public function messages(): iterable
    {
        return array_map(
            fn (array $turn): Message => new Message($turn['role'], $turn['content']),
            $this->history,
        );
    }
}
