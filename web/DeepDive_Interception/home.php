<?php
require_once '../src/functions.php';

$session_id = get_custom_session_id();

if (is_admin($session_id)) {
    header('Location: admin.php');
    exit();
} else {
    header('Location: guest.php');
    exit();
}
