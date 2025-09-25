<?php
session_start();
require 'conn.php';

if (!isset($_SESSION['username'])) {
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];

// 获取学生信息
$stmt = $conn->prepare("SELECT id, real_name FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id, $real_name);
$stmt->fetch();
$stmt->close();

// 获取当前已绑定的教练（status=approved, action_type=bind）
$stmt = $conn->prepare("
    SELECT sc.id AS relation_id, c.real_name, c.campus, c.phone 
    FROM student_coach_relations sc
    JOIN coaches c ON sc.coach_id = c.id
    WHERE sc.student_id=? AND sc.status='approved' AND sc.action_type='bind'
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$coaches = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 处理解绑申请（更新，而不是插入）
if (isset($_POST['unbind'])) {
    $relation_id = $_POST['relation_id'];

    // 检查是否已经有解绑申请
    $stmt = $conn->prepare("
        SELECT id FROM student_coach_relations 
        WHERE id=? AND student_id=? AND action_type='unbind' AND status='pending'
    ");
    $stmt->bind_param("ii", $relation_id, $student_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error_msg = "你已经对该教练提交过解绑申请，请耐心等待处理。";
    } else {
        $stmt->close();
        // 更新绑定关系为解绑申请
        $stmt = $conn->prepare("
            UPDATE student_coach_relations
            SET status='pending', action_type='unbind', request_date=NOW(), response_date=NULL
            WHERE id=? AND student_id=? AND status='approved' AND action_type='bind'
        ");
        $stmt->bind_param("ii", $relation_id, $student_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success_msg = "解绑申请已提交，请等待教练审批。";
        } else {
            $error_msg = "提交失败，请稍后重试。";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>申请解绑教练</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f4f4f9;padding:20px;}
.container{max-width:600px;margin:40px auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h2{text-align:center;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;}
th, td{border:1px solid #ddd;padding:10px;text-align:center;}
th{background:#007bff;color:white;}
button{padding:8px 16px;background:#dc3545;color:#fff;border:none;border-radius:6px;cursor:pointer;}
button:hover{background:#c82333;}
.success-msg{color:green;text-align:center;margin-bottom:10px;}
.error-msg{color:red;text-align:center;margin-bottom:10px;}
</style>
</head>
<body>
<div class="container">
<h2>申请解绑教练</h2>

<?php
if (isset($success_msg)) echo "<div class='success-msg'>$success_msg</div>";
if (isset($error_msg)) echo "<div class='error-msg'>$error_msg</div>";
?>

<?php if (count($coaches) > 0): ?>
    <table>
        <tr>
            <th>教练姓名</th>
            <th>校区</th>
            <th>电话</th>
            <th>操作</th>
        </tr>
        <?php foreach($coaches as $coach): ?>
        <tr>
            <td><?php echo htmlspecialchars($coach['real_name']); ?></td>
            <td><?php echo htmlspecialchars($coach['campus']); ?></td>
            <td><?php echo htmlspecialchars($coach['phone']); ?></td>
            <td>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="relation_id" value="<?php echo $coach['relation_id']; ?>">
                    <button type="submit" name="unbind">申请解绑</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p style="text-align:center;">你当前没有绑定任何教练。</p>
<?php endif; ?>

<div style="text-align:center;margin-top:20px;">
    <form action="s_success.php" method="get">
        <button type="submit" style="background:#3498db;">返回主页</button>
    </form>
</div>
</div>
</body>
</html>
