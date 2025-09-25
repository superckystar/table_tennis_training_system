<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$coach_username = $_SESSION['username'];

// 获取教练ID
$stmt = $conn->prepare("SELECT id, level FROM coaches WHERE username=?");
$stmt->bind_param("s", $coach_username);
$stmt->execute();
$stmt->bind_result($coach_id, $coach_level);
$stmt->fetch();
$stmt->close();

// 教练级别对应费用
$level_fee = [1 => 80, 2 => 150, 3 => 200];

// 处理教练操作：确认或拒绝取消
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = intval($_POST['appointment_id']);
    $action = $_POST['action'] ?? '';

    if ($action === 'confirm') {
        // 获取预约信息
        $stmt = $conn->prepare("SELECT student_id, status, paid FROM appointments WHERE id=? AND coach_id=?");
        $stmt->bind_param("ii", $appointment_id, $coach_id);
        $stmt->execute();
        $stmt->bind_result($student_id, $status, $paid);
        if ($stmt->fetch()) {
            $stmt->close();
            if ($status === 'cancel_requested') {
                // 开启事务
                $conn->begin_transaction();
                try {
                    // 更新预约状态为 cancelled
                    $stmt = $conn->prepare("UPDATE appointments SET status='cancelled', cancelled_time=NOW() WHERE id=?");
                    $stmt->bind_param("i", $appointment_id);
                    $stmt->execute();
                    $stmt->close();

                    // 如果已支付，则退款
                    if ($paid === 'yes') {
                        // 计算费用（通过教练级别）
                        $stmt = $conn->prepare("SELECT c.level FROM appointments a JOIN coaches c ON a.coach_id=c.id WHERE a.id=?");
                        $stmt->bind_param("i", $appointment_id);
                        $stmt->execute();
                        $stmt->bind_result($coach_level_refund);
                        $stmt->fetch();
                        $stmt->close();

                        $refund_amount = $level_fee[$coach_level_refund] ?? 0;
                        if($refund_amount > 0){
                            $stmt = $conn->prepare("UPDATE students SET balance = balance + ? WHERE id=?");
                            $stmt->bind_param("di", $refund_amount, $student_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }

                    $conn->commit();
                    $msg = "已确认取消预约，若已支付，费用已退回学生账户。";
                } catch (Exception $e) {
                    $conn->rollback();
                    $msg = "操作失败，请重试。";
                }
            } else {
                $msg = "预约状态不是取消申请，无法确认";
            }
        } else {
            $stmt->close();
            $msg = "预约不存在或无权限";
        }
    } elseif ($action === 'reject') {
        // 拒绝取消
        $stmt = $conn->prepare("UPDATE appointments SET status='confirmed', cancel_requested_by=NULL WHERE id=? AND coach_id=?");
        $stmt->bind_param("ii", $appointment_id, $coach_id);
        $stmt->execute();
        $stmt->close();
        $msg = "已拒绝学生取消申请";
    }
}

// 获取待教练确认的取消申请
$stmt = $conn->prepare("
    SELECT a.id, s.username, a.date, a.time_slot, a.court_number, a.status
    FROM appointments a
    JOIN students s ON a.student_id = s.id
    WHERE a.coach_id=? AND a.status='cancel_requested' AND a.cancel_requested_by='student'
    ORDER BY a.date, a.time_slot
");
$stmt->bind_param("i", $coach_id);
$stmt->execute();
$res = $stmt->get_result();
$requests = [];
while ($row = $res->fetch_assoc()) $requests[] = $row;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>取消预约确认</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f6f9;margin:0;padding:0;}
.container{max-width:900px;margin:40px auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;font-size:22px;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;font-size:14px;margin-bottom:20px;}
th,td{border:1px solid #ddd;padding:8px;text-align:center;}
th{background:#3498db;color:#fff;}
button{padding:6px 12px;margin:2px;border:none;border-radius:6px;cursor:pointer;color:#fff;}
button.confirm{background:#2ecc71;}
button.confirm:hover{background:#27ae60;}
button.reject{background:#e74c3c;}
button.reject:hover{background:#c0392b;}
.message{color:#e67e22;text-align:center;margin-bottom:10px;}
a{display:inline-block;margin-top:10px;text-decoration:none;color:#fff;background:#3498db;padding:8px 16px;border-radius:6px;}
a:hover{background:#2980b9;}
</style>
</head>
<body>
<div class="container">
<h2>学生取消预约确认</h2>

<?php if(isset($msg)) echo "<div class='message'>".htmlspecialchars($msg)."</div>"; ?>

<?php if(count($requests)===0): ?>
<p style="text-align:center;">暂无待确认的取消预约申请</p>
<?php else: ?>
<table>
<tr>
<th>学生用户名</th>
<th>日期</th>
<th>时间段</th>
<th>球台</th>
<th>预约状态</th>
<th>操作</th>
</tr>
<?php foreach($requests as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r['username']); ?></td>
<td><?php echo $r['date']; ?></td>
<td><?php echo $r['time_slot']; ?></td>
<td><?php echo $r['court_number']; ?></td>
<td><?php echo htmlspecialchars($r['status']); ?></td>
<td>
<form method="post" style="display:inline-block;">
    <input type="hidden" name="appointment_id" value="<?php echo $r['id']; ?>">
    <button type="submit" name="action" value="confirm" class="confirm">确认取消</button>
    <button type="submit" name="action" value="reject" class="reject">拒绝取消</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<div style="text-align:center;">
    <a href="t_success.php">返回主页</a>
</div>
</div>
</body>
</html>
