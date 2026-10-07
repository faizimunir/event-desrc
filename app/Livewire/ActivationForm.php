<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\WhacenterService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class ActivationForm extends Component
{
    public int $step = 1;

    public string $whatsapp = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $otp = '';

    public ?int $activationUserId = null;

    public function mount(): void
    {
        $this->activationUserId = session('activation_user_id');
        if ($this->activationUserId) {
            $user = User::find($this->activationUserId);
            if ($user && $user->isActivated()) {
                session()->forget('activation_user_id');
                $this->activationUserId = null;
            } elseif ($user) {
                $this->whatsapp = $user->whatsapp ?? '';
                $this->step = 2;
            }
        }
    }

    public function submitWhatsapp(): void
    {
        $this->validate([
            'whatsapp' => ['required', 'string', 'max:20'],
        ]);

        $normalized = WhacenterService::normalizeWhatsApp($this->whatsapp);
        $user = User::where('whatsapp', $normalized)->first();

        if (! $user) {
            $this->addError('whatsapp', __('No account found for this WhatsApp number. Please register at an event first.'));

            return;
        }

        if ($user->isActivated()) {
            $this->addError('whatsapp', __('This account is already activated. You can log in with your email and password.'));

            return;
        }

        session()->put('activation_user_id', $user->id);
        $this->activationUserId = $user->id;
        $this->step = 2;
    }

    public function submitCredentials(WhacenterService $whacenter): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->activationUserId],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->numbers()],
        ]);

        $user = User::find($this->activationUserId);
        if (! $user || $user->isActivated()) {
            session()->forget('activation_user_id');
            $this->redirect(route('activation.show'), navigate: true);

            return;
        }

        $user->update([
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        if ($this->sendOtp($whacenter, $user)) {
            $this->step = 3;
        }
    }

    public function resendOtp(WhacenterService $whacenter): void
    {
        $user = User::find($this->activationUserId);
        if (! $user || $user->isActivated()) {
            session()->forget('activation_user_id');
            $this->redirect(route('activation.show'), navigate: true);

            return;
        }

        if ($this->sendOtp($whacenter, $user)) {
            $this->reset('otp');
            session()->flash('otp_resent', __('A new code has been sent to your WhatsApp.'));
        }
    }

    private function sendOtp(WhacenterService $whacenter, User $user): bool
    {
        $result = $whacenter->requestOtp($user->whatsapp, request()->ip());

        if ($result['sent']) {
            return true;
        }

        $this->addError('otp', match ($result['reason']) {
            'throttled' => __('Please wait :seconds seconds before requesting a new code.', [
                'seconds' => max(1, $result['retry_after']),
            ]),
            default => __('WhatsApp service is temporarily unavailable. Please try again in a few minutes.'),
        });

        return false;
    }

    public function submitOtp(WhacenterService $whacenter): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = User::find($this->activationUserId);
        if (! $user) {
            session()->forget('activation_user_id');
            $this->redirect(route('activation.show'), navigate: true);

            return;
        }

        if (! $whacenter->verifyOtp($user->whatsapp, $this->otp)) {
            $this->addError('otp', __('Invalid or expired code. Please try again or request a new code.'));

            return;
        }

        $user->update([
            'email_verified_at' => now(),
            'activated_at' => now(),
        ]);

        session()->forget('activation_user_id');
        session()->flash('status', __('Your account is now activated. You can log in with your email and password.'));

        $this->redirect(route('login'), navigate: true);
    }

    public function backToStep1(): void
    {
        session()->forget('activation_user_id');
        $this->activationUserId = null;
        $this->step = 1;
        $this->reset(['whatsapp', 'email', 'password', 'password_confirmation', 'otp']);
    }

    public function render()
    {
        return view('livewire.activation-form');
    }
}
