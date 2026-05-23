<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfilePhotoController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:4096',
        ]);

        $request->user()->updateProfilePhoto($request->file('photo'));

        return new UserResource($request->user()->fresh());
    }
}
