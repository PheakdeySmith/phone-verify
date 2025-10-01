<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use Illuminate\Http\Request;
use App\Models\NetworkPrefix;

/**
 * Network Prefix Controller
 *
 * Handles network prefix verification view and related operations.
 * This controller manages the display of network prefix data and verification results.
 */
class NetworkPrefixController extends Controller
{
    /**
     * Display the network prefix verification page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get latest network prefixes and verification records for display
        $network_prefixes = NetworkPrefix::latest()->get();
        $verifications = Verification::latest()->get();

        return view('forms.verification', compact('network_prefixes', 'verifications'));
    }
}
