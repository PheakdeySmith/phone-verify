<?php

namespace App\Http\Controllers;

use App\Models\CountryCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CarrierPrefixMapping;
use App\Services\PhoneCarrierService;

class CountryCheckController extends Controller
{
    protected $phoneCarrierService;

    public function __construct(PhoneCarrierService $phoneCarrierService)
    {
        $this->phoneCarrierService = $phoneCarrierService;
    }

    public function index()
    {
        return view('forms.country_check');
    }

    public function check(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string'
        ]);

        $result = $this->phoneCarrierService->identifyCarrier($request->phone_number);

        return response()->json($result);
    }

    public function clearCache()
    {
        $this->phoneCarrierService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully'
        ]);
    }

    public function getCountryCodes()
{
    $countryCodes = CountryCode::all(['dial_code', 'country_name', 'iso2'])
        ->keyBy('dial_code')
        ->toArray();

    $carrierMappings = CarrierPrefixMapping::all(['country_code', 'prefix', 'carrier_keyword']);

    foreach ($carrierMappings as $mapping) {
        $networkCarrier = DB::table('network_carried')
            ->where('iso2', $mapping->iso2)
            ->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower($mapping->carrier_keyword) . '%'])
            ->first();

        $fullName = $networkCarrier ? $networkCarrier->full_name : ucfirst($mapping->carrier_keyword);

        if (isset($countryCodes[$mapping->country_code])) {
            if (!isset($countryCodes[$mapping->country_code]['prefixes'])) {
                $countryCodes[$mapping->country_code]['prefixes'] = [];
            }
            $countryCodes[$mapping->country_code]['prefixes'][$mapping->prefix] = $fullName;
        }
    }

    return response()->json($countryCodes);
}


}
