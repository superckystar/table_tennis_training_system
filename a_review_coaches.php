<?php
session_start();
require 'conn.php';
if(!isset($_SESSION['username']) || $_SESSION['role']!=='admin'){
    header("Location: login.php");
    exit();
}

// 获取管理员所属校区
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT campus FROM admins WHERE username=?");
$stmt->bind_param("s",$username);
$stmt->execute();
$stmt->bind_result($campus);
$stmt->fetch();
$stmt->close();

// 分页设置
$limit = 10;
$page = isset($_GET['page'])?intval($_GET['page']):1;
if($page<1) $page=1;

// 审核操作
if(isset($_POST['action']) && isset($_POST['coach_id'])){
    $coach_id = $_POST['coach_id'];
    $action = $_POST['action'];
    $level = intval($_POST['level'] ?? 1); // 获取等级，默认1
    if($level<1) $level=1;
    if($level>3) $level=3;

    if($action==='approve') $status='approved';
    elseif($action==='reject') $status='rejected';
    else $status='pending';

    $stmt = $conn->prepare("UPDATE coaches SET status=?, level=? WHERE id=? AND campus=?");
    $stmt->bind_param("siis",$status,$level,$coach_id,$campus);
    $stmt->execute();
    $stmt->close();
    header("Location:a_review_coaches.php?page=$page");
    exit();
}

// 获取待审核教练
$result_count = $conn->query("SELECT COUNT(*) AS total FROM coaches WHERE status='pending' AND campus='".$campus."'");
$total = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total/$limit);
$offset = ($page-1)*$limit;

$result = $conn->query("SELECT * FROM coaches WHERE status='pending' AND campus='".$campus."' LIMIT $limit OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>审核教练申请</title>
<style>
body{font-family:Arial;background:#f4f6f9;}
.container{width:95%;margin:20px auto;background:#fff;padding:20px;border-radius:12px;}
table{width:100%;border-collapse:collapse;margin-top:20px;}
th,td{border:1px solid #ddd;padding:10px;text-align:center;}
th{background:#3498db;color:#fff;}
tr:hover{background:#f1f9ff;}
button{padding:6px 12px;border:none;border-radius:6px;cursor:pointer;}
.approve{background:#2ecc71;color:#fff;}
.approve:hover{background:#27ae60;}
.reject{background:#e74c3c;color:#fff;}
.reject:hover{background:#c0392b;}
select.level-select{padding:4px 6px;border-radius:4px;border:1px solid #ccc;}
.pagination{text-align:center;margin-top:20px;}
.pagination a{display:inline-block;padding:8px 12px;margin:0 4px;border-radius:6px;background:#ecf0f1;color:#2c3e50;text-decoration:none;}
.pagination a.active{background:#3498db;color:#fff;font-weight:bold;}
.pagination a:hover{background:#2980b9;color:#fff;}
.return-home{text-align:center;margin-top:20px;}
.return-home a{display:inline-block;padding:10px 20px;background:#e67e22;color:#fff;border-radius:6px;text-decoration:none;}
.return-home a:hover{background:#d35400;}
</style>
</head>
<body>
<div class="container">
<h2>本校区待审核教练申请</h2>
<table>
<tr>
<th>ID</th><th>用户名</th><th>姓名</th><th>性别</th><th>年龄</th><th>电话</th><th>邮箱</th><th>成就</th><th>照片</th><th>等级</th><th>操作</th>
</tr>
<?php if($result->num_rows>0): ?>
<?php while($row=$result->fetch_assoc()): ?>
<tr>
<td><?php echo $row['id'];?></td>
<td><?php echo htmlspecialchars($row['username']);?></td>
<td><?php echo htmlspecialchars($row['real_name']);?></td>
<td><?php echo htmlspecialchars($row['gender']);?></td>
<td><?php echo htmlspecialchars($row['age']);?></td>
<td><?php echo htmlspecialchars($row['phone']);?></td>
<td><?php echo htmlspecialchars($row['email']);?></td>
<td><?php echo htmlspecialchars($row['achievements']);?></td>
<td><?php if($row['photo']) echo "<img src='".htmlspecialchars($row['photo'])."' style='width:60px;height:60px;'>";?></td>
<td>
<form method="post" style="margin:0;">
<input type="hidden" name="coach_id" value="<?php echo $row['id'];?>">
<select name="level" class="level-select">
    <option value="1" <?php if($row['level']==1) echo 'selected';?>>1</option>
    <option value="2" <?php if($row['level']==2) echo 'selected';?>>2</option>
    <option value="3" <?php if($row['level']==3) echo 'selected';?>>3</option>
</select>
</td>
<td>
<button type="submit" name="action" value="approve" class="approve">通过</button>
<button type="submit" name="action" value="reject" class="reject">拒绝</button>
</td>
</form>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="11">没有待审核教练申请</td></tr>
<?php endif; ?>
</table>

<div class="pagination">
<?php if($page>1): ?><a href="?page=<?php echo $page-1;?>">« 上一页</a><?php endif;?>
<?php for($i=1;$i<=$total_pages;$i++): ?>
<a href="?page=<?php echo $i;?>" class="<?php if($i==$page) echo 'active';?>"><?php echo $i;?></a>
<?php endfor;?>
<?php if($page<$total_pages): ?><a href="?page=<?php echo $page+1;?>">下一页 »</a><?php endif;?>
</div>

<!-- 返回管理员主页按钮 -->
<div class="return-home">
    <a href="a_success.php">返回管理员主页</a>
</div>

</div>
</body>
</html>
