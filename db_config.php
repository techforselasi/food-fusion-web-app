<?php
const DB_HOST="127.0.0.1";
const DB_NAME="food_fusion_db";
const DB_USER="root";
const DB_PASSWORD="Leslie12345";


try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
    
    // This tells PDO to be strict and alert us immediately if the query fails
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "The handshake was successful! You are connected.";
    
} catch (PDOException $e) {
    // If it fails, stop everything and show the message "Connection failed"
    die("Connection failed: " . $e->getMessage());
}
?>