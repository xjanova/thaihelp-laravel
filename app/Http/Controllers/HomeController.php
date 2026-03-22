<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return view('pages.home', [
            'user' => $request->user(),
            'googleMapsApiKey' => config('services.google.maps_api_key'),
        ]);
    }
}
