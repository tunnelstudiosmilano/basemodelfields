<?php

namespace Mardok9185\Basemodelfields\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use OwenIt\Auditing\Models\Audit;

class BasemodelfieldsAuditController extends Controller
{
    public function index()
    {
        $audit = Audit::with('user')->get();
        return view('basemodelfields::audit', ['items' => $audit]);
    }
}
