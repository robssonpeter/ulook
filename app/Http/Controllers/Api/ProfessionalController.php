<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\ProfessionalResource;
use App\Models\Professional;

class ProfessionalController extends Controller
{
    public function index()
    {
        $professionals = Professional::with(['user', 'services'])->paginate();
        return ProfessionalResource::collection($professionals);
    }

    public function show($id)
    {
        $professional = Professional::with(['user', 'services'])->findOrFail($id);
        return new ProfessionalResource($professional);
    }
}
