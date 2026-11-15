<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>404 Not Found</title>
    <style>body{font-family:Arial,sans-serif;margin:24px;color:#222} .muted{color:#666}</style>
</head>
<body>
    <h1>404 Not Found</h1>
    <p><?php echo isset($error_message) ? htmlspecialchars($error_message) : 'The requested page could not be found.'; ?></p>
    <p class="muted"><a href="/">Return home</a></p>
</body>
</html>
