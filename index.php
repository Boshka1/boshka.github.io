<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Получаем ошибки из сессии
$errors = $_SESSION['errors'] ?? [];
// Очищаем ошибки после использования
$_SESSION['errors'] = [];
?>

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Стили для сообщений об успешной регистрации */
        #apiResponse {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        #apiResponse.alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        #apiResponse.alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        /* Стили для ошибок валидации, чтобы они были видны при JS-отправке */
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5">
    <h1 class="mb-4">Форма заявки</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" id="phpErrors">
            <?php foreach ($errors as $field => $error): ?>
                <p class="mb-1"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div id="apiResponse" style="display: none;"></div>

    <form action="process_form.php" method="POST" class="border p-4 bg-white shadow-sm rounded" id="applicationForm">
        <div class="mb-3">
            <label for="full_name" class="form-label">ФИО</label>
            <input type="text" class="form-control" id="full_name" name="full_name"
                placeholder="Введите ваше полное имя" required
                value="<?php echo isset($_COOKIE['full_name']) ? htmlspecialchars($_COOKIE['full_name']) : ''; ?>">
            <div class="invalid-feedback" id="full_name-error"></div>
        </div>

        <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input type="tel" class="form-control" id="phone" name="phone"
                placeholder="8(900)917 33-77" pattern="^\+?\d{10,15}$" required
                value="<?php echo isset($_COOKIE['phone']) ? htmlspecialchars($_COOKIE['phone']) : ''; ?>">
            <div class="invalid-feedback" id="phone-error"></div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email"
                placeholder="example@domain.com" required
                value="<?php echo isset($_COOKIE['email']) ? htmlspecialchars($_COOKIE['email']) : ''; ?>">
            <div class="invalid-feedback" id="email-error"></div>
        </div>

        <div class="mb-3">
            <label for="dob" class="form-label">Дата рождения</label>
            <input type="date" class="form-control" id="dob" name="dob" required
                value="<?php echo isset($_COOKIE['dob']) ? htmlspecialchars($_COOKIE['dob']) : ''; ?>">
            <div class="invalid-feedback" id="dob-error"></div>
        </div>

        <div class="mb-3">
            <label class="form-label">Пол</label><br>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="female" value="female" required
                    <?php if (isset($_COOKIE['gender']) && $_COOKIE['gender'] === 'female') echo 'checked'; ?>>
                <label class="form-check-label" for="female">Женский</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="gender" id="male" value="male"
                    <?php if (isset($_COOKIE['gender']) && $_COOKIE['gender'] === 'male') echo 'checked'; ?>>
                <label class="form-check-label" for="male">Мужской</label>
            </div>
            <div class="invalid-feedback" id="gender-error"></div>
        </div>

        <div class="mb-3">
            <label for="languages" class="form-label">Любимые языки программирования</label>
            <select id="languages" name="languages[]" class="form-select" multiple required>
                <?php
                $options = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                $selected = isset($_COOKIE['languages']) ? json_decode($_COOKIE['languages'], true) : [];
                foreach ($options as $lang) {
                    $isSelected = in_array($lang, $selected) ? 'selected' : '';
                    echo "<option value=\"$lang\" $isSelected>$lang</option>";
                }
                ?>
            </select>
            <div class="invalid-feedback" id="languages-error"></div>
        </div>

        <div class="mb-3">
            <label for="bio" class="form-label">Биография</label>
            <textarea id="bio" name="bio" class="form-control" rows="4" required><?php
                echo isset($_COOKIE['bio']) ? htmlspecialchars($_COOKIE['bio']) : '';
            ?></textarea>
            <div class="invalid-feedback" id="bio-error"></div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="contract" name="contract" required
                <?php if (isset($_COOKIE['contract'])) echo 'checked'; ?>>
            <label class="form-check-label" for="contract">С контрактом ознакомлен(а)</label>
            <div class="invalid-feedback" id="contract-error"></div>
        </div>

        <button type="submit" class="btn btn-primary">Отправить</button>
    </form>
    <p class="mt-3"><a href="login.php">Войти для редактирования данных</a></p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('applicationForm');
    const apiResponseDiv = document.getElementById('apiResponse');
    const phpErrorsDiv = document.getElementById('phpErrors'); // Скрываем PHP ошибки при наличии JS

    // Если JS включен, скрываем блок с PHP ошибками
    if (phpErrorsDiv) {
        phpErrorsDiv.style.display = 'none';
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault(); // Предотвращаем стандартную отправку формы

        // Очищаем предыдущие сообщения об ошибках и успешности
        apiResponseDiv.style.display = 'none';
        apiResponseDiv.className = '';
        apiResponseDiv.innerHTML = '';
        clearValidationErrors(); // Функция для очистки ошибок Bootstrap

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
                data[key] = true; // Если чекбокс отмечен, устанавливаем true
            }
            else {
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
                apiResponseDiv.classList.add('alert', 'alert-success');
                let successMessage = `
                    <p><strong>Пользователь успешно зарегистрирован!</strong></p>
                    <p>Ваш логин: <strong>${result.login}</strong></p>
                    <p>Ваш пароль: <strong>${result.password}</strong></p>
                    <p>Перейти в <a href="${result.profile_url}">профиль</a>.</p>
                `;
                apiResponseDiv.innerHTML = successMessage;
                form.reset(); // Очищаем форму после успешной отправки
                clearCookies(); // Очищаем куки после успешной регистрации
            } else {
                apiResponseDiv.classList.add('alert', 'alert-danger');
                apiResponseDiv.innerHTML = `<p><strong>Ошибка:</strong> ${result.message}</p>`;
                if (result.errors) {
                    for (const field in result.errors) {
                        const inputElement = document.getElementById(field);
                        const errorDiv = document.getElementById(`${field}-error`);
                        if (inputElement) {
                            inputElement.classList.add('is-invalid');
                        }
                        if (errorDiv) {
                            errorDiv.textContent = result.errors[field];
                        } else {
                            // Если нет специального div для ошибки, добавляем в общий блок
                            apiResponseDiv.innerHTML += `<p>${result.errors[field]}</p>`;
                        }
                    }
                     // Если ошибка для gender, нужно добавить класс is-invalid к инпутам радио
                    if (result.errors.gender) {
                        document.querySelectorAll('input[name="gender"]').forEach(input => {
                            input.classList.add('is-invalid');
                        });
                    }
                    // Если ошибка для languages, нужно добавить класс is-invalid к select
                    if (result.errors.languages) {
                         document.getElementById('languages').classList.add('is-invalid');
                    }
                }
            }
        })
        .catch(error => {
            apiResponseDiv.style.display = 'block';
            apiResponseDiv.classList.add('alert', 'alert-danger');
            apiResponseDiv.innerHTML = `<p>Произошла ошибка при отправке данных: ${error.message}</p>`;
            console.error('Error:', error);
        });
    });

    // Функция для очистки ошибок валидации Bootstrap
    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(element => {
            element.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(element => {
            element.textContent = '';
        });
    }

    // Функция для очистки куки
    function clearCookies() {
        const cookies = document.cookie.split("; ");
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i];
            const eqPos = cookie.indexOf("=");
            const name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
            // Убедитесь, что вы удаляете только те куки, которые устанавливаете
            // Здесь я предполагаю, что вы сохраняете full_name, phone, email, dob, gender, languages, bio, contract
            const relevantCookies = ['full_name', 'phone', 'email', 'dob', 'gender', 'languages', 'bio', 'contract'];
            if (relevantCookies.includes(name)) {
                document.cookie = name + "=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/";
            }
        }
    }
});
</script>
</body>
</html>
