<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

// Получение данных пользователя
$stmt = $conn->prepare("SELECT full_name, phone, email, birth_date, gender, biography, contract_accepted FROM applicants WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Получение языков, которые выбрал пользователь
$stmt = $conn->prepare("SELECT language_id FROM applicant_languages WHERE applicant_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$selected_language_ids = [];
while ($row = $result->fetch_assoc()) {
    $selected_language_ids[] = $row['language_id'];
}
$stmt->close();

// Получение всех языков для выпадающего списка
$all_languages = [];
$result = $conn->query("SELECT id, name FROM programming_languages ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $all_languages[$row['id']] = $row['name'];
}
$conn->close();

// Для вывода ошибок валидации
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']); // Очищаем после использования
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование данных</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Дополнительные стили для Bootstrap-подобных ошибок, если styles.css не включает их */
        .form-group.is-invalid input,
        .form-group.is-invalid select,
        .form-group.is-invalid textarea {
            border-color: #dc3545;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 5px;
        }
        /* Стили для сообщений от API */
        #apiResponse {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        #apiResponse.success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        #apiResponse.error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .errors { /* Стили для PHP ошибок, если они есть */
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .errors p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Редактирование данных</h1>

        <?php if (!empty($errors)): ?>
            <div class="errors" id="phpErrors">
                <?php foreach ($errors as $field => $error): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div id="apiResponse" style="display: none;"></div>

        <form action="save_edit.php" method="POST" id="editForm">
            <div class="form-group">
                <label for="fullName">ФИО:</label>
                <input type="text" id="fullName" name="full_name" value="<?php echo htmlspecialchars($data['full_name']); ?>" required>
                <div class="error-message" id="full_name-error"></div>
            </div>

            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($data['phone']); ?>" required pattern="\d{10,15}">
                <div class="error-message" id="phone-error"></div>
            </div>

            <div class="form-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                <div class="error-message" id="email-error"></div>
            </div>

            <div class="form-group">
                <label for="birthDate">Дата рождения:</label>
                <input type="date" id="birthDate" name="dob" value="<?php echo htmlspecialchars($data['birth_date']); ?>" required>
                 <div class="error-message" id="dob-error"></div>
            </div>

            <div class="form-group">
                <label>Пол:</label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" id="gender_male" value="male" <?php echo $data['gender'] === 'male' ? 'checked' : ''; ?>> Мужчина</label>
                    <label><input type="radio" name="gender" id="gender_female" value="female" <?php echo $data['gender'] === 'female' ? 'checked' : ''; ?>> Женщина</label>
                </div>
                <div class="error-message" id="gender-error"></div>
            </div>

            <div class="form-group">
                <label for="languages">Любимый язык программирования:</label>
                <select name="languages[]" id="languages" multiple required>
                    <?php foreach ($all_languages as $value => $name): ?>
                        <option value="<?php echo $value; ?>" <?php echo in_array($value, $selected_language_ids) ? 'selected' : ''; ?>>
                            <?php echo $name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="error-message" id="languages-error"></div>
            </div>

            <div class="form-group">
                <label for="biography">Биография:</label>
                <textarea id="biography" name="bio" required><?php echo htmlspecialchars($data['biography']); ?></textarea>
                <div class="error-message" id="bio-error"></div>
            </div>

            <div class="form-group">
                <label for="contractAccepted">
                    <input type="checkbox" name="contract" id="contractAccepted" <?php echo $data['contract_accepted'] ? 'checked' : ''; ?>> Я согласен с условиями контракта
                </label>
                <div class="error-message" id="contract-error"></div>
            </div>

            <button type="submit">Сохранить изменения</button>
        </form>
        <p><a href="logout.php">Выйти</a></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editForm');
        const apiResponseDiv = document.getElementById('apiResponse');
        const phpErrorsDiv = document.getElementById('phpErrors');

        // Скрываем PHP ошибки при наличии JS
        if (phpErrorsDiv) {
            phpErrorsDiv.style.display = 'none';
        }

        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Предотвращаем стандартную отправку формы

            // Очищаем предыдущие сообщения
            apiResponseDiv.style.display = 'none';
            apiResponseDiv.className = '';
            apiResponseDiv.innerHTML = '';
            clearValidationErrors();

            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                // Специальная обработка для множественного выбора (languages[])
                if (key.endsWith('[]')) {
                    const baseKey = key.slice(0, -2);
                    if (!data[baseKey]) {
                        data[baseKey] = [];
                    }
                    data[baseKey].push(value);
                } else if (key === 'contract') { // Обработка чекбокса
                    data[key] = true;
                } else {
                    data[key] = value;
                }
            }
            // Если чекбокс не отмечен, он не попадет в formData. Добавляем его явно.
            if (!formData.has('contract')) {
                data['contract'] = false;
            }

            fetch('api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                apiResponseDiv.style.display = 'block';
                if (result.success) {
                    apiResponseDiv.classList.add('success');
                    apiResponseDiv.innerHTML = `<p><strong>Успешно!</strong> ${result.message}</p>`;
                } else {
                    apiResponseDiv.classList.add('error');
                    apiResponseDiv.innerHTML = `<p><strong>Ошибка:</strong> ${result.message}</p>`;
                    if (result.errors) {
                        for (const field in result.errors) {
                            const inputElement = document.getElementById(field);
                            const errorDiv = document.getElementById(`${field}-error`);

                            // Для полей с инпутами
                            if (inputElement) {
                                // Добавляем класс, если элемент прямо input/textarea/select
                                inputElement.classList.add('is-invalid');
                            }
                            // Для радио-кнопок gender
                            if (field === 'gender') {
                                document.querySelectorAll('input[name="gender"]').forEach(radio => {
                                    radio.classList.add('is-invalid');
                                });
                            }
                            // Для select languages
                            if (field === 'languages') {
                                document.getElementById('languages').classList.add('is-invalid');
                            }

                            if (errorDiv) {
                                errorDiv.textContent = result.errors[field];
                            } else {
                                // Fallback для ошибок, которые не привязаны к конкретному полю
                                apiResponseDiv.innerHTML += `<p>${result.errors[field]}</p>`;
                            }
                        }
                    }
                }
            })
            .catch(error => {
                apiResponseDiv.style.display = 'block';
                apiResponseDiv.classList.add('error');
                apiResponseDiv.innerHTML = `<p>Произошла ошибка при отправке данных: ${error.message}</p>`;
                console.error('Error:', error);
            });
        });

        // Функция для очистки ошибок валидации
        function clearValidationErrors() {
            document.querySelectorAll('.is-invalid').forEach(element => {
                element.classList.remove('is-invalid');
            });
            document.querySelectorAll('.error-message').forEach(element => {
                element.textContent = '';
            });
            // Очищаем is-invalid для радио-кнопок gender
            document.querySelectorAll('input[name="gender"]').forEach(input => {
                input.classList.remove('is-invalid');
            });
            // Очищаем is-invalid для select languages
            document.getElementById('languages').classList.remove('is-invalid');
        }
    });
    </script>
</body>
</html>
