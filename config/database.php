<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$host = 'localhost';
$username = 'francois';
$password = '123';
$database = 'career_sim';

try {
    $con = new mysqli($host, $username, $password, $database);
    $con->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    exit('Database connection unavailable.');
}
