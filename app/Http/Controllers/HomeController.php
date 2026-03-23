<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $googleMapsApiKey = SiteSetting::get('google_maps_api_key') ?: config('services.google_maps.api_key', '');

        return view('pages.home', [
            'user' => $request->user(),
            'googleMapsApiKey' => $googleMapsApiKey,
        ]);
    }
}
