<?php
session_start();
require 'conn.php';

// 删除管理员
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM admins WHERE id=$delete_id");
    header("Location: ".$_SERVER['PHP_SELF']); // 删除后刷新页面
    exit();
}

// 分页设置
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// 获取管理员总数
$result_count = $conn->query("SELECT COUNT(*) AS total FROM admins");
$total = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);
$offset = ($page - 1) * $limit;

// 获取管理员列表
$result = $conn->query("SELECT * FROM admins ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>管理员管理</title>
    <style>
        body{
            font-family:"Microsoft YaHei",Arial;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin:0;
            padding:0;
        }
        .container{
            max-width:1100px;
            margin:40px auto;
            padding:20px;
            background:#fff;
            border-radius:12px;
            box-shadow:0 4px 15px rgba(0,0,0,0.1);
        }
        h2{text-align:center;color:#2c3e50;}
        table{width:100%;border-collapse:collapse;margin-top:20px;}
        th,td{border:1px solid #ddd;padding:10px;text-align:center;}
        th{background:#3498db;color:#fff;}
        tr:hover{background:#f1f9ff;}
        button{
            padding:6px 12px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:bold;
            background: linear-gradient(135deg,#4facfe,#00f2fe);
            color:#fff;
            transition: all 0.3s ease;
        }
        button:hover{
            background: linear-gradient(135deg,#43e97b,#38f9d7);
            transform: translateY(-2px);
        }
        .delete-btn{
            background: linear-gradient(135deg,#e74c3c,#ff6f61);
        }
        .delete-btn:hover{
            background: linear-gradient(135deg,#c0392b,#e74c3c);
            transform: translateY(-2px);
        }
        .pagination{text-align:center;margin-top:20px;}
        .pagination a{
            display:inline-block;
            padding:8px 12px;
            margin:0 4px;
            border-radius:6px;
            background:#ecf0f1;
            color:#2c3e50;
            text-decoration:none;
        }
        .pagination a.active{
            background:#3498db;
            color:#fff;
            font-weight:bold;
        }
        .pagination a:hover{
            background:#2980b9;
            color:#fff;
        }
        .back-btn{
            display:block;
            width:200px;
            margin:20px auto;
            padding:12px 0;
            border:none;
            border-radius:10px;
            font-weight:bold;
            background: linear-gradient(135deg,#3498db,#00c6ff);
            color:#fff;
            cursor:pointer;
            text-align:center;
            transition: all 0.3s ease;
        }
        .back-btn:hover{
            background: linear-gradient(135deg,#2980b9,#00a0c6);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

<div class="container">
    <h2>管理员管理</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>用户名</th>
            <th>真实姓名</th>
            <th>性别</th>
            <th>年龄</th>
            <th>校区</th>
            <th>电话</th>
            <th>邮箱</th>
            <th>创建时间</th>
            <th>操作</th>
        </tr>
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo $row['gender']; ?></td>
                    <td><?php echo $row['age']; ?></td>
                    <td><?php echo htmlspecialchars($row['campus']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo $row['created_at']; ?></td>
                    <td>
                        <form method="get" action="edit_admin.php" style="display:inline-block;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit">编辑</button>
                        </form>
                        <form method="get" style="display:inline-block;" onsubmit="return confirm('确定删除该管理员吗？');">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="delete-btn">删除</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="10">暂无管理员</td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>">« 上一页</a><?php endif; ?>
        <?php for ($i=1;$i<=$total_pages;$i++): ?>
            <a href="?page=<?php echo $i; ?>" class="<?php if($i==$page) echo 'active'; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page+1; ?>">下一页 »</a><?php endif; ?>
    </div>

    <button class="back-btn" onclick="window.location.href='sa_success.html'">« 返回超级管理员主页</button>
</div>

</body>
</html>
