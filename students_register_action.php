<?php
session_start();
require 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = $_POST['username'];
    $password  = $_POST['password'];
    $real_name = $_POST['real_name'];
    $gender    = $_POST['gender'];
    $age       = $_POST['age'] ?: null;
    $campus    = $_POST['campus'];
    $phone     = $_POST['phone'];
    $email     = $_POST['email'] ?: null;

    // 密码强度校验
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/', $password)) {
        echo "
<script>alert('密码必须为8-16位，包含字母、数字和特殊字符');window.history.back();</script>";
        exit();
    }

    // 用户名是否存在
    $stmt = $conn->prepare("SELECT id FROM students WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "
<script>alert('用户名已存在');window.history.back();</script>";
        exit();
    }
    $stmt->close();

    // 插入
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO students (username,password,real_name,gender,age,campus,phone,email) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssisss", $username, $hash, $real_name, $gender, $age, $campus, $phone, $email);

    if ($stmt->execute()) {
        header("Location: success.html");
        exit;
    } else {
        echo "注册失败: " . $conn->error;
    }
    $stmt->close();
    $conn->close();
}
