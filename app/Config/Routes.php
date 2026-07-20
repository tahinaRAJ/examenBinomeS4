<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Dashboard
$routes->get('/', 'Dashboard::index');
$routes->get('dashboard/gains/(:segment)', 'Dashboard::gains/$1');

// CRUD des préfixes
$routes->get('prefixes', 'Prefixes::index');
$routes->post('prefixes', 'Prefixes::store');
$routes->get('prefixes/(:num)/edit', 'Prefixes::edit/$1');
$routes->post('prefixes/(:num)', 'Prefixes::update/$1');
$routes->post('prefixes/(:num)/delete', 'Prefixes::delete/$1');

// Gestion des tarifs (frais)
$routes->get('tarifs', 'Tarifs::index');
$routes->post('tarifs', 'Tarifs::store');
$routes->get('tarifs/(:num)/edit', 'Tarifs::edit/$1');
$routes->post('tarifs/(:num)', 'Tarifs::update/$1');
$routes->post('tarifs/(:num)/delete', 'Tarifs::delete/$1');

// Comptes clients
$routes->get('comptes', 'Comptes::index');
$routes->get('comptes/create', 'Comptes::create');
$routes->post('comptes', 'Comptes::store');
$routes->get('comptes/(:num)', 'Comptes::show/$1');
$routes->get('comptes/(:num)/edit', 'Comptes::edit/$1');
$routes->post('comptes/(:num)', 'Comptes::update/$1');
$routes->post('comptes/(:num)/toggle', 'Comptes::toggle/$1');


// Authentification 
$routes->get('login', 'AuthController::showLoginForm');
$routes->post('login', 'AuthController::login');
$routes->post('logout', 'AuthController::logout');

// Espace client — identité (ClientController)
$routes->get('profil', 'ClientController::profile');
$routes->get('historique', 'ClientController::historique');

// Espace client — opérations (MouvementController)
$routes->get('client/depot', 'MouvementController::depotForm');
$routes->post('client/depot', 'MouvementController::depot');

$routes->get('client/retrait', 'MouvementController::retraitForm');
$routes->post('client/retrait', 'MouvementController::retrait');

$routes->get('client/transfert', 'MouvementController::transfertForm');
$routes->post('client/transfert', 'MouvementController::transfert');