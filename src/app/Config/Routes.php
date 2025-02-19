<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->options('(:any)', function() {
    return '';
});

$routes->group('api', ['namespace' => 'App\Controllers\Jiachu'], function($routes) {
    $routes->match(['get'], 'test', 'User::test');
    $routes->post('login', 'User::login');
    $routes->post('register', 'User::register');
    
    $routes->group('', ['filter' => 'auth'], function($routes) {
        $routes->get('logout', 'User::logout');
        $routes->get('user', 'User::getCurrentUser');
        // $routes->post('category', 'Category::create');
        // $routes->put('category/(:num)', 'Category::update/$1');
    });

    $routes->match(['get'], 'image/(:num)', 'ImageController::show/$1');
    $routes->match(['post'], 'file', 'FileController::upload');    // 上傳檔案

    $routes->group('frontend', function($routes) {
        $routes->post('category', 'Category::getCategory');
        $routes->get('product/(:num)', 'Product::getProduct/$1');
    });

    $routes->get('category', 'Category::index');
    $routes->post('category', 'Category::create');
    $routes->put('category/(:num)', 'Category::update/$1');
    $routes->delete('category/(:num)', 'Category::delete/$1');

    // $routes->get('product', 'Product::index');
    $routes->get('product/(:num)', 'Product::getProductByCategoryId/$1');
    $routes->post('product', 'Product::create');
    $routes->put('product/(:num)', 'Product::update/$1');
    $routes->delete('product/(:num)', 'Product::delete/$1');
    $routes->post('product/image', 'Product::deleteImage');     // 刪除產品圖片
    
});

$routes->get('test-auth', 'Jiachu\TestAuth::index');
