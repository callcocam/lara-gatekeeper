<?php
/**
 * Created by Claudio Campos.   
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Http\Controllers;

use App\Http\Controllers\AbstractController;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller{

    public function index(){ 
        return Inertia::render('admin/Dashboard');
    }
}