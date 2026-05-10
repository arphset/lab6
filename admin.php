<?php
/**
 * Задача 6. Панель администратора с HTTP Basic Auth (Упрощенная версия без шифрования)
 */

$dsn = 'mysql:host=localhost;dbname=u82579;charset=utf8mb4';
$db_user = 'u82579';
$db_pass = '1953280';
error_reporting(0);
ini_set('display_errors', 0);

// === DRY: Подключение к БД ===
function getDB() {
    global $dsn, $db_user, $db_pass;
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Детали ошибки записываем только в лог сервера
            error_log($e->getMessage());
            // Пользователю показываем общее сообщение
            die('Ошибка подключения к базе данных. Попробуйте позже.');
        }
            }
    return $pdo;
}

// === DRY: Проверка админа (БЕЗ ШИФРОВАНИЯ) ===
function checkAdmin($login, $pass) {
    $stmt = getDB()->prepare("SELECT password_plain FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();

    // Прямое сравнение строк: если логин совпал И пароль в базе равен введенному паролю
    return $admin && $admin['password_plain'] === $pass;
}

// === DRY: CRUD функции ===
function getAllUsers() { return getDB()->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(); }
function getUserById($id) { $s = getDB()->prepare("SELECT * FROM users WHERE id = ?"); $s->execute([$id]); return $s->fetch(); }
function deleteUser($id) { return getDB()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]); }
function updateUser($id, $fio, $email, $phone, $birthdate, $bio, $photo, $language) {
    return getDB()->prepare("UPDATE users SET fio=?, email=?, phone=?, birthdate=?, bio=?, photo=?, language=? WHERE id=?")
        ->execute([$fio, $email, $phone, $birthdate, $bio, $photo, $language, $id]);
}
function getLanguageStats() {
    return getDB()->query("SELECT language, COUNT(*) as count FROM users WHERE language != '' GROUP BY language ORDER BY count DESC")->fetchAll();
}

// 🔐 === HTTP BASIC AUTHENTICATION ===
if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    exit('<h1>401 Требуется авторизация</h1>');
}

if (!checkAdmin($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    exit('<h1>401 Неверный логин или пароль</h1>');
}

// 🛠 === ОБРАБОТКА ДЕЙСТВИЙ ===
$message = $error = '';
$editUser = null;
$editMode = false;

if (isset($_GET['action'], $_GET['id'])) {
    if ($_GET['action'] === 'delete' && deleteUser($_GET['id'])) $message = '✅ Запись удалена';
    elseif ($_GET['action'] === 'edit') {
        $editUser = getUserById($_GET['id']);
        if ($editUser) $editMode = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $fields = ['fio','email','phone','birthdate','bio','photo','language'];
    $data = array_map('trim', array_intersect_key($_POST, array_flip($fields)));
    if (count(array_filter($data)) === count($fields)) {
        if (updateUser($_POST['id'], ...array_values($data))) $message = '✅ Данные обновлены';
        else $error = '❌ Ошибка обновления';
    } else {
        $error = '❌ Заполните все обязательные поля';
    }
}$users = getAllUsers();
$stats = getLanguageStats();
$total = count($users);
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Admin Panel</title>
<style>
body{font-family:system-ui,sans-serif;max-width:1100px;margin:20px auto;padding:0 15px;background:#f4f6f8}
.box{background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
h1,h2{margin:0 0 15px} table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
th{background:#f8f9fa;font-weight:600}
.btn{display:inline-block;padding:6px 12px;color:#fff;text-decoration:none;border-radius:4px;font-size:13px;border:none;cursor:pointer}
.btn-del{background:#dc3545} .btn-edit{background:#0d6efd} .btn-save{background:#198754} .btn-cancel{background:#6c757d}
.msg{padding:10px;margin-bottom:15px;border-radius:4px;color:#fff} .msg-ok{background:#198754} .msg-err{background:#dc3545}
form label{display:block;margin-bottom:10px}
form input,form select,form textarea{width:100%;padding:8px;margin-top:4px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box}
.logout{text-align:right;margin-bottom:10px} .logout a{color:#dc3545;text-decoration:none;font-size:14px}
</style></head>
<body>

<div class="logout"><a href="./index.php">🚪 Выйти</a></div>
<h1>🔐 Панель администратора</h1>

<?php if($message): ?><div class="msg msg-ok"><?=$message?></div><?php endif; ?>
<?php if($error): ?><div class="msg msg-err"><?=$error?></div><?php endif; ?>

<div class="box">
<h2>📊 Статистика по языкам</h2>
<p>Всего пользователей: <strong><?=$total?></strong></p>
<?php if($stats): ?>
<table>
<tr><th>Язык</th><th>Кол-во</th><th>Доля</th></tr>
<?php foreach($stats as $s): ?>
<tr><td><?=htmlspecialchars($s['language'])?></td><td><?=$s['count']?></td><td><?=$total?round($s['count']/$total*100,1):0?>%</td></tr>
<?php endforeach; ?>
</table>
<?php else: ?><p>Нет данных</p><?php endif; ?>
</div>


<?php if($editMode): ?>
<div class="box">
<h2>✏️ Редактирование пользователя #<?=$editUser['id']?></h2>
<form method="POST">
<input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=$editUser['id']?>">
<label>ФИО: <input name="fio" value="<?=htmlspecialchars($editUser['fio'])?>" required></label>
<label>Email: <input name="email" type="email" value="<?=htmlspecialchars($editUser['email'])?>" required></label>
<label>Телефон: <input name="phone" value="<?=htmlspecialchars($editUser['phone'])?>" required></label>
<label>Дата рождения: <input name="birthdate" type="date" value="<?=htmlspecialchars($editUser['birthdate'])?>" required></label>
<label>Биография: <textarea name="bio" rows="3"><?=htmlspecialchars($editUser['bio'])?></textarea></label>
<label>Фото URL: <input name="photo" value="<?=htmlspecialchars($editUser['photo'])?>"></label>
<label>Любимый язык: <select name="language">
<option value="">Не выбран</option>
<?php foreach(['PHP','Python','JavaScript','Java','C++','C#','Go','Rust'] as $l): ?>
<option value="<?=$l?>" <?=($editUser['language']==$l)?'selected':''?>><?=$l?></option><?php endforeach; ?>
</select></label>
<button class="btn btn-save" type="submit">💾 Сохранить</button>
<a class="btn btn-cancel" href="admin.php">❌ Отмена</a>
</form>
</div>
<?php endif; ?><div class="box">
<h2>📋 Все пользователи</h2>
<?php if($users): ?>
<table>
<tr><th>ID</th><th>ФИО</th><th>Email</th><th>Телефон</th><th>Дата рожд.</th><th>Язык</th><th>Действия</th></tr>
<?php foreach($users as $u): ?>
<tr>
<td><?=$u['id']?></td>
<td><?=htmlspecialchars($u['fio'])?></td>
<td><?=htmlspecialchars($u['email'])?></td>
<td><?=htmlspecialchars($u['phone'])?></td>
<td><?=date('d.m.Y',strtotime($u['birthdate']))?></td>
<td><?=htmlspecialchars($u['language'])?:'-'?></td>
<td>
<a class="btn btn-edit" href="?action=edit&id=<?=$u['id']?>">✏️ Изменить</a>
<a class="btn btn-del" href="?action=delete&id=<?=$u['id']?>" onclick="return confirm('Удалить запись #<?=$u['id']?>?')">🗑 Удалить</a>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?><p>Пользователей пока нет</p><?php endif; ?>
</div>

</body>
</html>
