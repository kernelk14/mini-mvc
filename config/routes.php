<?php
/**
 * Application routes.
 * Loaded by Core_App during bootstrap (after compat).
 * Format: get|post|put|patch|delete|any($path, 'Controller@action');
 */

get('/', 'HomeController@index');
get('/users', 'UserController@index');
get('/users/show', 'UserController@show');   // e.g. /users/show?id=1
post('/users/save', 'UserController@save');

// put('/users/update', 'UserController@update');
// delete('/users/delete', 'UserController@delete');
// any('/health', 'HealthController@index');
