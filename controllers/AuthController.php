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
                    redirect($user['role'] === 'admin' ? 'admin' : 'account');
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

    public function forgot()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();

            if (!$this->allowResetRequest()) {
                flash('danger', 'Bạn đã gửi nhiều yêu cầu. Vui lòng thử lại sau 10 phút.');
                redirect('forgot-password');
            }

            $email = trim(request_string($_POST, 'email'));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('danger', 'Vui lòng nhập email hợp lệ.');
                redirect('forgot-password');
            }

            try {
                $users = new UserModel();
                $user = $users->findByLogin($email);
                $isOwner = $user && strtolower((string) $user['email']) === strtolower($email);

                if (!$isOwner) {
                    // Không tiết lộ email nào đã đăng ký trong hệ thống.
                    flash('success', 'Nếu email tồn tại, chúng tôi đã gửi link đặt lại mật khẩu. Vui lòng kiểm tra hộp thư.');
                    redirect('forgot-password');
                }

                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', time() + 60 * 60);
                (new PasswordResetModel())->create((int) $user['id'], hash('sha256', $token), $expiresAt);

                $link = $this->absoluteUrl('reset-password?token=' . $token);
                $sent = false;
                if (MAIL_FROM !== '') {
                    $subject = 'Đặt lại mật khẩu TruyenThuVi';
                    $body = "Xin chào {$user['full_name']},\n\n"
                        . "Bấm vào link sau để đặt lại mật khẩu (có hiệu lực 60 phút):\n{$link}\n\n"
                    . "Nếu bạn không yêu cầu, hãy bỏ qua email này.\n";
                    $headers = 'From: ' . MAIL_FROM . "\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                    $sent = @mail((string) $user['email'], $subject, $body, $headers);
                }

                if ($sent) {
                    flash('success', 'Chúng tôi đã gửi link đặt lại mật khẩu tới email của bạn.');
                } elseif (in_array(APP_ENV, ['local', 'dev', 'development'], true)) {
                    flash('warning', 'Chế độ local (chưa cấu hình email): link đặt lại mật khẩu — ' . $link);
                } else {
                    flash('danger', 'Chưa gửi được email đặt lại mật khẩu. Vui lòng liên hệ hỗ trợ.');
                }
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
            }

            redirect('forgot-password');
        }

        $title = 'Quên mật khẩu';
        $view = 'auth/forgot';
        require PATH_VIEW . 'main.php';
    }

    public function reset()
    {
        $token = trim(request_string($_GET, 'token', request_string($_POST, 'token')));
        $isValidToken = preg_match('/^[a-f0-9]{64}$/', $token) === 1;
        $record = $isValidToken
            ? (new PasswordResetModel())->findValid(hash('sha256', $token))
            : false;

        if (!$record) {
            flash('danger', 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
            redirect('forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $password = request_string($_POST, 'password');
            $confirmation = request_string($_POST, 'password_confirmation');

            if (strlen($password) < 8 || $password !== $confirmation) {
                flash('danger', 'Mật khẩu mới cần ít nhất 8 ký tự và phải khớp xác nhận.');
                redirect('reset-password?token=' . urlencode($token));
            }

            try {
                $users = new UserModel();
                $users->updatePassword((int) $record['user_id'], $password);
                $users->clearRememberToken((int) $record['user_id']);
                (new PasswordResetModel())->delete(hash('sha256', $token));
                clear_remember_cookie();
                flash('success', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập lại.');
                redirect('login');
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
                redirect('reset-password?token=' . urlencode($token));
            }
        }

        $title = 'Đặt lại mật khẩu';
        $view = 'auth/reset';
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

    private function allowResetRequest()
    {
        $now = time();
        $hits = $_SESSION['_password_reset_hits'] ?? [];
        if (!is_array($hits)) {
            $hits = [];
        }
        $hits = array_values(array_filter($hits, static function ($timestamp) use ($now) {
            return is_numeric($timestamp) && ($now - (int) $timestamp) < 600;
        }));
        if (count($hits) >= 3) {
            $_SESSION['_password_reset_hits'] = $hits;
            return false;
        }
        $hits[] = $now;
        $_SESSION['_password_reset_hits'] = $hits;

        return true;
    }

    private function absoluteUrl($path)
    {
        $link = url($path);
        if (preg_match('#^https?://#i', $link)) {
            return $link;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $link;
    }
}
