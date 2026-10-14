<?php
/**
 * Adviser AI-assisted document observations (not academic decisions)
 * before Approve / Request Revision.
 */
declare(strict_types=1);

require_once ROOT_PATH . '/includes/openai-client.php';

function rpLatestAiAnalysisForUpdate(PDO $crad, int $progressUpdateId): ?array
{
    if ($progressUpdateId <= 0) {
        return null;
    }
    $stmt = $crad->prepare(
        "SELECT *
         FROM `crad_research_progress_ai_analyses`
         WHERE progress_update_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([$progressUpdateId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $decoded = json_decode((string) ($row['notes_json'] ?? '[]'), true);
    if (is_array($decoded) && array_is_list($decoded)) {
        $row['notes'] = $decoded;
        $row['analysis_type'] = 'full';
        $row['key_points'] = [];
        $row['sections'] = [];
        $row['style_observations'] = [];
        $row['issues'] = $decoded;
        $row['missing_information'] = [];
    } else {
        $row['notes'] = is_array($decoded['notes'] ?? null) ? $decoded['notes'] : [];
        $row['analysis_type'] = (string) ($decoded['analysis_type'] ?? 'full');
        $row['key_points'] = is_array($decoded['key_points'] ?? null) ? $decoded['key_points'] : [];
        $row['sections'] = is_array($decoded['sections'] ?? null) ? $decoded['sections'] : [];
        $row['style_observations'] = is_array($decoded['style_observations'] ?? null) ? $decoded['style_observations'] : [];
        $row['issues'] = is_array($decoded['issues'] ?? null) ? $decoded['issues'] : $row['notes'];
        $row['missing_information'] = is_array($decoded['missing_information'] ?? null) ? $decoded['missing_information'] : [];
    }
    unset($row['notes_json']);

    return $row;
}

function rpSaveAiAnalysis(PDO $crad, array $data): int
{
    $stmt = $crad->prepare(
        "INSERT INTO `crad_research_progress_ai_analyses` (
            progress_update_id, attachment_id, milestone_name, verdict, grammar_quality,
            summary, notes_json, source, analyzed_by, analyzed_by_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        (int) ($data['progress_update_id'] ?? 0),
        (int) ($data['attachment_id'] ?? 0),
        (string) ($data['milestone_name'] ?? ''),
        (string) ($data['verdict'] ?? 'advisory_only'),
        (string) ($data['grammar_quality'] ?? 'not_scored'),
        (string) ($data['summary'] ?? ''),
        json_encode([
            'analysis_type' => (string) ($data['analysis_type'] ?? 'full'),
            'notes' => is_array($data['notes'] ?? null) ? $data['notes'] : [],
            'key_points' => is_array($data['key_points'] ?? null) ? $data['key_points'] : [],
            'sections' => is_array($data['sections'] ?? null) ? $data['sections'] : [],
            'style_observations' => is_array($data['style_observations'] ?? null) ? $data['style_observations'] : [],
            'issues' => is_array($data['issues'] ?? null) ? $data['issues'] : [],
            'missing_information' => is_array($data['missing_information'] ?? null) ? $data['missing_information'] : [],
        ], JSON_UNESCAPED_UNICODE),
        (string) ($data['source'] ?? 'openai_gpt_4_1'),
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
function rpAnalyzeResearchDocument(string $text, string $milestoneName, string $fileName, string $analysisType = 'full'): array
{
    $text = trim($text);
    if ($text === '') {
        return ['ok' => false, 'message' => 'No readable text could be extracted. This analysis works best with a text-based PDF or DOCX; scanned images and legacy DOC files may not be readable.'];
    }

    if (!in_array($analysisType, ['summary', 'structure', 'style', 'full'], true)) {
        $analysisType = 'full';
    }
    $excerpt = rpTruncateAnalysisText($text, 14000);
    $openAi = rpAnalyzeWithOpenAi($excerpt, $milestoneName, $fileName, $analysisType);
    if (!empty($openAi['ok'])) {
        return $openAi;
    }

    $fallback = rpAnalyzeWithLanguageTool($excerpt, $milestoneName, $fileName, $analysisType);
    if (!empty($fallback['ok'])) {
        if (in_array($analysisType, ['summary', 'structure'], true)) {
            return [
                'ok' => false,
                'message' => 'Summary and structure analysis require the configured AI provider. The available language-check fallback can only provide writing and style observations.',
            ];
        }
        $fallback['requested_analysis_type'] = $analysisType;
        $fallback['analysis_type'] = 'style';
        $fallback['message'] = 'OpenAI GPT-4.1 was unavailable, so only a limited LanguageTool writing check completed. No AI summary, structure analysis, or academic decision was generated.';
        return $fallback;
    }

    return [
        'ok' => false,
        'message' => (string) ($openAi['message'] ?? $fallback['message'] ?? 'AI analysis could not be completed.'),
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
 * @return array<string, mixed>
 */
function rpAnalyzeWithOpenAi(string $text, string $milestoneName, string $fileName, string $analysisType = 'full'): array
{
    $result = smsOpenAiJsonCompletion(
        'You are an assistive academic research document analyst using OpenAI GPT-4.1. Return only evidence-based observations as JSON. Never approve, reject, grade, score, or decide a student submission.',
        rpBuildAnalysisPrompt($text, $milestoneName, $fileName, $analysisType),
        3000
    );
    if (empty($result['ok'])) {
        return ['ok' => false, 'message' => (string) ($result['message'] ?? 'OpenAI GPT-4.1 analysis is unavailable.')];
    }
    $parsed = rpParseAnalysisPayload((string) json_encode($result['data'], JSON_UNESCAPED_UNICODE));
    if ($parsed === null) {
        return ['ok' => false, 'message' => 'OpenAI returned analysis that could not be validated. Please retry.'];
    }
    $parsed['ok'] = true;
    $parsed['source'] = 'openai_gpt_4_1';
    $parsed['model'] = (string) ($result['model'] ?? 'gpt-4.1');
    return $parsed;
}

function rpBuildAnalysisPrompt(string $text, string $milestoneName, string $fileName, string $analysisType = 'full'): string
{
    $milestone = $milestoneName !== '' ? $milestoneName : 'research milestone';
    $focus = match ($analysisType) {
        'summary' => 'Prioritize a concise overview and main key points. Keep other observations brief.',
        'structure' => 'Prioritize detected headings, section order, and information that may not be apparent in the text.',
        'style' => 'Prioritize academic tone, clarity, sentence quality, and actionable writing observations.',
        default => 'Provide a balanced overview, key points, structure observations, writing feedback, and items for a human to verify.',
    };
    return <<<PROMPT
You are an assistive academic research document reviewer. Analyze the student research file for "{$milestone}" (filename: {$fileName}).
The selected analysis mode is "{$analysisType}". {$focus}
Your observations are advisory. Never approve, reject, grade, or make a final academic decision about the document.
Review only what can be inferred from the extracted text. Do not claim citation validity or research correctness without evidence.

Reply with JSON only. No markdown. Use this shape:
{
    "analysis_type": "{$analysisType}",
    "summary": "2-4 sentence neutral overview of the document",
    "key_points": ["main point 1", "main point 2"],
    "sections": [{"name":"Introduction", "present":true, "observation":"brief note", "suggestion":"optional improvement"}],
    "style_observations": [{"observation":"writing observation", "suggestion":"actionable suggestion"}],
    "issues": [{"issue":"area to review", "suggestion":"suggested check", "example":"optional short excerpt", "severity":"review|attention"}],
    "missing_information": [{"item":"information or section not apparent in the text", "why_it_matters":"brief research-oriented context", "suggestion":"what the human author may consider adding"}],
    "notes": [
    {
      "category": "style|structure|issue",
      "issue": "what is wrong",
      "suggestion": "what the student should change",
      "example": "optional short excerpt from the paper",
      "severity": "review|attention"
    }
  ]
}

Do not include an approval/rejection verdict, grade, score, or recommendation about whether the submission should pass. Do not infer that expected sections are mandatory unless the document context makes this clear. Describe potentially missing information as items for the author and human reviewer to verify.
Use neutral, specific language. Include evidence-based observations and do not invent content absent from the extracted document.

STUDENT DOCUMENT TEXT:
{$text}
PROMPT;
}

/**
 * @return string
 */
function rpAnalysisTextValue(mixed $value): string
{
    return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
}

/**
 * @return array<string, mixed>|null
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
    $summary = rpAnalysisTextValue($data['summary'] ?? null);
    if ($summary === '') {
        return null;
    }
    $verdict = 'advisory_only';
    $quality = 'not_scored';
    $analysisType = strtolower(rpAnalysisTextValue($data['analysis_type'] ?? 'full'));
    if (!in_array($analysisType, ['summary', 'structure', 'style', 'full'], true)) {
        $analysisType = 'full';
    }
    $keyPoints = array_values(array_filter(array_map(
        static fn($point): string => rpAnalysisTextValue($point),
        is_array($data['key_points'] ?? null) ? $data['key_points'] : []
    ), static fn(string $point): bool => $point !== ''));
    $sections = [];
    foreach (is_array($data['sections'] ?? null) ? $data['sections'] : [] as $section) {
        if (is_string($section)) {
            $section = ['name' => $section];
        }
        if (!is_array($section) || rpAnalysisTextValue($section['name'] ?? null) === '') {
            continue;
        }
        $sections[] = [
            'name' => rpAnalysisTextValue($section['name']),
            'present' => !array_key_exists('present', $section) || (bool) $section['present'],
            'observation' => rpAnalysisTextValue($section['observation'] ?? null),
            'suggestion' => rpAnalysisTextValue($section['suggestion'] ?? null),
        ];
    }
    $styleObservations = [];
    foreach (is_array($data['style_observations'] ?? null) ? $data['style_observations'] : [] as $observation) {
        if (is_string($observation)) {
            $observation = ['observation' => $observation];
        }
        if (!is_array($observation) || rpAnalysisTextValue($observation['observation'] ?? null) === '') {
            continue;
        }
        $styleObservations[] = [
            'observation' => rpAnalysisTextValue($observation['observation']),
            'suggestion' => rpAnalysisTextValue($observation['suggestion'] ?? null),
        ];
    }
    $notes = [];
    foreach (is_array($data['notes'] ?? null) ? $data['notes'] : [] as $note) {
        if (!is_array($note)) {
            continue;
        }
        $issue = rpAnalysisTextValue($note['issue'] ?? $note['problem'] ?? null);
        $suggestion = rpAnalysisTextValue($note['suggestion'] ?? $note['fix'] ?? null);
        if ($issue === '' && $suggestion === '') {
            continue;
        }
        $notes[] = [
            'category' => in_array(($note['category'] ?? ''), ['style', 'structure', 'issue'], true) ? (string) $note['category'] : 'issue',
            'issue' => $issue !== '' ? $issue : $suggestion,
            'suggestion' => $suggestion,
            'example' => rpAnalysisTextValue($note['example'] ?? $note['excerpt'] ?? null),
            'severity' => in_array(($note['severity'] ?? ''), ['review', 'attention'], true) ? (string) $note['severity'] : 'review',
        ];
    }
    $issues = [];
    foreach (is_array($data['issues'] ?? null) ? $data['issues'] : [] as $issue) {
        if (is_string($issue)) {
            $issue = ['issue' => $issue];
        }
        if (!is_array($issue) || rpAnalysisTextValue($issue['issue'] ?? null) === '') {
            continue;
        }
        $issues[] = [
            'issue' => rpAnalysisTextValue($issue['issue']),
            'suggestion' => rpAnalysisTextValue($issue['suggestion'] ?? null),
            'example' => rpAnalysisTextValue($issue['example'] ?? null),
            'severity' => in_array(($issue['severity'] ?? ''), ['review', 'attention'], true) ? (string) $issue['severity'] : 'review',
        ];
    }
    if ($issues === []) {
        $issues = $notes;
    }
    $missingInformation = [];
    foreach (is_array($data['missing_information'] ?? null) ? $data['missing_information'] : [] as $missing) {
        if (is_string($missing)) {
            $missing = ['item' => $missing];
        }
        if (!is_array($missing) || rpAnalysisTextValue($missing['item'] ?? null) === '') {
            continue;
        }
        $missingInformation[] = [
            'item' => rpAnalysisTextValue($missing['item']),
            'why_it_matters' => rpAnalysisTextValue($missing['why_it_matters'] ?? null),
            'suggestion' => rpAnalysisTextValue($missing['suggestion'] ?? null),
        ];
    }

    return [
        'analysis_type' => $analysisType,
        'verdict' => $verdict,
        'grammar_quality' => $quality,
        'summary' => $summary,
        'key_points' => $keyPoints,
        'sections' => $sections,
        'style_observations' => $styleObservations,
        'issues' => $issues,
        'missing_information' => $missingInformation,
        'notes' => $notes,
    ];
}

/**
 * @return array{ok: bool, verdict?: string, grammar_quality?: string, summary?: string, notes?: list<array<string,string>>, source?: string, message?: string}
 */
function rpAnalyzeWithLanguageTool(string $text, string $milestoneName, string $fileName, string $analysisType = 'full'): array
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
                'category' => 'style',
                'issue' => $message,
                'suggestion' => $suggestion !== '' ? $suggestion : 'Revise this sentence for correct academic English.',
                'example' => $context,
                'severity' => 'review',
            ];
            if (count($notes) >= 12) {
                break 2;
            }
        }
    }

    $wordCount = str_word_count($text);
    if ($wordCount < 80) {
        $notes[] = [
            'category' => 'structure',
            'issue' => 'The extracted text is brief for the selected ' . ($milestoneName !== '' ? $milestoneName : 'research') . ' submission.',
            'suggestion' => 'Confirm that the complete intended document was submitted; this length check is only an automated observation.',
            'example' => 'Readable words found: ' . $wordCount . ' in ' . $fileName,
            'severity' => 'attention',
        ];
    }
    if (preg_match('/\\b(asap|gonna|wanna|u r|idk|lol)\\b/i', $text)) {
        $notes[] = [
            'category' => 'style',
            'issue' => 'Informal or chat-style wording may be present in the manuscript.',
            'suggestion' => 'Review the highlighted wording and consider a formal academic alternative.',
            'example' => '',
            'severity' => 'review',
        ];
    }

    if ($notes === []) {
        $notes[] = [
            'category' => 'style',
            'issue' => 'No major grammar errors were detected in the extracted text.',
            'suggestion' => 'A human reviewer should still read the full document and assess it in context.',
            'example' => '',
            'severity' => 'review',
        ];
    }

    $summary = 'LanguageTool checked grammar and sentence-level patterns only. It does not generate a document summary or evaluate research structure.';
    $styleObservations = array_values(array_map(static fn(array $note): array => [
        'observation' => (string) ($note['issue'] ?? ''),
        'suggestion' => (string) ($note['suggestion'] ?? ''),
    ], array_filter($notes, static fn(array $note): bool => ($note['category'] ?? '') === 'style')));

    return [
        'ok' => true,
        'source' => 'grammar_engine',
        'analysis_type' => 'style',
        'verdict' => 'advisory_only',
        'grammar_quality' => 'not_scored',
        'summary' => $summary,
        'key_points' => [],
        'sections' => [],
        'style_observations' => $styleObservations,
        'issues' => $notes,
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
    $lines[] = 'AI-assisted observations (' . (string) ($analysis['milestone_name'] ?? 'submission') . ')';
    if (!empty($analysis['summary'])) {
        $lines[] = trim((string) $analysis['summary']);
    }
    $lines[] = '';
    $lines[] = 'Optional areas to review with the complete document:';
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
    foreach (($analysis['missing_information'] ?? []) as $missing) {
        if (!is_array($missing) || trim((string) ($missing['item'] ?? '')) === '') {
            continue;
        }
        $lines[] = 'Potentially missing information to verify: ' . trim((string) $missing['item']);
        if (!empty($missing['suggestion'])) {
            $lines[] = '   Suggestion: ' . trim((string) $missing['suggestion']);
        }
    }

    return trim(implode("\n", $lines));
}
