<?php
session_start();
require 'conn.php';  // 数据库连接文件

// 检查数据库连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $username = $_POST['username'];
    $password = $_POST['password'];
    $real_name = $_POST['real_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'] ?? null;
    $campus = $_POST['campus'];
    $phone = $_POST['phone'];
    $email = $_POST['email'] ?? null;
    $achievements = $_POST['achievements'] ?? null;

    // 验证密码强度：8-16位，包含字母、数字、特殊字符
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/', $password)) {
        echo "<script>alert('密码必须为8-16位，包含字母、数字和特殊字符'); history.back();</script>";
        exit();
    }

    // 检查用户名是否已存在
    $stmt = $conn->prepare("SELECT id FROM coaches WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('用户名已存在，请选择其他用户名'); history.back();</script>";
        exit();
    }

    // 密码加密
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 处理上传的照片
    $photo_name = $_FILES['photo']['name'];
    $photo_tmp_name = $_FILES['photo']['tmp_name'];
    $photo_size = $_FILES['photo']['size'];
    $photo_error = $_FILES['photo']['error'];

    // 检查上传文件是否有错误
    if ($photo_error !== UPLOAD_ERR_OK) {
        echo "<script>alert('上传文件时发生错误'); history.back();</script>";
        exit();
    }

    // 设置上传文件的目标路径
    $photo_target_dir = "uploads/photos/";
    $photo_target_file = $photo_target_dir . basename($photo_name);

    // 检查文件大小（最大5MB）
    if ($photo_size > 5000000) {
        echo "<script>alert('上传的照片过大，最大为5MB'); history.back();</script>";
        exit();
    }

    // 允许的文件类型
    $allowed_file_types = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = strtolower(pathinfo($photo_target_file, PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_file_types)) {
        echo "<script>alert('只允许上传JPG, JPEG, PNG 或 GIF 格式的图片'); history.back();</script>";
        exit();
    }

    // 移动文件到目标目录
    if (!move_uploaded_file($photo_tmp_name, $photo_target_file)) {
        echo "<script>alert('文件上传失败'); history.back();</script>";
        exit();
    }

    // 插入数据到数据库
    $stmt = $conn->prepare("INSERT INTO coaches (username, password, real_name, gender, age, campus, phone, email, photo, achievements, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $status = 'pending'; // 状态为"待审核"
    $stmt->bind_param("ssssissssss", $username, $hashed_password, $real_name, $gender, $age, $campus, $phone, $email, $photo_target_file, $achievements, $status);

    if ($stmt->execute()) {
        echo "<script>alert('注册申请成功，等待管理员审核！'); window.location.href='login_t.html';</script>";
    } else {
        echo "<script>alert('注册失败，请重试'); history.back();</script>";
    }

    $stmt->close();
    $conn->close();
}
?>
