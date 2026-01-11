<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;

/**
 * Banner Controller
 * Handles public banner display
 */
class BannerController extends Controller
{
    /**
     * Get active banners
     */
    public function index()
    {
        $banners = Banner::active()->get();

        return response()->json([
            'banners' => $banners
        ]);
    }
}
