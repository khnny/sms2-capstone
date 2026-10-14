<?php
declare(strict_types=1);

function smsOpenAiApiKey(): string
{
    if (defined('OPENAI_API_KEY') && is_string(OPENAI_API_KEY) && trim(OPENAI_API_KEY) !== '') {
        return trim(OPENAI_API_KEY);
    }
    $key = function_exists('sms2_env_first')
        ? sms2_env_first(['SMS2_OPENAI_API_KEY', 'OPENAI_API_KEY'])
        : (getenv('SMS2_OPENAI_API_KEY') ?: getenv('OPENAI_API_KEY'));
    if (is_string($key) && trim($key) !== '') {
        return trim($key);
    }
    $keyFile = defined('ROOT_PATH') ? ROOT_PATH . '/storage/keys/openai_api_key' : '';
    if ($keyFile !== '' && is_readable($keyFile)) {
        $key = trim((string) file_get_contents($keyFile));
        if ($key !== '') {
            return $key;
        }
    }

    return '';
}

/**
 * Request structured advisory output from the configured OpenAI GPT-4.1 model.
 *
 * @return array{ok: bool, data?: array<string, mixed>, message?: string, model?: string}
 */
function smsOpenAiJsonCompletion(string $systemPrompt, string $userPrompt, int $maxOutputTokens = 1800): array
{
    $apiKey = smsOpenAiApiKey();
    if ($apiKey === '') {
        return ['ok' => false, 'message' => 'OpenAI API key is not configured.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'The PHP cURL extension is required for OpenAI requests.'];
    }

    $payload = json_encode([
        'model' => 'gpt-4.1',
        'temperature' => 0.2,
        'max_completion_tokens' => max(300, min(4000, $maxOutputTokens)),
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ],
    ], JSON_UNESCAPED_UNICODE);
    if (!is_string($payload)) {
        return ['ok' => false, 'message' => 'The OpenAI request could not be prepared.'];
    }

    $curl = curl_init('https://api.openai.com/v1/chat/completions');
    if ($curl === false) {
        return ['ok' => false, 'message' => 'The OpenAI request could not be initialized.'];
    }
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 55,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if (!is_string($response) || $response === '') {
        error_log('OpenAI request failed: ' . ($curlError !== '' ? $curlError : 'empty response'));
        return ['ok' => false, 'message' => 'OpenAI did not return a response. Please retry.'];
    }
    $body = json_decode($response, true);
    if (!is_array($body)) {
        error_log('OpenAI returned invalid JSON with HTTP status ' . $status . '.');
        return ['ok' => false, 'message' => 'OpenAI returned an unreadable response. Please retry.'];
    }
    if ($status < 200 || $status >= 300) {
        $providerMessage = (string) ($body['error']['message'] ?? '');
        error_log('OpenAI request returned HTTP ' . $status . ($providerMessage !== '' ? ': ' . $providerMessage : '.'));
        return [
            'ok' => false,
            'message' => $status === 401 || $status === 403
                ? 'OpenAI authentication failed. Check the server API key configuration.'
                : 'OpenAI could not complete the request. Please retry.',
        ];
    }

    $content = $body['choices'][0]['message']['content'] ?? null;
    if (!is_string($content) || trim($content) === '') {
        return ['ok' => false, 'message' => 'OpenAI returned no analysis content.'];
    }
    $data = json_decode($content, true);
    if (!is_array($data) || array_is_list($data)) {
        return ['ok' => false, 'message' => 'OpenAI returned results in an unexpected format.'];
    }

    return [
        'ok' => true,
        'data' => $data,
        'model' => (string) ($body['model'] ?? 'gpt-4.1'),
    ];
}
