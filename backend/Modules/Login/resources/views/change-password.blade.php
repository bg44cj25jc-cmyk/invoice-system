<!DOCTYPE html>
<html dir="ltr" lang="en">
<head>
    @include('include/meta')
    <style>
        .password-strength {
            margin-top: 10px;
        }
        .strength-meter {
            height: 5px;
            border-radius: 3px;
            background-color: #e0e0e0;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .strength-meter-fill {
            height: 100%;
            transition: all 0.3s ease;
        }
        .strength-weak { background-color: #dc3545; width: 33%; }
        .strength-medium { background-color: #ffc107; width: 66%; }
        .strength-strong { background-color: #28a745; width: 100%; }
        .password-requirements {
            font-size: 12px;
            margin-top: 5px;
        }
        .requirement {
            color: #dc3545;
            transition: color 0.3s ease;
        }
        .requirement.valid {
            color: #28a745;
        }
        .requirement i {
            margin-right: 5px;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        .password-input-wrapper {
            position: relative;
        }
    </style>
</head>
<body> 
@include('include/header')
@include('include/left')

<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body customBody">
                        <h5 class="card-title mb-0">Change Password</h5>
                        <button type="button" class="btn btn-secondary customBtn"
                                onclick="window.location.href='{{ url('/dashboard') }}'">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <!-- Success Message -->
                                    @if(session()->has('success'))
                                        <div class="alert alert-success alert-dismissible fade show">
                                            <i class="fas fa-check-circle"></i> {{ session()->get('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <!-- Error Messages -->
                                    @if($errors->any())
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <!-- Password Change Form -->
                                    <form class="form-horizontal" action="{{ route('password.update') }}" method="post" id="changePasswordForm">
                                        @csrf

                                        <!-- Current Password -->
                                        <div class="form-group row mb-4">
                                            <label class="col-sm-4 text-end control-label col-form-label">
                                                Current Password <span class="text-danger">*</span>
                                            </label>
                                            <div class="col-sm-8">
                                                <div class="password-input-wrapper">
                                                    <input
                                                        type="password"
                                                        class="form-control"
                                                        id="current_password"
                                                        name="current_password"
                                                        placeholder="Enter your current password"
                                                        required
                                                    />
                                                    <span class="password-toggle" onclick="togglePassword('current_password')">
                                                        <i class="fas fa-eye" id="current_password_icon"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- New Password -->
                                        <div class="form-group row mb-4">
                                            <label class="col-sm-4 text-end control-label col-form-label">
                                                New Password <span class="text-danger">*</span>
                                            </label>
                                            <div class="col-sm-8">
                                                <div class="password-input-wrapper">
                                                    <input
                                                        type="password"
                                                        class="form-control"
                                                        id="new_password"
                                                        name="new_password"
                                                        placeholder="Enter new password"
                                                        required
                                                    />
                                                    <span class="password-toggle" onclick="togglePassword('new_password')">
                                                        <i class="fas fa-eye" id="new_password_icon"></i>
                                                    </span>
                                                </div>
                                                
                                                <!-- Password Strength Meter -->
                                                <div class="password-strength">
                                                    <div class="strength-meter">
                                                        <div class="strength-meter-fill" id="strengthMeter"></div>
                                                    </div>
                                                    <small id="strengthText" class="text-muted"></small>
                                                </div>

                                                <!-- Password Requirements -->
                                                <div class="password-requirements">
                                                    <div class="requirement" id="req-length">
                                                        <i class="fas fa-times-circle"></i>
                                                        At least 8 characters
                                                    </div>
                                                    <div class="requirement" id="req-uppercase">
                                                        <i class="fas fa-times-circle"></i>
                                                        One uppercase letter
                                                    </div>
                                                    <div class="requirement" id="req-lowercase">
                                                        <i class="fas fa-times-circle"></i>
                                                        One lowercase letter
                                                    </div>
                                                    <div class="requirement" id="req-number">
                                                        <i class="fas fa-times-circle"></i>
                                                        One number
                                                    </div>
                                                    <div class="requirement" id="req-special">
                                                        <i class="fas fa-times-circle"></i>
                                                        One special character
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Confirm New Password -->
                                        <div class="form-group row mb-4">
                                            <label class="col-sm-4 text-end control-label col-form-label">
                                                Confirm New Password <span class="text-danger">*</span>
                                            </label>
                                            <div class="col-sm-8">
                                                <div class="password-input-wrapper">
                                                    <input
                                                        type="password"
                                                        class="form-control"
                                                        id="new_password_confirmation"
                                                        name="new_password_confirmation"
                                                        placeholder="Re-enter new password"
                                                        required
                                                    />
                                                    <span class="password-toggle" onclick="togglePassword('new_password_confirmation')">
                                                        <i class="fas fa-eye" id="new_password_confirmation_icon"></i>
                                                    </span>
                                                </div>
                                                <small class="form-text text-muted" id="passwordMatch"></small>
                                            </div>
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="border-top pt-3">
                                            <div class="row">
                                                <div class="col-sm-8 offset-sm-4">
                                                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                                        <i class="fas fa-key"></i> Update Password
                                                    </button>
                                                    <button type="reset" class="btn btn-secondary">
                                                        <i class="fas fa-redo"></i> Reset
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Security Tips -->
                                    <div class="alert alert-info mt-4">
                                        <h6><i class="fas fa-shield-alt"></i> Security Tips:</h6>
                                        <ul class="mb-0">
                                            <li>Use a unique password that you don't use elsewhere</li>
                                            <li>Change your password regularly</li>
                                            <li>Never share your password with anyone</li>
                                            <li>Use a password manager to store complex passwords</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('include/footer')

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[@$!%*#?&]/.test(password)
    };

    // Update requirements display
    updateRequirement('req-length', requirements.length);
    updateRequirement('req-uppercase', requirements.uppercase);
    updateRequirement('req-lowercase', requirements.lowercase);
    updateRequirement('req-number', requirements.number);
    updateRequirement('req-special', requirements.special);

    // Calculate strength
    Object.values(requirements).forEach(req => {
        if (req) strength++;
    });

    // Update strength meter
    const meter = document.getElementById('strengthMeter');
    const strengthText = document.getElementById('strengthText');
    
    meter.className = 'strength-meter-fill';
    
    if (strength === 0) {
        meter.style.width = '0%';
        strengthText.textContent = '';
    } else if (strength <= 2) {
        meter.classList.add('strength-weak');
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#dc3545';
    } else if (strength <= 4) {
        meter.classList.add('strength-medium');
        strengthText.textContent = 'Medium password';
        strengthText.style.color = '#ffc107';
    } else {
        meter.classList.add('strength-strong');
        strengthText.textContent = 'Strong password';
        strengthText.style.color = '#28a745';
    }

    checkFormValidity();
});

// Check password confirmation match
document.getElementById('new_password_confirmation').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const matchText = document.getElementById('passwordMatch');

    if (confirmPassword === '') {
        matchText.textContent = '';
    } else if (newPassword === confirmPassword) {
        matchText.textContent = '✓ Passwords match';
        matchText.style.color = '#28a745';
    } else {
        matchText.textContent = '✗ Passwords do not match';
        matchText.style.color = '#dc3545';
    }

    checkFormValidity();
});

// Update requirement display
function updateRequirement(id, isValid) {
    const element = document.getElementById(id);
    const icon = element.querySelector('i');
    
    if (isValid) {
        element.classList.add('valid');
        icon.classList.remove('fa-times-circle');
        icon.classList.add('fa-check-circle');
    } else {
        element.classList.remove('valid');
        icon.classList.remove('fa-check-circle');
        icon.classList.add('fa-times-circle');
    }
}

// Check if form is valid
function checkFormValidity() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('new_password_confirmation').value;
    const currentPassword = document.getElementById('current_password').value;
    const submitBtn = document.getElementById('submitBtn');

    const requirements = {
        length: newPassword.length >= 8,
        uppercase: /[A-Z]/.test(newPassword),
        lowercase: /[a-z]/.test(newPassword),
        number: /[0-9]/.test(newPassword),
        special: /[@$!%*#?&]/.test(newPassword)
    };

    const allRequirementsMet = Object.values(requirements).every(req => req);
    const passwordsMatch = newPassword === confirmPassword && confirmPassword !== '';
    const currentPasswordFilled = currentPassword !== '';

    submitBtn.disabled = !(allRequirementsMet && passwordsMatch && currentPasswordFilled);
}

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    });
}, 5000);
</script>
</body>
</html>