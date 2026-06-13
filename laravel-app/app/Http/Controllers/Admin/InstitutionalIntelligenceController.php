<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminInstitutionalIntelligenceService;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionalIntelligenceController extends Controller
{
    public function __construct(
        private AdminInstitutionalIntelligenceService $adminInstitutionalIntelligence
    ) {}

    public function index(): Response
    {
        return Inertia::render(
            'Admin/InstitutionalIntelligence/Dashboard',
            $this->adminInstitutionalIntelligence->dashboard()
        );
    }
}
