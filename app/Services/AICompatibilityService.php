<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Message;
use App\Models\SwapRequest;
use App\Models\User;
use App\Support\SkillMatchEngine;
use Illuminate\Support\Collection;

class AICompatibilityService
{
    private const WEIGHTS = [
        'skill_match' => 0.32,
        'rating_alignment' => 0.13,
        'swap_history' => 0.20,
        'activity_consistency' => 0.15,
        'tone_alignment' => 0.10,
        'style_compatibility' => 0.10,
    ];

    public function score(
        User $viewer,
        User $candidate,
        array $swapSkillsByUser = [],
        array $activityCounts = [],
        array $toneScores = [],
        array $styleScores = []
    ): array
    {
        $swapSkillsByUser = $swapSkillsByUser ?: $this->swapSkillsForUsers([$viewer->id, $candidate->id]);
        $activityCounts = $activityCounts ?: $this->activityCountsForUsers([$viewer->id, $candidate->id]);

        $skillMatch = $this->skillMatchScore($viewer, $candidate);
        $ratingAlignment = $this->ratingAlignmentScore($viewer, $candidate);
        $swapHistory = $this->swapHistoryScore(
            $swapSkillsByUser[$viewer->id] ?? [],
            $swapSkillsByUser[$candidate->id] ?? []
        );
        $activityConsistency = $this->activityConsistencyScore(
            $activityCounts[$viewer->id] ?? null,
            $activityCounts[$candidate->id] ?? null
        );
        $toneAlignment = $toneScores[$candidate->id] ?? $this->toneAlignmentScore($viewer, $candidate);
        $styleCompatibility = $styleScores[$candidate->id] ?? $this->styleCompatibilityScore($viewer, $candidate);

        $finalScore = (int) round(
            ($skillMatch * self::WEIGHTS['skill_match'])
            + ($ratingAlignment * self::WEIGHTS['rating_alignment'])
            + ($swapHistory * self::WEIGHTS['swap_history'])
            + ($activityConsistency * self::WEIGHTS['activity_consistency'])
            + ($toneAlignment * self::WEIGHTS['tone_alignment'])
            + ($styleCompatibility * self::WEIGHTS['style_compatibility'])
        );

        return [
            'score' => min(100, max(0, $finalScore)),
            'breakdown' => [
                'skill_match_strength' => $skillMatch,
                'rating_alignment' => $ratingAlignment,
                'swap_history_similarity' => $swapHistory,
                'activity_consistency' => $activityConsistency,
                'tone_alignment' => $toneAlignment,
                'style_compatibility' => $styleCompatibility,
            ],
        ];
    }

    /**
     * @return array<int, array{score:int, breakdown:array<string,int>}>
     */
    public function bulkScores(User $viewer, Collection $candidates): array
    {
        if ($candidates->isEmpty()) {
            return [];
        }

        $candidateIds = $candidates->pluck('id')->all();
        $userIds = array_values(array_unique(array_merge($candidateIds, [$viewer->id])));

        $swapSkillsByUser = $this->swapSkillsForUsers($userIds);
        $activityCounts = $this->activityCountsForUsers($userIds);
        $toneScores = $this->toneAlignmentScoresForUsers($viewer->id, $candidateIds);
        $styleScores = $this->styleCompatibilityScores($viewer, $candidates);

        $scores = [];

        foreach ($candidates as $candidate) {
            $scores[$candidate->id] = $this->score(
                $viewer,
                $candidate,
                $swapSkillsByUser,
                $activityCounts,
                $toneScores,
                $styleScores
            );
        }

        return $scores;
    }

    private function skillMatchScore(User $viewer, User $candidate): int
    {
        $myWanted = SkillMatchEngine::parseSkills($viewer->skills_wanted);
        $myOffered = SkillMatchEngine::parseSkills($viewer->skills_offered);
        $theirOffered = SkillMatchEngine::parseSkills($candidate->skills_offered);
        $theirWanted = SkillMatchEngine::parseSkills($candidate->skills_wanted);

        if (empty($myWanted) && empty($myOffered)) {
            return 55;
        }

        $teachesYou = array_intersect($myWanted, $theirOffered);
        $learnsFromYou = array_intersect($myOffered, $theirWanted);

        $matches = count($teachesYou) + count($learnsFromYou);
        $potential = max(1, count($myWanted) + count($myOffered));

        return (int) round(min(100, ($matches / $potential) * 100));
    }

    private function ratingAlignmentScore(User $viewer, User $candidate): int
    {
        $viewerRating = (float) $viewer->rating;
        $candidateRating = (float) $candidate->rating;

        if ($viewerRating <= 0 || $candidateRating <= 0) {
            return 60;
        }

        $diff = abs($viewerRating - $candidateRating);
        $score = 100 - min(100, ($diff / 5) * 100);

        return (int) round($score);
    }

    /**
     * @param array<int, string> $viewerSkills
     * @param array<int, string> $candidateSkills
     */
    private function swapHistoryScore(array $viewerSkills, array $candidateSkills): int
    {
        $viewerSkills = array_values(array_unique($viewerSkills));
        $candidateSkills = array_values(array_unique($candidateSkills));

        if (empty($viewerSkills) && empty($candidateSkills)) {
            return 55;
        }

        $union = array_values(array_unique(array_merge($viewerSkills, $candidateSkills)));
        if (empty($union)) {
            return 55;
        }

        $intersection = array_intersect($viewerSkills, $candidateSkills);
        $ratio = count($intersection) / count($union);

        return (int) round(min(100, 30 + (70 * $ratio)));
    }

    private function activityConsistencyScore(?int $viewerCount, ?int $candidateCount): int
    {
        if (!ActivityLog::enabled()) {
            return 60;
        }

        $viewerCount = $viewerCount ?? 0;
        $candidateCount = $candidateCount ?? 0;

        if ($viewerCount === 0 && $candidateCount === 0) {
            return 60;
        }

        $viewerRate = min(1, $viewerCount / 30);
        $candidateRate = min(1, $candidateCount / 30);
        $diff = abs($viewerRate - $candidateRate);
        $score = (int) round(100 - ($diff * 100));

        return max(40, min(100, $score));
    }

    private function toneAlignmentScore(User $viewer, User $candidate): int
    {
        $scores = $this->toneAlignmentScoresForUsers($viewer->id, [$candidate->id]);

        return $scores[$candidate->id] ?? 60;
    }

    private function styleCompatibilityScore(User $viewer, User $candidate): int
    {
        $scores = $this->styleCompatibilityScores($viewer, collect([$candidate]));

        return $scores[$candidate->id] ?? 55;
    }

    /** @return array{0:int,1:int} */
    private function teachingLearningSignals(User $user): array
    {
        $text = mb_strtolower(trim(implode(' ', array_filter([
            $user->headline,
            $user->bio,
            $user->skills_offered,
            $user->skills_wanted,
        ]))));

        if ($text === '') {
            return [55, 55];
        }

        $teachKeywords = ['teach', 'mentor', 'coach', 'trainer', 'guide', 'instructor'];
        $learnKeywords = ['learn', 'student', 'new to', 'beginner', 'practice', 'study'];

        $teachHits = 0;
        $learnHits = 0;

        foreach ($teachKeywords as $word) {
            if (str_contains($text, $word)) {
                $teachHits++;
            }
        }

        foreach ($learnKeywords as $word) {
            if (str_contains($text, $word)) {
                $learnHits++;
            }
        }

        $teachScore = min(100, 45 + ($teachHits * 12));
        $learnScore = min(100, 45 + ($learnHits * 12));

        return [$teachScore, $learnScore];
    }

    /** @return array<int, int> */
    private function toneAlignmentScoresForUsers(int $viewerId, array $candidateIds): array
    {
        if (empty($candidateIds)) {
            return [];
        }

        $messages = Message::query()
            ->where('created_at', '>=', now()->subDays(90))
            ->where(function ($query) use ($viewerId, $candidateIds) {
                $query->where(function ($inner) use ($viewerId, $candidateIds) {
                    $inner->where('from_user_id', $viewerId)
                        ->whereIn('to_user_id', $candidateIds);
                })->orWhere(function ($inner) use ($viewerId, $candidateIds) {
                    $inner->where('to_user_id', $viewerId)
                        ->whereIn('from_user_id', $candidateIds);
                });
            })
            ->orderByDesc('id')
            ->get(['from_user_id', 'to_user_id', 'message']);

        if ($messages->isEmpty()) {
            return [];
        }

        $positive = ['thanks', 'thank you', 'great', 'awesome', 'appreciate', 'nice', 'good', 'cool', 'perfect', 'sounds good'];
        $negative = ['late', 'rude', 'angry', 'mad', 'bad', 'cancel', 'no show', 'annoyed', 'issue', 'problem'];

        $deltaByUser = [];

        foreach ($messages as $message) {
            $candidateId = $message->from_user_id === $viewerId ? $message->to_user_id : $message->from_user_id;
            if (!in_array($candidateId, $candidateIds, true)) {
                continue;
            }
            $text = mb_strtolower((string) $message->message);
            foreach ($positive as $word) {
                if (str_contains($text, $word)) {
                    $deltaByUser[$candidateId] = ($deltaByUser[$candidateId] ?? 0) + 1;
                }
            }
            foreach ($negative as $word) {
                if (str_contains($text, $word)) {
                    $deltaByUser[$candidateId] = ($deltaByUser[$candidateId] ?? 0) - 1;
                }
            }
        }

        $scores = [];
        foreach ($candidateIds as $candidateId) {
            if (!isset($deltaByUser[$candidateId])) {
                continue;
            }
            $delta = $deltaByUser[$candidateId];
            $score = 60 + max(-30, min(30, $delta * 4));
            $scores[$candidateId] = (int) round(max(35, min(100, $score)));
        }

        return $scores;
    }

    /** @return array<int, int> */
    private function styleCompatibilityScores(User $viewer, Collection $candidates): array
    {
        $scores = [];
        [$viewerTeach, $viewerLearn] = $this->teachingLearningSignals($viewer);

        foreach ($candidates as $candidate) {
            [$candidateTeach, $candidateLearn] = $this->teachingLearningSignals($candidate);
            $teachFit = min($viewerLearn, $candidateTeach);
            $learnFit = min($viewerTeach, $candidateLearn);
            $score = (int) round(($teachFit + $learnFit) / 2);
            $scores[$candidate->id] = max(45, min(100, $score > 0 ? $score : 55));
        }

        return $scores;
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, array<int, string>>
     */
    private function swapSkillsForUsers(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $skillsByUser = [];

        $swapRequests = SwapRequest::query()
            ->where(function ($query) use ($userIds) {
                $query->whereIn('from_user_id', $userIds)
                    ->orWhereIn('to_user_id', $userIds);
            })
            ->get(['from_user_id', 'to_user_id', 'skill_offered', 'skill_requested']);

        foreach ($swapRequests as $swap) {
            $skills = [
                $this->normalizeSkill($swap->skill_offered),
                $this->normalizeSkill($swap->skill_requested),
            ];

            foreach ([$swap->from_user_id, $swap->to_user_id] as $userId) {
                foreach ($skills as $skill) {
                    if ($skill === '') {
                        continue;
                    }
                    $skillsByUser[$userId][] = $skill;
                }
            }
        }

        return $skillsByUser;
    }

    /**
     * @param array<int, int> $userIds
     * @return array<int, int>
     */
    private function activityCountsForUsers(array $userIds): array
    {
        if (empty($userIds) || !ActivityLog::enabled()) {
            return [];
        }

        return ActivityLog::query()
            ->whereIn('user_id', $userIds)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->pluck('count', 'user_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function normalizeSkill(?string $skill): string
    {
        $skill = trim(mb_strtolower((string) $skill));

        return $skill !== '' ? $skill : '';
    }
}
