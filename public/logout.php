<?php
require dirname(__DIR__) . '/app/bootstrap.php';
logout();
redirect('login.php');
