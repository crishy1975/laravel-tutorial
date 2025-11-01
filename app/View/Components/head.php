<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class head extends Component
{
    /**
     * Create a new component instance.
     */

    public string $title;
    public function __construct(string $title = 'UschiWeb')
    {
        $this->title=$title;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.head');
    }
}
