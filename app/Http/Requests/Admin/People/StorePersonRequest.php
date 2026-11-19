<?php

namespace App\Http\Requests\Admin\People;

use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Password;

class StorePersonRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() === true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'email_verified' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array{name: string, email: string, password: string, email_verified: bool, avatar: UploadedFile|null}
     */
    public function personData(): array
    {
        /** @var array{name: string, email: string, password: string, email_verified?: bool} $validated */
        $validated = $this->safe()->except(['avatar', 'password_confirmation']);

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified' => (bool) ($validated['email_verified'] ?? true),
            'avatar' => $this->file('avatar'),
        ];
    }
}
