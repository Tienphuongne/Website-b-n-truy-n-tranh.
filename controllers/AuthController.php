<?php

class AuthController extends BaseController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $login = trim(request_string($_POST, 'login'));
            $password = request_string($_POST, 'password');

            try {
                $user = $login !== '' ? (new UserModel())->findByLogin($login) : false;
                if ($user && password_verify($password, $user['password_hash'])) {
                    $users = new UserModel();
                    if (request_string($_POST, 'remember') === '1') {
                        $token = bin2hex(random_bytes(32));
                        $expiresAt = time() + 30 * 24 * 60 * 60;
                        $users->saveRememberToken(
                            (int) $user['id'],
                            hash('sha256', $token),
                            date('Y-m-d H:i:s', $expiresAt)
                        );
                        set_remember_cookie($token, $expiresAt);
                    } else {
                        $users->clearRememberToken((int) $user['id']);
                        clear_remember_cookie();
                    }
                    session_regenerate_id(true);
                    $_SESSION['user'] = [
                        'id' => (int) $user['id'],
                        'name' => $user['full_name'],
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                        'role' => $user['role'],
                    ];
                    redirect('account');
                }
                flash('danger', 'Email/tên đăng nhập hoặc mật khẩu không chính xác.');
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
            }
        }

        $title = 'Đăng nhập';
        $view = 'auth/login';
        require PATH_VIEW . 'main.php';
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $user = [
                'full_name' => trim(request_string($_POST, 'full_name')),
                'email' => trim(request_string($_POST, 'email')),
                'phone' => trim(request_string($_POST, 'phone')),
                'password' => request_string($_POST, 'password'),
                'password_confirmation' => request_string($_POST, 'password_confirmation'),
            ];
            $errors = [];
            if ($user['full_name'] === '' || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Vui lòng nhập họ tên và email hợp lệ.';
            }
            if (strlen($user['password']) < 8) {
                $errors[] = 'Mật khẩu cần có ít nhất 8 ký tự.';
            }
            if ($user['password'] !== $user['password_confirmation']) {
                $errors[] = 'Mật khẩu xác nhận không khớp.';
            }
            if (empty($_POST['terms'])) {
                $errors[] = 'Bạn cần đồng ý với điều khoản sử dụng.';
            }

            if (!$errors) {
                try {
                    $model = new UserModel();
                    if ($model->emailExists($user['email'])) {
                        $errors[] = 'Email này đã được đăng ký.';
                    } else {
                        $user['username'] = $user['email'];
                        $userId = $model->create($user);
                        session_regenerate_id(true);
                        $_SESSION['user'] = [
                            'id' => $userId,
                            'name' => $user['full_name'],
                            'email' => $user['email'],
                            'phone' => $user['phone'],
                            'role' => 'customer',
                        ];
                        flash('success', 'Tạo tài khoản thành công.');
                        redirect('account');
                    }
                } catch (PDOException | RuntimeException $exception) {
                    $errors[] = $this->reportDatabaseFailure($exception);
                }
            }
            if ($errors) {
                flash('danger', implode(' ', $errors));
            }
        }

        $title = 'Đăng ký';
        $view = 'auth/register';
        require PATH_VIEW . 'main.php';
    }

    public function logout()
    {
        $this->requirePost();
        if (!empty($_SESSION['user']['id'])) {
            try {
                (new UserModel())->clearRememberToken((int) $_SESSION['user']['id']);
            } catch (PDOException | RuntimeException $exception) {
                error_log($exception->getMessage());
            }
        }
        clear_remember_cookie();
        unset($_SESSION['user']);
        session_regenerate_id(true);
        flash('success', 'Bạn đã đăng xuất.');
        redirect('');
    }
}
