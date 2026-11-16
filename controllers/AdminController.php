<?php

class AdminController extends BaseController
{
    public function dashboard()
    {
        $this->requireAdmin();
        $metrics = null;
        $databaseError = null;

        try {
            $metrics = (new AdminModel())->dashboard();
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải dữ liệu quản trị. Hãy kiểm tra cấu hình cơ sở dữ liệu.';
        }

        $title = 'Tổng quan quản trị';
        $view = 'admin/dashboard';
        require PATH_VIEW . 'admin.php';
    }

    public function resource()
    {
        $this->requireAdmin();
        $key = request_string($_GET, 'resource');
        $task = request_string($_GET, 'task');
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $resource = null;
        $record = [];
        $definition = null;
        $formOptions = [];
        $databaseError = null;
        $detail = null;
        $report = null;

        try {
            $model = new AdminModel();
            $definition = $model->getDefinition($key);

            if (
                !$definition
                || ($task === 'create' && empty($definition['create']))
                || ($task === 'edit' && empty($definition['edit']))
                || ($task === 'detail' && empty($definition['detail']))
            ) {
                http_response_code(404);
                $databaseError = 'Tác vụ quản trị này không tồn tại.';
                $title = 'Không tìm thấy thao tác';
                $view = 'admin/resource';
                require PATH_VIEW . 'admin.php';
                return;
            }

            if ($task === 'detail') {
                $detail = $id ? $model->getOrderDetail($id) : null;
                if (!$detail) {
                    http_response_code(404);
                    $databaseError = 'Không tìm thấy đơn hàng cần xem.';
                    $title = 'Không tìm thấy đơn hàng';
                    $view = 'admin/resource';
                    $resource = ['title' => 'Đơn hàng', 'rows' => []];
                    require PATH_VIEW . 'admin.php';
                    return;
                }

                $title = 'Chi tiết đơn hàng #' . (int) $id;
                $view = 'admin/order-detail';
                require PATH_VIEW . 'admin.php';
                return;
            }

            if ($task === 'delete') {
                $this->requirePost();
                $model->deleteRecord($key, filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT));
                flash('success', 'Đã xóa bản ghi.');
                redirect('admin/' . $key);
            }

            if ($task === 'create' || $task === 'edit') {
                if ($key === 'products') {
                    $formOptions = $model->getForeignOptions();
                }
                if ($task === 'edit') {
                    $record = $id ? $model->getRecord($key, $id) : null;
                    if (!$record) {
                        http_response_code(404);
                        $databaseError = 'Không tìm thấy bản ghi cần chỉnh sửa.';
                    } elseif ($key === 'products') {
                        $record['_genre_ids'] = $model->getProductGenreIds((int) $id);
                        $record['_images'] = $model->getProductImages((int) $id);
                    }
                }

                if ($databaseError === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->requirePost();
                    if ($key === 'orders' && $task === 'edit') {
                        (new OrderModel())->updateStatus($id, request_string($_POST, 'status'));
                        try {
                            $mailDetail = (new AdminModel())->getOrderDetail((int) $id);
                            if ($mailDetail) {
                                $mailOrder = $mailDetail['order'];
                                $mailOrder['items'] = $mailDetail['items'];
                                send_order_status_email($mailOrder);
                            }
                        } catch (Throwable $e) {
                            error_log('Order status email failed: ' . $e->getMessage());
                        }
                    } else {
                        $model->saveRecord(
                            $key,
                            $task === 'edit' ? $id : null,
                            $_POST
                        );
                    }
                    flash('success', $task === 'edit' ? 'Đã cập nhật dữ liệu.' : 'Đã thêm dữ liệu.');
                    if ($key === 'orders' && $id) {
                        redirect('admin/orders/detail?id=' . (int) $id);
                    }
                    redirect('admin/' . $key);
                }

                $title = ($task === 'edit' ? 'Chỉnh sửa: ' : 'Thêm: ') . ($definition['title'] ?? 'dữ liệu');
                $view = 'admin/form';
                require PATH_VIEW . 'admin.php';
                return;
            }

            if ($key === 'reports') {
                $reportFrom = $this->reportDate($_GET['from'] ?? '', date('Y-m-01'));
                $reportTo = $this->reportDate($_GET['to'] ?? '', date('Y-m-d'));
                if ($reportFrom > $reportTo) {
                    [$reportFrom, $reportTo] = [$reportTo, $reportFrom];
                }
                $oldestAllowed = date('Y-m-d', strtotime($reportTo . ' -5 years +1 day'));
                if ($reportFrom < $oldestAllowed) {
                    $reportFrom = $oldestAllowed;
                    flash('warning', 'Báo cáo chỉ tổng hợp tối đa 5 năm gần nhất để đảm bảo hiệu năng.');
                }
                $report = $model->getReport($reportFrom, $reportTo);
                $title = 'Thống kê và báo cáo';
                $view = 'admin/reports';
                require PATH_VIEW . 'admin.php';
                return;
            }

            if ($key === 'orders' && $task === '') {
                $filters = [
                    'status' => request_string($_GET, 'status'),
                    'q' => trim(request_string($_GET, 'q')),
                    'from' => $this->reportDate($_GET['from'] ?? '', ''),
                    'to' => $this->reportDate($_GET['to'] ?? '', ''),
                ];
                $resource = [
                    'title' => 'Đơn hàng',
                    'rows' => $model->getOrders($filters),
                    'filters' => $filters,
                ];
            } else {
                $resource = $model->getResource($key);
            }
        } catch (DomainException $exception) {
            flash('danger', $exception->getMessage());
            redirect($this->formTarget($key, $task, $id));
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
            $isFormSubmission = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
                && in_array($task, ['create', 'edit'], true);
            if ($isFormSubmission && in_array($sqlState, ['23000', '22001'], true)) {
                flash(
                    'danger',
                    'Không thể lưu dữ liệu: thông tin bị trùng hoặc vượt quá độ dài cho phép.'
                );
                redirect($this->formTarget($key, $task, $id));
            }
            $databaseError = 'Chưa thể tải dữ liệu quản trị.';
        } catch (RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải dữ liệu quản trị.';
        }

        if (!$resource && !$databaseError) {
            http_response_code(404);
        }

        $title = $resource['title'] ?? ($definition['title'] ?? 'Quản lý dữ liệu');
        $view = 'admin/resource';
        require PATH_VIEW . 'admin.php';
    }

    public function invoice()
    {
        $this->requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            flash('warning', 'Đơn hàng không hợp lệ.');
            redirect('admin/orders');
        }

        try {
            $detail = (new AdminModel())->getOrderDetail($id);
        } catch (PDOException | RuntimeException $exception) {
            flash('danger', $this->reportDatabaseFailure($exception));
            redirect('admin/orders');
        }

        if (!$detail) {
            http_response_code(404);
            flash('warning', 'Không tìm thấy đơn hàng cần in.');
            redirect('admin/orders');
        }

        $title = 'Hoá đơn #' . (int) $id;
        require PATH_VIEW . 'admin/invoice.php';
    }

    private function reportDate($value, $fallback)
    {
        $value = is_string($value) ? trim($value) : '';
        $date = DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }

    private function formTarget($key, $task, $id)
    {
        $target = 'admin/' . $key;
        if ($task === 'edit') {
            $target .= '/edit?id=' . (int) $id;
        } elseif ($task === 'create') {
            $target .= '/create';
        }

        return $target;
    }
}
