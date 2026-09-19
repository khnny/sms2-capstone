<?php
declare(strict_types=1);

function rscImageFont(bool $bold = false): ?string
{
    $files = $bold
        ? [
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\calibrib.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ]
        : [
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\calibri.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];
    foreach ($files as $file) {
        if (is_file($file)) {
            return $file;
        }
    }
    return null;
}

function rscImageText($im, int $x, int $y, string $text, int $size, $color, bool $bold = false, ?int $maxWidth = null): void
{
    $font = rscImageFont($bold);
    if ($font) {
        if ($maxWidth && $maxWidth > 40) {
            while ($size > 8 && ($box = imagettfbbox($size, 0, $font, $text)) && (abs($box[2] - $box[0]) > $maxWidth)) {
                $size--;
            }
        }
        imagettftext($im, $size, 0, $x, $y + $size, $color, $font, $text);
        return;
    }
    imagestring($im, $bold ? 5 : 3, $x, $y, $text, $color);
}

function rscImageCenter($im, int $x, int $w, int $y, string $text, int $size, $color, bool $bold = false): void
{
    $font = rscImageFont($bold);
    $width = 0;
    if ($font) {
        $box = imagettfbbox($size, 0, $font, $text);
        $width = abs($box[2] - $box[0]);
    } else {
        $width = strlen($text) * ($bold ? 9 : 7);
    }
    rscImageText($im, $x + (int) max(0, ($w - $width) / 2), $y, $text, $size, $color, $bold);
}

function rscImageFromDataUrl(string $dataUrl)
{
    if (!preg_match('#^data:image/(png|jpe?g);base64,(.+)$#i', $dataUrl, $m)) {
        return null;
    }
    $bin = base64_decode($m[2], true);
    if ($bin === false) {
        return null;
    }
    return @imagecreatefromstring($bin) ?: null;
}

function rscImageCell($im, int $x, int $y, int $w, int $h, $border, bool $fill = false, $bg = null): void
{
    if ($fill && $bg !== null) {
        imagefilledrectangle($im, $x, $y, $x + $w, $y + $h, $bg);
    }
    imagerectangle($im, $x, $y, $x + $w, $y + $h, $border);
}

function rscDrawFormCopy($im, array $row, int $left, int $top, int $width): int
{
    $black = imagecolorallocate($im, 17, 17, 17);
    $navy = imagecolorallocate($im, 30, 58, 138);
    $head = imagecolorallocate($im, 248, 250, 252);
    $gray = imagecolorallocate($im, 51, 65, 85);

    $x = $left;
    $y = $top;
    $meta = 'Leader Student No.: ' . trim((string) ($row['leader_student_no'] ?? ''))
        . '    Leader Group No.: ' . trim((string) ($row['leader_group_no'] ?? ''));
    rscImageText($im, $x + $width - 620, $y, $meta, 10, $gray, false, 620);
    $y += 22;

    $crestPath = ROOT_PATH . '/images/bcp-crest.png';
    if (is_file($crestPath)) {
        $crest = @imagecreatefrompng($crestPath);
        if ($crest) {
            imagecopyresampled($im, $crest, $x + 8, $y, 0, 0, 52, 60, imagesx($crest), imagesy($crest));
            imagedestroy($crest);
        }
    }
    rscImageCenter($im, $x, $width, $y, 'BESTLINK COLLEGE OF THE PHILIPPINES', 13, $black, true);
    rscImageCenter($im, $x, $width, $y + 20, '#1071 Brgy. Kaligayahan, Quirino Highway, Novaliches, Quezon City', 9, $gray);
    rscImageCenter($im, $x, $width, $y + 36, 'CENTER FOR RESEARCH AND DEVELOPMENT', 11, $black, true);
    rscImageCenter($im, $x, $width, $y + 54, 'Research Services Clearance', 12, $black, true);
    imageellipse($im, $x + $width - 36, $y + 28, 48, 48, $navy);
    rscImageCenter($im, $x + $width - 60, 48, $y + 22, 'CRD', 9, $navy, true);
    $y += 78;

    $col1 = (int) ($width * 0.18);
    $col2 = (int) ($width * 0.47);
    $col3 = (int) ($width * 0.15);
    $col4 = $width - $col1 - $col2 - $col3;
    $rh = 28;
    rscImageCell($im, $x, $y, $col1, $rh, $black, true, $head);
    rscImageText($im, $x + 6, $y + 7, 'Program', 10, $black, true);
    rscImageCell($im, $x + $col1, $y, $col2, $rh, $black);
    rscImageText($im, $x + $col1 + 6, $y + 7, (string) ($row['program'] ?? ''), 10, $black, false, $col2 - 12);
    rscImageCell($im, $x + $col1 + $col2, $y, $col3, $rh, $black, true, $head);
    rscImageText($im, $x + $col1 + $col2 + 6, $y + 7, 'Section', 10, $black, true);
    rscImageCell($im, $x + $col1 + $col2 + $col3, $y, $col4, $rh, $black);
    rscImageText($im, $x + $col1 + $col2 + $col3 + 6, $y + 7, (string) ($row['section'] ?? ''), 10, $black, false, $col4 - 12);
    $y += $rh;
    rscImageCell($im, $x, $y, $col1, $rh, $black, true, $head);
    rscImageText($im, $x + 6, $y + 7, 'Research Title', 10, $black, true);
    rscImageCell($im, $x + $col1, $y, $width - $col1, $rh, $black);
    rscImageText($im, $x + $col1 + 6, $y + 7, (string) ($row['research_title'] ?? ''), 10, $black, false, $width - $col1 - 12);
    $y += $rh;

    $m1 = (int) ($width * 0.22);
    $m2 = (int) ($width * 0.26);
    $m3 = (int) ($width * 0.32);
    $m4 = $width - $m1 - $m2 - $m3;
    $cx = $x;
    foreach (['Last Name', 'First Name', rscOrColumnLabel((string) ($row['research_stage'] ?? 'research_1')), 'Remarks'] as $i => $label) {
        $w = [$m1, $m2, $m3, $m4][$i];
        rscImageCell($im, $cx, $y, $w, $rh, $black, true, $head);
        rscImageText($im, $cx + 6, $y + 7, $label, 10, $black, true, $w - 10);
        $cx += $w;
    }
    $y += $rh;
    $members = rscDedupeMembers(json_decode((string) ($row['members_json'] ?? ''), true) ?: []);
    if ($members === []) {
        $members = [['name' => '', 'or_number' => '']];
    }
    $fallbackOr = trim((string) ($row['or_number'] ?? ''));
    foreach ($members as $member) {
        $split = rscSplitName((string) ($member['name'] ?? ''));
        $or = rscExtractOrNumber((string) ($member['or_number'] ?? '')) ?: $fallbackOr;
        $vals = [$split['last'], $split['first'], $or, trim((string) ($row['payment_remarks'] ?? '')) ?: 'HMA'];
        $cx = $x;
        foreach ([$m1, $m2, $m3, $m4] as $i => $w) {
            rscImageCell($im, $cx, $y, $w, $rh, $black);
            rscImageText($im, $cx + 6, $y + 7, $vals[$i], 10, $black, false, $w - 10);
            $cx += $w;
        }
        $y += $rh;
    }

    $t1 = (int) ($width * 0.48);
    $t2 = (int) ($width * 0.34);
    $t3 = $width - $t1 - $t2;
    foreach (['Task', 'Name and Signature', 'Date'] as $i => $label) {
        $w = [$t1, $t2, $t3][$i];
        $cx = $x + ($i === 1 ? $t1 : 0) + ($i === 2 ? $t1 + $t2 : 0);
        rscImageCell($im, $cx, $y, $w, $rh, $black, true, $head);
        rscImageText($im, $cx + 6, $y + 7, $label, 10, $black, true, $w - 10);
    }
    $y += $rh;

    $adviserDate = rscFormatDate($row['adviser_signed_at'] ?? null);
    $misDate = rscFormatDate(rscDateIfSigned((string) ($row['mis_signature'] ?? ''), $row['mis_verified_at'] ?? null, $row['uploaded_at'] ?? null));
    $aaDate = rscFormatDate(rscDateIfSigned((string) ($row['aa_signature'] ?? ''), $row['aa_verified_at'] ?? null, $row['uploaded_at'] ?? null));
    $cradDate = rscFormatDate($row['crad_signed_at'] ?? null);
    $tasks = [
        ['1. Submitted OR Copy to Research Adviser', 'Adviser: ' . trim((string) ($row['adviser_name'] ?? '')), $adviserDate, (string) ($row['adviser_signature'] ?? ''), 56],
        ['2. OR no. Verified by Accounting / MIS', 'MIS:', $misDate, (string) ($row['mis_signature'] ?? ''), 40],
        ['3. Turnitin username and Password Released by AAI / AA', 'AA:', $aaDate, (string) ($row['aa_signature'] ?? ''), 40],
        [
            '4. Research Services Personnel Assignment',
            'CRAD: ' . trim((string) ($row['crad_name'] ?? '')),
            $cradDate,
            (string) ($row['crad_signature'] ?? ''),
            72,
            'Grammarian: ' . trim((string) ($row['grammarian_name'] ?? ''))
                . "\nStatistician / Technical Adviser: " . trim((string) ($row['adviser_name'] ?? $row['statistician_name'] ?? '')),
        ],
    ];
    foreach ($tasks as $task) {
        $leftText = (string) $task[0];
        $midText = (string) $task[1];
        $date = (string) $task[2];
        $sig = (string) $task[3];
        $th = (int) $task[4];
        $extra = (string) ($task[5] ?? '');
        rscImageCell($im, $x, $y, $t1, $th, $black);
        rscImageText($im, $x + 6, $y + 8, $leftText, 9, $black, false, $t1 - 12);
        if ($extra !== '') {
            $lines = preg_split("/\n/", $extra) ?: [$extra];
            $lineY = $y + 26;
            foreach ($lines as $line) {
                rscImageText($im, $x + 6, $lineY, trim($line), 9, $black, true, $t1 - 12);
                $lineY += 16;
            }
        }
        rscImageCell($im, $x + $t1, $y, $t2, $th, $black);
        rscImageText($im, $x + $t1 + 6, $y + 8, $midText, 9, $black, false, $t2 - 12);
        $sigIm = $sig !== '' ? rscImageFromDataUrl($sig) : null;
        if ($sigIm) {
            $sw = min(150, imagesx($sigIm));
            $sh = (int) (imagesy($sigIm) * ($sw / max(1, imagesx($sigIm))));
            $sh = min(28, $sh);
            imagecopyresampled($im, $sigIm, $x + $t1 + 8, $y + $th - $sh - 4, 0, 0, $sw, $sh, imagesx($sigIm), imagesy($sigIm));
            imagedestroy($sigIm);
        }
        rscImageCell($im, $x + $t1 + $t2, $y, $t3, $th, $black);
        $dateLines = [];
        if ($date !== '') {
            $split = preg_split('/\s+\/\s+/', $date);
            $dateLines = ($split !== false && $split !== []) ? $split : [$date];
        }
        $dateY = $y + 8;
        foreach ($dateLines as $dateLine) {
            rscImageText($im, $x + $t1 + $t2 + 6, $dateY, (string) $dateLine, 9, $black, false, $t3 - 10);
            $dateY += 14;
        }
        $y += $th;
    }

    return $y + 10;
}

function rscBuildFormPng(array $row): string
{
    if (!function_exists('imagecreatetruecolor')) {
        throw new RuntimeException('PNG download needs the GD image library.');
    }
    $width = 1240;
    $pad = 28;
    $inner = $width - ($pad * 2);
    $im = imagecreatetruecolor($width, 1900);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, $width, 1900, $white);
    $y = rscDrawFormCopy($im, $row, $pad, $pad, $inner);
    $y = rscDrawFormCopy($im, $row, $pad, $y + 8, $inner);
    $cropped = imagecreatetruecolor($width, $y + $pad);
    $white2 = imagecolorallocate($cropped, 255, 255, 255);
    imagefilledrectangle($cropped, 0, 0, $width, $y + $pad, $white2);
    imagecopy($cropped, $im, 0, 0, 0, 0, $width, $y + $pad);
    imagedestroy($im);

    ob_start();
    imagepng($cropped);
    imagedestroy($cropped);
    return (string) ob_get_clean();
}

function rscCanDownloadFormImage(PDO $crad, array $row): bool
{
    $role = getCurrentUserRoleKey();
    if ($role === 'adviser') {
        return rscAdviserCanAccess($row);
    }
    if ($role === 'student') {
        return rscStudentCanAccess($crad, $row);
    }
    return rscCanManageAsCrad();
}

function rscSendFormPngDownload(PDO $crad, array $row): void
{
    $fresh = rscRefreshExisting($crad, $row) ?: $row;
    $png = rscBuildFormPng($fresh);
    $crad->prepare('UPDATE research_services_clearances SET export_hash = ? WHERE id = ?')
        ->execute([hash('sha256', $png), (int) $fresh['id']]);
    $group = preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($fresh['leader_group_no'] ?? 'clearance')) ?: 'clearance';
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="research-clearance-' . $group . '.png"');
    header('Content-Length: ' . strlen($png));
    header('Cache-Control: no-store');
    echo $png;
}

function rscSignatureCellBoxes(array $row, int $pageWidth, int $pad): array
{
    $width = max(200, $pageWidth - ($pad * 2));
    $x = $pad;
    $y = $pad + 22 + 78 + 28 + 28 + 28;
    $members = rscDedupeMembers(json_decode((string) ($row['members_json'] ?? ''), true) ?: []);
    $y += 28 * max(1, count($members));
    $y += 28;
    $t1 = (int) ($width * 0.48);
    $t2 = (int) ($width * 0.34);
    $misY = $y + 56;
    $aaY = $misY + 40;
    return [
        'mis' => ['x' => $x + $t1 + 8, 'y' => $misY + 14, 'w' => max(20, $t2 - 16), 'h' => 22],
        'aa' => ['x' => $x + $t1 + 8, 'y' => $aaY + 14, 'w' => max(20, $t2 - 16), 'h' => 22],
    ];
}

function rscInkRatio($im, int $x, int $y, int $w, int $h): float
{
    $maxX = imagesx($im);
    $maxY = imagesy($im);
    $x = max(0, $x);
    $y = max(0, $y);
    $w = min($w, $maxX - $x);
    $h = min($h, $maxY - $y);
    if ($w < 8 || $h < 8) {
        return 0.0;
    }
    $dark = 0;
    $total = 0;
    $step = 1;
    for ($yy = $y; $yy < $y + $h; $yy += $step) {
        for ($xx = $x; $xx < $x + $w; $xx += $step) {
            $rgb = imagecolorat($im, $xx, $yy);
            $avg = ((($rgb >> 16) & 255) + (($rgb >> 8) & 255) + ($rgb & 255)) / 3;
            $total++;
            if ($avg < 155) {
                $dark++;
            }
        }
    }
    return $total > 0 ? $dark / $total : 0.0;
}

function rscCropHasInk($im, int $x, int $y, int $w, int $h): bool
{
    return rscInkRatio($im, $x, $y, $w, $h) >= 0.004;
}

function rscPixelLuma(int $rgb): float
{
    return ((($rgb >> 16) & 255) * 0.299) + ((($rgb >> 8) & 255) * 0.587) + (($rgb & 255) * 0.114);
}

function rscRemoveFormLines($im): void
{
    $w = imagesx($im);
    $h = imagesy($im);
    $white = imagecolorallocate($im, 255, 255, 255);
    $isRule = [];
    for ($y = 0; $y < $h; $y++) {
        $run = 0;
        $maxRun = 0;
        $marked = 0;
        for ($x = 0; $x < $w; $x++) {
            if (rscPixelLuma((int) imagecolorat($im, $x, $y)) < 232) {
                $run++;
                $marked++;
                $maxRun = max($maxRun, $run);
            } else {
                $run = 0;
            }
        }
        $isRule[$y] = $w >= 16 && $maxRun >= (int) ($w * 0.38) && $marked >= (int) ($w * 0.26);
    }
    $groups = [];
    $start = null;
    for ($y = 0; $y <= $h; $y++) {
        $on = $y < $h && !empty($isRule[$y]);
        if ($on && $start === null) {
            $start = $y;
        }
        if (!$on && $start !== null) {
            $groups[] = [$start, $y - 1];
            $start = null;
        }
    }
    foreach ($groups as $group) {
        $a = $group[0];
        $b = $group[1];
        $gh = $b - $a + 1;
        $mid = ($a + $b) / 2;
        if ($gh <= 7 && ($mid <= $h * 0.28 || $mid >= $h * 0.42)) {
            for ($y = $a; $y <= $b; $y++) {
                imageline($im, 0, $y, $w - 1, $y, $white);
            }
        }
    }
    for ($x = 0; $x < $w; $x++) {
        $run = 0;
        $maxRun = 0;
        $dark = 0;
        for ($y = 0; $y < $h; $y++) {
            if (rscPixelLuma((int) imagecolorat($im, $x, $y)) < 220) {
                $run++;
                $dark++;
                $maxRun = max($maxRun, $run);
            } else {
                $run = 0;
            }
        }
        if ($h >= 16 && $maxRun >= (int) ($h * 0.62) && $dark >= (int) ($h * 0.50)) {
            imageline($im, $x, 0, $x, $h - 1, $white);
        }
    }
}

function rscInkBounds($im): ?array
{
    $w = imagesx($im);
    $h = imagesy($im);
    $minX = $w;
    $minY = $h;
    $maxX = -1;
    $maxY = -1;
    $count = 0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            if (rscPixelLuma((int) imagecolorat($im, $x, $y)) < 128) {
                $count++;
                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }
    }
    if ($count < 18 || $maxX < 0) {
        return null;
    }
    $bw = $maxX - $minX + 1;
    $bh = $maxY - $minY + 1;
    if ($bh <= 4 && $bw >= max(24, (int) ($w * 0.42))) {
        return null;
    }
    if ($bw <= 3 && $bh >= max(16, (int) ($h * 0.42))) {
        return null;
    }
    return ['x' => $minX, 'y' => $minY, 'w' => $bw, 'h' => $bh, 'ink' => $count];
}

function rscIsolateSignatureImage($src, int $x, int $y, int $w, int $h): string
{
    $maxX = imagesx($src);
    $maxY = imagesy($src);
    $x = max(0, min($x, $maxX - 2));
    $y = max(0, min($y, $maxY - 2));
    $w = max(8, min($w, $maxX - $x));
    $h = max(8, min($h, $maxY - $y));
    $crop = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($crop, 255, 255, 255);
    imagefilledrectangle($crop, 0, 0, $w, $h, $white);
    imagecopy($crop, $src, 0, 0, $x, $y, $w, $h);
    rscRemoveFormLines($crop);
    $box = rscInkBounds($crop);
    if (!$box) {
        imagedestroy($crop);
        return '';
    }
    $pad = 2;
    $outW = $box['w'] + ($pad * 2);
    $outH = $box['h'] + ($pad * 2);
    $out = imagecreatetruecolor($outW, $outH);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $clear = imagecolorallocatealpha($out, 255, 255, 255, 127);
    imagefilledrectangle($out, 0, 0, $outW, $outH, $clear);
    for ($yy = 0; $yy < $box['h']; $yy++) {
        for ($xx = 0; $xx < $box['w']; $xx++) {
            $rgb = (int) imagecolorat($crop, $box['x'] + $xx, $box['y'] + $yy);
            if (rscPixelLuma($rgb) >= 132) {
                continue;
            }
            $color = imagecolorallocatealpha($out, ($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255, 0);
            imagesetpixel($out, $xx + $pad, $yy + $pad, $color);
        }
    }
    imagedestroy($crop);
    ob_start();
    imagepng($out);
    $bin = (string) ob_get_clean();
    imagedestroy($out);
    return $bin !== '' ? ('data:image/png;base64,' . base64_encode($bin)) : '';
}

function rscCleanSignatureDataUrl(string $dataUrl): string
{
    $dataUrl = trim($dataUrl);
    if ($dataUrl === '' || !preg_match('#^data:image/[^;]+;base64,(.+)$#s', $dataUrl, $m)) {
        return '';
    }
    $bin = base64_decode($m[1], true);
    if ($bin === false || $bin === '') {
        return '';
    }
    $im = @imagecreatefromstring($bin);
    if (!$im) {
        return '';
    }
    $clean = rscIsolateSignatureImage($im, 0, 0, imagesx($im), imagesy($im));
    imagedestroy($im);
    return $clean;
}

function rscCropToDataUrl($im, int $x, int $y, int $w, int $h): string
{
    return rscIsolateSignatureImage($im, $x, $y, $w, $h);
}

function rscBestInkBand($im, int $x, int $w, int $centerY, int $h, int $search): array
{
    $bestY = $centerY;
    $bestInk = rscInkRatio($im, $x, $centerY, $w, $h);
    for ($y = $centerY - $search; $y <= $centerY + $search; $y += 4) {
        $ink = rscInkRatio($im, $x, $y, $w, $h);
        if ($ink > $bestInk) {
            $bestInk = $ink;
            $bestY = $y;
        }
    }
    return ['y' => $bestY, 'ink' => $bestInk];
}

function rscExtractPhysicalSignatures(string $path, array $row): array
{
    $empty = ['mis' => '', 'aa' => ''];
    if ($path === '' || !is_file($path) || !function_exists('imagecreatefromstring')) {
        return $empty;
    }
    $bin = @file_get_contents($path);
    $src = $bin !== false ? @imagecreatefromstring($bin) : null;
    if (!$src) {
        return $empty;
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $copyH = (int) max(200, round($h * 0.5));
    $x = (int) ($w * 0.54);
    $sigW = (int) ($w * 0.22);
    $bandH = max(28, (int) ($copyH * 0.10));
    $search = max(16, (int) ($copyH * 0.06));

    $copies = [0];
    if ($h > ($copyH + 80)) {
        $copies[] = $copyH;
    }

    $found = $empty;
    foreach ($copies as $top) {
        $misCenter = $top + (int) ($copyH * 0.60);
        $aaCenter = $top + (int) ($copyH * 0.70);
        $mis = rscBestInkBand($src, $x, $sigW, $misCenter, $bandH, $search);
        $aa = rscBestInkBand($src, $x, $sigW, $aaCenter, $bandH, $search);
        if ($found['mis'] === '' && $mis['ink'] >= 0.004) {
            $found['mis'] = rscCropToDataUrl($src, $x, (int) $mis['y'], $sigW, $bandH);
        }
        if ($found['aa'] === '' && $aa['ink'] >= 0.004) {
            $found['aa'] = rscCropToDataUrl($src, $x, (int) $aa['y'], $sigW, $bandH);
        }
        if ($found['mis'] !== '' && $found['aa'] !== '') {
            break;
        }
    }

    imagedestroy($src);
    return $found;
}
