<?php
session_start();

// 清除所有 session 数据
$_SESSION = [];

// 销毁 session
session_destroy();

// 跳回登录页面
header("Location: login_t.html");
exit();
?>
