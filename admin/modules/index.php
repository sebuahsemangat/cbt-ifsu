<?php
// Mencegah 403 Forbidden directory listing pada folder admin/modules/
header("Location: ../dashboard.php");
exit;
