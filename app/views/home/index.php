<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; line-height: 1.5; color: #222; }
        nav a { margin-right: 12px; }
        .box { padding: 12px; background: #f7f7f7; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <nav>
        <a href="/">Home</a>
        <a href="/users">Users</a>
    </nav>
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <p><?php echo htmlspecialchars($message); ?></p>
    <div class="box">
        <strong>PHP Version:</strong> <?php echo htmlspecialchars($phpVersion); ?><br>
        <strong>Framework:</strong> Mini MVC (PHP 5.0+ compatible core)
    </div>
</body>
</html>
