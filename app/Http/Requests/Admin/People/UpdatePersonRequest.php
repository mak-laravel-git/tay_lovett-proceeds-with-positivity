<?php

namespace App\Http\Requests\Admin\People;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Password;

class UpdatePersonRequest extends FormRequest
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
        /** @var User $person */
        $person = $this->route('person');

        return [
            ...$this->profileRules($person->id),
            'password' => ['nullable', 'string', Password::defaults(), 'confirmed'],
            'email_verified' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array{name: string, email: string, password: string|null, email_verified: bool, avatar: UploadedFile|null, remove_avatar: bool}
     */
    public function personData(): array
    {
        /** @var array{name: string, email: string, password?: string|null, email_verified?: bool, remove_avatar?: bool} $validated */
        $validated = $this->safe()->except(['avatar', 'password_confirmation']);

        return [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'email_verified' => (bool) ($validated['email_verified'] ?? true),
            'avatar' => $this->file('avatar'),
            'remove_avatar' => (bool) ($validated['remove_avatar'] ?? false),
        ];
    }
}
