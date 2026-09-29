<?php

namespace App\Services;

use App\Models\Candidate;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\JobOffer;
use App\Models\JobPosition;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecruitmentService
{
    /**
     * Get candidate pipeline grouped by stage
     */
    public function getPipeline(?int $companyId = null, ?int $jobPositionId = null)
    {
        $stages = ['applied', 'screening', 'shortlisted', 'interview', 'selected', 'offer', 'hired', 'rejected'];

        $candidatesQuery = Candidate::with(['jobPosition.department']);
        if ($companyId) {
            $candidatesQuery->where('company_id', $companyId);
        }
        if ($jobPositionId) {
            $candidatesQuery->where('job_position_id', $jobPositionId);
        }

        $allCandidates = $candidatesQuery->get();

        $pipeline = [];
        foreach ($stages as $stage) {
            $pipeline[$stage] = $allCandidates->where('stage', $stage)->values();
        }

        return [
            'stages' => $stages,
            'pipeline' => $pipeline,
            'total_candidates' => $allCandidates->count(),
        ];
    }

    /**
     * Move candidate to next stage
     */
    public function updateCandidateStage(Candidate $candidate, string $newStage): Candidate
    {
        $candidate->update(['stage' => $newStage]);

        // Also update any active application
        JobApplication::where('candidate_id', $candidate->id)->latest()->first()?->update(['stage' => $newStage]);

        return $candidate->fresh(['jobPosition']);
    }

    /**
     * Schedule an interview
     */
    public function scheduleInterview(array $data, ?int $companyId = null): Interview
    {
        $candidate = Candidate::findOrFail($data['candidate_id']);
        $companyId = $companyId ?? $candidate->company_id;

        return Interview::create([
            'company_id' => $companyId,
            'candidate_id' => $candidate->id,
            'job_position_id' => $candidate->job_position_id,
            'interview_type' => $data['interview_type'] ?? 'video',
            'interview_date' => $data['interview_date'],
            'interview_time' => $data['interview_time'],
            'interviewers' => $data['interviewers'] ?? 'Hiring Panel',
            'meeting_link' => $data['meeting_link'] ?? 'https://meet.falconerp.com/' . substr(md5(uniqid()), 0, 10),
            'feedback' => $data['feedback'] ?? null,
            'rating' => $data['rating'] ?? null,
            'status' => 'scheduled',
        ]);
    }

    /**
     * Create job offer
     */
    public function createOffer(array $data, ?int $companyId = null): JobOffer
    {
        $candidate = Candidate::findOrFail($data['candidate_id']);
        $companyId = $companyId ?? $candidate->company_id;

        return JobOffer::create([
            'company_id' => $companyId,
            'candidate_id' => $candidate->id,
            'job_position_id' => $candidate->job_position_id,
            'offer_date' => $data['offer_date'] ?? Carbon::today(),
            'joining_date' => $data['joining_date'],
            'salary' => $data['salary'],
            'benefits' => $data['benefits'] ?? 'Standard Health Insurance, 401(k), 25 Days Annual Paid Leave',
            'status' => 'sent',
        ]);
    }
}
