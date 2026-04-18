<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class StaffLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public bool $hideHeader = false,
    ) {}

    public function render(): View
    {
        return view('layouts.staff');
    }
}
