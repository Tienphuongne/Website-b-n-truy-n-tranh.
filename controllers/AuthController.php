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

    public function forgotPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $email = trim(request_string($_POST, 'email'));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('danger', 'Vui lòng nhập địa chỉ email hợp lệ.');
                redirect('forgot-password');
            }

            try {
                $users = new UserModel();
                $user = $users->findByEmail($email);
                if ($user) {
                    $token = $users->createPasswordReset((int) $user['id']);
                    $link = absolute_url('reset-password?token=' . urlencode($token));
                    $sent = send_mail(
                        $user['email'],
                        'Đặt lại mật khẩu TruyenThuVi',
                        "Xin chào {$user['full_name']},\n\n"
                        . "Bạn đã yêu cầu đặt lại mật khẩu tại TruyenThuVi.\n"
                        . "Mở liên kết sau để tạo mật khẩu mới (hết hạn sau 1 giờ):\n"
                        . $link . "\n\n"
                        . "Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email."
                    );
                    if (!$sent) {
                        error_log('Không gửi được email đặt lại mật khẩu tới ' . $user['email'] . '. Link: ' . $link);
                    }
                    if (strtolower((string) (getenv('APP_ENV') ?: 'local')) !== 'production') {
                        $_SESSION['_dev_reset_link'] = $link;
                    }
                }
                // Luôn trả về thông báo chung để tránh dò email đã đăng ký.
                flash('success', 'Nếu email này đã đăng ký, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu.');
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
            }
            redirect('forgot-password');
        }

        $developmentResetLink = $_SESSION['_dev_reset_link'] ?? null;
        unset($_SESSION['_dev_reset_link']);

        $title = 'Quên mật khẩu';
        $view = 'auth/forgot-password';
        require PATH_VIEW . 'main.php';
    }

    public function resetPassword()
    {
        $token = request_string($_GET, 'token');
        if ($token === '') {
            $token = request_string($_POST, 'token');
        }

        $reset = null;
        try {
            if ($token !== '') {
                $reset = (new UserModel())->findPasswordReset($token);
            }
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
            redirect('forgot-password');
        }

        if (!$reset) {
            flash('danger', 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
            redirect('forgot-password');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $new = request_string($_POST, 'password');
            $confirm = request_string($_POST, 'password_confirmation');

            if (strlen($new) < 8 || $new !== $confirm) {
                flash('danger', 'Mật khẩu mới cần ít nhất 8 ký tự và phải khớp xác nhận.');
                redirect('reset-password?token=' . urlencode($token));
            }

            try {
                $users = new UserModel();
                $users->updatePassword((int) $reset['user_id'], $new);
                $users->deletePasswordResets((int) $reset['user_id']);
                $users->clearRememberToken((int) $reset['user_id']);
                clear_remember_cookie();
                unset($_SESSION['user']);
                session_regenerate_id(true);
                flash('success', 'Đã đặt lại mật khẩu. Vui lòng đăng nhập bằng mật khẩu mới.');
                redirect('login');
            } catch (PDOException | RuntimeException $exception) {
                flash('danger', $this->reportDatabaseFailure($exception));
                redirect('reset-password?token=' . urlencode($token));
            }
        }

        $title = 'Đặt lại mật khẩu';
        $view = 'auth/reset-password';
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
