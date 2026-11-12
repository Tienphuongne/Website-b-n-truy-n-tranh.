<?php

class PageController extends BaseController
{
    public function about()
    {
        $title = 'Về chúng tôi';
        $view = 'pages/about';
        require PATH_VIEW . 'main.php';
    }

    public function contact()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requirePost();
            $contact = [
                'full_name' => trim(request_string($_POST, 'full_name')),
                'email' => trim(request_string($_POST, 'email')),
                'phone' => trim(request_string($_POST, 'phone')),
                'message' => trim(request_string($_POST, 'message')),
            ];
            if (
                $contact['full_name'] === ''
                || !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)
                || $contact['message'] === ''
            ) {
                flash('danger', 'Vui lòng nhập họ tên, email hợp lệ và nội dung liên hệ.');
            } else {
                try {
                    (new ContactModel())->create($contact);
                    flash('success', 'Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm.');
                    redirect('contact');
                } catch (PDOException | RuntimeException $exception) {
                    flash('danger', $this->reportDatabaseFailure($exception));
                }
            }
        }

        $title = 'Liên hệ';
        $view = 'pages/contact';
        require PATH_VIEW . 'main.php';
    }
}
