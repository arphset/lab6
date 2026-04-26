

-- Создаем новую таблицу
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    password_plain VARCHAR(255) NOT NULL, -- Поле для обычного текста пароля
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Вставляем админа с паролем 123 в открытом виде
INSERT INTO admins (login, password_plain) VALUES 
('admin', '123');
