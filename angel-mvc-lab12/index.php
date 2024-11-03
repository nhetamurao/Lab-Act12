<?php

require "vendor/autoload.php";
require "init.php";

// Database connection object (from init.php)
global $conn;

try {
    // Create Router instance
    $router = new \Bramus\Router\Router();

    // Define routes
    $router->get('/', '\App\Controllers\HomeController@index');

    // Registration routes
    $router->get('/register', '\App\Controllers\ExamController@registrationForm');
    $router->post('/register', '\App\Controllers\ExamController@register');

    // Login routes
    $router->get('/login', '\App\Controllers\ExamController@loginForm');
    $router->post('/login', '\App\Controllers\ExamController@login');

    // Exam route, restricted to authenticated users
    $router->before('GET|POST', '/exam', function() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    });

    $router->get('/exam', '\App\Controllers\ExamController@exam');
    $router->post('/exam', '\App\Controllers\ExamController@exam');

    // Result route, accessible only after exam
    $router->get('/result', '\App\Controllers\ExamController@result');

    // Examinees route to list all exam attempts
    $router->get('/examinees', '\App\Controllers\ExamController@listExaminees');
    
    // Export single exam attempt to PDF
    $router->get('/export/attempt/{id}', '\App\Controllers\ExamController@exportAttemptToPDF');

    // Run the router
    $router->run();

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
