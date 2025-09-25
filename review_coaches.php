<?php
session_start();
require 'conn.php';

// 每页显示条数
$limit = 10;

// 当前页码，默认第 1 页
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 查询总记录数
$result_count = $conn->query("SELECT COUNT(*) AS total FROM coaches WHERE status='pending'");
$total = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// 计算偏移量
$offset = ($page - 1) * $limit;

// 处理审核操作
if (isset($_POST['action']) && isset($_POST['coach_id'])) {
    $coach_id = $_POST['coach_id'];
    $action = $_POST['action']; // approve 或 reject

    if ($action === 'approve') {
        $status = 'approved';
        // 同时更新等级
        $level = isset($_POST['level']) ? intval($_POST['level']) : 1; // 默认等级 1
        $stmt = $conn->prepare("UPDATE coaches SET status = ?, level = ? WHERE id = ?");
        $stmt->bind_param("sii", $status, $level, $coach_id);
    } elseif ($action === 'reject') {
        $status = 'rejected';
        $stmt = $conn->prepare("UPDATE coaches SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $coach_id);
    } else {
        $status = 'pending';
    }

    if (isset($stmt)) {
        $stmt->execute();
        $stmt->close();
    }

    // 审核操作完成后刷新页面，避免重复提交
    header("Location: review_coaches.php?page=$page");
    exit();
}

// 获取当前页的待审核教练
$result = $conn->query("SELECT id, username, real_name, gender, age, campus, phone, email, achievements, photo 
                        FROM coaches 
                        WHERE status='pending' 
                        LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>审核教练注册申请 - 管理员</title>
    <style>
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background: #f4f8fb;
            margin: 0;
            padding: 0;
        }
        h2 {
            text-align: center;
            margin: 20px 0;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            display: inline-block;
            padding-bottom: 5px;
        }
        .container {
            width: 95%;
            margin: auto;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        a {
            text-decoration: none;
            color: #3498db;
            margin-bottom: 15px;
            display: inline-block;
        }
        a:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
        }
        th {
            background: #3498db;
            color: #fff;
            padding: 12px;
            text-align: center;
        }
        td {
            border-bottom: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        tr:hover {
            background: #f1f9ff;
        }
        img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 4px;
        }
        .approve {
            background: #2ecc71;
            color: white;
        }
        .approve:hover {
            background: #27ae60;
        }
        .reject {
            background: #e74c3c;
            color: white;
        }
        .reject:hover {
            background: #c0392b;
        }
        select.level {
            padding: 4px 6px;
            margin-right: 5px;
        }
        label {
            margin-right: 3px;
            font-weight: bold;
        }
        /* 分页样式 */
        .pagination {
            margin-top: 20px;
            text-align: center;
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
            color: white;
            font-weight: bold;
        }
        .pagination a:hover {
            background: #2980b9;
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>待审核教练注册申请</h2><br>
    <a href="sa_success.html">返回管理员主页</a>
    <table>
        <tr>
            <th>ID</th>
            <th>用户名</th>
            <th>姓名</th>
            <th>性别</th>
            <th>年龄</th>
            <th>校区</th>
            <th>电话</th>
            <th>邮箱</th>
            <th>获奖信息</th>
            <th>照片</th>
            <th>操作</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['real_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['gender']); ?></td>
                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                    <td><?php echo htmlspecialchars($row['campus']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['achievements']); ?></td>
                    <td>
                        <?php if($row['photo']) echo "<img src='".htmlspecialchars($row['photo'])."' alt='照片'>"; ?>
                    </td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="coach_id" value="<?php echo $row['id']; ?>">
                            <label for="level_<?php echo $row['id']; ?>">等级:</label>
                            <select name="level" id="level_<?php echo $row['id']; ?>" class="level">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select><br>
                            <button type="submit" name="action" value="approve" class="btn approve">通过</button>
                            <button type="submit" name="action" value="reject" class="btn reject">拒绝</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="11">没有待审核的教练申请</td></tr>
        <?php endif; ?>
    </table>

    <!-- 分页导航 -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>">« 上一页</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php if($i==$page) echo 'active'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>">下一页 »</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

<?php
$conn->close();
?>
