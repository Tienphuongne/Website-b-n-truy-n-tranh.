<?php

if (!function_exists('debug')) {
    function debug($data)
    {
        echo '<pre>';
        print_r($data);
        die;
    }
}

if (!function_exists('upload_file')) {
    function upload_file($folder, $file)
    {
        $targetFile = $folder . '/' . time() . '-' . $file["name"];

        if (move_uploaded_file($file["tmp_name"], PATH_ASSETS_UPLOADS . $targetFile)) {
            return $targetFile;
        }

        throw new Exception('Upload file không thành công!');
    }
}

if (!function_exists('e')) {
    function e($value)
    {
        if (!is_scalar($value) && $value !== null) {
            $value = '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('request_string')) {
    function request_string(array $source, $key, $default = '')
    {
        $value = $source[$key] ?? $default;

        return is_scalar($value) ? (string) $value : (string) $default;
    }
}

if (!function_exists('url')) {
    function url($path = '')
    {
        return BASE_URL . ltrim($path, '/');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field()
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token()
    {
        $submitted = $_POST['_csrf_token'] ?? '';
        $saved = $_SESSION['_csrf_token'] ?? '';

        return is_string($submitted) && is_string($saved) && $saved !== ''
            && hash_equals($saved, $submitted);
    }
}

if (!function_exists('flash')) {
    function flash($type, $message)
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('take_flash_messages')) {
    function take_flash_messages()
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }
}

if (!function_exists('redirect')) {
    function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('set_remember_cookie')) {
    function set_remember_cookie($token, $expiresAt)
    {
        $path = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
        setcookie('manga_house_remember', $token, [
            'expires' => $expiresAt,
            'path' => $path,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}

if (!function_exists('clear_remember_cookie')) {
    function clear_remember_cookie()
    {
        set_remember_cookie('', time() - 3600);
    }
}