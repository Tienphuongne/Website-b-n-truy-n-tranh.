<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
$basePath = rtrim($basePath, '/');
if ($basePath !== '' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}
$path = '/' . trim($path, '/');
$requestedAction = $_GET['action'] ?? ($path === '/' ? 'home' : trim($path, '/'));
$action = is_string($requestedAction) ? $requestedAction : '';

$routes = [
    'home' => [HomeController::class, 'index'],
    '/' => [HomeController::class, 'index'],
    'products' => [ProductsController::class, 'index'],
    'products/suggest' => [ProductsController::class, 'suggest'],
    'product' => [ProductsController::class, 'detail'],
    'product/review' => [ReviewController::class, 'create'],
    'cart' => [CartController::class, 'index'],
    'cart/add' => [CartController::class, 'add'],
    'cart/update' => [CartController::class, 'update'],
    'cart/remove' => [CartController::class, 'remove'],
    'checkout' => [CheckoutController::class, 'index'],
    'login' => [AuthController::class, 'login'],
    'register' => [AuthController::class, 'register'],
    'forgot-password' => [AuthController::class, 'forgotPassword'],
    'reset-password' => [AuthController::class, 'resetPassword'],
    'logout' => [AuthController::class, 'logout'],
    'account' => [AccountController::class, 'index'],
    'account/order/cancel' => [AccountController::class, 'cancelOrder'],
    'account/order/reorder' => [AccountController::class, 'reorder'],
    'account/address/delete' => [AccountController::class, 'deleteAddress'],
    'account/address/default' => [AccountController::class, 'defaultAddress'],
    'wishlist' => [WishlistController::class, 'index'],
    'wishlist/toggle' => [WishlistController::class, 'toggle'],
    'about' => [PageController::class, 'about'],
    'contact' => [PageController::class, 'contact'],
    'admin' => [AdminController::class, 'dashboard'],
];

if (preg_match('#^product/detail/(\d+)$#', $action, $matches)) {
    $_GET['id'] = $matches[1];
    $handler = [ProductsController::class, 'detail'];
} elseif (preg_match('#^admin/([a-z-]+)(?:/(create|edit|delete|detail))?$#', $action, $matches)) {
    $_GET['resource'] = $matches[1];
    $_GET['task'] = $matches[2] ?? '';
    $handler = [AdminController::class, 'resource'];
} else {
    $handler = $routes[$action] ?? null;
}

if ($handler === null) {
    http_response_code(404);
    $title = 'Không tìm thấy trang';
    $message = 'Trang bạn đang tìm không tồn tại hoặc đã được di chuyển.';
    $view = 'errors/404';
    require PATH_VIEW . 'main.php';
    return;
}

[$controllerClass, $method] = $handler;
(new $controllerClass())->$method();