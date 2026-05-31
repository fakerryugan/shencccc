<?php
declare(strict_types=1);

$pdo->query('SELECT 1');
json_response([
    'ok' => true,
    'service' => 'sencha-recruitment',
    'db' => 'postgresql',
    'time' => date('c'),
]);
