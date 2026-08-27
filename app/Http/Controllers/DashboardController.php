<?php

namespace App\Http\Controllers;

use App\Actions\ObtenerMetricasDashboard;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ObtenerMetricasDashboard $metricas): View
    {
        return view('dashboard', $metricas->handle());
    }
}
