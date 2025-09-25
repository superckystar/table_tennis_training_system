<?php
session_start();
require 'conn.php';

if(!isset($_SESSION['username'])){
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 处理修改信息
if(isset($_POST['update'])){
    $new_username = $_POST['username'];
    $real_name = $_POST['real_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password_new = $_POST['password'];

    // 检查新用户名是否存在
    if ($new_username != $username) {
        // 准备 SQL 查询
        $stmt = $conn->prepare("SELECT id FROM students WHERE username=?");
        if ($stmt) {
            $stmt->bind_param("s", $new_username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error_msg = "用户名已存在，请选择其他用户名！";
                $stmt->close();
            } else {
                // 如果用户名不重复，更新用户名
                $stmt->close();
            }
        } else {
            $error_msg = "无法准备SQL语句！";
        }
    }

    // 如果提供了新密码，验证密码格式
    if (!empty($password_new)) {
        if (preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/", $password_new)) {
            // 密码符合规则
            $password_hash = password_hash($password_new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE students SET username=?, password=?, real_name=?, gender=?, age=?, phone=?, email=? WHERE username=?");
            if ($stmt) {
                $stmt->bind_param("ssssisss", $new_username, $password_hash, $real_name, $gender, $age, $phone, $email, $username);
            } else {
                $error_msg = "无法准备SQL语句！";
            }
        } else {
            $error_msg = "密码必须包含字母、数字和特殊符号，且长度为8-16位！";
        }
    } else {
        // 如果没有新密码，更新其他信息
        $stmt = $conn->prepare("UPDATE students SET username=?, real_name=?, gender=?, age=?, phone=?, email=? WHERE username=?");
        if ($stmt) {
            $stmt->bind_param("ssissss", $new_username, $real_name, $gender, $age, $phone, $email, $username);
        } else {
            $error_msg = "无法准备SQL语句！";
        }
    }

    if (!isset($error_msg)) {
        $stmt->execute();
        $stmt->close();
        $_SESSION['username'] = $new_username;  // 更新session中的用户名
        $success_msg = "信息更新成功！";
    }
}

// 获取学生信息（包括余额）
$stmt = $conn->prepare("SELECT username, real_name, gender, age, campus, phone, email, balance FROM students WHERE username=?");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($username, $real_name, $gender, $age, $campus, $phone, $email, $balance);
    $stmt->fetch();
    $stmt->close();
} else {
    $error_msg = "无法获取学生信息！";
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>个人信息</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:600px;margin:50px auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}

h2{text-align:center;color:#333;}
form label{display:block;margin-top:10px;font-weight:bold;color:#444;}
form input, form select{width:100%;padding:10px;margin-top:5px;border:1px solid #ddd;border-radius:6px;font-size:14px;}
form input:focus, form select:focus{border-color:#28a745;outline:none;}
button{margin-top:20px;padding:10px 20px;border:none;border-radius:6px;background:#28a745;color:#fff;font-size:16px;cursor:pointer;transition:background 0.3s;}
button:hover{background:#218838;}
.success-msg{color:green;margin-top:10px;text-align:center;}
.error-msg{color:red;margin-top:10px;text-align:center;}
</style>
</head>
<body>
<div class="container">
<h2>个人信息</h2>
<?php 
    if (isset($success_msg)) echo "<div class='success-msg'>$success_msg</div>"; 
    if (isset($error_msg)) echo "<div class='error-msg'>$error_msg</div>";
?>
<form method="post">
<label>用户名：</label>
<input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>

<label>密码（留空不修改）：</label>
<input type="password" name="password">

<label>真实姓名：</label>
<input type="text" name="real_name" value="<?php echo htmlspecialchars($real_name); ?>" required>

<label>性别：</label>
<select name="gender" required>
    <option value="male" <?php if($gender == 'male') echo 'selected'; ?>>男</option>
    <option value="female" <?php if($gender == 'female') echo 'selected'; ?>>女</option>
</select>

<label>年龄：</label>
<input type="number" name="age" value="<?php echo htmlspecialchars($age); ?>" required>

<label>校区：</label>
<input type="text" value="<?php echo htmlspecialchars($campus); ?>" disabled>

<label>电话：</label>
<input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>

<label>邮箱：</label>
<input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

<label>账户余额：</label>
<input type="text" value="<?php echo htmlspecialchars($balance); ?> 元" disabled>

<button type="submit" name="update">保存修改</button>
</form>
<form action="s_success.php" method="get" style="margin-top:20px; text-align:center;">
    <button type="submit" style="background:#3498db; color:#fff; padding:8px 16px; border:none; border-radius:6px; cursor:pointer;">返回主页</button>
</form>
</div>
</body>
</html>
