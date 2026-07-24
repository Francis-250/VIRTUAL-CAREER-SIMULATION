<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_content_manager();
verify_csrf();

$title = trim($_POST['title'] ?? '');
if ($title === '' || mb_strlen($title) > 160) {
    json_response(['ok' => false, 'message' => 'Enter a valid career title first.'], 422);
}

$reply = groq_chat([
    [
        'role' => 'system',
        'content' => 'Draft accurate, student-friendly career content for a content manager to review. Return valid JSON only with exactly this shape: {"summary":"...","description":"..."}. The summary must be one concise sentence. The description must be one useful paragraph of 3-5 sentences explaining typical responsibilities, work context, and useful abilities. Do not invent salaries, qualifications, employers, statistics, or guarantees. Do not mention AI.',
    ],
    [
        'role' => 'user',
        'content' => "Career title: {$title}",
    ],
], ['json' => true, 'max_tokens' => 500, 'timeout' => 20]);

if ($reply === null) {
    json_response(['ok' => false, 'message' => 'AI is temporarily unavailable. Please try again later.'], 503);
}

$draft = json_decode($reply, true);
$summary = trim((string)($draft['summary'] ?? ''));
$description = trim((string)($draft['description'] ?? ''));
if ($summary === '' || $description === '') {
    error_log('[CareerSim Groq] Invalid career content JSON');
    json_response(['ok' => false, 'message' => 'AI is temporarily unavailable. Please try again later.'], 503);
}

json_response([
    'ok' => true,
    'summary' => $summary,
    'description' => $description,
    'message' => 'Draft generated. Review and edit it before saving.',
]);
