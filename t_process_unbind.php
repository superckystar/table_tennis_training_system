<?php
session_start();
require_once 'conn.php'; // 数据库连接

// 确认教练已登录
if (!isset($_SESSION['username'])) {
    die("请先登录教练账号！");
}

$username = $_SESSION['username'];

// 获取当前教练 id
$sql = "SELECT id FROM coaches WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($coach_id);
if (!$stmt->fetch()) {
    die("未找到该教练信息！");
}
$stmt->close();

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $relation_id = intval($_POST['relation_id']);
    $decision = $_POST['decision']; // agree 或 reject

    if ($decision === 'agree') {
        // 同意解绑 → 改为 rejected
        $sql = "UPDATE student_coach_relations 
                SET status='rejected', response_date=NOW() 
                WHERE id=? AND coach_id=? AND action_type='unbind' AND status='pending'";
    } elseif ($decision === 'reject') {
        // 拒绝解绑 → 维持绑定 approved
        $sql = "UPDATE student_coach_relations 
                SET status='approved', response_date=NOW() 
                WHERE id=? AND coach_id=? AND action_type='unbind' AND status='pending'";
    } else {
        die("无效操作！");
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $relation_id, $coach_id);
    if ($stmt->execute()) {
        $msg = "处理成功！";
    } else {
        $msg = "处理失败：" . $conn->error;
    }
    $stmt->close();
}

// 查询该教练待处理的解绑申请
$sql = "SELECT scr.id, s.username AS student_name, scr.request_date 
        FROM student_coach_relations scr
        JOIN students s ON scr.student_id = s.id
        WHERE scr.coach_id=? AND scr.status='pending' AND scr.action_type='unbind'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>审核解绑申请</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: center; }
        th { background-color: #f2f2f2; }
        form { display: inline; }
        button { padding: 5px 10px; margin: 0 5px; cursor: pointer; }
        .msg { color: green; margin-bottom: 10px; }
        .back-btn { display: block; margin: 20px auto; padding: 10px 20px;
                    background: #007bff; color: white; border: none;
                    border-radius: 6px; cursor: pointer; text-decoration: none; }
        .back-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>学生解绑申请审核</h2>

    <?php if (isset($msg)) echo "<p class='msg'>$msg</p>"; ?>

    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>学生账号</th>
                <th>申请时间</th>
                <th>操作</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                    <td><?php echo $row['request_date']; ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="relation_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="decision" value="agree">同意解绑</button>
                            <button type="submit" name="decision" value="reject">拒绝解绑</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>暂无待处理的解绑申请。</p>
    <?php endif; ?>

    <!-- 返回主页按钮 -->
<div style="text-align:center; margin-top: 30px;">
    <a href="t_success.php" class="back-btn">← 返回主页</a>
</div>

<style>
.back-btn {
    display: inline-block;
    padding: 12px 28px;
    font-size: 16px;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(135deg, #4facfe, #00f2fe);
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}
.back-btn:hover {
    background: linear-gradient(135deg, #43e97b, #38f9d7);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}
</style>


</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
