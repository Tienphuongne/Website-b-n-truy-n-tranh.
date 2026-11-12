<?php

session_start();

$root = __DIR__ . DIRECTORY_SEPARATOR;
require_once $root . 'configs/env.php';
require_once $root . 'configs/helper.php';

spl_autoload_register(function ($class) {
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $class)) {
        return;
    }

    $fileName = "$class.php";

    $fileModel              = PATH_MODEL . $fileName;
    $fileController         = PATH_CONTROLLER . $fileName;

    if (is_readable($fileModel)) {
        require_once $fileModel;
    } elseif (is_readable($fileController)) {
        require_once $fileController;
    }
});

if (
    empty($_SESSION['user'])
    && isset($_COOKIE['manga_house_remember'])
    && is_string($_COOKIE['manga_house_remember'])
    && preg_match('/^[a-f0-9]{64}$/', $_COOKIE['manga_house_remember'])
) {
    try {
        $users = new UserModel();
        $rememberedUser = $users->findByRememberToken(hash('sha256', $_COOKIE['manga_house_remember']));
        if ($rememberedUser) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int) $rememberedUser['id'],
                'name' => $rememberedUser['full_name'],
                'email' => $rememberedUser['email'],
                'phone' => $rememberedUser['phone'],
                'role' => $rememberedUser['role'],
            ];
            $token = bin2hex(random_bytes(32));
            $expiresAt = time() + 30 * 24 * 60 * 60;
            $users->saveRememberToken(
                (int) $rememberedUser['id'],
                hash('sha256', $token),
                date('Y-m-d H:i:s', $expiresAt)
            );
            set_remember_cookie($token, $expiresAt);
        } else {
            clear_remember_cookie();
        }
    } catch (PDOException | RuntimeException $exception) {
        error_log($exception->getMessage());
        clear_remember_cookie();
    }
}

require_once PATH_ROOT . 'routes/index.php';
