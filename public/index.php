<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;

$router = new Router();
$routes = [
    'GET' => [
        '/' => 'HomeController@index',
        '/catalog' => 'CatalogController@index',
        '/product' => 'CatalogController@show',
        '/login' => 'AuthController@showLogin',
        '/register' => 'AuthController@showRegister',
        '/cart' => 'RentalController@cart',
        '/orders' => 'RentalController@orders',
        '/order' => 'RentalController@show',
        '/account' => 'AccountController@index',
        '/partner' => 'PartnerController@index',
        '/staff' => 'StaffController@index',
        '/admin' => 'AdminController@index',
        '/admin/products' => 'AdminController@products',
        '/admin/containers' => 'AdminController@containers',
        '/admin/locations' => 'AdminController@locations',
        '/admin/users' => 'AdminController@users',
        '/admin/assignments' => 'AccessController@assignments',
        '/admin/reviews' => 'ReviewController@adminIndex',
        '/admin/reports' => 'AdminController@reports',
    ],
    'POST' => [
        '/login' => 'AuthController@login',
        '/register' => 'AuthController@register',
        '/logout' => 'AuthController@logout',
        '/cart/add' => 'RentalController@add',
        '/cart/remove' => 'RentalController@remove',
        '/checkout' => 'RentalController@checkout',
        '/order/payment/change' => 'RentalController@changePaymentMethod',
        '/order/extend' => 'RentalController@extend',
        '/order/return-request' => 'RentalController@requestReturn',
        '/account/profile' => 'AccountController@updateProfile',
        '/account/notifications/read' => 'AccountController@readNotifications',
        '/order/cancel' => 'AccountController@cancelOrder',
        '/review' => 'AccountController@submitReview',
        '/staff/action' => 'StaffController@action',
        '/staff/return-request' => 'StaffController@processReturnRequest',
        '/staff/payment/verify' => 'StaffController@verifyPayment',
        '/staff/deposit/refund' => 'StaffController@refundDeposit',
        '/staff/inventory' => 'StaffController@inventory',
        '/admin/product/save' => 'ProductAdminController@save',
        '/admin/product/update' => 'ProductAdminController@update',
        '/admin/product/delete' => 'AdminController@deleteProduct',
        '/admin/category/save' => 'AdminController@saveCategory',
        '/admin/category/update' => 'AdminController@updateCategory',
        '/admin/category/delete' => 'AdminController@deleteCategory',
        '/admin/container/save' => 'AdminController@saveContainer',
        '/admin/location/save' => 'AdminController@saveLocation',
        '/admin/location/update' => 'AdminController@updateLocation',
        '/admin/location/delete' => 'AdminController@deleteLocation',
        '/admin/user/status' => 'AdminController@userStatus',
        '/admin/user/role' => 'AdminController@userRole',
        '/admin/assignments' => 'AccessController@saveAssignments',
        '/admin/review/status' => 'ReviewController@setStatus',
    ],
];

foreach ($routes['GET'] as $path => $handler) $router->get($path, $handler);
foreach ($routes['POST'] as $path => $handler) $router->post($path, $handler);
$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
