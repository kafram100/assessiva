<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use App\Services\Concerns\RollsUpDepartmentIntelligence;

class HodAcademicIntelligenceService
{
    use RollsUpDepartmentIntelligence;

    /**
     * @return array<string, mixed>
     */
    public function dashboard(User $hod): array
    {
        if (! $hod->isHod()) {
            abort(403);
        }

        $id = (int) ($hod->department_id ?? 0);
        if ($id < 1) {
            return [
                'department' => null,
                'intelligence' => array_merge(
                    ['department_id' => null],
                    $this->rollupDepartment(\PHP_INT_MAX, 'No department assigned')
                ),
            ];
        }

        $department = Department::query()->with('school')->find($id);

        return [
            'department' => $department ? [
                'id' => $department->id,
                'name' => $department->name,
                'school' => $department->school ? [
                    'id' => $department->school->id,
                    'name' => $department->school->name,
                ] : null,
            ] : null,
            'intelligence' => array_merge(
                ['department_id' => $id],
                $this->rollupDepartment($id, $department?->name ?? 'Department')
            ),
        ];
    }
}
