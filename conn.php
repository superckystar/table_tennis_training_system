<?php
$host = 'localhost';
$user = 'root1';
$pass = '123';
$db = 'table_tennis_training_system';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("连接失败: " . $conn->connect_error);
?>