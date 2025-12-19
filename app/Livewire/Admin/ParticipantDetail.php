<?php

namespace App\Livewire\Admin;

use App\Models\Participant;
use App\Models\Package;
use App\Models\Event;
use App\Models\Category;
use App\Jobs\SendConfirmNotificationJob;
use App\Jobs\SendCancelNotificationJob;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ParticipantDetail extends Component
{
    public $participantId;
    public $participant;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $isDeleting = false;

    // Form fields
    public $name;
    public $nickname;
    public $number_plate;
    public $komunitas;
    public $email;
    public $phone;
    public $address;
    public $date_of_birth;
    public $gender;
    public $emergency_contact_name;
    public $emergency_contact_phone;
    public $status;
    public $package_id;
    public $category_id;
    public $packages = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'nickname' => 'nullable|string|max:255',
        'number_plate' => 'nullable|string|max:255',
        'komunitas' => 'nullable|string|max:255',
        'email' => 'required|email|max:255',
        'phone' => 'required|string|max:20',
        'address' => 'nullable|string',
        'date_of_birth' => 'nullable|date',
        'gender' => 'nullable|in:male,female',
        'emergency_contact_name' => 'nullable|string|max:255',
        'emergency_contact_phone' => 'nullable|string|max:20',
        'status' => 'required|in:pending,registered,confirmed,cancelled',
        'package_id' => 'required|exists:packages,id',
        'category_id' => 'nullable|exists:categories,id',
    ];

    public function mount($id)
    {
        try {
            $this->participantId = $id;
            $this->loadParticipant();
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal memuat data peserta: ' . $e->getMessage());
            \Log::error('Error loading participant: ' . $e->getMessage());
        }
    }

    public function loadParticipant()
    {
        try {
            $admin = Auth::guard('admin')->user();
            
            if (!$admin) {
                throw new \Exception('Admin tidak terautentikasi.');
            }
            
            $query = Participant::select(
                'participants.id',
                'participants.package_id',
                'participants.category_id',
                'participants.registration_number',
                'participants.unique_code',
                'participants.name',
                'participants.nickname',
                'participants.number_plate',
                'participants.komunitas',
                'participants.email',
                'participants.phone',
                'participants.address',
                'participants.date_of_birth',
                'participants.gender',
                'participants.emergency_contact_name',
                'participants.emergency_contact_phone',
                'participants.status',
                'participants.created_at',
                'participants.updated_at'
            )
        ->with(['package.event', 'category.event', 'payment']);

        // Check admin access
        if (!$admin->isSuperAdmin()) {
            $query->where(function($q) use ($admin) {
                $q->whereHas('category.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                })
                ->orWhereHas('package.event', function ($query) use ($admin) {
                    $query->where('created_by', $admin->id)
                          ->orWhere('id', $admin->event_id);
                });
            });
        }

            $this->participant = $query->findOrFail($this->participantId);
            
            if (!$this->participant) {
                throw new \Exception('Participant tidak ditemukan.');
            }

            // Load form data
            $this->name = $this->participant->name ?? '';
            $this->nickname = $this->participant->nickname ?? '';
            $this->number_plate = $this->participant->number_plate ?? '';
            $this->komunitas = $this->participant->komunitas ?? '';
            $this->email = $this->participant->email ?? '';
            $this->phone = $this->participant->phone ?? '';
            $this->address = $this->participant->address ?? '';
            $this->date_of_birth = $this->participant->date_of_birth ? $this->participant->date_of_birth->format('Y-m-d') : null;
            $this->gender = $this->participant->gender ?? '';
            $this->emergency_contact_name = $this->participant->emergency_contact_name ?? '';
            $this->emergency_contact_phone = $this->participant->emergency_contact_phone ?? '';
            $this->status = $this->participant->status ?? 'pending';
            $this->package_id = $this->participant->package_id ?? null;
            $this->category_id = $this->participant->category_id ?? null;
            
            // Load packages for the event (all packages in the event)
            if ($this->participant->category) {
                $event = $this->participant->category->event;
            } elseif ($this->participant->package) {
                $event = $this->participant->package->event;
            } else {
                $event = null;
            }
            
            // Load all packages for this event
            if ($event) {
                $this->packages = Package::select('id', 'event_id', 'name', 'price')
                    ->where('event_id', $event->id)
                    ->get();
            }
        } catch (\Exception $e) {
            \Log::error('Error in loadParticipant: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            session()->flash('error', 'Gagal memuat data peserta: ' . $e->getMessage());
            $this->participant = null;
        }
    }

    public function openEditModal()
    {
        if (!$this->participant) {
            session()->flash('error', 'Data peserta tidak ditemukan.');
            return;
        }
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetValidation();
        $this->loadParticipant(); // Reload data
    }

    public function openDeleteModal()
    {
        if (!$this->participant) {
            session()->flash('error', 'Data peserta tidak ditemukan.');
            return;
        }
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->isDeleting = false;
    }

    public function update()
    {
        $this->validate();

        $oldStatus = $this->participant->status;

        DB::beginTransaction();
        try {
            // Category harus dipilih secara manual karena package tidak terikat ke category spesifik
            // Package tersedia untuk semua category di event yang sama
            
            $this->participant->update([
                'name' => $this->name,
                'nickname' => $this->nickname,
                'number_plate' => $this->number_plate,
                'komunitas' => $this->komunitas,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'date_of_birth' => $this->date_of_birth ?: null,
                'gender' => $this->gender ?: null,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'status' => $this->status,
                'package_id' => $this->package_id,
                'category_id' => $this->category_id,
            ]);

            // Handle status change notifications
            if ($oldStatus !== $this->status) {
                if ($this->status === 'confirmed' && $oldStatus !== 'confirmed') {
                    SendConfirmNotificationJob::dispatch($this->participant->fresh());
                } elseif ($this->status === 'cancelled' && $oldStatus !== 'cancelled') {
                    SendCancelNotificationJob::dispatch($this->participant->fresh());
                }
            }

            DB::commit();
            session()->flash('success', 'Data peserta berhasil diupdate.');
            $this->closeEditModal();
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Terjadi kesalahan saat mengupdate data: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        $this->isDeleting = true;
        
        DB::beginTransaction();
        try {
            if (!$this->participant) {
                throw new \Exception('Participant not found');
            }

            // Delete payment if exists
            if ($this->participant->payment) {
                // Delete payment proof file if exists
                if ($this->participant->payment->payment_proof) {
                    Storage::disk('public')->delete($this->participant->payment->payment_proof);
                }
                $this->participant->payment->delete();
            }

            // Tidak perlu update package current_participants karena kuota diatur di kategori

            $participantId = $this->participant->id;
            $this->participant->delete();

            DB::commit();
            
            $this->isDeleting = false;
            $this->showDeleteModal = false;
            
            session()->flash('success', 'Data peserta berhasil dihapus.');
            
            // Redirect after successful deletion
            return $this->redirect(route('admin.registrations.index'), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->isDeleting = false;
            session()->flash('error', 'Terjadi kesalahan saat menghapus data: ' . $e->getMessage());
            \Log::error('Error deleting participant: ' . $e->getMessage());
        }
    }

    public function getPackagesProperty()
    {
        if (!$this->participant) {
            return collect();
        }

        $event = $this->participant->category ? $this->participant->category->event : $this->participant->package->event;
        
        return Package::select('id', 'event_id', 'name', 'price')
            ->where('event_id', $event->id)
            ->get();
    }

    public function render()
    {
        try {
            return view('livewire.admin.participant-detail');
        } catch (\Exception $e) {
            \Log::error('Error rendering participant detail: ' . $e->getMessage());
            return view('livewire.admin.participant-detail', [
                'participant' => null,
                'showEditModal' => false,
                'showDeleteModal' => false,
            ]);
        }
    }
}

