<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма обратной связи</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 20px auto; padding: 0 15px; }
        .error { color: red; font-weight: bold; margin-bottom: 5px; }
        .success { color: green; font-weight: bold; margin-bottom: 5px; }
        .info { background: #e7f3ff; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 0.9em; }
        label { display: block; margin-bottom: 15px; }
        input, textarea, select { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        input[type="submit"] { width: auto; background: #4CAF50; color: white; border: none; cursor: pointer; padding: 10px 20px; }
        input[type="submit"]:hover { background: #45a049; }
        .auth-block { text-align: right; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="auth-block">
        <?php 
        session_start();
        if (!empty($_SESSION['login'])): 
        ?>
            <!-- Если вошел: показываем статус и кнопку выхода -->
            Вы вошли как <strong><?= htmlspecialchars($_SESSION['login']) ?></strong>. 
            <a href="login.php?logout=1">Выйти</a>
        <?php else: ?>
            <!-- Если НЕ вошел: показываем ссылку на вход -->
            Уже есть логин и пароль? <a href="login.php">Войти для редактирования</a>
        <?php endif; ?>
    </div>

    <h2>Форма обратной связи</h2>
    
    <?php if (!empty($messages)) { 
        print('<div>'); 
        foreach ($messages as $message) print($message); 
        print('</div>'); 
    } ?>

    <form action="" method="post">
        <label>Ваше имя:<br><input name="fio" type="text" value="<?= htmlspecialchars($values['fio']) ?>"></label>
        
        <label>Ваш email:<br><input name="email" type="email" value="<?= htmlspecialchars($values['email']) ?>"></label>
        
        <label>Ваш телефон:<br><input name="phone" type="tel" value="<?= htmlspecialchars($values['phone']) ?>"></label>
        
        <label>Дата рождения:<br><input name="birthdate" type="date" value="<?= htmlspecialchars($values['birthdate']) ?>"></label>
        
        <label>Биография:<br><textarea name="bio"><?= htmlspecialchars($values['bio']) ?></textarea></label>
        
        <label>Фото (URL):<br><input name="photo" type="text" value="<?= htmlspecialchars($values['photo']) ?>"></label>
        
        <label>Любимый язык программирования:<br>
            <select name="language">
                <option value="">Выберите язык</option>
                <?php 
                $langs = ['PHP', 'Python', 'JavaScript', 'Java', 'C++', 'C#', 'Go', 'Rust'];
                foreach ($langs as $lang) {
                    $selected = ($values['language'] == $lang) ? 'selected' : '';
                    print("<option value=\"{$lang}\" {$selected}>{$lang}</option>");
                }
                ?>
            </select>
        </label>
        
        <input type="submit" value="Отправить">
    </form>
</body>
</html>
