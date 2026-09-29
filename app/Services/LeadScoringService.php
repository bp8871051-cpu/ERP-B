<?php

namespace App\Services;

use App\Models\CrmLead;
use App\Models\CrmLeadScore;
use App\Models\CrmMeeting;

class LeadScoringService
{
    /**
     * Common free email providers to detect if email is a business email
     */
    protected array $freeEmailDomains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com', 'mail.com', 'protonmail.com'
    ];

    public function calculateScore(CrmLead $lead): array
    {
        $breakdown = [];
        $totalScore = 0;

        // 1. Email provided (+10)
        if (!empty($lead->email)) {
            $totalScore += 10;
            $breakdown[] = [
                'rule' => 'Email Provided',
                'points' => 10,
                'description' => 'Valid email address was provided on lead record',
            ];

            // 2. Business Email provided (+10)
            $emailParts = explode('@', $lead->email);
            if (count($emailParts) === 2) {
                $domain = strtolower($emailParts[1]);
                if (!in_array($domain, $this->freeEmailDomains)) {
                    $totalScore += 10;
                    $breakdown[] = [
                        'rule' => 'Corporate Business Email',
                        'points' => 10,
                        'description' => "Verified corporate domain ({$domain})",
                    ];
                }
            }
        }

        // 3. Phone provided (+10)
        if (!empty($lead->phone)) {
            $totalScore += 10;
            $breakdown[] = [
                'rule' => 'Direct Phone Provided',
                'points' => 10,
                'description' => 'Direct phone or mobile contact supplied',
            ];
        }

        // 4. Company website provided (+10)
        if (!empty($lead->website)) {
            $totalScore += 10;
            $breakdown[] = [
                'rule' => 'Company Website Provided',
                'points' => 10,
                'description' => 'Official website listed for company verification',
            ];
        }

        // 5. Budget provided (> 0) (+15)
        if (!empty($lead->budget) && $lead->budget > 0) {
            $totalScore += 15;
            $breakdown[] = [
                'rule' => 'Budget Declared',
                'points' => 15,
                'description' => 'Explicit project budget allocated: ' . number_format($lead->budget, 2),
            ];
        }

        // 6. Engaged with campaign (+15)
        if (!empty($lead->campaign_id)) {
            $totalScore += 15;
            $breakdown[] = [
                'rule' => 'Campaign Engagement',
                'points' => 15,
                'description' => 'Lead originated from or engaged with an active enterprise marketing campaign',
            ];
        }

        // 7. Meeting completed / scheduled (+20)
        $hasMeeting = CrmMeeting::where('lead_id', $lead->id)
            ->whereIn('status', ['completed', 'scheduled'])
            ->exists();

        if ($hasMeeting) {
            $totalScore += 20;
            $breakdown[] = [
                'rule' => 'Meeting Scheduled / Completed',
                'points' => 20,
                'description' => 'Executive discovery call or client demo completed',
            ];
        }

        // Additional points for high expected value
        if (!empty($lead->expected_value) && $lead->expected_value >= 100000) {
            $totalScore += 10;
            $breakdown[] = [
                'rule' => 'High Value Pipeline Prospect',
                'points' => 10,
                'description' => 'High-ticket deal potential exceeding ₹1,00,000 / $10,000',
            ];
        }

        // Cap score at 100
        $totalScore = min(100, $totalScore);

        // Determine category
        $category = match (true) {
            $totalScore >= 81 => 'very_hot',
            $totalScore >= 61 => 'hot',
            $totalScore >= 31 => 'warm',
            default => 'cold',
        };

        // Persist lead score breakdown in database
        CrmLeadScore::where('lead_id', $lead->id)->delete();
        foreach ($breakdown as $item) {
            CrmLeadScore::create([
                'lead_id' => $lead->id,
                'rule_name' => $item['rule'],
                'points' => $item['points'],
                'description' => $item['description'],
                'evaluated_at' => now(),
            ]);
        }

        $lead->update([
            'score' => $totalScore,
            'score_category' => $category,
        ]);

        return [
            'score' => $totalScore,
            'category' => ucfirst(str_replace('_', ' ', $category)),
            'score_category' => $category,
            'breakdown' => $breakdown,
        ];
    }
}
