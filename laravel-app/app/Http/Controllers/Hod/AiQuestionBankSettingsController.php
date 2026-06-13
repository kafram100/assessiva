<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiQuestionBankSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isApprovedHod()) {
            abort(403, 'Access denied. Approved heads of department only.');
        }

        $department = Department::query()
            ->where('id', $user->department_id)
            ->firstOrFail();

        return Inertia::render('Hod/AiQuestionBankSettings', [
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
                'require_hod_approval_for_ai_questions' => (bool) $department->require_hod_approval_for_ai_questions,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isApprovedHod()) {
            abort(403);
        }

        $data = $request->validate([
            'require_hod_approval_for_ai_questions' => 'required|boolean',
        ]);

        Department::query()
            ->where('id', $user->department_id)
            ->update([
                'require_hod_approval_for_ai_questions' => $data['require_hod_approval_for_ai_questions'],
            ]);

        return redirect()
            ->route('hod.ai-question-bank-settings.edit')
            ->with('success', 'AI question bank settings saved for your department.');
    }
}
