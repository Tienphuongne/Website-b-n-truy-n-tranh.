<?php

$configuredBaseUrl = getenv('APP_BASE_URL');
if ($configuredBaseUrl === false || $configuredBaseUrl === '') {
    $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $configuredBaseUrl = ($scriptDirectory === '/' ? '' : rtrim($scriptDirectory, '/')) . '/';
}
define('BASE_URL', rtrim($configuredBaseUrl, '/') . '/');

define('PATH_ROOT',         __DIR__ . '/../');

// Nạp biến từ file .env. Biến đã có trong môi trường (vd. SetEnv của Apache)
// luôn được ưu tiên, nên cấu hình hosting không bị ghi đè.
$dotenvPath = PATH_ROOT . '.env';
if (is_readable($dotenvPath)) {
    foreach (file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $dotenvLine) {
        $dotenvLine = trim($dotenvLine);
        if ($dotenvLine === '' || str_starts_with($dotenvLine, '#') || !str_contains($dotenvLine, '=')) {
            continue;
        }

        [$dotenvName, $dotenvValue] = explode('=', $dotenvLine, 2);
        $dotenvName = trim($dotenvName);
        $dotenvValue = trim($dotenvValue);
        if ($dotenvName === '' || getenv($dotenvName) !== false) {
            continue;
        }

        if (strlen($dotenvValue) >= 2) {
            $firstChar = $dotenvValue[0];
            $lastChar = substr($dotenvValue, -1);
            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                $dotenvValue = substr($dotenvValue, 1, -1);
            }
        }

        putenv($dotenvName . '=' . $dotenvValue);
        $_ENV[$dotenvName] = $dotenvValue;
    }
    unset($dotenvLine, $dotenvName, $dotenvValue, $dotenvPath);
}

define('PATH_VIEW',         PATH_ROOT . 'views/');

define('PATH_VIEW_MAIN',    PATH_ROOT . 'views/main.php');

define('BASE_ASSETS_UPLOADS',   BASE_URL . 'assets/uploads/');

define('PATH_ASSETS_UPLOADS',   PATH_ROOT . 'assets/uploads/');

define('PATH_CONTROLLER',       PATH_ROOT . 'controllers/');

define('PATH_MODEL',            PATH_ROOT . 'models/');
define('PATH_SERVICE',          PATH_ROOT . 'services/');
define('PATH_DATA',             PATH_ROOT . 'data/');
define('PATH_ASSETS',            BASE_URL . 'assets/');
define('PATH_CSS',               PATH_ASSETS . 'css/');
define('PATH_JS',                PATH_ASSETS . 'js/');

define('DB_HOST',     getenv('DB_HOST') ?: 'localhost');
define('DB_PORT',     getenv('DB_PORT') ?: '3306');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME',     getenv('DB_NAME') ?: '');
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Chatbot AI: key chỉ nằm ở server, không bao giờ đẩy ra trình duyệt.
define('AI_API_KEY', getenv('AI_API_KEY') ?: '');
define('AI_MODEL', getenv('AI_MODEL') ?: 'gemini-3.5-flash-lite');
$fallbackModels = getenv('AI_MODEL_FALLBACKS');
if ($fallbackModels === false || $fallbackModels === '') {
    $fallbackModels = 'gemini-3.1-flash-lite,gemini-flash-latest';
}
define('AI_MODEL_FALLBACKS', array_values(array_filter(array_map('trim', explode(',', $fallbackModels)))));
unset($fallbackModels);

// Môi trường chạy: đặt APP_ENV=local để hiển thị link đặt lại mật khẩu trên màn hình
// khi chưa cấu hình email. Production luôn gửi link qua email.
define('APP_ENV', strtolower(getenv('APP_ENV') ?: 'production'));
define('MAIL_FROM', getenv('MAIL_FROM') ?: '');
