<?php
// Connect to the SQL database
// Adapted from https://phpdelusions.net/pdo
require_once 'login.php';

$dsn = "mysql:host=$hostname;dbname=$database;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try{
    $pdo = new PDO($dsn, $username, $password, $options);
    }
    catch (PDOException $e) {
    exit('Database connection failed: ' . $e->getMessage());
}


