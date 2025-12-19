<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Kelola Bukti Transfer</h1>

    @if(session()->has('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter Event</label>
                <select wire:model.live="eventFilter" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}">{{ $event->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter Status</label>
                <select wire:model.live="statusFilter" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="verified">Verified</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peserta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bukti</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($payments as $payment)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $payment->participant->name }}</div>
                                <div class="text-xs text-gray-500">{{ $payment->participant->email }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $payment->participant->package->category->event->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($payment->payment_proof)
                                    <button 
                                        wire:click="viewProof({{ $payment->id }})"
                                        class="text-blue-600 hover:text-blue-900 text-sm"
                                    >
                                        Lihat Bukti
                                    </button>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $payment->status === 'verified' ? 'bg-green-100 text-green-800' : 
                                       ($payment->status === 'paid' ? 'bg-blue-100 text-blue-800' : 
                                       ($payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800')) }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">{{ $payment->created_at->format('d M Y H:i') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                @if($payment->status === 'paid' || $payment->status === 'pending')
                                    <button wire:click="verify({{ $payment->id }})" class="text-green-600 hover:text-green-900">Setuju</button>
                                    <button wire:click="reject({{ $payment->id }})" wire:confirm="Apakah Anda yakin menolak pembayaran ini?" class="text-red-600 hover:text-red-900">Tolak</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data pembayaran</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal && $selectedPayment)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">Bukti Pembayaran</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Peserta:</p>
                        <p class="text-gray-900">{{ $selectedPayment->participant->name }}</p>
                        <p class="text-sm text-gray-500">{{ $selectedPayment->participant->email }}</p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-700">Jumlah:</p>
                        <p class="text-gray-900">Rp {{ number_format($selectedPayment->amount, 0, ',', '.') }}</p>
                    </div>

                    @if($selectedPayment->payment_proof)
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-2">Bukti Transfer:</p>
                            <img src="{{ asset('storage/' . $selectedPayment->payment_proof) }}" alt="Bukti Pembayaran" class="max-w-full h-auto rounded-md border border-gray-200">
                        </div>
                    @endif

                    <div class="flex justify-end space-x-3 pt-4">
                        @if($selectedPayment->status === 'paid' || $selectedPayment->status === 'pending')
                            <button wire:click="reject({{ $selectedPayment->id }})" wire:confirm="Apakah Anda yakin menolak pembayaran ini?" class="px-4 py-2 border border-red-300 text-red-700 rounded-md hover:bg-red-50">
                                Tolak
                            </button>
                            <button wire:click="verify({{ $selectedPayment->id }})" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                Setuju & Verifikasi
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

