<?php
session_start();
require 'conn.php';

if(!isset($_SESSION['username'])){
    header("Location: login_t.php");
    exit();
}

$username = $_SESSION['username'];
$appointment_id = intval($_POST['appointment_id'] ?? 0);
if(!$appointment_id) die("参数错误");

// 1. 获取该预约、教练级别、学生余额
$stmt = $conn->prepare("
    SELECT a.id, a.paid, c.level AS coach_level, s.balance 
    FROM appointments a
    JOIN coaches c ON a.coach_id = c.id
    JOIN students s ON s.username = ?
    WHERE a.id = ?");
$stmt->bind_param("si", $username, $appointment_id);
$stmt->execute();
$stmt->bind_result($aid, $paid, $coach_level, $balance);
if(!$stmt->fetch()){
    $stmt->close();
    die("未找到预约记录");
}
$stmt->close();

// 2. 判断是否已经支付
if($paid === 'yes'){
    echo "<script>alert('该课程已缴费，无需重复支付');window.location='s_pay.php';</script>";
    exit();
}

// 3. 根据教练级别计算费用
switch($coach_level){
    case 1: $fee = 80; break;
    case 2: $fee = 150; break;
    case 3: $fee = 200; break;
    default: 
        echo "<script>alert('未知教练级别，无法计算费用');window.location='s_pay.php';</script>";
        exit();
}

// 4. 判断余额是否足够
if($balance < $fee){
    echo "<script>alert('余额不足，请先充值');window.location='s_recharge.php';</script>";
    exit();
}

// 5. 开始事务，扣费 + 更新支付状态
$conn->begin_transaction();

try {
    // 扣除学生余额
    $stmt = $conn->prepare("UPDATE students SET balance = balance - ? WHERE username = ?");
    $stmt->bind_param("ds", $fee, $username);
    $stmt->execute();
    $stmt->close();

    // 更新预约为已支付
    $stmt = $conn->prepare("UPDATE appointments SET paid='yes' WHERE id=?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo "<script>alert('缴费成功');window.location='s_pay.php';</script>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('缴费失败，请重试');window.location='s_pay.php';</script>";
}
?>
