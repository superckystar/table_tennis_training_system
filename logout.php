<?php
session_start();

// ������� session ����
$_SESSION = [];

// ���� session
session_destroy();

// ���ص�¼ҳ��
header("Location: login_t.html");
exit();
?>
