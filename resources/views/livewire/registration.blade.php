<div class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('event.detail', $event->id) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200" wire:navigate>
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Detail Event</span>
            </a>
        </div>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Registrasi Peserta</h1>
            <p class="text-gray-600">Isi form di bawah ini untuk melanjutkan pendaftaran</p>
        </div>

        <!-- Event Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Ringkasan Event</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Event</p>
                    <p class="font-medium text-gray-900">{{ $event->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Kategori</p>
                    <p class="font-medium text-gray-900">{{ $category->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Paket</p>
                    <p class="font-medium text-gray-900">{{ $package->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Harga</p>
                    <p class="font-medium text-blue-600 text-lg">Rp {{ number_format($package->price, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <!-- Registration Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Data Peserta</h2>

            <form wire:submit="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nama Lengkap -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name"
                            wire:model="name"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                            placeholder="Masukkan nama lengkap"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nama Panggilan -->
                    <div>
                        <label for="nickname" class="block text-sm font-medium text-gray-700 mb-2">
                            Nama Panggilan <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="nickname"
                            wire:model="nickname"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('nickname') border-red-500 @enderror"
                            placeholder="Masukkan nama panggilan"
                        >
                        @error('nickname')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Number Plate -->
                    <div>
                        <label for="number_plate" class="block text-sm font-medium text-gray-700 mb-2">
                            Number Plate <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="number_plate"
                            wire:model="number_plate"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('number_plate') border-red-500 @enderror"
                            placeholder="Masukkan nomor plat"
                        >
                        @error('number_plate')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Komunitas -->
                    <div>
                        <label for="komunitas" class="block text-sm font-medium text-gray-700 mb-2">
                            Komunitas <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="komunitas"
                            wire:model="komunitas"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('komunitas') border-red-500 @enderror"
                            placeholder="Masukkan nama komunitas"
                        >
                        @error('komunitas')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="email" 
                            id="email"
                            wire:model="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                            placeholder="email@example.com"
                        >
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            No. Telepon <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="phone"
                            wire:model="phone"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @enderror"
                            placeholder="08xxxxxxxxxx"
                        >
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- City -->
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                            Asal Kota <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="city"
                            wire:model="city"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('city') border-red-500 @enderror"
                            placeholder="Masukkan asal kota"
                        >
                        @error('city')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                            Tanggal Lahir <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="date_of_birth"
                            wire:model="date_of_birth"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('date_of_birth') border-red-500 @enderror"
                        >
                        @error('date_of_birth')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Dynamic Form Fields from FormBuilder -->
                @if(count($formFields) > 0)
                    <div class="mt-8 pt-8 border-t border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-6">Informasi Tambahan</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($formFields as $field)
                                <div class="{{ in_array($field->type, ['textarea']) ? 'md:col-span-2' : '' }}">
                                    <label for="form_field_{{ $field->name }}" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ $field->label }}
                                        @if($field->required)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </label>

                                    @if($field->type === 'text' || $field->type === 'email' || $field->type === 'tel' || $field->type === 'number')
                                        <input 
                                            type="{{ $field->type === 'tel' ? 'text' : $field->type }}"
                                            id="form_field_{{ $field->name }}"
                                            wire:model="formFieldsData.{{ $field->name }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('formFieldsData.' . $field->name) border-red-500 @enderror"
                                            placeholder="{{ $field->help_text ?: '' }}"
                                            @if($field->required) required @endif
                                        >
                                    @elseif($field->type === 'textarea')
                                        <textarea 
                                            id="form_field_{{ $field->name }}"
                                            wire:model="formFieldsData.{{ $field->name }}"
                                            rows="3"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('formFieldsData.' . $field->name) border-red-500 @enderror"
                                            placeholder="{{ $field->help_text ?: '' }}"
                                            @if($field->required) required @endif
                                        ></textarea>
                                    @elseif($field->type === 'date')
                                        <input 
                                            type="date"
                                            id="form_field_{{ $field->name }}"
                                            wire:model="formFieldsData.{{ $field->name }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('formFieldsData.' . $field->name) border-red-500 @enderror"
                                            @if($field->required) required @endif
                                        >
                                    @elseif($field->type === 'select')
                                        <select 
                                            id="form_field_{{ $field->name }}"
                                            wire:model="formFieldsData.{{ $field->name }}"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 @error('formFieldsData.' . $field->name) border-red-500 @enderror"
                                            @if($field->required) required @endif
                                        >
                                            <option value="">Pilih {{ $field->label }}</option>
                                            @if($field->options && is_array($field->options))
                                                @foreach($field->options as $option)
                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    @elseif($field->type === 'radio')
                                        <div class="space-y-2">
                                            @if($field->options && is_array($field->options))
                                                @foreach($field->options as $option)
                                                    <label class="flex items-center">
                                                        <input 
                                                            type="radio"
                                                            name="formFieldsData[{{ $field->name }}]"
                                                            wire:model="formFieldsData.{{ $field->name }}"
                                                            value="{{ $option }}"
                                                            class="mr-2"
                                                            @if($field->required) required @endif
                                                        >
                                                        <span>{{ $option }}</span>
                                                    </label>
                                                @endforeach
                                            @endif
                                        </div>
                                    @elseif($field->type === 'checkbox')
                                        <label class="flex items-center">
                                            <input 
                                                type="checkbox"
                                                id="form_field_{{ $field->name }}"
                                                wire:model="formFieldsData.{{ $field->name }}"
                                                value="1"
                                                class="rounded border-gray-300"
                                                @if($field->required) required @endif
                                            >
                                            <span class="ml-2 text-sm text-gray-700">{{ $field->help_text ?: 'Ya' }}</span>
                                        </label>
                                    @endif

                                    @if($field->help_text && $field->type !== 'checkbox')
                                        <p class="mt-1 text-xs text-gray-500">{{ $field->help_text }}</p>
                                    @endif

                                    @error('formFieldsData.' . $field->name)
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Error Messages -->
                @error('package')
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    </div>
                @enderror

                @error('registration')
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    </div>
                @enderror

                @error('category')
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    </div>
                @enderror

                @error('general')
                    <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    </div>
                @enderror

                <!-- Submit Button -->
                <div class="mt-8 flex justify-end space-x-4">
                    <a 
                        href="{{ route('event.detail', $event->id) }}" 
                        class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                        wire:navigate
                    >
                        Batal
                    </a>
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                    >
                        <span wire:loading.remove wire:target="submit">Daftar Sekarang</span>
                        <span wire:loading wire:target="submit" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
