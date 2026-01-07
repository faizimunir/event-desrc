<?php

namespace App\Livewire;

use App\Models\Package;
use App\Models\Participant;
use App\Models\FormField;
use App\Models\PaymentSetting;
use App\Jobs\SendPendingNotificationJob;
use Livewire\Component;

class Registration extends Component
{

    public $packageId;
    public $categoryId;
    public $package;
    public $event;
    public $category;

    // Form fields (keep for backward compatibility)
    public $name = '';
    public $nickname = '';
    public $number_plate = '';
    public $komunitas = '';
    public $email = '';
    public $phone = '';
    public $city = '';
    public $date_of_birth = '';

    // Dynamic form fields from FormBuilder
    public $formFields = [];
    public $formFieldsData = [];

    public $participant = null;
    public $paymentSettingMissing = false;

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'nickname' => 'required|string|max:255',
            'number_plate' => 'required|string|max:255',
            'komunitas' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
        ];

        // Add dynamic validation rules for form fields
        foreach ($this->formFields as $field) {
            if ($field->status === 'active') {
                $fieldName = 'formFieldsData.' . $field->name;
                $fieldRules = [];
                
                if ($field->required) {
                    $fieldRules[] = 'required';
                } else {
                    $fieldRules[] = 'nullable';
                }

                switch ($field->type) {
                    case 'email':
                        $fieldRules[] = 'email';
                        $fieldRules[] = 'max:255';
                        break;
                    case 'number':
                        $fieldRules[] = 'numeric';
                        break;
                    case 'date':
                        $fieldRules[] = 'date';
                        break;
                    case 'tel':
                    case 'text':
                        $fieldRules[] = 'string';
                        $fieldRules[] = 'max:255';
                        break;
                    case 'textarea':
                        $fieldRules[] = 'string';
                        // No max length for textarea
                        break;
                    case 'select':
                    case 'radio':
                        // Options validation will be handled in the view
                        $fieldRules[] = 'string';
                        $fieldRules[] = 'max:255';
                        break;
                    case 'checkbox':
                        $fieldRules[] = 'boolean';
                        // No max length for boolean
                        break;
                }

                $rules[$fieldName] = implode('|', $fieldRules);
            }
        }

        return $rules;
    }

    public function mount($packageId, $categoryId = null)
    {
        $this->packageId = $packageId;
        // Ensure categoryId is properly cast to integer if provided
        $this->categoryId = $categoryId ? (int) $categoryId : null;
        $this->loadPackage();
    }

    public function loadPackage()
    {
        $this->package = Package::select('id', 'event_id', 'name', 'description', 'price', 'status')
            ->with([
                'event' => function ($query) {
                    $query->select('id', 'name', 'description', 'location', 'start_date', 'end_date', 'registration_start', 'registration_end', 'registration_open', 'payment_method');
                }
            ])
            ->findOrFail($this->packageId);

        $this->event = $this->package->event;
        
        // Category harus dipilih karena package tidak terikat ke category spesifik
        // Category wajib untuk registrasi
        // Check both categoryId property and request parameter
        if (empty($this->categoryId)) {
            // Try to get from request if not set in property
            $this->categoryId = request()->route('categoryId') ?? request()->input('categoryId');
            if ($this->categoryId) {
                $this->categoryId = (int) $this->categoryId;
            }
        }
        
        if (empty($this->categoryId)) {
            session()->flash('error', 'Kategori harus dipilih untuk melanjutkan pendaftaran.');
            return redirect()->route('event.detail', $this->event->id);
        }
        
        $this->category = \App\Models\Category::where('id', $this->categoryId)
            ->where('event_id', $this->event->id)
            ->first();
        
        // If category not found or doesn't belong to this event, redirect back
        if (!$this->category) {
            session()->flash('error', 'Kategori tidak ditemukan atau tidak valid untuk event ini.');
            return redirect()->route('event.detail', $this->event->id);
        }

        // Check if package is available (status active)
        if ($this->package->status !== 'active') {
            session()->flash('error', 'Paket tidak tersedia.');
            return redirect()->route('home');
        }
        
        // Check registration status
        // If registration_open is explicitly set to false, registration is closed
        if ($this->event->registration_open === false) {
            session()->flash('error', 'Pendaftaran untuk event ini sedang ditutup.');
            return redirect()->route('event.detail', $this->event->id);
        }
        
        // If registration_open is null (not explicitly set), check time-based registration
        if (is_null($this->event->registration_open)) {
            // Check registration period using WIB timezone
            $now = now('Asia/Jakarta');
            $registrationStart = \Carbon\Carbon::parse($this->event->registration_start)->setTimezone('Asia/Jakarta');
            $registrationEnd = \Carbon\Carbon::parse($this->event->registration_end)->setTimezone('Asia/Jakarta');
            
            if ($now < $registrationStart) {
                session()->flash('error', 'Pendaftaran belum dibuka. Pendaftaran dibuka pada ' . $registrationStart->format('d M Y H:i') . ' WIB.');
                return redirect()->route('event.detail', $this->event->id);
            }
            
            if ($now > $registrationEnd) {
                session()->flash('error', 'Pendaftaran sudah ditutup. Pendaftaran ditutup pada ' . $registrationEnd->format('d M Y H:i') . ' WIB.');
                return redirect()->route('event.detail', $this->event->id);
            }
        }
        
        // Check category quota if category is selected
        if ($this->categoryId && $this->category) {
            $category = \App\Models\Category::find($this->categoryId);
            if ($category) {
                $totalRegistered = \App\Models\Participant::where('category_id', $category->id)
                    ->whereIn('status', ['pending', 'registered', 'confirmed'])
                    ->count();
                
                if ($category->max_participants && $totalRegistered >= $category->max_participants) {
                    session()->flash('error', 'Kuota kategori sudah penuh.');
                    return redirect()->route('event.detail', $this->event->id);
                }
            }
        }

        // Check payment setting for manual verification events
        $paymentMethod = $this->event->payment_method ?? 'manual';
        if ($paymentMethod === 'manual') {
            $paymentSetting = PaymentSetting::where(function($query) {
                $query->where('event_id', $this->event->id)
                      ->orWhereNull('event_id');
            })
            ->where('status', 'active')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
            
            if (!$paymentSetting) {
                $this->paymentSettingMissing = true;
            }
        }

        // Load form fields for this package
        $this->loadFormFields();
    }

    public function loadFormFields()
    {
        $this->formFields = FormField::where('package_id', $this->packageId)
            ->where('status', 'active')
            ->orderBy('order')
            ->get();

        // Initialize form fields data
        foreach ($this->formFields as $field) {
            if (!isset($this->formFieldsData[$field->name])) {
                $this->formFieldsData[$field->name] = '';
            }
        }
    }

    public function submit()
    {
        $this->validate($this->rules());

        // Check package availability again
        $package = Package::findOrFail($this->packageId);
        if ($package->status !== 'active') {
            $this->addError('package', 'Paket tidak tersedia.');
            return;
        }

        // Reload event to ensure we have latest registration_open status
        $this->event->refresh();

        // Check registration status
        // If registration_open is explicitly set to false, registration is closed
        if ($this->event->registration_open === false) {
            $this->addError('registration', 'Pendaftaran untuk event ini sedang ditutup.');
            return;
        }
        
        // If registration_open is null (not explicitly set), check time-based registration
        if (is_null($this->event->registration_open)) {
            // Check registration period using WIB timezone
            $now = now('Asia/Jakarta');
            $registrationStart = \Carbon\Carbon::parse($this->event->registration_start)->setTimezone('Asia/Jakarta');
            $registrationEnd = \Carbon\Carbon::parse($this->event->registration_end)->setTimezone('Asia/Jakarta');
            
            if ($now < $registrationStart) {
                $this->addError('registration', 'Pendaftaran belum dibuka. Pendaftaran dibuka pada ' . $registrationStart->format('d M Y H:i') . ' WIB.');
                return;
            }
            
            if ($now > $registrationEnd) {
                $this->addError('registration', 'Pendaftaran sudah ditutup. Pendaftaran ditutup pada ' . $registrationEnd->format('d M Y H:i') . ' WIB.');
                return;
            }
        }

        // Check category quota
        // Category harus dipilih karena package tidak terikat ke category spesifik
        $categoryId = $this->categoryId;
        if (!$categoryId) {
            $this->addError('category', 'Silakan pilih kategori terlebih dahulu.');
            return;
        }
        
        if ($categoryId) {
            $category = \App\Models\Category::find($categoryId);
            if ($category) {
                $totalRegistered = \App\Models\Participant::where('category_id', $category->id)
                    ->whereIn('status', ['pending', 'registered', 'confirmed'])
                    ->count();
                
                if ($category->max_participants && $totalRegistered >= $category->max_participants) {
                    $this->addError('category', 'Kuota kategori sudah penuh.');
                    return;
                }
            }
        }

        // Check payment setting for manual verification events
        $paymentMethod = $this->event->payment_method ?? 'manual';
        if ($paymentMethod === 'manual') {
            $paymentSetting = PaymentSetting::where(function($query) {
                $query->where('event_id', $this->event->id)
                      ->orWhereNull('event_id');
            })
            ->where('status', 'active')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();
            
            if (!$paymentSetting) {
                $this->addError('payment_setting', 'Pendaftaran belum diatur oleh admin event. Silakan hubungi admin untuk informasi lebih lanjut.');
                $this->paymentSettingMissing = true;
                return;
            }
        }

        // Prepare form_data from dynamic form fields
        $formData = [];
        foreach ($this->formFields as $field) {
            if (isset($this->formFieldsData[$field->name])) {
                $value = $this->formFieldsData[$field->name];
                // Convert checkbox to boolean
                if ($field->type === 'checkbox') {
                    $value = (bool) $value;
                }
                $formData[$field->name] = $value;
            }
        }

        // Create participant
        try {
            $participant = Participant::create([
                'package_id' => $this->packageId,
                'category_id' => $categoryId, // Store category_id for quota calculation
                'name' => $this->name,
                'nickname' => $this->nickname,
                'number_plate' => $this->number_plate,
                'komunitas' => $this->komunitas,
                'email' => $this->email,
                'phone' => $this->phone,
                'city' => $this->city,
                'date_of_birth' => $this->date_of_birth,
                'form_data' => !empty($formData) ? $formData : null,
                'status' => 'pending',
            ]);

            // Create payment record for Moota events (so webhook can match it)
            $event = $this->package->event;
            if ($event && $event->payment_method === 'moota') {
                \App\Models\Payment::create([
                    'participant_id' => $participant->id,
                    'payment_method' => 'bank_transfer',
                    'amount' => $this->package->price,
                    'status' => 'pending',
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error creating participant: ' . $e->getMessage());
            $this->addError('general', 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.');
            return;
        }

        // Tidak perlu update package current_participants karena kuota diatur di kategori

        // Dispatch job for pending notification (email + WhatsApp via queue)
        SendPendingNotificationJob::dispatch($participant);

        // Redirect to payment page
        return $this->redirect(route('payment.show', $participant->id), navigate: true);
    }

    public function render()
    {
        return view('livewire.registration');
    }
}
