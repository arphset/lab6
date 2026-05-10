<?php
header('Content-Type: text/html; charset=UTF-8');
$dsn = 'mysql:host=localhost;dbname=web2025;charset=utf8mb4';
$db_user = 'root'; $db_pass = '';
error_reporting(0);
ini_set('display_errors', 0);

try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Детали ошибки записываем только в лог сервера
    error_log($e->getMessage());
    // Пользователю показываем общее сообщение
    die('Ошибка подключения к базе данных. Попробуйте позже.');
}

$session_started = false;
$error = '';

if (!empty($_COOKIE[session_name()]) && session_start()) {
    $session_started = true;
    if (!empty($_SESSION['login'])) {
        if (!empty($_GET['logout'])) {
            session_destroy();
            setcookie(session_name(), '', time() - 3600);
            header('Location: login.php'); exit();
        }
        header('Location: ./'); exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Вход</title>
<style>.error { color: red; font-weight: bold; } body { font-family: sans-serif; max-width: 400px; margin: 40px auto; padding: 0 15px; }</style>
</head>
<body>
<h2>Вход в систему</h2>
<?php if (!empty($error)): ?><div class="error"><?= $error ?></div><?php endif; ?>
<form action="" method="post">
    <div><label>Логин:<br><input name="login" type="text" required /></label></div><br>
    <div><label>Пароль:<br><input name="pass" type="password" required /></label></div><br>
    <input type="submit" value="Войти" />
</form>
<p><a href="index.php">На главную</a></p>
</body>
</html>
<?php } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? AND pass = ?");
    $stmt->execute([$_POST['login'], md5($_POST['pass'])]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $error = 'Неверный логин или пароль';
        ?><!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Вход</title><style>.error { color: red; font-weight: bold; } body { font-family: sans-serif; max-width: 400px; margin: 40px auto; padding: 0 15px; }</style></head><body>
        <h2>Вход в систему</h2><div class="error"><?= $error ?></div>
        <form action="" method="post"><div><label>Логин:<br><input name="login" type="text" value="<?= htmlspecialchars($_POST['login']) ?>" required /></label></div><br><div><label>Пароль:<br><input name="pass" type="password" required /></label></div><br><input type="submit" value="Войти" /></form>
        <p><a href="index.php">На главную</a></p></body></html><?php exit();
    }
    if (!$session_started) session_start();
    $_SESSION['login'] = $_POST['login'];
    $_SESSION['uid'] = $user['id'];
    header('Location: ./'); exit();
}
?>
