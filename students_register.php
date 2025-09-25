<?php
require 'conn.php';
// 查询校区
$campus_options = "";
$sql = "SELECT id, name FROM campuses ORDER BY id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = htmlspecialchars($row['id']);
        $name = htmlspecialchars($row['name']);
        $campus_options .= "<option value=\"$name\">$name</option>\n";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>学员注册 - 乒乓球培训系统</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f4f4; padding:50px; }
        .container { max-width:600px; margin:0 auto; background:#fff; padding:30px;
            border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,.1); }
        label { display:block; margin-bottom:10px; font-weight:bold; }
        input, select { width:100%; padding:10px; margin-bottom:20px;
            border-radius:5px; border:1px solid #ccc; }
        input[type="submit"] { background:#4CAF50; color:#fff; border:none; cursor:pointer; }
        input[type="submit"]:hover { background:#45a049; }
    </style>
</head>
<body>
<div class="container">
    <h2>学员注册</h2>
    <form action="students_register_action.php" method="POST">
        <label for="username">用户名：</label>
        <input type="text" id="username" name="username" required placeholder="请输入用户名" />

        <label for="password">密码：</label>
        <input type="password" id="password" name="password" required placeholder="请输入密码（8-16位，字母+数字+特殊字符）" />

        <label for="real_name">真实姓名：</label>
        <input type="text" id="real_name" name="real_name" required placeholder="请输入真实姓名" />

        <label for="gender">性别：</label>
        <select id="gender" name="gender">
            <option value="">请选择</option>
            <option value="male">男</option>
            <option value="female">女</option>
        </select>

        <label for="age">年龄：</label>
        <input type="number" id="age" name="age" placeholder="请输入年龄" />

        <label for="campus">校区：</label>
        <select id="campus" name="campus" required>
            <option value="">请选择校区</option>
            <?= $campus_options ?>
        </select>

        <label for="phone">电话：</label>
        <input type="text" id="phone" name="phone" required placeholder="请输入联系电话" />

        <label for="email">邮箱：</label>
        <input type="email" id="email" name="email" placeholder="请输入邮箱（可选）" />

        <input type="submit" value="注册" />
    </form>
</div>
</body>
</html>
