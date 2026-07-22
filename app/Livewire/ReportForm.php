<?php

namespace App\Livewire;

use App\Models\UserReport;
use Livewire\Component;

/**
 * Componente Livewire para enviar reportes (tickets).
 * Accesible globalmente a través de un modal.
 */
class ReportForm extends Component
{
    public bool $isOpen = false;

    public string $category = '';
    public string $description = '';
    
    public ?int $related_item_id = null;
    public ?string $related_item_type = null;

    protected $listeners = ['openReportModal' => 'open'];

    protected function rules()
    {
        return [
            'category'    => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(UserReport::$categories))],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function open(?string $type = null, ?int $id = null)
    {
        $this->reset(['category', 'description']);
        $this->related_item_type = $type;
        $this->related_item_id   = $id;
        $this->isOpen = true;
    }

    public function close()
    {
        $this->isOpen = false;
        $this->reset(['category', 'description', 'related_item_type', 'related_item_id']);
    }

    public function submit()
    {
        $this->validate();

        UserReport::create([
            'user_id'           => auth()->id(),
            'category'          => $this->category,
            'description'       => $this->description,
            'related_item_type' => $this->related_item_type,
            'related_item_id'   => $this->related_item_id,
            'status'            => UserReport::STATUS_PENDING,
        ]);

        $this->close();
        
        session()->flash('success', 'Reporte enviado correctamente. Gracias por ayudarnos a mejorar.');
    }

    public function render()
    {
        return view('livewire.report-form');
    }
}
