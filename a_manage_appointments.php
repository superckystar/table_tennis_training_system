<?php
session_start();
require 'conn.php';

// 获取管理员所属校区
$username = $_SESSION['username'];
if (!$username) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT campus FROM admins WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($campus);
$stmt->fetch();
$stmt->close();

// 分页设置
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 处理预约状态更新
if (isset($_POST['action']) && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $action = $_POST['action'];
    $status = '';
    $refund_amount = 0;

    if ($action === 'confirm') {
        $status = 'confirmed';
    } elseif ($action === 'cancel') {
        $status = 'cancelled';

        // 获取该预约的学生ID和预约费用
        $stmt = $conn->prepare("SELECT student_id, coach_id, campus FROM appointments WHERE id=?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->bind_result($student_id, $coach_id, $campus);
        $stmt->fetch();
        $stmt->close();

        // 获取教练等级
        $stmt = $conn->prepare("SELECT level FROM coaches WHERE id=?");
        $stmt->bind_param("i", $coach_id);
        $stmt->execute();
        $stmt->bind_result($coach_level);
        $stmt->fetch();
        $stmt->close();

        // 根据教练等级来决定退款金额
        if ($coach_level == 3) {
            $refund_amount = 200; 
        } elseif ($coach_level == 2) {
            $refund_amount = 150; 
        } elseif ($coach_level == 1) {
            $refund_amount = 80; 
        }

        // 获取学生当前余额
        $stmt = $conn->prepare("SELECT balance FROM students WHERE id=? AND campus=?");
        $stmt->bind_param("is", $student_id, $campus);
        $stmt->execute();
        $stmt->bind_result($balance);
        $stmt->fetch();
        $stmt->close();

        // 更新学生余额：退款金额
        $new_balance = $balance + $refund_amount;
        $stmt = $conn->prepare("UPDATE students SET balance=? WHERE id=? AND campus=?");
        $stmt->bind_param("dis", $new_balance, $student_id, $campus);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'cancel_requested') {
        $status = 'cancel_requested';
    }

    // 更新预约状态
    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=? AND campus=?");
    $stmt->bind_param("sis", $status, $appointment_id, $campus);
    $stmt->execute();
    $stmt->close();

    header("Location: a_manage_appointments.php?page=$page");
    exit();
}

// 获取预约数量和分页
$result_count = $conn->query("SELECT COUNT(*) AS total FROM appointments WHERE campus='$campus'");
$total = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);
$offset = ($page - 1) * $limit;

// 获取预约信息
$result = $conn->query("SELECT * FROM appointments WHERE campus='$campus' ORDER BY date DESC, time_slot DESC LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>本校区预约管理</title>
    <style>
        body {
            font-family: "Microsoft YaHei", Arial;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #3498db;
            color: #fff;
        }

        tr:hover {
            background: #f1f9ff;
        }

        button {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .approve {
            background: #2ecc71;
            color: #fff;
        }

        .approve:hover {
            background: #27ae60;
        }

        .cancel {
            background: #e74c3c;
            color: #fff;
        }

        .cancel:hover {
            background: #c0392b;
        }

        .cancel-requested {
            background: #f39c12;
            color: #fff;
        }

        .cancel-requested:hover {
            background: #e67e22;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            display: inline-block;
            padding: 8px 12px;
            margin: 0 4px;
            border-radius: 6px;
            background: #ecf0f1;
            color: #2c3e50;
            text-decoration: none;
        }

        .pagination a.active {
            background: #3498db;
            color: #fff;
            font-weight: bold;
        }

        .pagination a:hover {
            background: #2980b9;
            color: #fff;
        }

        .back-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .back-btn:hover {
            background: #2980b9;
        }

        .disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>本校区预约管理</h2>

    <!-- 返回管理员主页按钮 -->
    <button class="back-btn" onclick="window.location.href='a_success.php'">返回管理员主页</button>

    <!-- 预约列表 -->
    <table>
        <tr>
            <th>ID</th>
            <th>学员</th>
            <th>教练</th>
            <th>课程时间</th>
            <th>状态</th>
            <th>付款状态</th>
            <th>操作</th>
        </tr>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['coach_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['date']); ?> <?php echo htmlspecialchars($row['time_slot']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['paid']); ?></td>
                    <td>
                        <form method="post" style="display:inline-block;">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['id']; ?>">
                            <?php if ($row['status'] === 'confirmed' && $row['paid'] === 'yes'): ?>
                                <button type="submit" name="action" value="cancel" class="cancel">取消预约</button>
                            <?php else: ?>
                                <button type="submit" class="cancel disabled" disabled>不可操作</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">没有预约记录</td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- 分页 -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>">« 上一页</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>">下一页 »</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
