<?php
require 'conn.php'; // 数据库连接
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>教练注册 - 乒乓球培训系统</title>
    <style>
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            margin: 60px auto;
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #444;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            border-color: #28a745;
            outline: none;
        }
        .password-hint {
            font-size: 13px;
            color: #888;
            margin-top: -12px;
            margin-bottom: 12px;
        }
        button {
            width: 100%;
            background: #28a745;
            color: #fff;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #218838;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>教练注册</h2>
    <form action="coaches_register_action.php" method="post" enctype="multipart/form-data">
        <label>用户名：</label>
        <input type="text" name="username" required>

        <label>密码：</label>
        <input type="password" name="password" required>
        <div class="password-hint">密码至少 8 位，且必须包含字母、数字和特殊符号</div>

        <label>真实姓名：</label>
        <input type="text" name="real_name" required>

        <label>性别：</label>
        <select name="gender" required>
            <option value="">请选择</option>
            <option value="male">男</option>
            <option value="female">女</option>
        </select>

        <label>年龄：</label>
        <input type="number" name="age" min="18" required>

        <label>所属校区：</label>
        <select name="campus" required>
            <option value="">请选择校区</option>
            <?php
            $sql = "SELECT id, name FROM campuses ORDER BY id ASC";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $campus_name = htmlspecialchars($row['name']);
                    echo "<option value=\"{$campus_name}\">{$campus_name}</option>";
                }
            }
            ?>
        </select>

        <label>电话：</label>
        <input type="text" name="phone" required>

        <label>邮箱：</label>
        <input type="email" name="email">

        <label>上传照片：</label>
        <input type="file" name="photo" accept="image/*" required>

        <label>个人成就：</label>
        <textarea name="achievements" rows="4"></textarea>

        <button type="submit">提交注册</button>
    </form>
</div>

</body>
</html>
