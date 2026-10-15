<?php

// This entry point deliberately checks environment names, never prints their values.
$required = ['APP_KEY', 'DB_URL', 'CLOUDINARY_CLOUD_NAME', 'CLOUDINARY_API_KEY', 'CLOUDINARY_API_SECRET', 'DEMO_ACCESS_HASH'];
foreach ($required as $name) {
    if (! getenv($name)) {
        fwrite(STDERR, 'Falta configurar '.$name.".\n");
        exit(1);
    }
}
$expected = ['APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'DEMO_MODE' => 'true', 'DB_CONNECTION' => 'pgsql', 'DB_SSLMODE' => 'verify-full', 'CATALOG_STORAGE' => 'cloudinary', 'SESSION_DRIVER' => 'database', 'SESSION_SECURE_COOKIE' => 'true', 'SESSION_LIFETIME' => '10080', 'CACHE_STORE' => 'database', 'MAIL_MAILER' => 'array'];
foreach ($expected as $name => $value) {
    if (getenv($name) !== $value) {
        fwrite(STDERR, 'Configuración de demo inválida: '.$name.".\n");
        exit(1);
    }
}
$key = getenv('APP_KEY');
$decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : false;
$url = getenv('APP_URL');
$db = parse_url(getenv('DB_URL'));
parse_str($db['query'] ?? '', $options);
if ($decoded === false || strlen($decoded) !== 32 || ! filter_var($url, FILTER_VALIDATE_URL) || ! str_starts_with($url, 'https://') || ! in_array($db['scheme'] ?? '', ['postgres', 'postgresql'], true) || (isset($options['sslmode']) && $options['sslmode'] !== 'verify-full') || ! preg_match('/^\$2[aby]\$[0-9]{2}\$[.\/A-Za-z0-9]{53}$/D', getenv('DEMO_ACCESS_HASH'))) {
    fwrite(STDERR, "Revisá APP_KEY, APP_URL, DB_URL (TLS verify-full) y DEMO_ACCESS_HASH (bcrypt).\n");
    exit(1);
}
