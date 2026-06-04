<?php
require_once 'config/database.php';

$error = '';
$success = '';
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $checkQuery = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            $error = "Email already registered";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, email, password) VALUES (:name, :email, :password)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Registration successful! Please login.";
                $name = $email = '';
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SmartStore</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Password Strength Checker Styles */
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-group input.valid {
            border-color: #27ae60;
            background-color: #f0fff4;
        }
        
        .form-group input.invalid {
            border-color: #e74c3c;
            background-color: #fff5f5;
        }
        
        /* Password Strength Meter */
        .password-strength-container {
            margin-top: 0.5rem;
        }
        
        .strength-meter {
            height: 6px;
            border-radius: 3px;
            transition: all 0.3s ease;
            background: #e0e0e0;
        }
        
        .strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease, background 0.3s ease;
            width: 0%;
        }
        
        .strength-text {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        
        .strength-0 .strength-bar { width: 20%; background: #e74c3c; }
        .strength-1 .strength-bar { width: 40%; background: #e74c3c; }
        .strength-2 .strength-bar { width: 60%; background: #f39c12; }
        .strength-3 .strength-bar { width: 80%; background: #2ecc71; }
        .strength-4 .strength-bar { width: 100%; background: #27ae60; }
        
        /* Password Requirements List */
        .requirements-list {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-top: 0.5rem;
            list-style: none;
        }
        
        .requirements-list li {
            font-size: 0.8rem;
            padding: 0.3rem 0;
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .requirements-list li::before {
            content: "○";
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .requirements-list li.met {
            color: #27ae60;
        }
        
        .requirements-list li.met::before {
            content: "✓";
            color: #27ae60;
        }
        
        /* Password Toggle Button */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            user-select: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            color: #666;
        }
        
        /* Validation Icons */
        .validation-icon {
            position: absolute;
            right: 40px;
            top: 38px;
            font-size: 1rem;
        }
        
        /* Tips Section */
        .password-tips {
            background: #e8f4fd;
            border-left: 4px solid var(--primary-color);
            padding: 0.8rem;
            border-radius: var(--border-radius);
            margin-top: 0.5rem;
        }
        
        .password-tips h4 {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--primary-color);
        }
        
        .password-tips p {
            font-size: 0.75rem;
            color: #666;
            margin: 0;
        }
        
        /* Live Indicator */
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .live-indicator.valid {
            background: #27ae60;
            box-shadow: 0 0 5px #27ae60;
        }
        
        .live-indicator.invalid {
            background: #e74c3c;
            box-shadow: 0 0 5px #e74c3c;
        }
        
        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Score Display */
        .password-score {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        .score-weak { background: #e74c3c; color: white; }
        .score-medium { background: #f39c12; color: white; }
        .score-strong { background: #2ecc71; color: white; }
        .score-very-strong { background: #27ae60; color: white; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">SmartStore</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 550px; margin: 0 auto;">
            <div class="card-header">
                <h2>Create Account</h2>
                <p>Join SmartStore for exclusive deals and secure shopping</p>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form id="registerForm" method="POST" novalidate>
                    <!-- Name Field -->
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="name" 
                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                               placeholder="Enter your full name" required>
                        <div id="nameError" class="error-message" style="color: #e74c3c; font-size: 0.75rem; margin-top: 0.25rem;"></div>
                    </div>
                    
                    <!-- Email Field -->
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" id="email" 
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                               placeholder="example@domain.com" required>
                        <div id="emailError" class="error-message" style="color: #e74c3c; font-size: 0.75rem; margin-top: 0.25rem;"></div>
                    </div>
                    
                    <!-- Password Field with Strength Checker -->
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" id="password" 
                               placeholder="Create a strong password" required>
                        <button type="button" class="password-toggle" id="togglePassword" tabindex="-1">👁️</button>
                        <div id="passwordError" class="error-message" style="color: #e74c3c; font-size: 0.75rem; margin-top: 0.25rem;"></div>
                        
                        <!-- Password Strength Meter -->
                        <div class="password-strength-container">
                            <div class="strength-meter">
                                <div class="strength-bar"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Enter a password</div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <ul class="requirements-list" id="requirementsList">
                            <li id="reqLength">✓ At least 8 characters</li>
                            <li id="reqUppercase">✓ At least 1 uppercase letter (A-Z)</li>
                            <li id="reqLowercase">✓ At least 1 lowercase letter (a-z)</li>
                            <li id="reqNumber">✓ At least 1 number (0-9)</li>
                            <li id="reqSpecial">✓ At least 1 special character (!@#$%^&*)</li>
                            <li id="reqNoCommon">✓ Not a common password</li>
                        </ul>
                        
                        <!-- Password Tips -->
                        <div class="password-tips" id="passwordTips">
                            <h4>💡 Tips for a strong password:</h4>
                            <p>• Use a mix of uppercase, lowercase, numbers, and symbols</p>
                            <p>• Make it at least 12 characters for better security</p>
                            <p>• Avoid using personal information like names or birthdays</p>
                            <p>• Don't use common passwords like "password123" or "qwerty"</p>
                        </div>
                    </div>
                    
                    <!-- Confirm Password Field -->
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirmPassword" 
                               placeholder="Confirm your password" required>
                        <button type="button" class="password-toggle" id="toggleConfirmPassword" tabindex="-1">👁️</button>
                        <div id="confirmError" class="error-message" style="color: #e74c3c; font-size: 0.75rem; margin-top: 0.25rem;"></div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" class="btn btn-primary submit-btn" disabled>
                        Create Account
                    </button>
                </form>
                
                <p style="margin-top: 1rem; text-align: center;">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // DOM Elements
        const form = document.getElementById('registerForm');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirmPassword');
        const submitBtn = document.getElementById('submitBtn');
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.getElementById('strengthText');
        
        // ==================== PASSWORD STRENGTH CHECKER CORE FUNCTIONS ====================
        
        /**
         * Check password against common passwords list
         */
        const commonPasswords = [
            'password', '123456', '12345678', 'qwerty', 'abc123', 
            'monkey', 'letmein', 'dragon', 'baseball', 'master',
            'password123', 'admin', 'welcome', 'login', 'passw0rd'
        ];
        
        function isCommonPassword(password) {
            return commonPasswords.includes(password.toLowerCase());
        }
        
        /**
         * Calculate password entropy (bits)
         * Higher entropy = stronger password
         */
        function calculateEntropy(password) {
            let charset = 0;
            if (/[a-z]/.test(password)) charset += 26;
            if (/[A-Z]/.test(password)) charset += 26;
            if (/[0-9]/.test(password)) charset += 10;
            if (/[^a-zA-Z0-9]/.test(password)) charset += 33;
            
            if (charset === 0) return 0;
            return Math.log2(Math.pow(charset, password.length));
        }
        
        /**
         * Check for repeated characters
         */
        function hasRepeatedChars(password) {
            return /(.)\1{2,}/.test(password);
        }
        
        /**
         * Check for sequential patterns
         */
        function hasSequentialPattern(password) {
            const sequences = ['abc', 'bcd', 'cde', 'def', 'efg', 'fgh', 'ghi', 'hij', 'ijk', 'jkl', 'klm', 'lmn', 'mno', 'nop', 'opq', 'pqr', 'qrs', 'rst', 'stu', 'tuv', 'uvw', 'vwx', 'wxy', 'xyz', '123', '234', '345', '456', '567', '678', '789'];
            const lowerPassword = password.toLowerCase();
            return sequences.some(seq => lowerPassword.includes(seq));
        }
        
        /**
         * Calculate password strength score (0-4)
         * 0: Very Weak, 1: Weak, 2: Medium, 3: Strong, 4: Very Strong
         */
        function calculatePasswordStrength(password) {
            if (!password) return { score: 0, feedback: 'Enter a password' };
            
            let score = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 8) {
                score++;
                if (password.length >= 12) score++;
                if (password.length >= 16) score++;
            } else {
                feedback.push('Password too short');
            }
            
            // Character variety checks
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^a-zA-Z0-9]/.test(password);
            
            if (hasLower && hasUpper && hasNumber && hasSpecial) {
                score += 2;
                feedback.push('Excellent character variety');
            } else if ((hasLower && hasUpper && hasNumber) || (hasLower && hasUpper && hasSpecial) || (hasLower && hasNumber && hasSpecial)) {
                score++;
                feedback.push('Good character variety');
            } else {
                feedback.push('Add more character types');
            }
            
            // Bonus for entropy
            const entropy = calculateEntropy(password);
            if (entropy > 60) score++;
            if (entropy > 80) score++;
            
            // Penalties
            if (isCommonPassword(password)) {
                score = Math.max(0, score - 2);
                feedback.push('Common password detected');
            }
            
            if (hasRepeatedChars(password)) {
                score = Math.max(0, score - 1);
                feedback.push('Avoid repeated characters');
            }
            
            if (hasSequentialPattern(password)) {
                score = Math.max(0, score - 1);
                feedback.push('Avoid sequential patterns');
            }
            
            // Cap score at 4
            score = Math.min(4, Math.floor(score / 2) + (score % 2));
            
            let strengthText = '';
            let strengthClass = '';
            switch(score) {
                case 0: strengthText = 'Very Weak'; strengthClass = 'weak'; break;
                case 1: strengthText = 'Weak'; strengthClass = 'weak'; break;
                case 2: strengthText = 'Medium'; strengthClass = 'medium'; break;
                case 3: strengthText = 'Strong'; strengthClass = 'strong'; break;
                case 4: strengthText = 'Very Strong'; strengthClass = 'very-strong'; break;
            }
            
            return {
                score: score,
                text: strengthText,
                class: strengthClass,
                feedback: feedback.join(', '),
                entropy: Math.round(entropy)
            };
        }
        
        /**
         * Update password requirements in real-time
         */
        function updateRequirements(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password),
                notCommon: !isCommonPassword(password)
            };
            
            // Update each requirement
            document.getElementById('reqLength').classList.toggle('met', requirements.length);
            document.getElementById('reqUppercase').classList.toggle('met', requirements.uppercase);
            document.getElementById('reqLowercase').classList.toggle('met', requirements.lowercase);
            document.getElementById('reqNumber').classList.toggle('met', requirements.number);
            document.getElementById('reqSpecial').classList.toggle('met', requirements.special);
            document.getElementById('reqNoCommon').classList.toggle('met', requirements.notCommon);
            
            return Object.values(requirements).every(v => v === true);
        }
        
        /**
         * Validate password and update strength meter
         */
        function validatePassword() {
            const password = passwordInput.value;
            const strength = calculatePasswordStrength(password);
            const allRequirementsMet = updateRequirements(password);
            
            // Update strength meter
            strengthBar.parentElement.parentElement.className = 'password-strength-container strength-' + strength.score;
            strengthText.innerHTML = `Password Strength: ${strength.text}`;
            
            // Add entropy display
            if (password.length > 0) {
                strengthText.innerHTML += ` <span class="password-score score-${strength.class}">${strength.entropy} bits entropy</span>`;
            }
            
            // Update validation state
            const passwordError = document.getElementById('passwordError');
            if (!password) {
                passwordError.textContent = 'Password is required';
                passwordInput.classList.add('invalid');
                passwordInput.classList.remove('valid');
                return false;
            } else if (!allRequirementsMet) {
                passwordError.textContent = 'Please meet all password requirements above';
                passwordInput.classList.add('invalid');
                passwordInput.classList.remove('valid');
                return false;
            } else if (strength.score < 2) {
                passwordError.textContent = 'Password is too weak. Please make it stronger.';
                passwordInput.classList.add('invalid');
                passwordInput.classList.remove('valid');
                return false;
            } else {
                passwordError.textContent = '✓ Strong password!';
                passwordError.style.color = '#27ae60';
                passwordInput.classList.add('valid');
                passwordInput.classList.remove('invalid');
                return true;
            }
        }
        
        /**
         * Validate confirm password match
         */
        function validateConfirmPassword() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            const confirmError = document.getElementById('confirmError');
            
            if (!confirm) {
                confirmError.textContent = 'Please confirm your password';
                confirmInput.classList.add('invalid');
                confirmInput.classList.remove('valid');
                return false;
            } else if (password !== confirm) {
                confirmError.textContent = 'Passwords do not match';
                confirmInput.classList.add('invalid');
                confirmInput.classList.remove('valid');
                return false;
            } else {
                confirmError.textContent = '✓ Passwords match';
                confirmError.style.color = '#27ae60';
                confirmInput.classList.add('valid');
                confirmInput.classList.remove('invalid');
                return true;
            }
        }
        
        /**
         * Validate email
         */
        function validateEmail() {
            const email = emailInput.value;
            const emailError = document.getElementById('emailError');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!email) {
                emailError.textContent = 'Email is required';
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
                return false;
            } else if (!emailPattern.test(email)) {
                emailError.textContent = 'Please enter a valid email address';
                emailInput.classList.add('invalid');
                emailInput.classList.remove('valid');
                return false;
            } else {
                emailError.textContent = '✓ Valid email';
                emailError.style.color = '#27ae60';
                emailInput.classList.add('valid');
                emailInput.classList.remove('invalid');
                return true;
            }
        }
        
        /**
         * Validate name
         */
        function validateName() {
            const name = nameInput.value;
            const nameError = document.getElementById('nameError');
            
            if (!name) {
                nameError.textContent = 'Full name is required';
                nameInput.classList.add('invalid');
                nameInput.classList.remove('valid');
                return false;
            } else if (name.length < 2) {
                nameError.textContent = 'Name must be at least 2 characters';
                nameInput.classList.add('invalid');
                nameInput.classList.remove('valid');
                return false;
            } else {
                nameError.textContent = '✓ Valid name';
                nameError.style.color = '#27ae60';
                nameInput.classList.add('valid');
                nameInput.classList.remove('invalid');
                return true;
            }
        }
        
        /**
         * Update submit button state
         */
        function updateSubmitButton() {
            const isNameValid = validateName();
            const isEmailValid = validateEmail();
            const isPasswordValid = validatePassword();
            const isConfirmValid = validateConfirmPassword();
            
            const allValid = isNameValid && isEmailValid && isPasswordValid && isConfirmValid;
            submitBtn.disabled = !allValid;
            
            if (allValid) {
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                submitBtn.style.opacity = '0.6';
                submitBtn.style.cursor = 'not-allowed';
            }
        }
        
        // ==================== EVENT LISTENERS ====================
        
        // Real-time validation on input
        nameInput.addEventListener('input', () => {
            validateName();
            updateSubmitButton();
        });
        
        emailInput.addEventListener('input', () => {
            validateEmail();
            updateSubmitButton();
        });
        
        passwordInput.addEventListener('input', () => {
            validatePassword();
            if (confirmInput.value.length > 0) {
                validateConfirmPassword();
            }
            updateSubmitButton();
        });
        
        confirmInput.addEventListener('input', () => {
            validateConfirmPassword();
            updateSubmitButton();
        });
        
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const toggleConfirm = document.getElementById('toggleConfirmPassword');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        }
        
        if (toggleConfirm) {
            toggleConfirm.addEventListener('click', function() {
                const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmInput.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        }
        
        // Initial validations
        validateName();
        validateEmail();
        validatePassword();
        updateSubmitButton();
        
        // Form submission
        form.addEventListener('submit', function(e) {
            const isPasswordStrong = validatePassword();
            const isMatch = validateConfirmPassword();
            
            if (!isPasswordStrong) {
                e.preventDefault();
                alert('Please choose a stronger password for better security.');
                passwordInput.focus();
                return false;
            }
            
            if (!isMatch) {
                e.preventDefault();
                alert('Passwords do not match. Please check again.');
                confirmInput.focus();
                return false;
            }
        });
        
        // Real-time password strength demonstration
        console.log('✅ Password Strength Checker is active!');
        console.log('Features include:');
        console.log('- Real-time password strength meter');
        console.log('- Password entropy calculation');
        console.log('- Common password detection');
        console.log('- Real-time requirement checklist');
        console.log('- Show/Hide password toggle');
        console.log('- Submit button enables only when password is strong enough');
    </script>
</body>
</html>