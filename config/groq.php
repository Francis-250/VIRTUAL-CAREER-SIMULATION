<?php
/**
 * Failure-safe Groq chat completion helper.
 * Returns null on any API/configuration failure so callers can preserve core flows.
 */
function groq_settings(): array {
    static $settings;
    if ($settings !== null) return $settings;
    $settings = [];
    $path = dirname(__DIR__) . '/.env';
    if (is_file($path)) foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $settings[$key] = trim($value, "\"'");
    }
    return $settings;
}

function groq_chat(array $messages, array $options = []): ?string {
    try {
        $settings = groq_settings();
        $key = $settings['GROQ_API_KEY'] ?? '';
        if ($key === '') throw new RuntimeException('GROQ_API_KEY is missing.');
        $payload = json_encode([
            'model' => $settings['GROQ_MODEL'] ?? 'llama-3.3-70b-versatile',
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.45,
            'max_tokens' => $options['max_tokens'] ?? 500,
            'response_format' => $options['json'] ?? false ? ['type' => 'json_object'] : null,
        ], JSON_THROW_ON_ERROR);
        $curl = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($curl, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => $options['timeout'] ?? 18,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$key, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
        ]);
        $body = curl_exec($curl); $error = curl_error($curl); $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        if ($body === false || $error || $status < 200 || $status >= 300) throw new RuntimeException("Groq HTTP $status: ".($error ?: substr((string)$body, 0, 300)));
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        $text = trim((string)($data['choices'][0]['message']['content'] ?? ''));
        if ($text === '') throw new RuntimeException('Groq returned an empty completion.');
        return $text;
    } catch (Throwable $e) {
        error_log('[CareerSim Groq] '.$e->getMessage());
        return null;
    }
}
