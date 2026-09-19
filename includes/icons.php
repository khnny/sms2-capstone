<?php
/**
 * Tabler Icons helper — maps legacy Font Awesome names to Tabler (tabler.io/icons).
 */
if (!function_exists('smsTablerIconName')) {
    function smsTablerIconName(string $icon): string
    {
        $icon = trim($icon);
        $icon = preg_replace('/^(fa[srb]?|fa-solid|fa-regular|fa-brands)\s+fa-/', 'fa-', $icon);
        $icon = preg_replace('/^(fa[srb]?|fa-solid|fa-regular|fa-brands)\s+/', '', $icon);
        $icon = preg_replace('/^fa-/', '', $icon);
        $icon = trim($icon);

        static $map = [
            'shield-alt' => 'shield',
            'shield-halved' => 'shield',
            'sign-in-alt' => 'login',
            'sign-out-alt' => 'logout',
            'right-to-bracket' => 'login',
            'th-large' => 'layout-grid',
            'tachometer-alt' => 'gauge',
            'sliders-h' => 'adjustments-horizontal',
            'cloud-upload-alt' => 'cloud-upload',
            'check-double' => 'checks',
            'layer-group' => 'stack-2',
            'stream' => 'list',
            'exchange-alt' => 'switch-horizontal',
            'magic' => 'wand',
            'id-badge' => 'badge',
            'phone-alt' => 'phone',
            'file-signature' => 'signature',
            'stamp' => 'rubber-stamp',
            'scroll' => 'scroll',
            'user-tie' => 'tie',
            'hand-holding-usd' => 'coins',
            'file-alt' => 'file-text',
            'file-lines' => 'file-text',
            'user-friends' => 'users-group',
            'paper-plane' => 'send',
            'star-half-alt' => 'star-half-filled',
            'user-graduate' => 'school',
            'peso-sign' => 'currency-peso',
            'file-invoice-dollar' => 'file-invoice',
            'chalkboard-teacher' => 'chalkboard',
            'hand-holding-heart' => 'heart-handshake',
            'users-cog' => 'user-cog',
            'user-cog' => 'user-cog',
            'times-circle' => 'circle-x',
            'calendar-times' => 'calendar-x',
            'check-square' => 'square-check',
            'search-dollar' => 'report-money',
            'user-shield' => 'shield-lock',
            'crown' => 'crown',
            'fire' => 'flame',
            'poll' => 'chart-bar',
            'gavel' => 'gavel',
            'map-marker-alt' => 'map-pin',
            'arrow-up' => 'arrow-up',
            'arrow-down' => 'arrow-down',
            'user-clock' => 'clock-hour-4',
            'envelope-open' => 'mail-opened',
            'envelope-open-text' => 'mail',
            'info-circle' => 'info-circle',
            'exclamation-triangle' => 'alert-triangle',
            'exclamation-circle' => 'alert-circle',
            'spinner' => 'loader-2',
            'hourglass-half' => 'hourglass',
            'file-check' => 'file-check',
            'pause' => 'player-pause',
            'play' => 'player-play',
            'minus-circle' => 'circle-minus',
            'times' => 'x',
            'xmark' => 'x',
            'arrow-right' => 'arrow-right',
            'bars' => 'menu-2',
            'search-minus' => 'zoom-out',
            'user-circle' => 'user-circle',
            'bell-slash' => 'bell-off',
            'mobile-alt' => 'device-mobile',
            'project-diagram' => 'topology',
            'comments' => 'messages',
            'bullhorn' => 'speakerphone',
            'folder-open' => 'folder-open',
            'calendar-alt' => 'calendar',
            'calendar-check' => 'calendar-check',
            'book-open' => 'book',
            'building-columns' => 'building',
            'circle-check' => 'circle-check',
            'id-card' => 'id',
            'toggle-on' => 'toggle-right',
            'robot' => 'robot',
            'university' => 'building-bank',
            'save' => 'device-floppy',
            'file-export' => 'file-export',
            'download' => 'download',
            'upload' => 'upload',
            'file-upload' => 'upload',
            'arrow-left' => 'arrow-left',
            'chevron-down' => 'chevron-down',
            'chevron-right' => 'chevron-right',
            'eye-slash' => 'eye-off',
            'home' => 'home',
            'circle' => 'circle',
            'check-circle' => 'circle-check',
            'clock' => 'clock',
            'print' => 'printer',
            'ellipsis-v' => 'dots-vertical',
            'ellipsis-h' => 'dots',
            'cog' => 'settings',
            'wrench' => 'tool',
            'trash-alt' => 'trash',
            'trash' => 'trash',
            'edit' => 'edit',
            'pen' => 'pencil',
            'pencil' => 'pencil',
            'undo' => 'arrow-back-up',
            'user-lock' => 'lock',
            'user-check' => 'user-check',
            'user-slash' => 'user-off',
            'user-off' => 'user-off',
            'user-plus' => 'user-plus',
            'user-x' => 'user-x',
            'user-minus' => 'user-minus',
            'list-alt' => 'list',
            'plus-circle' => 'circle-plus',
            'plus' => 'plus',
            'minus-circle' => 'circle-minus',
            'archive' => 'archive',
            'bookmark' => 'bookmark',
            'sync-alt' => 'refresh',
            'redo' => 'refresh',
            'filter' => 'filter',
            'sort' => 'arrows-sort',
            'external-link-alt' => 'external-link',
            'link' => 'link',
            'copy' => 'copy',
            'question-circle' => 'help-circle',
            'ban' => 'ban',
            'unlock' => 'lock-open',
            'microscope' => 'microscope',
            'flask' => 'flask',
            'laptop' => 'device-laptop',
            'chart-bar' => 'chart-bar',
            'chart-line' => 'chart-line',
            'chart-pie' => 'chart-pie',
            'award' => 'award',
            'credit-card' => 'credit-card',
            'wallet' => 'wallet',
            'receipt' => 'receipt',
            'qrcode' => 'qrcode',
            'globe' => 'world',
            'database' => 'database',
            'history' => 'history',
            'tasks' => 'checklist',
            'clipboard-list' => 'clipboard-list',
            'clipboard-check' => 'clipboard-check',
            'star' => 'star',
            'sun' => 'sun',
            'moon' => 'moon',
            'envelope' => 'mail',
            'inbox' => 'inbox',
            'bell' => 'bell',
            'search' => 'search',
            'user' => 'user',
            'users' => 'users',
            'key' => 'key',
            'lock' => 'lock',
            'eye' => 'eye',
            'check' => 'check',
            'book' => 'book',
            'folder' => 'folder',
            'file' => 'file',
            'calendar' => 'calendar',
            'graduation-cap' => 'school',
        ];

        if (isset($map[$icon])) {
            return $map[$icon];
        }

        return str_replace('_', '-', $icon);
    }
}

if (!function_exists('smsIcon')) {
    /**
     * Render a Tabler icon element.
     *
     * @param string $icon Font Awesome-style name (fa-user), bare name (user), or Tabler name (ti-user)
     * @param array<string, mixed> $attrs HTML attributes (class, aria-hidden, etc.)
     */
    function smsIcon(string $icon, array $attrs = []): string
    {
        $icon = trim($icon);
        if (str_starts_with($icon, 'ti-')) {
            $name = substr($icon, 3);
        } elseif (str_starts_with($icon, 'ti ')) {
            $parts = preg_split('/\s+/', $icon) ?: [];
            $name = '';
            foreach ($parts as $part) {
                if (str_starts_with($part, 'ti-')) {
                    $name = substr($part, 3);
                    break;
                }
            }
            if ($name === '') {
                $name = smsTablerIconName($icon);
            }
        } else {
            $name = smsTablerIconName($icon);
        }

        $classes = ['ti', 'ti-' . $name];
        if (!empty($attrs['class'])) {
            $classes[] = trim((string) $attrs['class']);
            unset($attrs['class']);
        }

        $attrStr = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $attrStr .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
                continue;
            }
            $attrStr .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<i class="' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') . '"' . $attrStr . '></i>';
    }
}

if (!function_exists('smsIconClass')) {
    function smsIconClass(string $icon, string $extraClass = ''): string
    {
        $name = smsTablerIconName($icon);
        $classes = trim('ti ti-' . $name . ' ' . $extraClass);
        return $classes;
    }
}
