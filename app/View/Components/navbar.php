<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class navbar extends Component
{
   /** @var string Aktiver Menüpunkt */
    public string $active;


     /**
     * Erzeugt eine neue Navbar-Komponente.
     *
     * @param string $active aktueller Menüpunkt (optional)
     */
    
    public function __construct(string $active)
    {
        $this->active = $active;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.navbar');
    }
}
