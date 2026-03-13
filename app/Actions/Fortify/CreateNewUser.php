<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Services\WhacenterService;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'whatsapp' => ['required', 'string', 'max:20'],
            'password' => $this->passwordRules(),
        ])->after(function ($validator) use ($input) {
            $normalized = WhacenterService::normalizeWhatsApp($input['whatsapp'] ?? '');
            if (User::where('whatsapp', $normalized)->exists()) {
                $validator->errors()->add('whatsapp', __('This WhatsApp number is already registered.'));
            }
        })->validate();

        $whatsapp = WhacenterService::normalizeWhatsApp($input['whatsapp']);

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'whatsapp' => $whatsapp,
            'password' => $input['password'],
        ]);

        $user->assignRole('member');

        return $user;
    }
}
