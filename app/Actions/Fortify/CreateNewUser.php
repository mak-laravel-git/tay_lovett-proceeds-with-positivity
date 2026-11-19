<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\AppSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user. Registration always creates a regular user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        if (! app(AppSettings::class)->get('authentication.registration_open')) {
            throw ValidationException::withMessages([
                'email' => 'New registrations are closed at the moment.',
            ]);
        }

        $input['name'] = trim((string) ($input['name'] ?? ''));
        $input['email'] = Str::lower(trim((string) ($input['email'] ?? '')));

        Validator::make($input, [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ], [
            'name.required' => 'Please enter your full name.',
        ])->validate();

        // role is not mass assignable on purpose: nothing a visitor sends can make an administrator.
        $user = new User;
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => UserRole::User,
        ])->save();

        return $user;
    }
}
