<?php
session_start();
require 'conn.php';

// 检查是否为管理员
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// 获取学生ID并查询该学生信息
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];

    // 获取学生信息
    $stmt = $conn->prepare("SELECT id, username, real_name, gender, age, phone, email, balance FROM students WHERE id=?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->bind_result($id, $username, $real_name, $gender, $age, $phone, $email, $balance);
    $stmt->fetch();
    $stmt->close();
} else {
    header("Location: a_manage_students.php");
    exit();
}

// 处理学生信息更新
if (isset($_POST['update'])) {
    $new_username   = $_POST['username'];
    $new_real_name  = $_POST['real_name'];
    $new_gender     = $_POST['gender'];
    $new_age        = $_POST['age'];
    $new_phone      = $_POST['phone'];
    $new_email      = $_POST['email'];
    $new_password   = $_POST['password'];
    $new_balance    = $_POST['balance'];

    // 检查新用户名是否存在
    if ($new_username != $username) {
        $stmt = $conn->prepare("SELECT id FROM students WHERE username=?");
        $stmt->bind_param("s", $new_username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error_msg = "用户名已存在，请选择其他用户名！";
            $stmt->close();
        } else {
            $stmt->close();
        }
    }

    // 如果提供了新密码
    if (!empty($new_password)) {
        // 验证密码是否符合要求
        if (preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/", $new_password)) {
            // 密码符合规则
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET username=?, real_name=?, gender=?, age=?, phone=?, email=?, password=?, balance=? WHERE id=?");
            $stmt->bind_param("sssssssdi", $new_username, $new_real_name, $new_gender, $new_age, $new_phone, $new_email, $password_hash, $new_balance, $student_id);
        } else {
            $error_msg = "密码必须包含字母、数字和特殊符号，且长度为8-16位！";
        }
    } else {
        // 如果没有新密码，更新其他信息
        $stmt = $conn->prepare("UPDATE students SET username=?, real_name=?, gender=?, age=?, phone=?, email=?, balance=? WHERE id=?");
        $stmt->bind_param("ssssssdi", $new_username, $new_real_name, $new_gender, $new_age, $new_phone, $new_email, $new_balance, $student_id);
    }

    // 执行更新
    if (!isset($error_msg)) {
        if ($stmt->execute()) {
            // 更新成功后立即查询数据库最新数据
            $stmt = $conn->prepare("SELECT id, username, real_name, gender, age, phone, email, balance FROM students WHERE id=?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $stmt->bind_result($id, $username, $real_name, $gender, $age, $phone, $email, $balance);
            $stmt->fetch();
            $stmt->close();

            $success_msg = "信息更新成功！";
        } else {
            $error_msg = "更新失败！";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑学生信息</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
        }

        .container {
            width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input, select, button {
            margin: 10px 0;
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        button {
            background: #3498db;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #2980b9;
        }

        .error-msg {
            color: red;
            text-align: center;
        }

        .success-msg {
            color: green;
            text-align: center;
        }

        .back-btn {
            background: #e74c3c;
            color: white;
        }

        .back-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>编辑学生信息</h2>
        <?php 
        if (isset($success_msg)) {
            echo "<div class='success-msg'>$success_msg</div>";
        }
        if (isset($error_msg)) {
            echo "<div class='error-msg'>$error_msg</div>";
        }
        ?>
        <form method="POST">
            <label>用户名：</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

            <label>真实姓名：</label>
            <input type="text" name="real_name" value="<?php echo htmlspecialchars($real_name); ?>" required>

            <label>性别：</label>
            <select name="gender" required>
                <option value="male" <?php echo $gender == 'male' ? 'selected' : ''; ?>>男</option>
                <option value="female" <?php echo $gender == 'female' ? 'selected' : ''; ?>>女</option>
            </select>

            <label>年龄：</label>
            <input type="number" name="age" value="<?php echo htmlspecialchars($age); ?>" required>

            <label>电话：</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>

            <label>邮箱：</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

            <label>账户余额（存款）：</label>
            <input type="number" name="balance" step="0.01" value="<?php echo htmlspecialchars($balance); ?>" required>

            <label>新密码（留空不修改）：</label>
            <input type="password" name="password">

            <button type="submit" name="update">保存修改</button>
        </form>

        <button class="back-btn" onclick="location.href='a_manage_students.php'">返回学生管理</button>
    </div>
</body>
</html>
