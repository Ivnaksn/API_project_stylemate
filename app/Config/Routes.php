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
     $routes->post('events', 'EventController::create');

    $routes->post('analyze-clothing', 'AIWardrobeController::analyzeClothing');
    $routes->post('generate-outfits', 'AIWardrobeController::generateOutfits');
    $routes->get('health', 'AIWardrobeController::health');
});