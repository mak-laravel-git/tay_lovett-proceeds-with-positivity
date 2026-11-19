<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed one administrator and six verified users.
     *
     * Every account uses the password "password".
     */
    public function run(): void
    {
        $this->seedAccount(name: 'Administrator', email: 'admin@dev.com', role: UserRole::Administrator);
        $this->seedAccount(name: 'John Doe', email: 'user@dev.com', role: UserRole::User);

        $people = [
            'Avery Chen',
            'Jordan Blake',
            'Riley Patel',
            'Sam Okonkwo',
            'Morgan Ellis',
            'Casey Nguyen',
        ];

        foreach ($people as $index => $name) {
            $this->seedAccount(name: $name, email: 'user' . ($index + 1) . '@dev.com', role: UserRole::User);
        }
    }

    /**
     * Create or refresh a testing account. Role is assigned explicitly because it is not mass assignable.
     */
    private function seedAccount(string $name, string $email, UserRole $role): void
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'password' => Hash::make($email),
            'email_verified_at' => now(),
            'role' => $role,
        ])->save();
    }
}
