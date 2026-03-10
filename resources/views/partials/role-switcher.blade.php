@auth
    @if(auth()->user()->hasMultipleRoles())
        <flux:menu.separator />
        <flux:menu.radio.group>
            @foreach(auth()->user()->roles()->orderBy('name')->get() as $role)
                @php
                    $isActive = auth()->user()->activeRole()?->name === $role->name;
                    $label = match($role->name) {
                        'super_admin' => __('Super Admin'),
                        'admin' => __('Admin'),
                        'coach' => __('Coach'),
                        'member' => __('Member'),
                        default => Str::title(str_replace('_', ' ', $role->name)),
                    };
                @endphp
                <form method="POST" action="{{ route('switch-role') }}" class="w-full">
                    @csrf
                    <input type="hidden" name="role" value="{{ $role->name }}">
                    <flux:menu.item
                        as="button"
                        type="submit"
                        :icon="$isActive ? 'check-circle' : 'user-circle'"
                        class="w-full cursor-pointer"
                        data-test="switch-role-{{ $role->name }}"
                    >
                        {{ $label }}
                    </flux:menu.item>
                </form>
            @endforeach
        </flux:menu.radio.group>
    @endif
@endauth
