<?php
require dirname(__DIR__) . '/app/bootstrap.php';
if (is_post()) {
    csrf_verify();
    if (rate_limit_allow('demo:' . client_ip(), 5, 30)) {
        insert('demo_requests', [
            'name' => post('name'), 'hospital' => post('hospital'), 'phone' => post('phone'),
            'email' => post('email'), 'message' => post('message'), 'ip' => client_ip(), 'created_at' => now(),
        ]);
    }
}
redirect('index.php?demo=1#demo');
