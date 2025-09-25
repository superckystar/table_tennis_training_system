<?php 
session_start();
if (!isset($_SESSION['username'])){
    header("Location: login_s.php");
    exit();
}

require_once "conn.php"; // 使用统一数据库连接文件

// 获取当前学生 id
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_id = $student['id'];

// 教练级别对应费用
$level_fee = [1 => 80, 2 => 150, 3 => 200];

// 处理确认操作
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])){
    $appointment_id = intval($_POST['appointment_id']);

    // 开始事务
    $conn->begin_transaction();
    try {
        // 获取预约信息和教练级别及是否已支付
        $stmt = $conn->prepare("
            SELECT a.paid, c.level 
            FROM appointments a 
            JOIN coaches c ON a.coach_id = c.id 
            WHERE a.id=? AND a.student_id=? AND a.status='cancel_requested' AND a.cancel_requested_by='coach'
        ");
        $stmt->bind_param("ii", $appointment_id, $student_id);
        $stmt->execute();
        $stmt->bind_result($paid, $coach_level);
        if($stmt->fetch()){
            $stmt->close();

            // 更新预约状态为 cancelled
            $stmt = $conn->prepare("UPDATE appointments SET status='cancelled', cancelled_time=NOW() WHERE id=?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $stmt->close();

            // 如果已支付则退款
            if($paid === 'yes'){
                $refund = $level_fee[$coach_level] ?? 0;
                if($refund > 0){
                    $stmt = $conn->prepare("UPDATE students SET balance = balance + ? WHERE id=?");
                    $stmt->bind_param("di", $refund, $student_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conn->commit();
            $message = "✅ 已确认教练的取消请求，本次预约已取消，若已支付，费用已退回余额。";
        } else {
            $stmt->close();
            $conn->rollback();
            $message = "❌ 确认失败，请稍后重试。";
        }
    } catch(Exception $e){
        $conn->rollback();
        $message = "❌ 操作失败，请稍后重试。";
    }
}

// 查询当前学生需要确认的取消请求
$stmt = $conn->prepare("SELECT a.*, c.real_name AS coach_name 
                        FROM appointments a
                        JOIN coaches c ON a.coach_id = c.id
                        WHERE a.student_id=? AND a.status='cancel_requested' AND a.cancel_requested_by='coach'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$requests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>教练取消预约确认</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:900px;margin:40px auto;padding:20px;background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);text-align:center;}
h2{text-align:center;color:#333;}
.message{text-align:center;margin:15px 0;font-weight:bold;color:#d35400;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{border:1px solid #ddd;padding:10px;text-align:center;}
th{background:#3498db;color:#fff;}
button{padding:8px 16px;border:none;border-radius:6px;background:#2ecc71;color:#fff;cursor:pointer;}
button:hover{background:#27ae60;}
a.return-btn{display:inline-block;margin-top:20px;padding:10px 20px;background:#007BFF;color:#fff;text-decoration:none;border-radius:6px;}
a.return-btn:hover{background:#0056b3;}
</style>
</head>
<body>
<div class="container">
    <h2>教练取消预约确认</h2>

    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if ($requests->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>教练</th>
                <th>校区</th>
                <th>球桌号</th>
                <th>日期</th>
                <th>时间段</th>
                <th>操作</th>
            </tr>
            <?php while($row = $requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['coach_name']); ?></td>
                <td><?php echo htmlspecialchars($row['campus']); ?></td>
                <td><?php echo htmlspecialchars($row['court_number']); ?></td>
                <td><?php echo htmlspecialchars($row['date']); ?></td>
                <td><?php echo htmlspecialchars($row['time_slot']); ?></td>
                <td>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                        <button type="submit">确认取消</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p class="message">暂无需要确认的教练取消请求。</p>
    <?php endif; ?>

    <!-- 返回学生主页按钮 -->
    <a href="s_success.php" class="return-btn">返回学生主页</a>
</div>
</body>
</html>
