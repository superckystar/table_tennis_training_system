<?php
session_start();
require 'conn.php';

// 获取要编辑的管理员ID
if (!isset($_GET['id'])) {
    echo "<script>alert('缺少管理员ID');window.location.href='manage_admins.php';</script>";
    exit();
}
$id = intval($_GET['id']);

// 获取管理员信息
$stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<script>alert('管理员不存在');window.location.href='manage_admins.php';</script>";
    exit();
}
$admin = $result->fetch_assoc();
$stmt->close();

// 获取所有校区
$campuses = $conn->query("SELECT name FROM campuses ORDER BY name ASC");

// 处理表单提交
if (isset($_POST['update'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // 可以为空表示不修改
    $name = trim($_POST['name']);
    $gender = $_POST['gender'];
    $age = $_POST['age'] !== '' ? intval($_POST['age']) : null;
    $campus = $_POST['campus'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

    // 检查用户名是否重复（排除自己）
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE username=? AND id<>?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) {
        echo "<script>alert('用户名已存在，请使用其他用户名');history.back();</script>";
        exit();
    }

    // 如果有密码修改，检查格式
    if (!empty($password)) {
        if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=]).{8,16}$/', $password)) {
            echo "<script>alert('密码必须为8-16位，且包含字母、数字和特殊字符');history.back();</script>";
            exit();
        }
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE admins SET username=?, password=?, name=?, gender=?, age=?, campus=?, phone=?, email=? WHERE id=?");
        $stmt->bind_param("ssssisssi", $username, $password_hashed, $name, $gender, $age, $campus, $phone, $email, $id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET username=?, name=?, gender=?, age=?, campus=?, phone=?, email=? WHERE id=?");
        $stmt->bind_param("sssisssi", $username, $name, $gender, $age, $campus, $phone, $email, $id);
    }
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('修改成功');window.location.href='manage_admins.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>编辑管理员信息</title>
    <style>
        body{font-family:"Microsoft YaHei",Arial;background:#f4f6f9;margin:0;padding:0;}
        .container{max-width:600px;margin:50px auto;padding:20px;background:#fff;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
        h2{text-align:center;color:#333;}
        form label{display:block;margin-top:15px;font-weight:bold;}
        form input, form select{width:100%;padding:8px;margin-top:5px;border:1px solid #ccc;border-radius:6px;font-size:14px;}
        form button{margin-top:20px;width:100%;padding:10px;background:#28a745;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
        form button:hover{background:#218838;}
        .note{font-size:12px;color:#888;margin-top:3px;}
        .back-btn{display:block;margin-top:20px;text-align:center;}
        .back-btn a{color:#3498db;text-decoration:none;}
    </style>
</head>
<body>
<div class="container">
    <h2>编辑管理员信息</h2>
    <form method="post">
        <label>用户名：</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>

        <label>密码（留空表示不修改）：</label>
        <input type="password" name="password">
        <div class="note">密码必须8-16位，包含字母、数字和特殊字符</div>

        <label>真实姓名：</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>

        <label>性别：</label>
        <select name="gender" required>
            <option value="">请选择</option>
            <option value="男" <?php if($admin['gender']=='男') echo 'selected'; ?>>男</option>
            <option value="女" <?php if($admin['gender']=='女') echo 'selected'; ?>>女</option>
        </select>

        <label>年龄：</label>
        <input type="number" name="age" min="18" value="<?php echo htmlspecialchars($admin['age']); ?>">

        <label>校区：</label>
        <select name="campus" required>
            <option value="">请选择</option>
            <?php while($row = $campuses->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($row['name']); ?>" <?php if($row['name']==$admin['campus']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>电话：</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>" required>

        <label>邮箱：</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>">

        <button type="submit" name="update">保存修改</button>
    </form>
    <div class="back-btn">
        <a href="manage_admins.php">« 返回管理员列表</a>
    </div>
</div>
</body>
</html>
