<?php
session_start();
require 'conn.php';



// 获取所有校区的学生列表
$students = [];
$stmt = $conn->prepare("SELECT s.id, s.username, s.real_name, s.gender, s.age, s.phone, s.email, s.balance, s.campus FROM students s");
$stmt->execute();
$stmt->bind_result($id, $username, $real_name, $gender, $age, $phone, $email, $balance, $campus);
while ($stmt->fetch()) {
    $students[] = [
        'id' => $id,
        'username' => $username,
        'real_name' => $real_name,
        'gender' => $gender,
        'age' => $age,
        'phone' => $phone,
        'email' => $email,
        'balance' => $balance,
        'campus' => $campus,
    ];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>超级管理员 - 学员管理</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; }
        .container { width: 80%; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        h2 { text-align: center; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: center; border: 1px solid #ddd; }
        th { background-color: #3498db; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #ecf0f1; }
        button { padding: 5px 10px; border: none; border-radius: 6px; background: #3498db; color: white; cursor: pointer; }
        button:hover { background: #2980b9; }
        .delete-btn { background: #e74c3c; }
        .delete-btn:hover { background: #c0392b; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a { padding: 8px 12px; margin: 0 4px; border-radius: 6px; background: #ecf0f1; color: #2c3e50; text-decoration: none; }
        .pagination a.active { background: #3498db; color: #fff; font-weight: bold; }
        .pagination a:hover { background: #2980b9; }
        .back-btn { display: block; margin-top: 20px; text-align: center; background: #e74c3c; color: white; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .back-btn:hover { background: #c0392b; }
    </style>
</head>
<body>

<div class="container">
    <h2>超级管理员 - 管理所有校区学员</h2>

    <!-- 学生信息表格 -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>用户名</th>
                <th>姓名</th>
                <th>性别</th>
                <th>年龄</th>
                <th>电话</th>
                <th>邮箱</th>
                <th>校区</th>
                <th>余额</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo $student['id']; ?></td>
                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                    <td><?php echo htmlspecialchars($student['real_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                    <td><?php echo htmlspecialchars($student['age']); ?></td>
                    <td><?php echo htmlspecialchars($student['phone']); ?></td>
                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                    <td><?php echo htmlspecialchars($student['campus']); ?></td>
                    <td><?php echo htmlspecialchars($student['balance']); ?> 元</td>
                    <td>
                        <button onclick="location.href='edit_student.php?id=<?php echo $student['id']; ?>'">编辑</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 返回超级管理员首页按钮 -->
    <button class="back-btn" onclick="location.href='sa_success.html'">返回超级管理员首页</button>
</div>

</body>
</html>
