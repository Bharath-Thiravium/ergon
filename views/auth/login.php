<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ergon</title>
    <link rel="stylesheet" href="/ergon/assets/css/ergon.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1f2937;
        }
        
        .login-container {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
        }
        
        .login-form-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
            background: #ffffff;
        }
        
        .login-form-card {
            width: 100%;
            max-width: 420px;
            padding: 3rem 2.5rem;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .login-header h1 {
            font-size: 2.25rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 0.75rem;
            letter-spacing: -0.025em;
        }
        
        .login-header p {
            color: #6b7280;
            font-size: 1rem;
            font-weight: 400;
        }
        
        .slides-section {
            flex: 1.2;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #ec4899 100%);
        }
        
        .slides-container {
            position: relative;
            width: 100%;
            height: 100%;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            text-align: left;
            padding: 4rem;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.8s ease-in-out;
        }
        
        .slide.active {
            opacity: 1;
            transform: translateX(0);
        }
        
        .slide-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .slide-icon {
            font-size: 3rem;
            margin-right: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            animation: pulse 2s ease-in-out infinite;
        }
        
        .slide h2 {
            font-size: 2.2rem;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .slide-content {
            max-width: 500px;
        }
        
        .slide-description {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .feature-highlights {
            list-style: none;
            padding: 0;
        }
        
        .feature-highlights li {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.95rem;
        }
        
        .feature-highlights li::before {
            content: '✓';
            background: rgba(255, 255, 255, 0.2);
            color: #10b981;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.8rem;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .stats-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.15);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            margin-top: 1rem;
            backdrop-filter: blur(10px);
        }
        
        .stats-badge span {
            color: #10b981;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        
        .slide-indicators {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
        }
        
        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .indicator.active {
            background: white;
            transform: scale(1.2);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide.active .slide-content {
            animation: slideInUp 0.6s ease-out 0.3s both;
        }
        
        .form-group {
            margin-bottom: 1.75rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.625rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
            letter-spacing: 0.025em;
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 400;
            background-color: #ffffff;
            transition: all 0.2s ease;
            color: #111827;
        }
        
        .form-control::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            background-color: #ffffff;
        }
        
        .form-control:hover {
            border-color: #9ca3af;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
            letter-spacing: 0.025em;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #4338ca 0%, #6d28d9 100%);
            transform: translateY(-1px);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            font-size: 1rem;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: #374151;
        }
        
        .alert {
            padding: 0.875rem 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            text-align: center;
        }
        
        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 1024px) {
            .login-container {
                flex-direction: column;
            }
            .slides-section {
                min-height: 45vh;
                flex: none;
            }
            .login-form-section {
                min-height: 55vh;
                flex: none;
                padding: 2rem 1.5rem;
            }
            .login-form-card {
                padding: 2rem 1.5rem;
                max-width: 380px;
            }
        }
        
        @media (max-width: 640px) {
            .login-form-section {
                padding: 1.5rem 1rem;
            }
            .login-form-card {
                padding: 1.5rem 1rem;
                border-radius: 12px;
            }
            .login-header h1 {
                font-size: 1.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Login Form Section -->
        <div class="login-form-section">
            <div class="login-form-card">
                <div class="login-header">
                    <h1>🏢 Ergon</h1>
                    <p>Employee Management System</p>
                </div>
                
                <?php if (isset($_SESSION['logout_message'])): ?>
                <div class="alert alert-error">
                    ⚠ <?= htmlspecialchars($_SESSION['logout_message']) ?>
                </div>
                <?php unset($_SESSION['logout_message']); endif; ?>
                
                <form id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div style="position: relative;">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" id="togglePassword" class="password-toggle">
                                👁️
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        Sign In
                    </button>
                </form>
                
                <div id="message"></div>
            </div>
        </div>
        
        <!-- Slides Section -->
        <div class="slides-section">
            <div class="slides-container">
                <div class="slide active">
                    <div class="slide-header">
                        <div class="slide-icon">🎯</div>
                        <h2>Smart Task Management</h2>
                    </div>
                    <div class="slide-content">
                        <p class="slide-description">Transform your workflow with intelligent task assignment, real-time collaboration, and automated progress tracking that keeps your team aligned and productive.</p>
                        <ul class="feature-highlights">
                            <li>Real-time task progress monitoring with visual dashboards</li>
                            <li>Automated deadline reminders and priority alerts</li>
                            <li>Collaborative workspaces with file sharing</li>
                            <li>Custom workflow templates for recurring processes</li>
                        </ul>
                        <div class="stats-badge">
                            <span>40%</span> faster project completion
                        </div>
                    </div>
                </div>
                
                <div class="slide">
                    <div class="slide-header">
                        <div class="slide-icon">👥</div>
                        <h2>Advanced HR Analytics</h2>
                    </div>
                    <div class="slide-content">
                        <p class="slide-description">Gain deep insights into your workforce with comprehensive analytics, performance tracking, and predictive insights that drive strategic HR decisions.</p>
                        <ul class="feature-highlights">
                            <li>Biometric attendance integration with GPS tracking</li>
                            <li>Performance analytics with 360-degree feedback</li>
                            <li>Predictive turnover analysis and retention strategies</li>
                            <li>Skill gap analysis and training recommendations</li>
                        </ul>
                        <div class="stats-badge">
                            <span>25%</span> improvement in employee retention
                        </div>
                    </div>
                </div>
                
                <div class="slide">
                    <div class="slide-header">
                        <div class="slide-icon">💳</div>
                        <h2>Intelligent Expense Control</h2>
                    </div>
                    <div class="slide-content">
                        <p class="slide-description">Revolutionize expense management with AI-powered receipt scanning, automated approval workflows, and real-time budget tracking for complete financial control.</p>
                        <ul class="feature-highlights">
                            <li>OCR receipt scanning with automatic categorization</li>
                            <li>Multi-level approval workflows with budget controls</li>
                            <li>Real-time expense analytics and budget alerts</li>
                            <li>Integration with accounting systems and tax compliance</li>
                        </ul>
                        <div class="stats-badge">
                            <span>60%</span> reduction in processing time
                        </div>
                    </div>
                </div>
                
                <div class="slide">
                    <div class="slide-header">
                        <div class="slide-icon">🏗️</div>
                        <h2>Dynamic Organization Structure</h2>
                    </div>
                    <div class="slide-content">
                        <p class="slide-description">Build flexible organizational hierarchies with role-based permissions, cross-functional teams, and dynamic reporting structures that adapt to your business needs.</p>
                        <ul class="feature-highlights">
                            <li>Drag-and-drop organizational chart builder</li>
                            <li>Matrix management with dual reporting lines</li>
                            <li>Custom role definitions with granular permissions</li>
                            <li>Department-wise resource allocation and budgeting</li>
                        </ul>
                        <div class="stats-badge">
                            <span>50%</span> faster onboarding process
                        </div>
                    </div>
                </div>
                
                <div class="slide">
                    <div class="slide-header">
                        <div class="slide-icon">📊</div>
                        <h2>Executive Intelligence Dashboard</h2>
                    </div>
                    <div class="slide-content">
                        <p class="slide-description">Make data-driven decisions with comprehensive business intelligence, predictive analytics, and customizable executive dashboards that provide actionable insights.</p>
                        <ul class="feature-highlights">
                            <li>Real-time KPI monitoring with trend analysis</li>
                            <li>Predictive workforce planning and capacity forecasting</li>
                            <li>Custom report builder with automated scheduling</li>
                            <li>Mobile executive dashboard with offline access</li>
                        </ul>
                        <div class="stats-badge">
                            <span>35%</span> better decision accuracy
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="slide-indicators">
                <div class="indicator active" data-slide="0"></div>
                <div class="indicator" data-slide="1"></div>
                <div class="indicator" data-slide="2"></div>
                <div class="indicator" data-slide="3"></div>
                <div class="indicator" data-slide="4"></div>
            </div>
        </div>
    </div>

    <script>
        // Slides functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('.indicator');
        
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === index);
            });
        }
        
        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }
        
        // Auto-advance slides
        setInterval(nextSlide, 6000);
        
        // Indicator click handlers
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });
        
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                this.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                this.textContent = '👁️';
            }
        });
        
        // Login form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Signing In...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            const messageDiv = document.getElementById('message');
            
            fetch('/ergon/login', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageDiv.innerHTML = '<div class="alert alert-success">✓ Login successful! Redirecting...</div>';
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else {
                    messageDiv.innerHTML = '<div class="alert alert-error">⚠ ' + data.error + '</div>';
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                messageDiv.innerHTML = '<div class="alert alert-error">⚠ Login failed. Please try again.</div>';
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
