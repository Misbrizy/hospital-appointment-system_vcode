<?php
/** Tiny inline icon set (stroke, 18x18) so the app never depends on an external icon font. */
$ICONS = [
  'grid' => 'M3 3h6v6H3zM11 3h6v6h-6zM3 11h6v6H3zM11 11h6v6h-6z',
  'users' => 'M6.5 8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM2.5 15c0-2.5 2-4 4-4s4 1.5 4 4M12.5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM11 15c.2-2 1.7-3.4 3.5-3.7',
  'cal' => 'M3 4.5h12v10.5H3zM3 7.5h12M6.5 2.5v3M11.5 2.5v3',
  'clock' => 'M9 9V5.3M9 9l3 1.7M9 15.5a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13z',
  'heart' => 'M9 15.2S2.5 11 2.5 6.7a3.3 3.3 0 0 1 6.5-1 3.3 3.3 0 0 1 6.5 1c0 4.3-6.5 8.5-6.5 8.5z',
  'refresh' => 'M14.5 8A5.5 5.5 0 1 1 12.9 4M14.5 4v4h-4',
  'msg' => 'M3 4h12v8H7l-4 3z',
  'bell' => 'M5 7a4 4 0 0 1 8 0c0 3 1 4 1 4H4s1-1 1-4zM7.5 13.5a1.5 1.5 0 0 0 3 0',
  'send' => 'M15.5 2.5l-6 13-2-5.5-5.5-2z',
  'id' => 'M2.5 4h13v10h-13zM5.5 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM4 12.5c.3-1.3 1.4-2 2.5-2s2.2.7 2.5 2M11 6.5h3M11 9h3',
  'layers' => 'M9 2.5l6.5 3.5L9 9.5 2.5 6zM2.5 10l6.5 3.5L15.5 10M2.5 13l6.5 3.5L15.5 13',
  'chart' => 'M3 15.5V9M8 15.5v-9M13 15.5V5',
  'card' => 'M2 4.5h14v9H2zM2 7.5h14',
  'shield' => 'M9 2.5l6 2v5c0 4-2.7 6-6 7-3.3-1-6-3-6-7v-5z',
  'gear' => 'M9 11.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5zM9 2v2M9 14v2M2 9h2M14 9h2M4 4l1.4 1.4M12.6 12.6L14 14M14 4l-1.4 1.4M5.4 12.6L4 14',
  'building' => 'M4 15.5V3h6v3h4v9.5zM6 6h2M6 9h2M6 12h2M11.5 9h1.5M11.5 12h1.5',
  'logout' => 'M7 15.5H3.5v-13H7M11.5 12.5l3-3-3-3M14.5 9.5h-9',
  'plus' => 'M9 3v12M3 9h12',
  'search' => 'M8 13.5A5.5 5.5 0 1 0 8 2.5a5.5 5.5 0 0 0 0 11zM15.5 15.5l-3.6-3.6',
];
function icon(string $name, string $class = ''): string
{
    global $ICONS;
    $d = $ICONS[$name] ?? $ICONS['grid'];
    return '<svg class="icon ' . e($class) . '" width="17" height="17" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . $d . '"/></svg>';
}
