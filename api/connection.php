<?php
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "dbpms";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $dbpassword);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log("Database connection successful to: $dbname");
    }catch(PDOException $e){
        error_log("Database connection failed: " . $e->getMessage());
        echo "Database connection failed: " . $e->getMessage();
    }
?>