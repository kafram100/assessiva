<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Services\HodAcademicIntelligenceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicIntelligenceController extends Controller
{
    public function __construct(
        private HodAcademicIntelligenceService $hodAcademicIntelligence
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'Hod/AcademicIntelligence/Dashboard',
            $this->hodAcademicIntelligence->dashboard($request->user())
        );
    }
}
