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

    $routes->get('wardrobe', 'WardrobeController::index');
    $routes->post('wardrobe', 'WardrobeController::create');
    $routes->get('wardrobe/(:num)', 'WardrobeController::show/$1');
    $routes->put('wardrobe/(:num)', 'WardrobeController::update  /$1');
    $routes->delete('wardrobe/(:num)', 'WardrobeController::delete/$1');

    $routes->get('/boards', 'BoardController::index');
    $routes->post('/boards', 'BoardController::create');
    $routes->delete('/boards/(:num)', 'BoardController::delete/$1');

    $routes->get('/boards/semua/outfits', 'BoardController::semuaOutfits');
    $routes->get('/boards/(:num)/outfits', 'BoardController::boardOutfits/$1');
    $routes->post('/boards/(:num)/outfits', 'BoardController::addOutfitToBoard/$1');

    $routes->post('/outfits', 'OutfitController::store');

});