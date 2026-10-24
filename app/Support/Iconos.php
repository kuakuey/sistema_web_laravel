<?php

namespace App\Support;

class Iconos
{
    /**
     * Normaliza nombres legacy (bi-*) y alias a la clave del mapa SVG.
     */
    public static function clave(?string $nombre): string
    {
        $nombre = strtolower(trim((string) $nombre));
        $nombre = preg_replace('/^bi\s+/', '', $nombre) ?? $nombre;
        $nombre = preg_replace('/^bi-/', '', $nombre) ?? $nombre;

        $alias = [
            '4' => 'grid',
            'grid-1x2-fill' => 'app-grid',
            'plus-lg' => 'plus',
            'x-lg' => 'x',
            'check2' => 'check',
            'check-lg' => 'check',
            'check2-circle' => 'check-circle',
            'arrow-counterclockwise' => 'rotate-ccw',
            'box-arrow-right' => 'log-out',
            'box-arrow-in-right' => 'log-in',
            'moon-stars' => 'moon',
            'file-earmark-pdf' => 'file-pdf',
            'file-earmark-excel' => 'file-excel',
            'file-earmark-text' => 'file-text',
            'file-earmark-bar-graph' => 'chart',
            'file-earmark-arrow-up' => 'upload',
            'cloud-upload' => 'upload',
            'cash-stack' => 'cash',
            'wallet2' => 'wallet',
            'calendar3' => 'calendar',
            'calendar-event' => 'calendar',
            'calendar-x' => 'calendar-x',
            'chat-heart' => 'heart',
            'diagram-3' => 'diagram',
            'database-gear' => 'database',
            'shield-check' => 'shield',
            'journal-text' => 'journal',
            'list-ul' => 'list',
            'person-plus' => 'user-plus',
            'person-check' => 'user-check',
            'person' => 'user',
            'people' => 'users',
            'house-heart' => 'home-heart',
            'house' => 'home',
            'geo-alt' => 'map-pin',
            'exclamation-triangle' => 'warning',
            'exclamation-circle' => 'alert',
            'info-circle' => 'info',
            'x-circle' => 'x-circle',
            'hourglass-split' => 'hourglass',
            'eye-slash' => 'eye-off',
            'mortarboard' => 'graduation',
            'zoom-in' => 'zoom-in',
            'zoom-out' => 'zoom-out',
        ];

        return $alias[$nombre] ?? $nombre;
    }

    /**
     * @return array<string, string> path d / inner SVG markup (paths only)
     */
    public static function paths(): array
    {
        return [
            'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'x' => '<path d="M18 6 6 18M6 6l12 12"/>',
            'x-circle' => '<circle cx="12" cy="12" r="9"/><path d="m15 9-6 6M9 9l6 6"/>',
            'check' => '<path d="M20 6 9 17l-5-5"/>',
            'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
            'trash' => '<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>',
            'funnel' => '<path d="M4 4h16l-6 7v6l-4 2v-8L4 4z"/>',
            'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
            'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
            'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
            'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
            'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
            'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 20a8 8 0 0 1 16 0"/>',
            'users' => '<circle cx="9" cy="8" r="3.5"/><circle cx="17" cy="9" r="2.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M14 20a5 5 0 0 1 7.5-4.3"/>',
            'user-plus' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M19 8v6M16 11h6"/>',
            'user-check' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 11l2 2 4-4"/>',
            'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off' => '<path d="M3 3l18 18M10.7 10.7a2.5 2.5 0 0 0 3.6 3.6M9.9 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.1 6.1A17 17 0 0 0 2 12s3.5 7 10 7a10 10 0 0 0 4.1-.9"/>',
            'moon' => '<path d="M21 14.5A8.5 8.5 0 1 1 9.5 3 7 7 0 0 0 21 14.5z"/>',
            'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
            'log-out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'log-in' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
            'warning' => '<path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
            'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
            'rotate-ccw' => '<path d="M1 4v6h6"/><path d="M3.5 15a9 9 0 1 0 .8-8.4L1 10"/>',
            'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
            'download' => '<path d="M12 3v12M7 10l5 5 5-5M5 21h14"/>',
            'upload' => '<path d="M12 21V9M7 14l5-5 5 5M5 3h14"/>',
            'printer' => '<path d="M6 9V3h12v6"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/>',
            'key' => '<circle cx="8" cy="15" r="4"/><path d="m12 11 7-7 3 3-1.5 1.5L18 6l-2 2"/>',
            'eraser' => '<path d="m7 21-4.3-4.3a1 1 0 0 1 0-1.4l9.6-9.6a1 1 0 0 1 1.4 0l4.3 4.3a1 1 0 0 1 0 1.4L13 21H7zM5 15l5 5"/>',
            'circle' => '<circle cx="12" cy="12" r="9"/>',
            'app-grid' => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>',
            'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            'book' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
            'graduation' => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1 3 3 6 3s6-2 6-3v-5"/>',
            'droplet' => '<path d="M12 2.7S6 9 6 13.5a6 6 0 0 0 12 0C18 9 12 2.7 12 2.7z"/>',
            'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 13h18"/>',
            'cash' => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/>',
            'wallet' => '<path d="M2 7a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1H4a2 2 0 0 0 0 4h16v3a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7z"/><circle cx="17" cy="12" r="1"/>',
            'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
            'calendar-x' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18M10 15l4 4M14 15l-4 4"/>',
            'heart' => '<path d="M12 21s-7-4.4-9.5-8.2C.4 9.6 2.2 5.5 6 5.5c2 0 3.4 1.2 4 2 .6-.8 2-2 4-2 3.8 0 5.6 4.1 3.5 7.3C19 16.6 12 21 12 21z"/>',
            'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'diagram' => '<circle cx="12" cy="5" r="2.5"/><circle cx="5" cy="18" r="2.5"/><circle cx="19" cy="18" r="2.5"/><path d="M12 7.5v3.5M12 11 5.8 16M12 11l6.2 5"/>',
            'sliders' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
            'map' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/>',
            'map-pin' => '<path d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
            'home' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/>',
            'home-heart' => '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M12 18s-2.5-1.5-3.2-2.8A2.2 2.2 0 0 1 12 12.2a2.2 2.2 0 0 1 3.2 3C14.5 16.5 12 18 12 18z"/>',
            'shield' => '<path d="M12 3 4 6v6c0 5 3.4 8.4 8 9.5 4.6-1.1 8-4.5 8-9.5V6l-8-3z"/><path d="m9 12 2 2 4-4"/>',
            'database' => '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
            'journal' => '<path d="M6 3h11a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M8 7h8M8 11h8M8 15h5"/>',
            'file-text' => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>',
            'file-pdf' => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 15h1.5a1.5 1.5 0 0 0 0-3H9v6M13 18v-6h1.2a1.8 1.8 0 0 1 0 3.6H13"/>',
            'file-excel' => '<path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 17l3-4 3 4M12 13v4"/>',
            'hourglass' => '<path d="M6 2h12M6 22h12M8 2v4l4 4 4-4V2M8 22v-4l4-4 4 4v4"/>',
            'whatsapp' => '<path d="M20.5 11.5A8.5 8.5 0 0 1 7.2 18.8L4 20l1.3-3A8.5 8.5 0 1 1 20.5 11.5z"/><path d="M9.5 9.2c.3-.3.7-.3.9 0l.7 1c.2.2.1.6-.1.8l-.4.4a6 6 0 0 0 2.6 2.6l.4-.4c.2-.2.6-.3.8-.1l1 .7c.3.2.3.6 0 .9l-.6.6c-.3.3-.8.4-1.2.2A9 9 0 0 1 8.7 11c-.2-.4-.1-.9.2-1.2z"/>',
            'zoom-in' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>',
            'zoom-out' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5M8 11h6"/>',
        ];
    }

    public static function svg(string $nombre, string $class = ''): string
    {
        $clave = self::clave($nombre);
        $inner = self::paths()[$clave] ?? self::paths()['circle'];
        $classAttr = trim($class) !== '' ? ' class="'.e(trim($class)).'"' : '';

        return '<svg'.$classAttr.' xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
    }
}
