<?php
// app/Http/Controllers/API/Admin/SettingController.php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // GET /api/v1/admin/settings
    public function index(): JsonResponse
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();

        return response()->json([
            'success' => true,
            'data'    => $settings->groupBy('group'),
        ]);
    }

    // PUT /api/v1/admin/settings/{key}
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => ['required'],
        ]);

        Setting::setValue($key, $request->input('value'), $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => "Paramètre '{$key}' mis à jour.",
        ]);
    }
}
