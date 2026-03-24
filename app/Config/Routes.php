<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/bookmarks/load', 'Home::loadMoreBookmarks');
$routes->get('/bookmark/(:segment)', 'Home::show/$1');

// Admin routes
$routes->get('/admin', 'Admin\Home::index');
$routes->get('/admin/datatable', 'Admin\Home::datatable');
$routes->post('/admin/delete', 'Admin\Home::delete');
$routes->get('/admin/bookmark/create', 'Admin\Bookmarks::create');
$routes->get('/admin/bookmark/(:segment)/edit', 'Admin\Bookmarks::edit/$1');

// API routes
$routes->match(['get', 'options'], '/api/test/ping', 'Api\Test::ping');
$routes->match(['get', 'options'], '/api/tags', 'Api\Tags::index');
$routes->match(['get', 'options'], '/api/bookmarks/check-url', 'Api\Bookmarks::checkUrl');
$routes->match(['post', 'options'], '/api/bookmarks', 'Api\Bookmarks::create');
$routes->match(['put', 'options'], '/api/bookmarks/(:segment)', 'Api\Bookmarks::update/$1');
$routes->match(['post', 'options'], '/api/markdown/preview', 'Api\MarkdownPreview::convert');
$routes->match(['get', 'options'], '/api/screenshot/preview', 'Api\ScreenshotPreview::url');
$routes->match(['post', 'options'], '/api/screenshot/capture', 'Api\ScreenshotPreview::capture');

// Command line routes
$routes->cli('cli/test/index/(:segment)', 'CLI\Test::index/$1');
$routes->cli('cli/test/count', 'CLI\Test::count');

// Metrics route
$routes->post('/metrics/receive', 'Metrics::receive');

// Feed routes
$routes->get('/feed/rss', 'Feed::rss');

// Logout route
$routes->get('/logout', 'Auth::logout');

// Unauthorised route
$routes->get('/unauthorised', 'Unauthorised::index');

// Custom 404 route
$routes->set404Override('App\Controllers\Errors::show404');

// Debug routes
$routes->get('/debug', 'Debug\Home::index');
$routes->get('/debug/(:segment)', 'Debug\Rerouter::reroute/$1');
$routes->get('/debug/(:segment)/(:segment)', 'Debug\Rerouter::reroute/$1/$2');
