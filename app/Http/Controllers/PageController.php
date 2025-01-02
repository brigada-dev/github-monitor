<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function repositories()
    {
        return view('dashboard');
    }

    public function commits($owner,$repo_name)
    {
        return view('commits',compact('owner','repo_name'));
    }
}
