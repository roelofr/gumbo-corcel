<?php

/**
 * WEB ROUTES
 *
 * Here is where you can register web routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * contains the "web" middleware group. Now create something great!
 */

// Home and privacy policy
Route::get('/', 'PageController@homepage')->name('home');
Route::get('/privacy-policy', 'PageController@privacy')->name('privacy');

// Sitemap
Route::get('/sitemap.xml', 'SitemapController@index')->name('sitemap');

// News route
Route::get('/news', 'NewsController@index');
Route::get('/news/{slug}', 'NewsController@post');

// Add search route
Route::get('/search', 'SearchController@index')->name('search-form');
Route::get('/search/{query}', 'SearchController@search')->name('search');

/**
 * Plazacam routes
 */
Route::get('plazacam/{image}', 'PlazaCamController@image')
    ->middleware(['auth', 'member'])
    ->name('plazacam');

/**
 * Files route
 */
Route::middleware('auth')->prefix('files')->name('files.')->group(function () {
    // Main route
    Route::get('/', 'FileController@index')->name('index');

    // Subcategory route
    Route::get('/{category}', 'FileController@category')->name('category');

    // Single file view
    Route::get('/view/{file}', 'FileController@show')->name('show');

    // Download view
    Route::get('/download/{file}', 'FileController@download')->name('download');
});

/**
 * Activities
 */
Route::prefix('activity')->name('activity.')->group(function () {
    // USER ROUTES
    // Main route
    Route::get('/', 'ActivityController@index')->name('index');

    // Single view
    Route::get('/{activity}', 'ActivityController@show')->name('show');

    // Single view
    Route::get('/{activity}/login', 'ActivityController@login')->name('login');
});
// Fix sometimes linking to /activities
Route::permanentRedirect('/activities', '/activity');

/**
 * Enrollments
 */
Route::prefix('enroll')->name('enroll.')->group(function () {
    // Default route¸ redirect → my enrollments
    Route::permanentRedirect('/', '/me');

    // My enrollments
    Route::get('/me', 'EnrollmentController@index')->name('index');

    // Enroll status view
    Route::post('/{activity}', 'EnrollmentController@status')->name('show');

    // Enroll start
    Route::post('/{activity}/create', 'EnrollmentController@create')->name('create');

    // Enroll update
    Route::get('/{activity}/update', 'EnrollmentController@edit')->name('edit');

    // Enroll update
    Route::post('/{activity}/update', 'EnrollmentController@update');

    // Enroll payment (configure)
    Route::get('/{activity}/payment', 'EnrollmentController@payment')->name('pay');

    // Enroll payment (apply or update)
    Route::post('/{activity}/payment', 'EnrollmentController@paymentStart');

    // Enroll payment (completed)
    Route::get('/{activity}/payment', 'EnrollmentController@paymentReturn')->name('pay-after');

    // Enroll removal (confirm)
    Route::get('/{activity}/delete', 'EnrollmentController@unenroll')->name('delete');

    // Enroll removal (perform)
    Route::delete('/{activity}/delete', 'EnrollmentController@destroy');
});

/**
 * News (through WordPress posts)
 */
Route::prefix('news')->name('news.')->group(function () {
    // Main route
    Route::get('/', 'NewsController@index')->name('index');

    // Single view
    Route::get('/{news}', 'NewsController@show')->name('show');
});

/**
 * Join controller
 */
Route::prefix('join')->name('join.')->group(function () {
    // Join form
    Route::get('/', 'JoinController@index')->name('form');

    // Submit button
    Route::post('/send', 'JoinController@submit')->name('submit');

    // Post-join
    Route::get('/welcome', 'JoinController@complete')->name('complete');
});

// Authentication and forgotten passwords
Route::prefix('auth')->group(function () {
    Route::auth([
        'verify' => true,
        'register' => true
    ]);
});

// My account
Route::prefix('me')->name('user.')->middleware('auth')->group(function () {
    Route::get('/', 'UserController@index')->name('home');
    Route::get('/info', 'UserController@view')->name('info');
    Route::patch('/info', 'UserController@update');
});

// Common mistakes handler
Route::redirect('/sign-up', '/join');

// Page fallback
Route::fallback('PageController@fallback');

// LEGACY REDIRECTS
Route::get('/nova', 'LegacyController@gone');
