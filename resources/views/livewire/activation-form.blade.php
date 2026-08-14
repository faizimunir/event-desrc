<div class="flex flex-col gap-6">
    <x-auth-header
        :title="__('Activate account')"
        :description="__('Already registered a rider? Activate your account to log in with email and password.')"
    />

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($step === 1)
        <form wire:submit="submitWhatsapp" class="flex flex-col gap-6">
            <flux:input
                wire:model="whatsapp"
                type="tel"
                :label="__('WhatsApp number')"
                :placeholder="__('e.g. 08123456789 or 628123456789')"
                required
            />
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Continue') }}</flux:button>
        </form>
    @elseif ($step === 2)
        <form wire:submit="submitCredentials" class="flex flex-col gap-6">
            <flux:input wire:model="email" type="email" :label="__('Email address')" required />
            <flux:input wire:model="password" type="password" :label="__('Password')" viewable required />
            <flux:input wire:model="password_confirmation" type="password" :label="__('Confirm password')" viewable required />
            <div class="flex gap-2">
                <flux:button variant="primary" type="submit" class="flex-1">{{ __('Set password & send OTP') }}</flux:button>
                <flux:button variant="ghost" type="button" wire:click="backToStep1">{{ __('Back') }}</flux:button>
            </div>
        </form>
    @else
        <form wire:submit="submitOtp" class="flex flex-col gap-6">
            <flux:input
                wire:model="otp"
                type="text"
                :label="__('Verification code')"
                :placeholder="__('6-digit code from WhatsApp')"
                maxlength="6"
                required
            />
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Verify & activate') }}</flux:button>
        </form>
    @endif

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
