<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DashboardLayout extends Component
{
    /**
     * @param  bool  $fills  Seite füllt genau die Bildschirmhöhe und scrollt nicht.
     *                       Innenbereiche kümmern sich dann selbst ums Scrollen.
     */
    public function __construct(public bool $fills = false) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.dashboard');
    }
}
