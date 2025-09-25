<?php
session_start();
require 'conn.php';

// 检查是否已登录
if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取管理员信息
$stmt = $conn->prepare("SELECT id, username, password, name, gender, age, campus, phone, email FROM admins WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($id, $username_db, $password_db, $name, $gender, $age, $campus, $phone, $email);
$stmt->fetch();
$stmt->close();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取提交数据
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $new_name = trim($_POST['name']);
    $new_gender = $_POST['gender'];
    $new_age = $_POST['age'] ? intval($_POST['age']) : null; // 可空
    $new_phone = trim($_POST['phone']);
    $new_email = trim($_POST['email']);

    // 验证用户名不为空
    if (empty($new_username)) {
        $errors[] = "用户名不能为空";
    } else {
        // 检查用户名是否重复（排除自己）
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username=? AND id<>?");
        $stmt->bind_param("si", $new_username, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "用户名已存在，请选择其他用户名";
        }
        $stmt->close();
    }

    // 验证密码是否符合规范，如果填写了密码则必须符合规范
    if (!empty($new_password)) {
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,16}$/', $new_password)) {
            $errors[] = "密码必须为8-16位，包含字母、数字和特殊字符";
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        }
    } else {
        // 不修改密码
        $password_hash = $password_db;
    }

    // 验证电话不为空
    if (empty($new_phone)) {
        $errors[] = "联系电话不能为空";
    }

    // 如果没有错误，更新数据库
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE admins SET username=?, password=?, name=?, gender=?, age=?, phone=?, email=? WHERE id=?");
        $stmt->bind_param("ssssisss", $new_username, $password_hash, $new_name, $new_gender, $new_age, $new_phone, $new_email, $id);
        if ($stmt->execute()) {
            $success = "信息更新成功";
            $_SESSION['username'] = $new_username; // 更新 session
            // 重新获取最新数据
            $username_db = $new_username;
            $name = $new_name;
            $gender = $new_gender;
            $age = $new_age;
            $phone = $new_phone;
            $email = $new_email;
        } else {
            $errors[] = "更新失败，请稍后重试";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>个人信息管理</title>
    <style>
        body{font-family:"Microsoft YaHei",Arial;background:#f4f6f9;margin:0;padding:0;}
        .container{max-width:600px;margin:40px auto;padding:20px;background:#fff;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
        h2{text-align:center;color:#333;}
        form{margin-top:20px;}
        label{display:block;margin:10px 0 5px;}
        input, select{width:100%;padding:8px;border:1px solid #ccc;border-radius:6px;}
        button{margin-top:20px;padding:10px 15px;border:none;border-radius:6px;background:#3498db;color:#fff;cursor:pointer;}
        button:hover{background:#2980b9;}
        .message{margin:10px 0;padding:10px;border-radius:6px;}
        .success{background:#2ecc71;color:#fff;}
        .error{background:#e74c3c;color:#fff;}
        .back-btn{display:block;margin:20px auto;padding:10px 20px;background:#3498db;color:#fff;border:none;border-radius:6px;cursor:pointer;}
        .back-btn:hover{background:#2980b9;}
    </style>
</head>
<body>
<div class="container">
    <h2>个人信息管理</h2>

    <button class="back-btn" onclick="window.location.href='a_success.php'">返回管理员主页</button>

    <?php if (!empty($success)) echo "<div class='message success'>{$success}</div>"; ?>
    <?php foreach ($errors as $err) echo "<div class='message error'>{$err}</div>"; ?>

    <form method="post">
        <label>用户名</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($username_db); ?>" required>

        <label>新密码（不修改请留空）</label>
        <input type="password" name="password" placeholder="8-16位，包含字母、数字和特殊字符">

        <label>真实姓名</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>

        <label>性别</label>
        <select name="gender">
            <option value="">请选择</option>
            <option value="男" <?php if($gender==='男') echo 'selected'; ?>>男</option>
            <option value="女" <?php if($gender==='女') echo 'selected'; ?>>女</option>
        </select>

        <label>年龄</label>
        <input type="number" name="age" value="<?php echo htmlspecialchars($age); ?>" min="0">

        <label>校区（不可修改）</label>
        <input type="text" value="<?php echo htmlspecialchars($campus); ?>" disabled>

        <label>电话</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>

        <label>邮箱</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>">

        <button type="submit">保存修改</button>
    </form>
</div>
</body>
</html>
