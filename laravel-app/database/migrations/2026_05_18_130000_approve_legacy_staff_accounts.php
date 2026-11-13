<?php

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-service lecturer registration always sets staff_id. Rows that are still
     * pending with a null staff_id are legacy accounts created before that flow and
     * the schema default status=pending — they could not log in once LoginRequest
     * required UserStatus::Approved.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'staff_id')) {
            return;
        }

        DB::table('users')
            ->whereIn('role', ['hod'])
            ->where('status', UserStatus::Pending->value)
            ->whereNull('staff_id')
            ->update([
                'status' => UserStatus::Approved->value,
                'approved_at' => now(),
                'is_active' => true,
            ]);
    }

    public function down(): void
    {
        // Cannot distinguish auto-approved legacy rows from real approvals.
    }
};
