<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserSettingUpdateRequest;
use App\Http\Resources\UserSettingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSettingController extends Controller
{
    public function show(Request $request): UserSettingResource
    {
        $user = $request->user();

        $setting = $user->setting()
            ->firstOrCreate(['user_id' => $user->id], ['starting_amount' => null]);

        return new UserSettingResource($setting);
    }

    public function update(UserSettingUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        $setting = $user->setting()
            ->firstOrCreate(['user_id' => $user->id], ['starting_amount' => null]);

        $setting->update($request->validated());

        return response()->json(new UserSettingResource($setting->refresh()));
    }
}

