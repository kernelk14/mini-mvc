<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; line-height: 1.5; color: #222; }
        nav a { margin-right: 12px; }
        table { border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        .error { color: #a00; background: #fff3f3; padding: 8px; border: 1px solid #f0cccc; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <nav>
        <a href="<?php echo escape($base_url); ?>/">Home</a>
        <a href="<?php echo escape($base_url); ?>/users">Users</a>
    </nav>
    <h1><?php echo htmlspecialchars($title); ?></h1>

    <?php if (!empty($dbError)) { ?>
        <div class="error"><strong>DB notice:</strong> <?php echo htmlspecialchars($dbError, ENT_QUOTES); ?></div>
        <p class="muted">Configure <code>config/database.php</code> and ensure a <code>users</code> table exists to see data.</p>
    <?php } ?>

    <?php if (is_array($users) && count($users) > 0) { ?>
        <table>
            <tr><th>ID</th><th>Name</th><th>Action</th></tr>
            <?php $i = 0; while ($i < count($users)) { $u = $users[$i]; ?>
                <tr>
                    <td><?php echo isset($u['id']) ? htmlspecialchars($u['id']) : ''; ?></td>
                    <td><?php echo isset($u['name']) ? htmlspecialchars($u['name']) : ''; ?></td>
                    <td>
                        <?php if (isset($u['id'])) { ?>
                            <a href="<?php echo escape($base_url); ?>/users/show?id=<?php echo urlencode($u['id']); ?>">View</a>
                        <?php } ?>
                    </td>
                </tr>
            <?php $i++; } ?>
        </table>
    <?php } else { ?>
        <p>No users found.</p>
    <?php } ?>

    <h2>Add user (POST example)</h2>
    <form method="post" action="<?php echo escape($base_url); ?>/users/save">
        <?php echo $csrf_field; ?>
        <input type="text" name="name" placeholder="Name" maxlength="255">
        <button type="submit">Save</button>
    </form>
    <p class="muted">POST <code>/users/save</code> returns JSON and requires a valid CSRF token.</p>
</body>
</html>

