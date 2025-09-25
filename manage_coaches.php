<?php
session_start();
require 'conn.php';

// 分页
$limit = 10;
$page = isset($_GET['page'])?intval($_GET['page']):1;
if($page<1) $page=1;

// 获取所有校区列表
$campus_list = [];
$result = $conn->query("SELECT name FROM campuses");
while ($row = $result->fetch_assoc()) {
    $campus_list[] = $row['name'];
}

// ------------------ 新增教练 ------------------
if(isset($_POST['add'])){
    $username_new = $_POST['username'];
    $password_new = $_POST['password'];
    $real_name_new = $_POST['real_name'];
    $gender_new = $_POST['gender'];
    $age_new = $_POST['age'];
    $level_new = $_POST['level'];
    $phone_new = $_POST['phone'];
    $email_new = $_POST['email'];
    $achievements_new = $_POST['achievements'];
    $campus_new = $_POST['campus'];

    // 用户名是否重复
    $stmt = $conn->prepare("SELECT COUNT(*) FROM coaches WHERE username=?");
    $stmt->bind_param("s",$username_new);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if($count > 0){
        echo "<script>alert('用户名已存在，请使用其他用户名'); history.back();</script>";
        exit();
    }

    // 密码校验
    if(!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/', $password_new)){
        echo "<script>alert('密码必须为8-16位，包含字母、数字和特殊字符'); history.back();</script>";
        exit();
    }

    $password_new_hashed = password_hash($password_new, PASSWORD_DEFAULT);

    // 上传照片
    $photo_path = "";
    if(isset($_FILES['photo']) && $_FILES['photo']['error']===0){
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_path = "uploads/photos/".uniqid().".".$ext;
        move_uploaded_file($_FILES['photo']['tmp_name'],$photo_path);
    }

    $stmt = $conn->prepare("INSERT INTO coaches (username,password,real_name,gender,age,campus,phone,email,achievements,photo,status,level) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
    $status = 'approved';
    $stmt->bind_param("ssssissssssi",$username_new,$password_new_hashed,$real_name_new,$gender_new,$age_new,$campus_new,$phone_new,$email_new,$achievements_new,$photo_path,$status,$level_new);
    $stmt->execute();
    $stmt->close();
    header("Location:manage_coaches.php?page=$page");
    exit();
}

// ------------------ 更新教练信息 ------------------
if(isset($_POST['update'])){
    $id = $_POST['id'];
    $username_edit = $_POST['username'];
    $password_edit = isset($_POST['password']) ? $_POST['password'] : '';
    $real_name = $_POST['real_name'];
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $achievements = $_POST['achievements'];
    $level = $_POST['level'];
    $campus_edit = $_POST['campus'];

    // 用户名是否重复（排除自己）
    $stmt = $conn->prepare("SELECT COUNT(*) FROM coaches WHERE username=? AND id<>?");
    $stmt->bind_param("si",$username_edit,$id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if($count > 0){
        echo "<script>alert('用户名已存在，请使用其他用户名'); history.back();</script>";
        exit();
    }

    // 密码校验（如果有修改密码）
    if(!empty($password_edit)){
        if(!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$/', $password_edit)){
            echo "<script>alert('密码必须为8-16位，包含字母、数字和特殊字符'); history.back();</script>";
            exit();
        }
        $password_hashed = password_hash($password_edit, PASSWORD_DEFAULT);
    }

    // 处理照片更新
    $photo_path = $_POST['old_photo'];
    if(isset($_FILES['photo']) && $_FILES['photo']['error']===0){
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_path = "uploads/photos/".uniqid().".".$ext;
        move_uploaded_file($_FILES['photo']['tmp_name'],$photo_path);
    }

    if(!empty($password_edit)){
        $stmt = $conn->prepare("UPDATE coaches SET username=?, password=?, real_name=?, gender=?, age=?, phone=?, email=?, achievements=?, photo=?, level=?, campus=? WHERE id=?");
        $stmt->bind_param("ssssisssssisi",$username_edit,$password_hashed,$real_name,$gender,$age,$phone,$email,$achievements,$photo_path,$level,$campus_edit,$id);
    }else{
        $stmt = $conn->prepare("UPDATE coaches SET username=?, real_name=?, gender=?, age=?, phone=?, email=?, achievements=?, photo=?, level=?, campus=? WHERE id=?");
        $stmt->bind_param("sssisssssisi",$username_edit,$real_name,$gender,$age,$phone,$email,$achievements,$photo_path,$level,$campus_edit,$id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location:manage_coaches.php?page=$page");
    exit();
}

// 获取教练列表
$result_count = $conn->query("SELECT COUNT(*) AS total FROM coaches");
$total = $result_count->fetch_assoc()['total'];
$total_pages = ceil($total/$limit);
$offset = ($page-1)*$limit;
$coaches = $conn->query("SELECT * FROM coaches LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="UTF-8">
<title>超级管理员-教练管理</title>
<style>
body{font-family:"Microsoft YaHei",Arial;background:#f0f2f5;margin:0;padding:0;}
.container{max-width:1000px;margin:40px auto;padding:20px;}
h2{text-align:center;color:#333;}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,0.1);padding:20px;margin-bottom:20px;display:flex;align-items:flex-start;}
.card img{width:120px;height:120px;border-radius:10px;object-fit:cover;margin-right:20px;border:1px solid #ddd;}
.card-info{flex:1;}
.card-info label{display:block;font-weight:bold;margin-top:10px;}
.card-info input, .card-info select, .card-info textarea{width:100%;padding:10px;margin-top:5px;margin-bottom:10px;border:1px solid #ccc;border-radius:6px;font-size:14px;}
.card-info textarea{resize:vertical;}
button{padding:10px 20px;border:none;border-radius:6px;background:#28a745;color:#fff;font-size:14px;cursor:pointer;transition:background 0.3s;margin-top:5px;}
button:hover{background:#218838;}
#addCoachModalBg{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:999;}
#addCoachModal{display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:30px;border-radius:12px;box-shadow:0 5px 20px rgba(0,0,0,0.3);width:450px;max-height:90%;overflow-y:auto;z-index:1000;}
#addCoachModal h3{text-align:center;margin-top:0;margin-bottom:20px;}
#addCoachModal form label{margin-top:10px;}
#addCoachModal form input, #addCoachModal form select, #addCoachModal form textarea{margin-bottom:15px;}
#addCoachModal form button{width:45%; margin:5px 2.5%; font-size:15px;}
</style>
<script>
function showAddCoachModal(){
    document.getElementById('addCoachModal').style.display = 'block';
    document.getElementById('addCoachModalBg').style.display = 'block';
}
function hideAddCoachModal(){
    document.getElementById('addCoachModal').style.display = 'none';
    document.getElementById('addCoachModalBg').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('addCoachModalBg').addEventListener('click', hideAddCoachModal);
});
</script>
</head>
<body>
<div class="container">
<h2>超级管理员-教练管理</h2>

<h3 style="text-align:center;"><button type="button" onclick="showAddCoachModal()">新增教练</button></h3>
<h3 style="text-align:center;"><button onclick="window.location.href='sa_success.html'">返回主页</button></h3>

<div id="addCoachModalBg"></div>
<div id="addCoachModal">
<h3>新增教练</h3>
<form method="post" enctype="multipart/form-data">
    <label>用户名：</label><input type="text" name="username" required>
    <label>密码：</label><input type="password" name="password" required>
    <div style="font-size:13px;color:#888;margin-top:-8px;margin-bottom:12px;">8-16位，包含字母、数字和特殊符号</div>
    <label>真实姓名：</label><input type="text" name="real_name" required>
    <label>性别：</label><select name="gender" required><option value="">请选择</option><option value="male">男</option><option value="female">女</option></select>
    <label>年龄：</label><input type="number" name="age" min="18">
    <label>电话：</label><input type="text" name="phone" required>
    <label>邮箱：</label><input type="email" name="email">
    <label>等级：</label>
    <select name="level" required>
        <option value="1">1</option><option value="2">2</option><option value="3">3</option>
    </select>
    <label>校区：</label>
    <select name="campus" required>
        <?php foreach($campus_list as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
        <?php endforeach; ?>
    </select>
    <label>上传照片：</label><input type="file" name="photo" accept="image/*" required>
    <label>获奖经历：</label><textarea name="achievements" rows="3"></textarea>
    <div style="text-align:center;margin-top:15px;">
        <button type="submit" name="add">提交新增</button>
        <button type="button" onclick="hideAddCoachModal()" style="background:#e74c3c;">取消</button>
    </div>
</form>
</div>

<!-- 教练列表 -->
<?php if($coaches->num_rows>0): ?>
<?php while($row=$coaches->fetch_assoc()): ?>
<div class="card">
<img src="<?php echo htmlspecialchars($row['photo']);?>" alt="教练照片">
<div class="card-info">
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php echo $row['id'];?>">
<input type="hidden" name="old_photo" value="<?php echo htmlspecialchars($row['photo']);?>">
<label>ID：</label><input type="text" value="<?php echo $row['id'];?>" disabled>
<label>用户名：</label><input type="text" name="username" value="<?php echo htmlspecialchars($row['username']);?>" required>
<label>修改密码（留空不修改）：</label><input type="password" name="password">
<div style="font-size:13px;color:#888;margin-top:-8px;margin-bottom:12px;">8-16位，包含字母、数字和特殊符号</div>
<label>真实姓名：</label><input type="text" name="real_name" value="<?php echo htmlspecialchars($row['real_name']);?>" required>
<label>性别：</label>
<select name="gender" required>
    <option value="male" <?php if($row['gender']=='male') echo 'selected';?>>男</option>
    <option value="female" <?php if($row['gender']=='female') echo 'selected';?>>女</option>
</select>
<label>年龄：</label><input type="number" name="age" value="<?php echo $row['age'];?>">
<label>电话：</label><input type="text" name="phone" value="<?php echo htmlspecialchars($row['phone']);?>">
<label>邮箱：</label><input type="email" name="email" value="<?php echo htmlspecialchars($row['email']);?>">
<label>等级：</label>
<select name="level" required>
    <option value="1" <?php if($row['level']==1) echo 'selected';?>>1</option>
    <option value="2" <?php if($row['level']==2) echo 'selected';?>>2</option>
    <option value="3" <?php if($row['level']==3) echo 'selected';?>>3</option>
</select>
<label>校区：</label>
<select name="campus" required>
    <?php foreach($campus_list as $c): ?>
        <option value="<?php echo htmlspecialchars($c); ?>" <?php if($row['campus']==$c) echo 'selected';?>><?php echo htmlspecialchars($c); ?></option>
    <?php endforeach; ?>
</select>
<label>照片（可更新）：</label><input type="file" name="photo" accept="image/*">
<label>获奖经历：</label><textarea name="achievements" rows="3"><?php echo htmlspecialchars($row['achievements']);?></textarea>
<div style="text-align:center;margin-top:10px;">
    <button type="submit" name="update">保存修改</button>
</div>
</form>
</div>
</div>
<?php endwhile; ?>
<?php else: ?>
<p>暂无教练信息。</p>
<?php endif; ?>

<!-- 分页 -->
<div style="text-align:center;">
<?php if($page>1): ?><a href="?page=<?php echo $page-1;?>">« 上一页</a><?php endif; ?>
<?php for($i=1;$i<=$total_pages;$i++): ?>
<a href="?page=<?php echo $i;?>" style="margin:0 5px;<?php if($i==$page) echo 'font-weight:bold;color:#28a745;';?>"><?php echo $i;?></a>
<?php endfor; ?>
<?php if($page<$total_pages): ?><a href="?page=<?php echo $page+1;?>">下一页 »</a><?php endif; ?>
</div>

</div>
</body>
</html>
