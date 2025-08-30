<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Map::index');
$routes->get('map', 'Map::index');
$routes->get('simple-map', 'Map::simple');
$routes->get('upload', 'Upload::index');

// API Routes for spatial data
$routes->group('api', function($routes) {
    $routes->get('parcels', 'Api::parcels');
    $routes->get('parcels/(:num)', 'Api::parcel/$1');
    $routes->post('parcels', 'Api::createParcel');
    $routes->put('parcels/(:num)', 'Api::updateParcel/$1');
    $routes->delete('parcels/(:num)', 'Api::deleteParcel/$1');
    
    // Change management
    $routes->get('changes', 'Api::getChanges');
    $routes->post('changes/apply', 'Api::applyChanges');
    $routes->post('changes/reject', 'Api::rejectChanges');
    
    // Upload and processing
    $routes->post('upload/shapefile', 'Upload::processShapefile');
    $routes->post('upload/shapefile-clean', 'UploadApi::process'); // Clean JSON endpoint
    $routes->post('upload/detect-changes', 'Upload::detectChanges');
    $routes->get('upload/test-json', 'Upload::testJson');
    
    // Test upload workflow
    $routes->post('test/upload', 'TestUpload::simulateUpload');
});

// Enable CORS for API routes
$routes->options('api/(:any)', function() {
    $response = service('response');
    $response->setHeader('Access-Control-Allow-Origin', '*');
    $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    return $response;
});
