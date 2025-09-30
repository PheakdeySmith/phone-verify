<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Illuminate\Http\Request;
use App\Models\NetworkPrefix;

class NetworkPrefixController extends Controller
{
    public function index()
    {
        $network_prefixes = NetworkPrefix::latest()->get();
        $verifications = Verification::latest()->get();

        return view('forms.verification', compact('network_prefixes', 'verifications'));
    }
}
