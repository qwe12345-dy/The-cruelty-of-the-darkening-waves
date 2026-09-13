<?php
$dirs = ['uploads', 'uploads/avatars', 'uploads/covers', 'uploads/icons', 'uploads/html'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    chmod($dir, 0755);
}
echo '✅ 目录创建完成！请删除此文件。';