!<?php
// Destroy admin session
session_name('ireserve_admin');
session_start();
session_unset();
session_destroy();

// Destroy user session
session_name('ireserve_user');
session_start();
session_unset();
session_destroy();

header("Location: index.html");
exit();
?>