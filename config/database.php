<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $con = new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'career_sim');
    $con->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    exit('Database connection unavailable.');
}
