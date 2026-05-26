<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    // Cette fonction retourne toutes les campagnes en JSON
    public function index()
    {
        return Campaign::with('category')->get();
    }
}