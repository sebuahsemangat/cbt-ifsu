<?php
// Mencegah 403 Forbidden directory listing pada folder config/
header("Location: ../index.php");
exit;
