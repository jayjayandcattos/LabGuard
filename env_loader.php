<?php
// Minimal .env loader for the legacy PHP app during the Next.js migration.
// Parses KEY=VALUE lines from the repo-root .env into getenv()/$_ENV.
// No third-party dependency. Real values stay out of git.

$labguardEnvFile = __DIR__ . '/.env';
if (is_readable($labguardEnvFile)) {
    $lines = file($labguardEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        // Strip surrounding quotes, e.g. DATABASE_URL="mysql://..."
        if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '') {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}
