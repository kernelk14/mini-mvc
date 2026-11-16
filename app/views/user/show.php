<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; line-height: 1.5; color: #222; }
        nav a { margin-right: 12px; }
        .error { color: #a00; background: #fff3f3; padding: 8px; border: 1px solid #f0cccc; }
        pre { background: #f5f5f5; padding: 12px; }
    </style>
</head>
<body>
    <nav>
        <a href="<?php echo escape($base_url); ?>/">Home</a>
        <a href="<?php echo escape($base_url); ?>/users">Users</a>
    </nav>
    <h1>User</h1>

    <?php if (!empty($dbError)) { ?>
        <div class="error"><strong>DB notice:</strong> <?php echo htmlspecialchars($dbError); ?></div>
    <?php } ?>

    <?php if ($user) { ?>
        <pre><?php echo htmlspecialchars(print_r($user, true)); ?></pre>
    <?php } else { ?>
        <p>No user found<?php if ($id !== null) { echo ' for id ' . htmlspecialchars($id); } ?>.</p>
    <?php } ?>
</body>
</html>

