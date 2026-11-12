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

if (!function_exists('save_uploaded_image')) {
    /**
     * Lưu một ảnh tải lên vào assets/uploads/{folder} và trả về đường dẫn tương đối.
     * Chỉ chấp nhận các định dạng ảnh phổ biến, tối đa 5MB.
     */
    function save_uploaded_image(array $file, $folder = 'products')
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new DomainException('Tải ảnh lên không thành công. Vui lòng thử lại.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new DomainException('Tệp ảnh tải lên không hợp lệ.');
        }
        if ($size < 1024 || $size > 5 * 1024 * 1024) {
            throw new DomainException('Ảnh phải có dung lượng từ 1KB đến 5MB.');
        }

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        $mime = null;
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpName) ?: null;
        }
        if ($mime === null) {
            $info = @getimagesize($tmpName);
            $mime = $info['mime'] ?? null;
        }
        if (!is_string($mime) || !isset($extensions[$mime])) {
            throw new DomainException('Chỉ hỗ trợ ảnh JPG, PNG, WEBP hoặc GIF.');
        }

        $folder = trim(str_replace('\\', '/', (string) $folder), '/');
        if ($folder === '' || strpos($folder, '..') !== false) {
            throw new DomainException('Thư mục lưu ảnh không hợp lệ.');
        }

        $directory = PATH_ASSETS_UPLOADS . $folder . '/';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new DomainException('Không thể tạo thư mục lưu ảnh. Vui lòng kiểm tra quyền ghi.');
        }

        $fileName = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extensions[$mime];
        if (!move_uploaded_file($tmpName, $directory . $fileName)) {
            throw new DomainException('Không thể lưu ảnh tải lên. Vui lòng thử lại.');
        }

        return $folder . '/' . $fileName;
    }
}

if (!function_exists('delete_upload_file')) {
    function delete_upload_file($relativePath)
    {
        $relativePath = ltrim(str_replace(['\\', '..'], ['/', ''], (string) $relativePath), '/');
        if ($relativePath === '') {
            return;
        }

        $base = realpath(PATH_ASSETS_UPLOADS);
        $path = realpath(PATH_ASSETS_UPLOADS . $relativePath);
        if ($base && $path && strpos($path, $base . DIRECTORY_SEPARATOR) === 0 && is_file($path)) {
            @unlink($path);
        }
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