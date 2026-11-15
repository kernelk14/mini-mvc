<?php
/**
 * Front controller.
 */
define('BASE_PATH', dirname(__FILE__));

require BASE_PATH . '/system/compat/Compat.php';
Compat::boot(BASE_PATH);

$app = new Core_App();
$app->run();
