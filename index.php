<?php
header('Content-Type: text/html; charset=UTF-8');

$dsn = 'mysql:host=localhost;dbname=web2025;charset=utf8mb4';
$db_user = 'root';
$db_pass = '';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
error_reporting(0);
ini_set('display_errors', 0);
session_set_cookie_params([
    'secure' => true,   // Отправка только по HTTPS
    'httponly' => true, // Недоступно для JavaScript (защита от XSS кражи)
    'samesite' => 'Lax' // Защита от CSRF
]);


try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Детали ошибки записываем только в лог сервера
    error_log($e->getMessage());
    // Пользователю показываем общее сообщение
    die('Ошибка подключения к базе данных. Попробуйте позже.');
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    
    // Сообщение об успешном сохранении
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        setcookie('login', '', 100000);
        setcookie('pass', '', 100000);
        $messages[] = '<div class="success">Спасибо, результаты сохранены.</div>';
        if (!empty($_COOKIE['pass'])) {
            $messages[] = sprintf('<div class="info">Запомните ваш логин: <strong>%s</strong> и пароль: <strong>%s</strong>. Вы можете <a href="login.php">войти</a> с ними для изменения данных.</div>', strip_tags($_COOKIE['login']), strip_tags($_COOKIE['pass']));
        }
    }

    // Проверка ошибок из куки
    $errors = array();
    $fields = ['fio', 'email', 'phone', 'birthdate', 'bio', 'photo', 'language'];
    foreach ($fields as $field) {

        $errors[$field] = !empty($_COOKIE["{$field}_error"]);
    }

    // Вывод сообщений об ошибках
    $error_texts = [
        'fio' => 'Заполните имя.',
        'email' => 'Заполните email.',
        'phone' => 'Заполните телефон.',
        'birthdate' => 'Заполните дату рождения.',
        'bio' => 'Заполните биографию.',
        'photo' => 'Заполните фото.',
        'language' => 'Выберите язык программирования.'
    ];

    foreach ($errors as $field => $has_error) {
        if ($has_error) {
            setcookie("{$field}_error", '', 100000);
            $messages[] = '<div class="error">' . $error_texts[$field] . '</div>';
        }
    }

    // Загрузка предыдущих значений из куки
    $values = array();
    foreach ($fields as $field) {
        $values['fio'] = empty($_COOKIE['fio_value']) ? '' : htmlspecialchars($_COOKIE['fio_value'], ENT_QUOTES, 'UTF-8');
    }

    // Если пользователь авторизован, загружаем данные из БД (перезаписывая куки)
    if (empty(array_filter($errors)) && !empty($_COOKIE[session_name()]) && session_start() && !empty($_SESSION['login'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$_SESSION['login']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            foreach ($fields as $field) {
                $values[$field] = htmlspecialchars($user[$field]);
            }
        }
    }
    
    include('form.php');
} 
else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ошибка безопасности: невалидный CSRF токен.');
    // POST запрос
    $errors = FALSE;
    $fields = ['fio', 'email', 'phone', 'birthdate', 'bio', 'photo', 'language'];
    
    foreach ($fields as $field) {
        if (empty($_POST[$field])) {
            setcookie("{$field}_error", '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } else {
            setcookie("{$field}_value", $_POST[$field], time() + 30 * 24 * 60 * 60);
        }
    }

    if ($errors) {
        header('Location: index.php');
        exit();
    } else {
        foreach ($fields as $field) setcookie("{$field}_error", '', 100000);
    }

    // Сохранение или обновление
    if (!empty($_COOKIE[session_name()]) && session_start() && !empty($_SESSION['login'])) {
        // Обновление существующего пользователя
        $stmt = $pdo->prepare("UPDATE users SET fio=?, email=?, phone=?, birthdate=?, bio=?, photo=?, language=? WHERE login=?");
        $stmt->execute([
            $_POST['fio'], $_POST['email'], $_POST['phone'], 
            $_POST['birthdate'], $_POST['bio'], $_POST['photo'], 
            $_POST['language'], $_SESSION['login']
        ]);
    } else {
        // Регистрация нового
        $login = substr(md5(uniqid(rand(), true)), 0, 8);
        $pass = substr(md5(uniqid(rand(), true)), 0, 8);
        $pass_hash = md5($pass);
        
        setcookie('login', $login);
        setcookie('pass', $pass);
        
        $stmt = $pdo->prepare("INSERT INTO users (login, pass, fio, email, phone, birthdate, bio, photo, language) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $login, $pass_hash, $_POST['fio'], $_POST['email'], 
            $_POST['phone'], $_POST['birthdate'], $_POST['bio'], 
            $_POST['photo'], $_POST['language']
        ]);
    }
    
    setcookie('save', '1');
    header('Location: ./');
}
?>
