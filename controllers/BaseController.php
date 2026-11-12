<?php

class BaseController
{
    protected function render($view, array $data = [], $layout = 'main')
    {
        $title = $data['title'] ?? 'Manga House';
        extract($data, EXTR_SKIP);
        require PATH_VIEW . $layout . '.php';
    }

    protected function requirePost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('Phương thức không được hỗ trợ.');
        }

        if (!verify_csrf_token()) {
            http_response_code(419);
            exit('Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
        }
    }

    protected function reportDatabaseFailure(Throwable $exception)
    {
        error_log($exception->getMessage());

        return 'Dữ liệu hiện chưa sẵn sàng. Vui lòng cấu hình cơ sở dữ liệu hoặc thử lại sau.';
    }

    protected function requireAdmin()
    {
        if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
            flash('warning', 'Vui lòng đăng nhập bằng tài khoản quản trị.');
            redirect('login');
        }
    }
}
