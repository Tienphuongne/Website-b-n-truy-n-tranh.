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

            $resource = $model->getResource($key);
        } catch (PDOException | RuntimeException $exception) {
            error_log($exception->getMessage());
            $databaseError = 'Chưa thể tải dữ liệu quản trị.';
        } catch (DomainException $exception) {
            flash('danger', $exception->getMessage());
            $target = 'admin/' . $key;
            if ($task === 'edit') {
                $target .= '/edit?id=' . (int) $id;
            } elseif ($task === 'create') {
                $target .= '/create';
            }
            redirect($target);
        }

        if (!$resource && !$databaseError) {
            http_response_code(404);
        }

        $title = $resource['title'] ?? ($definition['title'] ?? 'Quản lý dữ liệu');
        $view = 'admin/resource';
        require PATH_VIEW . 'admin.php';
    }

    private function reportDate($value, $fallback)
    {
        $value = is_string($value) ? trim($value) : '';
        $date = DateTime::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : $fallback;
    }
}
