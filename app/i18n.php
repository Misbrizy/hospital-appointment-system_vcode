<?php
declare(strict_types=1);

const SUPPORTED_LANGS = ['en' => 'English', 'rw' => 'Kinyarwanda', 'fr' => 'Français'];

/** The one language the whole response renders in. Session-based: chosen once, used everywhere, never mixed. */
function ui_lang(): string
{
    $l = $_SESSION['ui_lang'] ?? 'en';
    return isset(SUPPORTED_LANGS[$l]) ? $l : 'en';
}

function set_ui_lang(string $lang): void
{
    if (isset(SUPPORTED_LANGS[$lang])) {
        $_SESSION['ui_lang'] = $lang;
    }
}

/** Translate a key into the current UI language. Falls back to English, then to the key itself. */
function t(string $key, array $vars = []): string
{
    static $dicts = [];
    $lang = ui_lang();
    if (!isset($dicts[$lang])) {
        $file = APP_ROOT . "/app/lang/{$lang}.php";
        $dicts[$lang] = is_file($file) ? require $file : [];
    }
    $text = $dicts[$lang][$key] ?? ($dicts['en'] ??= require APP_ROOT . '/app/lang/en.php')[$key] ?? $key;
    foreach ($vars as $k => $v) {
        $text = str_replace('{' . $k . '}', (string) $v, $text);
    }
    return $text;
}

/** One "Language" dropdown (not several separate links) — the same control on every page. */
function lang_switcher(): string
{
    static $n = 0;
    $n++;
    $cur = ui_lang();
    $uid = 'langmenu' . $n;
    $back = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    $h = '<div class="lang-switch">';
    $h .= '<button type="button" class="lang-switch-btn" onclick="var m=document.getElementById(\'' . $uid . '\');m.style.display=m.style.display===\'block\'?\'none\':\'block\';event.stopPropagation();" aria-haspopup="true">'
        . '<span>' . e(SUPPORTED_LANGS[$cur]) . '</span> <svg width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M1.5 3.5L5 7l3.5-3.5"/></svg></button>';
    $h .= '<div class="lang-switch-menu" id="' . $uid . '">';
    foreach (SUPPORTED_LANGS as $code => $label) {
        $h .= '<a href="' . e(url('lang.php?set=' . $code . '&back=' . $back)) . '" class="' . ($code === $cur ? 'active' : '') . '">' . e($label) . '</a>';
    }
    $h .= '</div></div>';
    if ($n === 1) {
        $h .= '<script>document.addEventListener("click", function(){ document.querySelectorAll(".lang-switch-menu").forEach(function(m){ m.style.display = "none"; }); });</script>';
    }
    return $h;
}
