<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard()
    {
        return view('user.dashboard');
    }

    public function booking()
    {
        return view('user.booking');
    }

    public function schedule()
    {
        return view('user.schedule');
    }

    public function payment()
    {
        return view('user.payment');
    }

    public function notification()
    {
        return view('user.notification');
    }

    public function profile()
    {
        return view('user.profile');
    }
}