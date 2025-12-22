<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\AuthService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Show the home page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('home');
    }

    /**
     * Show the profile page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showProfile(Request $request)
    {
        $user = $this->authService->getAuthenticatedUser($request);
        
        return view('profile', [
            'user' => $user,
        ]);
    }

    /**
     * Show the about page
     *
     * @return \Illuminate\View\View
     */
    public function showAbout()
    {
        return view('about');
    }

    /**
     * Show the contact page
     *
     * @return \Illuminate\View\View
     */
    public function showContact()
    {
        return view('contact');
    }

    /**
     * Show the vehicles listing page
     *
     * @return \Illuminate\View\View
     */
    public function showVehicles()
    {
        // Fetch available vehicles
        $vehicles = Vehicle::where('status', 'Available')
            ->orderBy('created_at', 'desc')
            ->limit(50) // Limit for initial load
            ->get();

        return view('vehicles', [
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Show the vehicle detail page
     *
     * @param string $serialNo
     * @return \Illuminate\View\View
     */
    public function showVehicleDetail($serialNo)
    {
        $vehicle = Vehicle::where('serial_no', $serialNo)->firstOrFail();

        return view('vehicle-detail', [
            'serialNo' => $serialNo,
            'vehicle' => $vehicle,
        ]);
    }
}

