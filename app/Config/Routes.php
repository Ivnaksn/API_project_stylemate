<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api', function($routes) {


    $routes->post('register', 'AuthController::register');
    $routes->post('login', 'AuthController::login');
    $routes->get('profile', 'AuthController::profile');

    $routes->get('wardrobe', 'WardrobeController::index');
    $routes->post('wardrobe', 'WardrobeController::create');
    $routes->get('wardrobe/(:num)', 'WardrobeController::show/$1');
    $routes->put('wardrobe/(:num)', 'WardrobeController::update/$1');
    $routes->delete('wardrobe/(:num)', 'WardrobeController::delete/$1');


});