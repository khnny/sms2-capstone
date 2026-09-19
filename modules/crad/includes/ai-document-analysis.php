<?php
/**
 * Adviser AI document analysis (grammar + research writing notes)
 * before Approve / Request Revision.
 */
declare(strict_types=1);

function rpCursorApiKey(): string
{
    if (defined('CURSOR_API_KEY') && is_string(CURSOR_API_KEY) && CURSOR_API_KEY !== '') {
        return trim(CURSOR_API_KEY);
    }
    if (function_exists('sms2_env')) {
        $env = sms2_env('CURSOR_API_KEY');
        if (is_string($env) && $env !== '') {
            return trim($env);
        }
    }
    $file = ROOT_PATH . '/storage/keys/cursor_api_key';
    if (is_readable($file)) {
        $raw = trim((string) file_get_contents($file));
        if ($raw !== '') {
            return $raw;
        }
    }

    return '';
}

function rpEnsureAiAnalysisSchema(PDO $crad): void
{
    $crad->exec(
        "CREATE TABLE IF NOT EXISTS research_progress_ai_analyses (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            progress_update_id INT UNSIGNED NOT NULL,
            attachment_id INT UNSIGNED NOT NULL DEFAULT 0,
            milestone_name VARCHAR(180) NOT NULL DEFAULT '',
            verdict VARCHAR(40) NOT NULL DEFAULT 'needs_revision',
            grammar_quality VARCHAR(40) NOT NULL DEFAULT 'fair',
            summary TEXT NOT NULL,
            notes_json MEDIUMTEXT NOT NULL,
            source VARCHAR(40) NOT NULL DEFAULT 'cursor',
            analyzed_by INT UNSIGNED NOT NULL DEFAULT 0,
            analyzed_by_name VARCHAR(180) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_rpai_update (progress_update_id, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function rpLatestAiAnalysisForUpdate(PDO $crad, int $progressUpdateId): ?array
{
    if ($progressUpdateId <= 0) {
        return null;
    }
    rpEnsureAiAnalysisSchema($crad);
    $stmt = $crad->prepare(
        "SELECT *
         FROM research_progress_ai_analyses
         WHERE progress_update_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([$progressUpdateId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $notes = json_decode((string) ($row['notes_json'] ?? '[]'), true);
    $row['notes'] = is_array($notes) ? $notes : [];
    unset($row['notes_json']);

    return $row;
}

function rpSaveAiAnalysis(PDO $crad, array $data): int
{
    rpEnsureAiAnalysisSchema($crad);
    $stmt = $crad->prepare(
        "INSERT INTO research_progress_ai_analyses (
            progress_update_id, attachment_id, milestone_name, verdict, grammar_quality,
            summary, notes_json, source, analyzed_by, analyzed_by_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        (int) ($data['progress_update_id'] ?? 0),
        (int) ($data['attachment_id'] ?? 0),
        (string) ($data['milestone_name'] ?? ''),
        (string) ($data['verdict'] ?? 'needs_revision'),
        (string) ($data['grammar_quality'] ?? 'fair'),
        (string) ($data['summary'] ?? ''),
        json_encode($data['notes'] ?? [], JSON_UNESCAPED_UNICODE),
        (string) ($data['source'] ?? 'cursor'),
        (int) ($data['analyzed_by'] ?? 0),
        (string) ($data['analyzed_by_name'] ?? ''),
    ]);

    return (int) $crad->lastInsertId();
}

function rpResolveUploadPath(string $storedPath): ?string
{
    $storedPath = trim($storedPath);
    if ($storedPath === '') {
        return null;
    }
    $root = realpath(smsUploadRoot());
    if ($root === false) {
        return null;
    }
    $candidates = [$storedPath];
    if (!preg_match('/^[A-Za-z]:[\\\\\\/]/', $storedPath) && !str_starts_with($storedPath, '/')) {
        $candidates[] = smsUploadRoot() . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath), DIRECTORY_SEPARATOR);
    }
    foreach ($candidates as $candidate) {
        $real = realpath($candidate);
        if ($real && is_file($real) && str_starts_with($real, $root)) {
            return $real;
        }
    }

    return null;
}

function rpExtractDocumentText(string $filePath, string $fileName = '', string $mime = ''): string
{
    $ext = strtolower(pathinfo($fileName !== '' ? $fileName : $filePath, PATHINFO_EXTENSION));
    $mime = strtolower($mime);
    if ($ext === 'docx' || str_contains($mime, 'wordprocessingml')) {
        return rpExtractDocxText($filePath);
    }
    if (in_array($ext, ['txt', 'md', 'csv'], true) || str_starts_with($mime, 'text/')) {
        $raw = (string) file_get_contents($filePath);
        return trim(preg_replace("/\xEF\xBB\xBF/", '', $raw) ?? $raw);
    }
    if ($ext === 'pdf' || str_contains($mime, 'pdf')) {
        return rpExtractPdfText($filePath);
    }
    if (in_array($ext, ['html', 'htm'], true)) {
        return trim(html_entity_decode(strip_tags((string) file_get_contents($filePath)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return '';
}

function rpExtractDocxText(string $filePath): string
{
    $xml = rpZipInnerFile($filePath, 'word/document.xml');
    if ($xml === null || $xml === '') {
        return '';
    }
    $xml = preg_replace('/<w:tab[^\\/]*\\/>/', "\t", $xml) ?? $xml;
    $xml = preg_replace('/<w:br[^\\/]*\\/>/', "\n", $xml) ?? $xml;
    $xml = preg_replace('/<\\/w:p>/', "\n", $xml) ?? $xml;
    $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    $text = preg_replace("/[ \\t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}

function rpZipInnerFile(string $zipPath, string $innerName): ?string
{
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $data = $zip->getFromName($innerName);
            $zip->close();
            return is_string($data) ? $data : null;
        }
    }

    return rpZipInnerFileManual($zipPath, $innerName);
}

function rpZipInnerFileManual(string $zipPath, string $innerName): ?string
{
    $fh = fopen($zipPath, 'rb');
    if ($fh === false) {
        return null;
    }
    $target = str_replace('\\', '/', $innerName);
    while (!feof($fh)) {
        $sig = fread($fh, 4);
        if ($sig === false || strlen($sig) < 4) {
            break;
        }
        if ($sig !== "PK\x03\x04") {
            break;
        }
        $header = fread($fh, 26);
        if ($header === false || strlen($header) < 26) {
            break;
        }
        $fields = unpack('vver/vflag/vmethod/vtime/vdate/Vcrc/Vcsz/Vusz/vnamelen/vextralen', $header);
        if (!is_array($fields)) {
            break;
        }
        $name = fread($fh, (int) $fields['namelen']);
        if ((int) $fields['extralen'] > 0) {
            fread($fh, (int) $fields['extralen']);
        }
        $payload = ($fields['csz'] > 0) ? fread($fh, (int) $fields['csz']) : '';
        if ($name === $target && is_string($payload)) {
            fclose($fh);
            if ((int) $fields['method'] === 0) {
                return $payload;
            }
            if ((int) $fields['method'] === 8) {
                $out = @gzinflate($payload);
                return is_string($out) ? $out : null;
            }
            return null;
        }
        if (((int) $fields['flag'] & 0x08) === 0x08) {
            break;
        }
    }
    fclose($fh);

    return null;
}

function rpExtractPdfText(string $filePath): string
{
    $raw = (string) file_get_contents($filePath);
    if ($raw === '') {
        return '';
    }
    $chunks = [];
    if (preg_match_all('/stream\\s*\\r?\\n(.*?)\\r?\\nendstream/s', $raw, $matches)) {
        foreach ($matches[1] as $stream) {
            $decoded = @gzuncompress($stream);
            if (!is_string($decoded)) {
                $decoded = @gzinflate($stream);
            }
            if (!is_string($decoded)) {
                $decoded = $stream;
            }
            if (preg_match_all('/\\((?:\\\\.|[^\\\\)]){2,}\\)/s', $decoded, $textBits)) {
                foreach ($textBits[0] as $bit) {
                    $chunks[] = stripcslashes(trim($bit, '()'));
                }
            }
        }
    }
    $text = trim(implode(' ', $chunks));
    $text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', ' ', $text) ?? $text;

    return trim($text);
}

/**
 * @return array{ok: bool, verdict?: string, grammar_quality?: string, summary?: string, notes?: list<array<string,string>>, source?: string, message?: string}
 */
function rpAnalyzeResearchDocument(string $text, string $milestoneName, string $fileName): array
{
    $text = trim($text);
    if ($text === '') {
        return ['ok' => false, 'message' => 'The attached file has no readable text. Please ask the student to upload a .docx or .txt research file.'];
    }

    $excerpt = rpTruncateAnalysisText($text, 14000);
    $cursor = rpAnalyzeWithCursor($excerpt, $milestoneName, $fileName);
    if (!empty($cursor['ok'])) {
        return $cursor;
    }

    $fallback = rpAnalyzeWithLanguageTool($excerpt, $milestoneName, $fileName);
    if (!empty($fallback['ok'])) {
        return $fallback;
    }

    return [
        'ok' => false,
        'message' => (string) ($cursor['message'] ?? $fallback['message'] ?? 'AI analysis could not be completed.'),
    ];
}

function rpTruncateAnalysisText(string $text, int $maxChars): string
{
    $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    if ($length <= $maxChars) {
        return $text;
    }
    $cut = function_exists('mb_substr') ? mb_substr($text, 0, $maxChars, 'UTF-8') : substr($text, 0, $maxChars);

    return $cut . "\n\n[Document truncated for analysis.]";
}

/**
 * @return array{ok: bool, verdict?: string, grammar_quality?: string, summary?: string, notes?: list<array<string,string>>, source?: string, message?: string}
 */
function rpAnalyzeWithCursor(string $text, string $milestoneName, string $fileName): array
{
    $apiKey = rpCursorApiKey();
    if ($apiKey === '') {
        return ['ok' => false, 'message' => 'Cursor API key is not configured.'];
    }

    $prompt = rpBuildAnalysisPrompt($text, $milestoneName, $fileName);
    $chat = rpCursorChatCompletions($apiKey, $prompt);
    if (!empty($chat['ok']) && !empty($chat['text'])) {
        $parsed = rpParseAnalysisPayload((string) $chat['text']);
        if ($parsed !== null) {
            $parsed['ok'] = true;
            $parsed['source'] = 'cursor';
            return $parsed;
        }
    }

    $message = (string) ($chat['message'] ?? 'Cursor analysis is not available from this endpoint.');
    return ['ok' => false, 'message' => $message];
}

function rpBuildAnalysisPrompt(string $text, string $milestoneName, string $fileName): string
{
    $milestone = $milestoneName !== '' ? $milestoneName : 'research milestone';
    return <<<PROMPT
You are an academic English grammarian for a college research monitoring system.
Analyze the student research file for "{$milestone}" (filename: {$fileName}).

Focus on:
1. Grammar, spelling, punctuation, subject-verb agreement, tense consistency
2. Academic writing quality (clarity, formality, citation language if present)
3. Whether this chapter/section looks complete enough to approve

Reply with JSON only. No markdown. Use this shape:
{
  "verdict": "acceptable" or "needs_revision",
  "grammar_quality": "good" or "fair" or "poor",
  "summary": "2-4 sentence English summary for the faculty adviser",
  "notes": [
    {
      "issue": "what is wrong",
      "suggestion": "what the student should change",
      "example": "optional short excerpt from the paper"
    }
  ]
}

If grammar is generally correct, set verdict to "acceptable" and still include 1-3 optional improvement notes.
If grammar is wrong, set verdict to "needs_revision" and list concrete notes the adviser can require before approval.
Write all notes in English.

STUDENT DOCUMENT TEXT:
{$text}
PROMPT;
}

/**
 * @return array{ok: bool, text?: string, message?: string}
 */
function rpCursorChatCompletions(string $apiKey, string $prompt): array
{
    $endpoints = [
        'https://api.cursor.com/v1/chat/completions',
        'https://api.cursor.com/chat/completions',
    ];
    foreach ($endpoints as $url) {
        $payload = [
            'model' => 'composer-2.5',
            'temperature' => 0.2,
            'messages' => [
                ['role' => 'system', 'content' => 'You are a strict academic grammar reviewer. Return JSON only.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ];
        $result = rpCursorHttpJson('POST', $url, $apiKey, $payload);
        if (!$result['ok']) {
            continue;
        }
        $body = $result['body'] ?? [];
        $text = (string) ($body['choices'][0]['message']['content'] ?? $body['result'] ?? $body['text'] ?? '');
        if ($text !== '') {
            return ['ok' => true, 'text' => $text];
        }
    }

    return ['ok' => false, 'message' => 'Cursor chat completions endpoint is not available.'];
}

/**
 * @return array{ok: bool, text?: string, message?: string}
 */
function rpCursorCreateAgentAndWait(string $apiKey, string $prompt): array
{
    $created = rpCursorHttpJson('POST', 'https://api.cursor.com/v1/agents', $apiKey, [
        'prompt' => ['text' => $prompt],
        'model' => ['id' => 'composer-2.5'],
        'name' => 'SMS2 research grammar review',
    ]);
    if (empty($created['ok'])) {
        return ['ok' => false, 'message' => (string) ($created['message'] ?? 'Unable to start Cursor agent.')];
    }
    $body = $created['body'] ?? [];
    $agentId = (string) ($body['agent']['id'] ?? $body['id'] ?? '');
    $runId = (string) ($body['run']['id'] ?? $body['agent']['latestRunId'] ?? $body['latestRunId'] ?? '');
    if ($agentId === '' || $runId === '') {
        $text = rpExtractCursorResultText($body);
        if ($text !== '') {
            return ['ok' => true, 'text' => $text];
        }
        return ['ok' => false, 'message' => 'Cursor agent started but no run id was returned.'];
    }

    $deadline = time() + 75;
    $lastMessage = 'Cursor agent timed out.';
    while (time() < $deadline) {
        sleep(4);
        $run = rpCursorHttpJson('GET', 'https://api.cursor.com/v1/agents/' . rawurlencode($agentId) . '/runs/' . rawurlencode($runId), $apiKey, null);
        if (empty($run['ok'])) {
            $lastMessage = (string) ($run['message'] ?? 'Cursor run polling failed.');
            continue;
        }
        $runBody = $run['body'] ?? [];
        $status = strtolower((string) ($runBody['status'] ?? $runBody['run']['status'] ?? ''));
        $text = rpExtractCursorResultText($runBody);
        if (in_array($status, ['finished', 'completed', 'success', 'done'], true) && $text !== '') {
            return ['ok' => true, 'text' => $text];
        }
        if (in_array($status, ['error', 'failed', 'cancelled', 'canceled'], true)) {
            return ['ok' => false, 'message' => $text !== '' ? $text : 'Cursor agent run failed.'];
        }
        if ($text !== '') {
            return ['ok' => true, 'text' => $text];
        }
    }

    return ['ok' => false, 'message' => $lastMessage];
}

function rpExtractCursorResultText(array $body): string
{
    $candidates = [
        $body['result']['text'] ?? null,
        $body['result'] ?? null,
        $body['run']['result']['text'] ?? null,
        $body['run']['result'] ?? null,
        $body['output'] ?? null,
        $body['text'] ?? null,
        $body['message'] ?? null,
    ];
    foreach ($candidates as $candidate) {
        if (is_string($candidate) && trim($candidate) !== '' && strlen($candidate) > 20) {
            return trim($candidate);
        }
        if (is_array($candidate)) {
            foreach (['text', 'content', 'summary', 'output'] as $key) {
                if (isset($candidate[$key]) && is_string($candidate[$key]) && trim($candidate[$key]) !== '') {
                    return trim($candidate[$key]);
                }
            }
        }
    }

    return '';
}

/**
 * @param array<string, mixed>|null $payload
 * @return array{ok: bool, status?: int, body?: array<string, mixed>, message?: string}
 */
function rpCursorHttpJson(string $method, string $url, string $apiKey, ?array $payload): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'message' => 'Unable to start HTTP request.'];
    }
    $headers = ['Accept: application/json'];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERPWD => $apiKey . ':',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_HTTPHEADER] = $headers;
        $opts[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if (!is_string($raw)) {
        return ['ok' => false, 'status' => $status, 'message' => $err !== '' ? $err : 'Empty Cursor API response.'];
    }
    $decoded = json_decode($raw, true);
    if ($status >= 200 && $status < 300 && is_array($decoded)) {
        return ['ok' => true, 'status' => $status, 'body' => $decoded];
    }
    $message = 'Cursor API HTTP ' . $status;
    if (is_array($decoded)) {
        if (isset($decoded['message']) && is_string($decoded['message'])) {
            $message = $decoded['message'];
        } elseif (isset($decoded['error']) && is_string($decoded['error'])) {
            $message = $decoded['error'];
        } elseif (isset($decoded['error']['message']) && is_string($decoded['error']['message'])) {
            $message = $decoded['error']['message'];
        }
    }

    return ['ok' => false, 'status' => $status, 'message' => $message, 'body' => is_array($decoded) ? $decoded : []];
}

/**
 * @return array{verdict: string, grammar_quality: string, summary: string, notes: list<array<string,string>>}|null
 */
function rpParseAnalysisPayload(string $raw): ?array
{
    $raw = trim($raw);
    if ($raw === '') {
        return null;
    }
    if (preg_match('/```(?:json)?\\s*(\\{.*?\\})\\s*```/s', $raw, $m)) {
        $raw = $m[1];
    } elseif (preg_match('/\\{[\\s\\S]*\\}/', $raw, $m)) {
        $raw = $m[0];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }
    $verdict = strtolower(trim((string) ($data['verdict'] ?? '')));
    if (!in_array($verdict, ['acceptable', 'needs_revision'], true)) {
        $verdict = 'needs_revision';
    }
    $quality = strtolower(trim((string) ($data['grammar_quality'] ?? '')));
    if (!in_array($quality, ['good', 'fair', 'poor'], true)) {
        $quality = $verdict === 'acceptable' ? 'good' : 'fair';
    }
    $notes = [];
    foreach (($data['notes'] ?? []) as $note) {
        if (!is_array($note)) {
            continue;
        }
        $issue = trim((string) ($note['issue'] ?? $note['problem'] ?? ''));
        $suggestion = trim((string) ($note['suggestion'] ?? $note['fix'] ?? ''));
        if ($issue === '' && $suggestion === '') {
            continue;
        }
        $notes[] = [
            'issue' => $issue !== '' ? $issue : $suggestion,
            'suggestion' => $suggestion,
            'example' => trim((string) ($note['example'] ?? $note['excerpt'] ?? '')),
        ];
    }

    return [
        'verdict' => $verdict,
        'grammar_quality' => $quality,
        'summary' => trim((string) ($data['summary'] ?? 'AI grammar review completed.')),
        'notes' => $notes,
    ];
}

/**
 * @return array{ok: bool, verdict?: string, grammar_quality?: string, summary?: string, notes?: list<array<string,string>>, source?: string, message?: string}
 */
function rpAnalyzeWithLanguageTool(string $text, string $milestoneName, string $fileName): array
{
    $notes = [];
    $chunks = rpSplitTextChunks($text, 18000);
    $errorCount = 0;
    foreach ($chunks as $chunk) {
        $matches = rpLanguageToolMatches($chunk);
        foreach ($matches as $match) {
            $message = trim((string) ($match['message'] ?? ''));
            if ($message === '') {
                continue;
            }
            $errorCount++;
            $replacements = $match['replacements'] ?? [];
            $suggestion = '';
            if (is_array($replacements) && isset($replacements[0]['value'])) {
                $suggestion = 'Change to: "' . (string) $replacements[0]['value'] . '"';
            }
            $context = '';
            if (isset($match['context']['text'])) {
                $context = trim((string) $match['context']['text']);
            }
            $notes[] = [
                'issue' => $message,
                'suggestion' => $suggestion !== '' ? $suggestion : 'Revise this sentence for correct academic English.',
                'example' => $context,
            ];
            if (count($notes) >= 12) {
                break 2;
            }
        }
    }

    $wordCount = str_word_count($text);
    if ($wordCount < 80) {
        $notes[] = [
            'issue' => 'The submitted file is too short for a complete ' . ($milestoneName !== '' ? $milestoneName : 'research') . ' chapter.',
            'suggestion' => 'Ask the student to upload the full chapter with introduction, discussion, and proper academic sentences.',
            'example' => 'Readable words found: ' . $wordCount . ' in ' . $fileName,
        ];
    }
    if (preg_match('/\\b(asap|gonna|wanna|u r|idk|lol)\\b/i', $text)) {
        $notes[] = [
            'issue' => 'Informal or chat-style wording appears in the manuscript.',
            'suggestion' => 'Replace slang with formal academic English before approval.',
            'example' => '',
        ];
    }

    $quality = 'good';
    $verdict = 'acceptable';
    if ($errorCount >= 8 || $wordCount < 80) {
        $quality = 'poor';
        $verdict = 'needs_revision';
    } elseif ($errorCount >= 3) {
        $quality = 'fair';
        $verdict = 'needs_revision';
    }

    if ($notes === []) {
        $notes[] = [
            'issue' => 'No major grammar errors were detected in the extracted text.',
            'suggestion' => 'You may still read the full document before approving.',
            'example' => '',
        ];
    }

    $summary = $verdict === 'acceptable'
        ? 'Grammar for ' . ($milestoneName !== '' ? $milestoneName : 'this submission') . ' looks generally acceptable. Review the notes, then approve or request revision.'
        : 'Grammar and writing issues were found in ' . ($milestoneName !== '' ? $milestoneName : 'this submission') . '. Do not approve until the student revises the notes below.';

    return [
        'ok' => true,
        'source' => 'grammar_engine',
        'verdict' => $verdict,
        'grammar_quality' => $quality,
        'summary' => $summary,
        'notes' => $notes,
    ];
}

/**
 * @return list<string>
 */
function rpSplitTextChunks(string $text, int $size): array
{
    if (strlen($text) <= $size) {
        return [$text];
    }
    $chunks = [];
    $len = strlen($text);
    for ($i = 0; $i < $len; $i += $size) {
        $chunks[] = substr($text, $i, $size);
    }

    return $chunks;
}

/**
 * @return list<array<string, mixed>>
 */
function rpLanguageToolMatches(string $text): array
{
    $ch = curl_init('https://api.languagetool.org/v2/check');
    if ($ch === false) {
        return [];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'text' => $text,
            'language' => 'en-US',
            'level' => 'picky',
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
    ]);
    $raw = curl_exec($ch);
    curl_close($ch);
    if (!is_string($raw)) {
        return [];
    }
    $decoded = json_decode($raw, true);
    $matches = $decoded['matches'] ?? [];

    return is_array($matches) ? $matches : [];
}

function rpFormatAiNotesForRevision(array $analysis): string
{
    $lines = [];
    $lines[] = 'AI grammar review (' . (string) ($analysis['milestone_name'] ?? 'submission') . ')';
    $lines[] = 'Verdict: ' . (string) ($analysis['verdict'] ?? 'needs_revision');
    if (!empty($analysis['summary'])) {
        $lines[] = trim((string) $analysis['summary']);
    }
    $lines[] = '';
    $lines[] = 'Please revise the following:';
    foreach (($analysis['notes'] ?? []) as $i => $note) {
        if (!is_array($note)) {
            continue;
        }
        $n = $i + 1;
        $lines[] = $n . '. ' . trim((string) ($note['issue'] ?? ''));
        if (!empty($note['suggestion'])) {
            $lines[] = '   Suggestion: ' . trim((string) $note['suggestion']);
        }
        if (!empty($note['example'])) {
            $lines[] = '   Example: ' . trim((string) $note['example']);
        }
    }

    return trim(implode("\n", $lines));
}
