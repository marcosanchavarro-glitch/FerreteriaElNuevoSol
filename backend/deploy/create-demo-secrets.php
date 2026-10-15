<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}
umask(0077);
$path = dirname(__DIR__).'/.env.demo-secrets';
$file = @fopen($path, 'x');
if ($file === false) {
    fwrite(STDERR, "No se creó el archivo: puede existir una copia anterior. No se sobrescriben secretos.\n");
    exit(1);
}
$access = bin2hex(random_bytes(24));
$values = [
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'DEMO_ACCESS_HASH' => password_hash($access, PASSWORD_BCRYPT),
    'DEMO_ACCESS_PASSWORD' => $access,
    'DEMO_ADMIN_PASSWORD' => bin2hex(random_bytes(24)),
    'DEMO_SELLER_PASSWORD' => bin2hex(random_bytes(24)),
    'DEMO_CUSTOMER_PASSWORD' => bin2hex(random_bytes(24)),
];
foreach ($values as $name => $value) {
    fwrite($file, $name."='".$value."'\n");
}
fclose($file);
fwrite(STDOUT, "Secretos guardados en backend/.env.demo-secrets (excluido de Git y Docker). No compartas este archivo.\n");
