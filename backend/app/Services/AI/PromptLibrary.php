<?php

namespace App\Services\AI;

/**
 * Every prompt the app sends an AI provider lives here — centralized so
 * a controller or feature service never builds prompt text itself (Phase
 * 22 §7). Each template is versioned in its method name (e.g. `V1`) so a
 * future revision can be added alongside the old one instead of silently
 * changing behavior for in-flight comparisons/evals.
 */
final class PromptLibrary
{
    private const COMMON_GUARDRAILS = <<<'TEXT'
        Rules:
        - Use ONLY the JSON data you are given. Never invent numbers, names, or dates.
        - If the data doesn't contain the answer, say so plainly instead of guessing.
        - Never give medical, health, injury, or diagnostic advice of any kind.
        - Keep the answer short: a few sentences or a short bulleted list, not a report.
        - You are speaking to gym staff, not the general public or the member themselves.
        TEXT;

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public static function assistantMessagesV1(string $question, array $snapshot): array
    {
        $system = "You are a data assistant embedded in a single gym's staff dashboard.\n"
            .self::COMMON_GUARDRAILS;

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Gym data snapshot (JSON):\n".json_encode($snapshot, JSON_PRETTY_PRINT)."\n\nStaff question: {$question}"],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public static function insightMessagesV1(string $domain, array $metrics): array
    {
        $system = "You write a short business-insight summary for a gym owner's dashboard, about the '{$domain}' area of their gym.\n"
            .self::COMMON_GUARDRAILS
            ."\nWrite 2-4 sentences: call out the most notable trend or pattern in the data, plainly stated.";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Metrics for '{$domain}' (JSON):\n".json_encode($metrics, JSON_PRETTY_PRINT)],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public static function memberInsightMessagesV1(array $memberData): array
    {
        $system = "You suggest practical member-engagement actions for gym staff based only on the member's account data.\n"
            .self::COMMON_GUARDRAILS
            ."\nRespond with 2-4 short bullet points staff could act on today (e.g. a renewal reminder, a check-in call, inviting them to a class). Never suggest workout, diet, or health/medical changes.";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Member data (JSON):\n".json_encode($memberData, JSON_PRETTY_PRINT)],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public static function reportSummaryMessagesV1(string $reportType, array $reportData): array
    {
        $system = "You summarize an already-computed gym report ('{$reportType}') for a staff member who will read it in under 10 seconds.\n"
            .self::COMMON_GUARDRAILS
            ."\nWrite 2-4 sentences analyzing what the numbers show — do not restate every figure, highlight what matters.";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Report data (JSON):\n".json_encode($reportData, JSON_PRETTY_PRINT)],
        ];
    }
}
