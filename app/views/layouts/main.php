<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Mini MVC'; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; line-height: 1.5; color: #222; }
        header { margin-bottom: 16px; }
        nav a { margin-right: 12px; }
        .container { max-width: 900px; }
        .muted { color: #666; }
        pre { background: #f5f5f5; padding: 12px; overflow: auto; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <nav>
            <a href="<?php echo escape($base_url); ?>/">Home</a>
            <a href="<?php echo escape($base_url); ?>/users">Users</a>
        </nav>
    </header>
    <main>
        <?php if (isset($content)) { echo $content; } ?>
    </main>
</div>
</body>
</html>

