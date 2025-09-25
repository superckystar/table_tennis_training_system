<?php
session_start();
require 'conn.php';

// 检查是否已登录
if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取教练id
$stmt = $conn->prepare("SELECT id FROM coaches WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$coach = $result->fetch_assoc();
$coach_id = $coach['id'];
$stmt->close();

// 处理审批操作
if (isset($_POST['action']) && isset($_POST['relation_id'])) {
    $relation_id = intval($_POST['relation_id']);
    $action = $_POST['action'];

    if ($action == "approve") {
        $stmt = $conn->prepare("UPDATE student_coach_relations SET status='approved', response_date=NOW() WHERE id=? AND coach_id=?");
    } else {
        $stmt = $conn->prepare("UPDATE student_coach_relations SET status='rejected', response_date=NOW() WHERE id=? AND coach_id=?");
    }
    $stmt->bind_param("ii", $relation_id, $coach_id);
    $stmt->execute();
    $stmt->close();
}

// 获取所有待审批的申请
$stmt = $conn->prepare("
    SELECT scr.id as relation_id, scr.status, scr.request_date,
           s.real_name, s.gender, s.age, s.campus, s.phone, s.email
    FROM student_coach_relations scr
    JOIN students s ON scr.student_id = s.id
    WHERE scr.coach_id=? AND scr.status='pending'
    ORDER BY scr.request_date DESC
");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$requests = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>审核学生申请</title>
<style>
body { font-family:"Microsoft YaHei",Arial; background:#f0f2f5; margin:0; padding:0; }
.container { max-width:900px; margin:40px auto; background:#fff; padding:25px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#333; margin-bottom:20px; }
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:center; }
th { background:#3498db; color:#fff; }
tr:hover { background:#f9f9f9; }
button { padding:6px 15px; border:none; border-radius:6px; cursor:pointer; font-size:14px; }
button.approve { background:#2ecc71; color:#fff; }
button.approve:hover { background:#27ae60; }
button.reject { background:#e74c3c; color:#fff; }
button.reject:hover { background:#c0392b; }
.empty { text-align:center; color:#888; margin-top:20px; }
.back-btn { margin-top:20px; text-align:center; }
.back-btn a { display:inline-block; padding:10px 20px; background:#3498db; color:#fff; text-decoration:none; border-radius:6px; }
.back-btn a:hover { background:#2980b9; }
</style>
</head>
<body>
<div class="container">
    <h2>学生申请审核</h2>

    <?php if ($requests->num_rows > 0): ?>
        <table>
            <tr>
                <th>姓名</th>
                <th>性别</th>
                <th>年龄</th>
                <th>校区</th>
                <th>电话</th>
                <th>邮箱</th>
                <th>申请时间</th>
                <th>操作</th>
            </tr>
            <?php while($row = $requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['real_name']); ?></td>
                <td><?php echo $row['gender']=='male'?'男':'女'; ?></td>
                <td><?php echo htmlspecialchars($row['age']); ?></td>
                <td><?php echo htmlspecialchars($row['campus']); ?></td>
                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['request_date']); ?></td>
                <td>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="relation_id" value="<?php echo $row['relation_id']; ?>">
                        <button type="submit" name="action" value="approve" class="approve">通过</button>
                    </form>
                    <form method="post" style="display:inline-block;">
                        <input type="hidden" name="relation_id" value="<?php echo $row['relation_id']; ?>">
                        <button type="submit" name="action" value="reject" class="reject">拒绝</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <div class="empty">当前没有待审核的学生申请</div>
    <?php endif; ?>

    <div class="back-btn">
        <a href="t_success.php">返回教练主页</a>
    </div>
</div>
</body>
</html>
