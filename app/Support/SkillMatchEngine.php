<?php

namespace App\Support;

use App\Models\Report;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Support\Collection;

class SkillMatchEngine
{
    public static function analyze(User $currentUser, User $candidate, array $fastResponderUserIds = [], array $signals = []): array
    {
        $myWanted = self::parseSkills($currentUser->skills_wanted);
        $myOffered = self::parseSkills($currentUser->skills_offered);
        $theirOffered = self::parseSkills($candidate->skills_offered);
        $theirWanted = self::parseSkills($candidate->skills_wanted);

        $myAvailability = self::parseAvailability($currentUser->availability_slots);
        $theirAvailability = self::parseAvailability($candidate->availability_slots);

        $teachesYou = array_values(array_intersect($myWanted, $theirOffered));
        $learnsFromYou = array_values(array_intersect($myOffered, $theirWanted));
        $availabilityOverlap = array_values(array_intersect($myAvailability, $theirAvailability));

        $reportCount = (int) ($signals['report_count'] ?? 0);
        $avgResponseMinutes = (float) ($signals['avg_response_minutes'] ?? 0);
        $acceptedHandledCount = (int) ($signals['accepted_handled_count'] ?? 0);

        $matchScore = (count($teachesYou) * 22) + (count($learnsFromYou) * 22) + (count($availabilityOverlap) * 4);

        if ($currentUser->city && $candidate->city && strcasecmp($currentUser->city, $candidate->city) === 0) {
            $matchScore += 12;
        }

        $matchScore = min(100, $matchScore);

        $smartScore = ($matchScore * 0.65) + (min((float) $candidate->rating, 5) * 7);

        if ($currentUser->city && $candidate->city && strcasecmp($currentUser->city, $candidate->city) === 0) {
            $smartScore += 8;
        }

        $smartScore += min(count($availabilityOverlap), 3) * 4;

        $trustScore = 55;
        $trustScore += min((float) $candidate->rating, 5) * 6;
        $trustScore += min($acceptedHandledCount, 6) * 3;

        if ($avgResponseMinutes > 0) {
            if ($avgResponseMinutes <= 120) {
                $trustScore += 12;
            } elseif ($avgResponseMinutes <= 360) {
                $trustScore += 7;
            } elseif ($avgResponseMinutes <= 720) {
                $trustScore += 3;
            }
        }

        $trustScore -= min($reportCount, 6) * 5;
        $trustScore = (int) max(0, min(100, round($trustScore)));

        $badges = [];

        if ((float) $candidate->rating >= 4.5) {
            $badges[] = 'Top Rated';
        }

        if ($matchScore >= 60) {
            $badges[] = 'High Match';
        }

        if (in_array($candidate->id, $fastResponderUserIds, true)) {
            $badges[] = 'Fast Responder';
        }

        if ($trustScore >= 80) {
            $badges[] = 'Trusted';
        }

        if ($acceptedHandledCount >= 5) {
            $badges[] = 'Reliable';
        }

        if ($candidate->email_verified_at) {
            $badges[] = 'Verified';
        }

        if ($acceptedHandledCount >= 1) {
            $badges[] = 'Swap Verified';
        }

        if (!empty($candidate->verified_badge)) {
            $badgeLabel = match ($candidate->verified_badge) {
                'verified' => 'Verified Mentor',
                'top_mentor' => 'Top Mentor',
                'five_star_pro' => '5 Star Pro',
                default => ucfirst($candidate->verified_badge),
            };
            $badges[] = $badgeLabel;
        }

        return [
            'match_score' => (int) round($matchScore),
            'smart_score' => (int) round(min(100, $smartScore)),
            'trust_score' => $trustScore,
            'teaches_you' => $teachesYou,
            'learns_from_you' => $learnsFromYou,
            'availability_overlap' => $availabilityOverlap,
            'swap_hint_offered' => $learnsFromYou[0] ?? '',
            'swap_hint_requested' => $teachesYou[0] ?? '',
            'badges' => $badges,
        ];
    }

    public static function userSignals(array $candidateUserIds): array
    {
        if (empty($candidateUserIds)) {
            return [];
        }

        $reportCounts = Report::query()
            ->selectRaw('reported_user_id, COUNT(*) as count')
            ->whereIn('reported_user_id', $candidateUserIds)
            ->groupBy('reported_user_id')
            ->pluck('count', 'reported_user_id');

        $responseStats = SwapRequest::query()
            ->selectRaw('to_user_id, AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_minutes, SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted_count')
            ->whereIn('to_user_id', $candidateUserIds)
            ->whereIn('status', ['accepted', 'rejected'])
            ->groupBy('to_user_id')
            ->get()
            ->keyBy('to_user_id');

        $signals = [];

        foreach ($candidateUserIds as $id) {
            $row = $responseStats->get($id);

            $signals[(int) $id] = [
                'report_count' => (int) ($reportCounts[$id] ?? 0),
                'avg_response_minutes' => $row ? (float) $row->avg_minutes : 0,
                'accepted_handled_count' => $row ? (int) $row->accepted_count : 0,
            ];
        }

        return $signals;
    }

    public static function fastResponderUserIds(array $candidateUserIds): array
    {
        if (empty($candidateUserIds)) {
            return [];
        }

        $requests = SwapRequest::query()
            ->whereIn('to_user_id', $candidateUserIds)
            ->whereIn('status', ['accepted', 'rejected'])
            ->get(['to_user_id', 'created_at', 'updated_at']);

        $avgMinutesByUser = $requests
            ->groupBy('to_user_id')
            ->map(function (Collection $items) {
                $avgMinutes = $items->avg(function (SwapRequest $request) {
                    return $request->created_at?->diffInMinutes($request->updated_at) ?? 99999;
                });

                return [
                    'avg_minutes' => $avgMinutes,
                    'handled_count' => $items->count(),
                ];
            });

        return $avgMinutesByUser
            ->filter(fn (array $stats) => $stats['handled_count'] >= 2 && $stats['avg_minutes'] <= 720)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function requestQualityScore(User $sender, User $receiver, string $skillOffered, string $skillRequested): int
    {
        $offered = trim(mb_strtolower($skillOffered));
        $requested = trim(mb_strtolower($skillRequested));

        $score = 20;

        if (mb_strlen($offered) >= 3) {
            $score += 10;
        }

        if (mb_strlen($requested) >= 3) {
            $score += 10;
        }

        if ($offered !== $requested) {
            $score += 10;
        }

        if (self::containsSkill($sender->skills_offered, $offered)) {
            $score += 15;
        }

        if (self::containsSkill($receiver->skills_offered, $requested)) {
            $score += 20;
        }

        if (self::containsSkill($receiver->skills_wanted, $offered)) {
            $score += 10;
        }

        if (self::containsSkill($sender->skills_wanted, $requested)) {
            $score += 5;
        }

        return (int) max(0, min(100, $score));
    }

    /**
     * @return array<int, string>
     */
    public static function parseSkills(?string $skills): array
    {
        if (!$skills) {
            return [];
        }

        return collect(preg_split('/[,\n]+/', mb_strtolower($skills)) ?: [])
            ->map(fn ($skill) => trim((string) $skill))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string>|null $slots
     * @return array<int, string>
     */
    public static function parseAvailability(?array $slots): array
    {
        if (!$slots) {
            return [];
        }

        return collect($slots)
            ->map(fn ($slot) => trim((string) $slot))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private static function containsSkill(?string $haystack, string $skill): bool
    {
        if (!$haystack || $skill === '') {
            return false;
        }

        return str_contains(mb_strtolower($haystack), $skill);
    }
}
