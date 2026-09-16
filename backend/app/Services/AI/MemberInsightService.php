<?php

namespace App\Services\AI;

use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Per-member engagement suggestions from the member's own account data
 * only — attendance, membership status, and training enrollment (Phase
 * 22 §5). The prompt explicitly forbids medical/health advice; see
 * PromptLibrary::memberInsightMessagesV1().
 */
class MemberInsightService
{
    public function __construct(private readonly AIRequestRunner $runner) {}

    public function generate(Tenant $tenant, ?User $user, Member $member): array
    {
        $data = $this->memberData($member);
        $messages = PromptLibrary::memberInsightMessagesV1($data);

        $response = $this->runner->run($tenant, $user, 'member_insight', $messages);

        return [
            'memberId' => (string) $member->id,
            'data' => $data,
            'suggestedActions' => $response->content,
            'generatedAt' => now()->toIso8601String(),
        ];
    }

    private function memberData(Member $member): array
    {
        $recentAttendance = $member->attendanceRecords()->where('date', '>=', Carbon::now()->subDays(30))->count();
        $activeMembership = $member->memberships()->with('plan')->where('status', 'Active')->latest('expiry_date')->first();

        return [
            'name' => $member->name,
            'status' => $member->status,
            'joinDate' => $member->join_date?->toDateString(),
            'attendanceRate' => $member->attendance_rate,
            'attendanceLast30Days' => $recentAttendance,
            'membership' => $activeMembership ? [
                'planName' => $activeMembership->plan?->name,
                'status' => $activeMembership->status,
                'expiryDate' => $activeMembership->expiry_date->toDateString(),
            ] : null,
            'trainingProgramsEnrolled' => $member->trainingPrograms()->count(),
            'hasPersonalTraining' => $member->personalTrainingSession()->exists(),
            'balanceDue' => $member->balance_due,
        ];
    }
}
