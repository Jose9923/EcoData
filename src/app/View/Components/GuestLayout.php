<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render()
    {
        return view('livewire.admin.schools.index', [
            'schools' => $this->schoolRepository()->paginateWithFilters($this->search, $this->perPage),
        ])->layout('components.layouts.app');
    }
}
