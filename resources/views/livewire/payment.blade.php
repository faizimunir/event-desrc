<div class="min-h-screen py-8" 
     @if(!$payment_confirmed)
         @php
             $event = $participant->package->event ?? null;
             $paymentMethod = $event->payment_method ?? 'manual';
         @endphp
         @if($paymentMethod === 'moota')
             wire:poll.10s="refreshPayment"
         @endif
     @endif>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors duration-200" wire:navigate>
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Home</span>
            </a>
        </div>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Pembayaran</h1>
            <p class="text-gray-600">Lakukan pembayaran sesuai nominal yang tertera di bawah</p>
        </div>

        @if(session()->has('success'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                <p class="text-sm text-green-600">{{ session('success') }}</p>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                <p class="text-sm text-red-600">{{ session('error') }}</p>
            </div>
        @endif

        <!-- Event Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Ringkasan Event</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Event</p>
                    <p class="font-medium text-gray-900">{{ $participant->package->event->name ?? ($participant->category->event->name ?? 'N/A') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Kategori</p>
                    <p class="font-medium text-gray-900">{{ $participant->category->name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">Paket</p>
                    <p class="font-medium text-gray-900">{{ $participant->package->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">No. Registrasi</p>
                    <p class="font-medium text-gray-900">{{ $participant->registration_number }}</p>
                </div>
            </div>
        </div>

        <!-- Participant Information Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Ringkasan Informasi Peserta</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Nama Lengkap</p>
                    <p class="font-medium text-gray-900">{{ $participant->name }}</p>
                </div>
                @if($participant->nickname)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Nama Panggilan</p>
                    <p class="font-medium text-gray-900">{{ $participant->nickname }}</p>
                </div>
                @endif
                @if($participant->number_plate)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Number Plate</p>
                    <p class="font-medium text-gray-900">{{ $participant->number_plate }}</p>
                </div>
                @endif
                @if($participant->komunitas)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Komunitas</p>
                    <p class="font-medium text-gray-900">{{ $participant->komunitas }}</p>
                </div>
                @endif
                <div>
                    <p class="text-sm text-gray-600 mb-1">Email</p>
                    <p class="font-medium text-gray-900">{{ $participant->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-1">No. Telepon</p>
                    <p class="font-medium text-gray-900">{{ $participant->phone }}</p>
                </div>
                @if($participant->city)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Asal Kota</p>
                    <p class="font-medium text-gray-900">{{ $participant->city }}</p>
                </div>
                @endif
                @if($participant->date_of_birth)
                <div>
                    <p class="text-sm text-gray-600 mb-1">Tanggal Lahir</p>
                    <p class="font-medium text-gray-900">{{ $participant->date_of_birth->format('d/m/Y') }}</p>
                </div>
                @endif
            </div>

            <!-- Dynamic Form Fields -->
            @if($participant->form_data && count($participant->form_data) > 0 && $formFields->count() > 0)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Tambahan</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($formFields as $field)
                            @if(isset($participant->form_data[$field->name]) && !empty($participant->form_data[$field->name]))
                                <div class="{{ in_array($field->type, ['textarea']) ? 'md:col-span-2' : '' }}">
                                    <p class="text-sm text-gray-600 mb-1">{{ $field->label }}</p>
                                    <p class="font-medium text-gray-900">
                                        @if($field->type === 'checkbox')
                                            {{ $participant->form_data[$field->name] ? 'Ya' : 'Tidak' }}
                                        @else
                                            {{ $participant->form_data[$field->name] }}
                                        @endif
                                    </p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Payment Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informasi Pembayaran</h2>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-gray-600">Harga Paket</span>
                    <span class="font-medium text-gray-900">Rp {{ number_format($participant->package->price, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <span class="text-gray-600">Kode Unik</span>
                    <span class="font-medium text-blue-600">{{ $participant->unique_code }}</span>
                </div>
                <div class="flex justify-between items-center py-4 bg-blue-50 rounded-md px-4">
                    <span class="text-lg font-semibold text-gray-900">Total Transfer</span>
                    <span class="text-2xl font-bold text-blue-600">
                        Rp {{ number_format($participant->package->price + (int)$participant->unique_code, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Bank Information -->
            @php
                $event = $participant->package->event ?? null;
                $paymentMethod = $event->payment_method ?? 'manual';
                
                // Get payment setting for this event
                $paymentSetting = \App\Models\PaymentSetting::where(function($query) use ($event) {
                    if ($event) {
                        $query->where('event_id', $event->id)
                              ->orWhereNull('event_id');
                    } else {
                        $query->whereNull('event_id');
                    }
                })
                ->where('status', 'active')
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
            @endphp

            <div class="mt-6 p-4 bg-gray-50 rounded-md">
                <h3 class="font-semibold text-gray-900 mb-3">Informasi Rekening</h3>
                @if($paymentSetting)
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Bank:</span>
                            <span class="font-medium text-gray-900">{{ $paymentSetting->bank_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">No. Rekening:</span>
                            <span class="font-medium text-gray-900">{{ $paymentSetting->account_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Atas Nama:</span>
                            <span class="font-medium text-gray-900">{{ $paymentSetting->account_name }}</span>
                        </div>
                    </div>
                @else
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Bank:</span>
                            <span class="font-medium text-gray-900">{{ config('app.bank_name') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">No. Rekening:</span>
                            <span class="font-medium text-gray-900">{{ config('app.bank_account') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Atas Nama:</span>
                            <span class="font-medium text-gray-900">{{ config('app.bank_account_name') }}</span>
                        </div>
                    </div>
                @endif

                @if($paymentMethod === 'moota')
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-800 font-medium mb-2">
                            <svg class="inline-block w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                            Pembayaran Otomatis oleh Sistem Kami
                        </p>
                        <p class="text-xs text-blue-700">
                            Setelah melakukan transfer, pembayaran Anda akan diverifikasi otomatis oleh sistem. 
                            Pastikan nominal transfer sesuai dengan total yang tertera di atas. 
                            Anda akan menerima notifikasi konfirmasi melalui email dan WhatsApp setelah pembayaran terverifikasi.
                        </p>
                        <p class="text-xs text-blue-700 mt-2">
                            <strong>Catatan: <i>Mohon transfer sesuai dengan nominal yang tertera (termasuk kode unik).</i> 
                            Sistem akan mendeteksi pembayaran Anda secara otomatis.
                            </strong>
                        </p>
                    </div>
                @else
                    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                        <p class="text-xs text-yellow-800">
                            <strong>Penting:</strong> Setelah melakukan transfer, silakan upload bukti pembayaran di bawah ini. 
                            Admin akan memverifikasi pembayaran Anda.
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Success Message -->
        @if($payment_confirmed)
            @php
                $event = $participant->package->event ?? null;
                $paymentMethod = $event->payment_method ?? 'manual';
            @endphp
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="mb-6">
                    <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Pendaftaran Telah Diterima</h2>
                @if($paymentMethod === 'moota')
                    <p class="text-gray-600 mb-6">
                        Terima kasih! Pembayaran Anda telah terverifikasi otomatis oleh sistem. Pendaftaran Anda telah dikonfirmasi.
                    </p>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-green-800">
                            <strong>No. Registrasi:</strong> {{ $participant->registration_number }}
                        </p>
                        <p class="text-sm text-green-800 mt-2">
                            Notifikasi konfirmasi telah dikirim melalui email dan WhatsApp. Silakan cek inbox Anda.
                        </p>
                    </div>
                @else
                    <p class="text-gray-600 mb-6">
                        Terima kasih telah melakukan konfirmasi pembayaran. Pendaftaran Anda telah diterima dan sedang menunggu verifikasi dari admin.
                    </p>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-blue-800">
                            <strong>No. Registrasi:</strong> {{ $participant->registration_number }}
                        </p>
                        <p class="text-sm text-blue-800 mt-2">
                            Kami akan mengirimkan notifikasi melalui email dan WhatsApp setelah admin memverifikasi pembayaran Anda.
                        </p>
                    </div>
                @endif
                <a 
                    href="{{ route('home') }}" 
                    class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors duration-200"
                    wire:navigate
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Kembali ke Home
                </a>
            </div>
        @else
        @php
            $event = $participant->package->event ?? null;
            $paymentMethod = $event->payment_method ?? 'manual';
        @endphp
        
        @if($paymentMethod === 'moota')
        <!-- Moota Payment Instructions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Instruksi Pembayaran</h2>
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-gray-700 mb-3">
                        <strong>Langkah-langkah pembayaran:</strong>
                    </p>
                    <ol class="list-decimal list-inside space-y-2 text-sm text-gray-700">
                        <li>Lakukan transfer sesuai dengan nominal yang tertera di atas</li>
                        <li>Pastikan nominal transfer <strong>sesuai persis</strong> (termasuk kode unik)</li>
                        <li>Setelah transfer, sistem akan mendeteksi pembayaran Anda secara otomatis</li>
                        <li>Anda akan menerima notifikasi konfirmasi melalui Email dan WhatsApp</li>
                    </ol>
                </div>
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                    <p class="text-sm text-yellow-800">
                        <strong>Catatan Penting:</strong> 
                        Tidak perlu upload bukti pembayaran. Sistem akan memverifikasi pembayaran Anda secara otomatis.
                    </p>
                </div>
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-md">
                    <p class="text-sm text-gray-700">
                        <strong>Status Pembayaran:</strong> 
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">
                            Menunggu Verifikasi Otomatis
                        </span>
                        <span class="ml-2 inline-flex items-center text-xs text-gray-500">
                            <svg class="animate-spin h-3 w-3 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Memeriksa pembayaran...
                        </span>
                    </p>
                    <p class="text-xs text-gray-600 mt-2">
                        Halaman ini akan otomatis terupdate setiap 10 detik setelah pembayaran terverifikasi.
                    </p>
                </div>
            </div>
        </div>
        @else
        <!-- Upload Payment Proof -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Upload Bukti Pembayaran</h2>
            
            <div class="space-y-6" wire:ignore.self>
                @if($payment_proof_url)
                    <!-- Show uploaded proof -->
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-medium text-gray-700">Bukti Pembayaran Terupload</span>
                            <div class="flex space-x-2">
                                <label for="replace_payment_proof" class="px-4 py-2 text-sm text-blue-600 hover:text-blue-800 border border-blue-300 rounded-md hover:bg-blue-50 transition-colors duration-200 cursor-pointer">
                                    Ganti
                                </label>
                                <input 
                                    id="replace_payment_proof" 
                                    type="file" 
                                    class="hidden" 
                                    wire:model="payment_proof"
                                    accept="image/*"
                                >
                                <button 
                                    wire:click="removePaymentProof"
                                    class="px-4 py-2 text-sm text-red-600 hover:text-red-800 border border-red-300 rounded-md hover:bg-red-50 transition-colors duration-200"
                                    wire:loading.attr="disabled"
                                >
                                    <span wire:loading.remove wire:target="removePaymentProof">Hapus</span>
                                    <span wire:loading wire:target="removePaymentProof">Menghapus...</span>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <img 
                                src="{{ $payment_proof_url }}?t={{ time() }}" 
                                alt="Bukti Pembayaran" 
                                class="max-w-full h-auto rounded-md border border-gray-200"
                                onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23f3f4f6\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%236b7280\' font-family=\'sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EGambar tidak dapat dimuat%3C/text%3E%3C/svg%3E';"
                            >
                        </div>
                        
                        @if($payment_proof)
                            <div class="mt-4">
                                <p class="text-sm text-gray-600 mb-2">File baru: {{ $payment_proof->getClientOriginalName() }}</p>
                                <button 
                                    wire:click="uploadPaymentProof"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200"
                                    wire:loading.attr="disabled"
                                    wire:target="uploadPaymentProof"
                                >
                                    <span wire:loading.remove wire:target="uploadPaymentProof">Upload Bukti Baru</span>
                                    <span wire:loading wire:target="uploadPaymentProof" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Mengupload...
                                    </span>
                                </button>
                            </div>
                        @else
                            <!-- Confirm Button - Only show when payment proof is uploaded and no new file is selected -->
                            <div class="mt-6">
                                <button 
                                    wire:click="confirmPayment"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-md transition-colors duration-200 shadow-md"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmPayment"
                                >
                                    <span wire:loading.remove wire:target="confirmPayment" class="flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Konfirmasi Pembayaran
                                    </span>
                                    <span wire:loading wire:target="confirmPayment" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        @endif
                    </div>
                @else
                    <!-- Upload Form -->
<div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload Bukti Pembayaran (Max 10MB) <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-400 transition-colors duration-200">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="payment_proof" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload file</span>
                                        <input 
                                            id="payment_proof" 
                                            name="payment_proof" 
                                            type="file" 
                                            class="sr-only" 
                                            wire:model="payment_proof"
                                            accept="image/*"
                                        >
                                    </label>
                                    <p class="pl-1">atau drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF hingga 10MB</p>
                            </div>
                        </div>
                        @error('payment_proof')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @if($payment_proof)
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                <p class="text-sm text-gray-700 mb-2">
                                    <span class="font-medium">File terpilih:</span> {{ $payment_proof->getClientOriginalName() }}
                                </p>
                                <p class="text-xs text-gray-500 mb-3">
                                    Ukuran: {{ number_format($payment_proof->getSize() / 1024, 2) }} KB
                                </p>
                                <button 
                                    wire:click="uploadPaymentProof"
                                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200 shadow-sm"
                                    wire:loading.attr="disabled"
                                    wire:target="uploadPaymentProof"
                                >
                                    <span wire:loading.remove wire:target="uploadPaymentProof" class="flex items-center justify-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        Upload Bukti Pembayaran
                                    </span>
                                    <span wire:loading wire:target="uploadPaymentProof" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Mengupload...
                                    </span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        @endif
        @endif
    </div>
</div>
