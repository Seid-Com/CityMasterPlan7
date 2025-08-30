<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Map extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'City Master Plan - Interactive Map',
            'center_lat' => 11.8311, // Woldia city coordinates
            'center_lng' => 39.6069
        ];

        return view('map', $data);
    }

    public function simple()
    {
        return view('simple-map');
    }
}
