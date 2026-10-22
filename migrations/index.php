<?php
// Mencegah 403 Forbidden directory listing pada folder migrations/
header("Location: ../index.php");
exit;
