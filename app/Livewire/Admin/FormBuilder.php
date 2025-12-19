<?php

namespace App\Livewire\Admin;

use App\Models\FormField;
use App\Models\Package;
use App\Models\Event;
use App\Models\Category;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class FormBuilder extends Component
{
    public $selectedPackageId = null;
    public $selectedPackage = null;
    public $packages = [];
    public $formFields = [];
    
    public $showModal = false;
    public $editingFieldId = null;
    
    // Form field properties
    public $name = '';
    public $label = '';
    public $type = 'text';
    public $options = '';
    public $help_text = '';
    public $required = false;
    public $order = 0;
    public $status = 'active';

    protected $rules = [
        'name' => 'required|string|max:255',
        'label' => 'required|string|max:255',
        'type' => 'required|in:text,textarea,email,tel,date,select,checkbox,radio,number',
        'options' => 'nullable|string',
        'help_text' => 'nullable|string',
        'required' => 'boolean',
        'order' => 'required|integer|min:0',
        'status' => 'required|in:active,inactive',
    ];

    public function mount($packageId = null)
    {
        $this->loadPackages();
        if ($packageId) {
            $this->selectPackage($packageId);
        }
    }

    public function loadPackages()
    {
        $admin = Auth::guard('admin')->user();
        
        $query = Package::with(['event']);

        if (!$admin->isSuperAdmin()) {
            $query->whereHas('event', function ($q) use ($admin) {
                $q->where('created_by', $admin->id)
                  ->orWhere('id', $admin->event_id);
            });
        }

        $this->packages = $query->orderBy('packages.name')->get();
    }

    public function selectPackage($packageId)
    {
        if (empty($packageId)) {
            $this->selectedPackageId = null;
            $this->selectedPackage = null;
            $this->formFields = [];
            return;
        }
        
        $this->selectedPackageId = $packageId;
        $this->selectedPackage = Package::with(['event'])->findOrFail($packageId);
        $this->loadFormFields();
    }

    public function updatedSelectedPackageId($value)
    {
        $this->selectPackage($value);
    }

    public function loadFormFields()
    {
        if ($this->selectedPackageId) {
            $this->formFields = FormField::where('package_id', $this->selectedPackageId)
                ->orderBy('order')
                ->get();
        } else {
            $this->formFields = [];
        }
    }

    public function openModal($fieldId = null)
    {
        $this->editingFieldId = $fieldId;
        $this->resetFieldForm();
        
        if ($fieldId) {
            $field = FormField::findOrFail($fieldId);
            $this->name = $field->name;
            $this->label = $field->label;
            $this->type = $field->type;
            $this->options = is_array($field->options) ? implode("\n", $field->options) : '';
            $this->help_text = $field->help_text;
            $this->required = $field->required;
            $this->order = $field->order;
            $this->status = $field->status;
        }
        
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFieldForm();
    }

    public function resetFieldForm()
    {
        $this->editingFieldId = null;
        $this->name = '';
        $this->label = '';
        $this->type = 'text';
        $this->options = '';
        $this->help_text = '';
        $this->required = false;
        $this->order = 0;
        $this->status = 'active';
        $this->resetErrorBag();
    }

    public function save()
    {
        if (!$this->selectedPackageId) {
            session()->flash('error', 'Pilih paket terlebih dahulu.');
            return;
        }

        $this->validate();

        $optionsArray = null;
        if (in_array($this->type, ['select', 'radio']) && $this->options) {
            $optionsArray = array_filter(array_map('trim', explode("\n", $this->options)));
        }

        $data = [
            'package_id' => $this->selectedPackageId,
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'options' => $optionsArray,
            'help_text' => $this->help_text,
            'required' => $this->required,
            'order' => $this->order,
            'status' => $this->status,
        ];

        if ($this->editingFieldId) {
            FormField::findOrFail($this->editingFieldId)->update($data);
            session()->flash('success', 'Field berhasil diupdate.');
        } else {
            FormField::create($data);
            session()->flash('success', 'Field berhasil ditambahkan.');
        }

        $this->closeModal();
        $this->loadFormFields();
    }

    public function delete($fieldId)
    {
        FormField::findOrFail($fieldId)->delete();
        session()->flash('success', 'Field berhasil dihapus.');
        $this->loadFormFields();
    }

    public function render()
    {
        return view('livewire.admin.form-builder');
    }
}

