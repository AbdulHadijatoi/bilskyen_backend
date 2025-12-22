<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
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
     * @return \Illuminate\View\View
     */
    public function showProfile()
    {
        return view('profile');
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
        return view('vehicles');
    }

    /**
     * Show the vehicle detail page
     *
     * @param string $serialNo
     * @return \Illuminate\View\View
     */
    public function showVehicleDetail($serialNo)
    {
        return view('vehicle-detail', ['serialNo' => $serialNo]);
    }
}

