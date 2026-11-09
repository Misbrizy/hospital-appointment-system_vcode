<?php
require dirname(__DIR__) . '/app/bootstrap.php';
set_ui_lang(get('set', 'en'));
$back = get('back', '');
if ($back !== '' && str_starts_with($back, '/') && !str_starts_with($back, '//')) {
    header('Location: ' . $back);
    exit;
}
redirect('index.php');
