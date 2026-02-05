<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\PaymentMethod;

class HomeController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $allPackages = Package::active()
            ->with('features')
            ->orderBy('price')
            ->get();

        $userPackages    = $allPackages->filter(fn($p) => $p->package_for === 'user');
        $companyPackages = $allPackages->filter(fn($p) => $p->package_for === 'company');

        $paymentMethods = PaymentMethod::active()->get();

        return view('home', compact('userPackages', 'companyPackages', 'paymentMethods'));
    }
}