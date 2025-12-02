<?php

session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? '',
];
$activeForm = $_SESSION['active_form'] ?? 'login';

$registerSuccess = isset($_SESSION['register_success']);
if ($registerSuccess) {
    unset($_SESSION['register_success']);
}

session_unset();

function showError($error) {
    return $error ? "<div class='error-message'>{$error}</div>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>iReserve: UPSI Facility Booking System</title>
        <link rel="stylesheet" href="assets/css/login_style.css?v=2">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
        .popup-success {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .popup-success-content {
            background: #fff;
            padding: 32px 24px;
            border-radius: 10px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.15);
            text-align: center;
            min-width: 300px;
        }
        .popup-success-content h3 {
            color: #2e7d32;
            margin-bottom: 12px;
        }
        .popup-success-content button {
            background: #2e7d32;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 24px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 18px;
        }
        .popup-success-content button:hover {
            background: #388e3c;
        }
        </style>
    </head>

    <body>
        <?php if ($registerSuccess): ?>
        <div class="popup-success" id="registerSuccessPopup">
            <div class="popup-success-content">
                <h3>Registration Successful!</h3>
                <p>Your account has been created. You can now log in.</p>
                <button onclick="closeRegisterSuccessPopup()">OK</button>
            </div>
        </div>
        <script>
        function closeRegisterSuccessPopup() {
            document.getElementById('registerSuccessPopup').style.display = 'none';
        }
        </script>
        <?php endif; ?>

        <div class="container">

            <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
                <form action="login_register.php" method="post">
                    <h2>Login</h2>
                    <?= showError($errors['login']); ?>
                    <input type="email" name="email" placeholder="Email" required>
                    <div class="password-field">
                        <input type="password" name="password" id="login-password" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('login-password', this)"></i>
                    </div>
                    <button type="submit"name="login">Login</button>
                    <p>Don't have an account? <a href="#" onclick="showForm('register-form')">Register</a></p>
                </form>
            </div>

            <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
                <form action="login_register.php" method="post">
                    <h2>Register</h2>
                    <?= showError($errors['register']); ?>
                    <input type="text" name="name" placeholder="Name" required>
                    <input type="text" name="matrics_number" placeholder="Matrics Number" required>
                    <input type="tel" name="phone_number" placeholder="Phone Number" required>
                    <input type="text" name="program" placeholder="Program" required>
                    <input type="text" name="faculty" placeholder="Faculty" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <div class="password-field">
                        <input type="password" name="password" id="register-password" placeholder="Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('register-password', this)"></i>
                    </div>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="confirm-password" placeholder="Confirm Password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm-password', this)"></i>
                    </div>
                    <input type="hidden" name="role" value="user">
                    <button type="submit"name="register">Register</button>
                    <p>Already have an account? <a href="#" onclick="showForm('login-form')">Login</a></p>
                </form>
            </div>

        </div>
        <script>
        function showForm(formId) {
            document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"));
            document.getElementById(formId).classList.add("active");
        }

        function togglePassword(inputId, iconElement) {
            const input = document.getElementById(inputId);
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    iconElement.classList.remove('fa-eye');
                    iconElement.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    iconElement.classList.remove('fa-eye-slash');
                    iconElement.classList.add('fa-eye');
                }
            }
        }
        </script>
    </body>
</html>