<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login_s.php");
    exit();
}

require_once "conn.php"; // 数据库连接

$username = $_SESSION['username'];

// 查询学生 ID 和当前余额
$stmt = $conn->prepare("SELECT id, balance FROM students WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_id = $student['id'];
$current_balance = $student['balance'];

$message = "";

// 处理充值提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);

    if ($amount > 0) {
        $stmt = $conn->prepare("UPDATE students SET balance = balance + ? WHERE id=?");
        $stmt->bind_param("di", $amount, $student_id);
        if ($stmt->execute()) {
            $message = "✅ 充值成功！充值金额：" . number_format($amount, 2) . " 元";
            // 更新最新余额
            $stmt = $conn->prepare("SELECT balance FROM students WHERE id=?");
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $current_balance = $row['balance'];
        } else {
            $message = "❌ 充值失败，请稍后重试。";
        }
    } else {
        $message = "❌ 请输入正确的充值金额（大于 0）。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>账户充值</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:600px;margin:50px auto;padding:30px;background:#fff;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h2{text-align:center;color:#333;margin-bottom:20px;}
.message{text-align:center;font-weight:bold;color:#d35400;margin:15px 0;}
form{text-align:center;}
input[type="number"]{padding:10px;width:200px;border:1px solid #ccc;border-radius:6px;margin-bottom:15px;}
button{padding:10px 20px;border:none;border-radius:6px;background:#3498db;color:#fff;cursor:pointer;}
button:hover{background:#2980b9;}
.balance{text-align:center;margin-top:20px;font-size:18px;color:#27ae60;}
.back{text-align:center;margin-top:20px;}
.back a{display:inline-block;padding:8px 16px;background:#95a5a6;color:#fff;border-radius:6px;text-decoration:none;}
.back a:hover{background:#7f8c8d;}
</style>
</head>
<body>
<div class="container">
    <h2>账户充值</h2>

    <?php if ($message): ?>
        <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="number" name="amount" step="0.01" min="0.01" placeholder="请输入充值金额" required>
        <br>
        <button type="submit">充值</button>
    </form>

    <p class="balance">当前余额：<?php echo number_format($current_balance, 2); ?> 元</p>

    <div class="back">
        <a href="s_success.php">返回学生主页</a>
    </div>
</div>
</body>
</html>
