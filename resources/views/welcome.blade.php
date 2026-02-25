{{-- resources/views/welcome.blade.php --}}
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Management System - Manage Your Fleet Efficiently</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('app-assets/images/ico/favicon.png') }}">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #55bcd7;
            --primary-dark: #3f66aa;
            --secondary: #3f66aa;
            --accent: #1e0d3a;
            --dark: #333333;
            --light: #f7fafc;
            --gray: #718096;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            overflow-x: hidden;
        }

        /* ==================== NAVBAR ==================== */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: white;
            box-shadow: 0 2px 30px rgba(0,0,0,0.15);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            width: auto;
            height: 46px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        /* ==================== HERO SECTION ==================== */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #4273b0 0%, #53b4d2 100%);
            position: relative;
            overflow: hidden;
            padding-top: 80px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,117.3C1248,117,1344,139,1392,149.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }

        .hero-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-content p {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-large {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }

        .btn-white {
            background: white;
            color: var(--primary);
        }

        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255,255,255,0.3);
        }

        .hero-image {
            position: relative;
        }

        .hero-image img {
            width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        /* ==================== FEATURES SECTION ==================== */
        .features {
            padding: 6rem 2rem;
            background: var(--light);
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .section-header p {
            font-size: 1.25rem;
            color: var(--gray);
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .feature-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .feature-card p {
            color: var(--gray);
            line-height: 1.8;
        }

        /* ==================== STATS SECTION ==================== */
        .stats {
            padding: 4rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .stats-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
        }

        /* ==================== PRICING SECTION ==================== */
        .pricing {
            padding: 6rem 2rem;
            background: white;
        }

        .pricing-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .pricing-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 2.5rem;
            transition: all 0.3s;
            position: relative;
        }

        .pricing-card.featured {
            border-color: var(--primary);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3);
            transform: scale(1.05);
        }

        .pricing-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .pricing-header h3 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }

        .pricing-price {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary);
            margin: 1.5rem 0;
        }

        .pricing-price span {
            font-size: 1rem;
            color: var(--gray);
        }

        .pricing-features {
            list-style: none;
            margin: 2rem 0;
        }

        .pricing-features li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .pricing-features li i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        /* ==================== TESTIMONIALS SECTION ==================== */
        .testimonials {
            padding: 6rem 2rem;
            background: var(--light);
        }

        .testimonials-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .testimonial-content {
            font-style: italic;
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.8;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }

        .author-info h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .author-info p {
            font-size: 0.9rem;
            color: var(--gray);
        }

        /* ==================== CTA SECTION ==================== */
        .cta {
            padding: 6rem 2rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 2rem;
        }

        /* ==================== FOOTER ==================== */
        .footer {
            background: var(--dark);
            color: white;
            padding: 4rem 2rem 2rem;
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.75rem;
        }

        .footer-section a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            color: rgba(255,255,255,0.7);
        }

        /* ==================== MOBILE RESPONSIVE ==================== */
        @media (max-width: 768px) {
            .hero-container {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-buttons {
                justify-content: center;
            }

            .nav-links {
                display: none;
            }

            .pricing-card.featured {
                transform: scale(1);
            }
        }

        /* ==================== ANIMATIONS ==================== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <div class="logo">
            <img src="{{ asset('app-assets/images/logo/app-logo.png') }}">
        </div>
        <ul class="nav-links">
            <li><a href="#aboutus">About Us</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#careers">Careers</a></li>
            <li><a href="#pricing">Pricing</a></li>
            <li><a href="#documentation">Documentation</a></li>
            <li><a href="#help-centre">Help Centre</a></li>
        </ul>
        <div class="nav-buttons">
            <a href="{{ route('login') }}" class="btn btn-outline">Login</a>
            <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-container">
        <div class="hero-content fade-in-up">
            <h1>Manage Your Fleet with Confidence</h1>
            <p>Streamline operations, reduce costs, and maximize efficiency with our comprehensive fleet management solution.</p>
            <div class="hero-buttons">
                <a href="{{ route('register') }}" class="btn btn-white btn-large">
                    Start Free Trial <i class="fas fa-arrow-right"></i>
                </a>
                <a href="#features" class="btn btn-outline btn-large" style="color: white; border-color: white;">
                    Learn More
                </a>
            </div>
        </div>
        <div class="hero-image fade-in-up">
            <img src="https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=800" alt="Fleet Management Dashboard">
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats">
    <div class="stats-grid">
        <div class="stat-item fade-in-up">
            <h3>500+</h3>
            <p>Active Companies</p>
        </div>
        <div class="stat-item fade-in-up">
            <h3>10,000+</h3>
            <p>Vehicles Managed</p>
        </div>
        <div class="stat-item fade-in-up">
            <h3>99.9%</h3>
            <p>Uptime Guarantee</p>
        </div>
        <div class="stat-item fade-in-up">
            <h3>24/7</h3>
            <p>Support Available</p>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features" id="features">
    <div class="section-header">
        <h2>Powerful Features for Modern Fleets</h2>
        <p>Everything you need to manage your fleet efficiently in one platform</p>
    </div>
    <div class="features-grid">
        <div class="feature-card fade-in-up">
            <div class="feature-icon">
                <i class="fas fa-car"></i>
            </div>
            <h3>Vehicle Management</h3>
            <p>Track and manage your entire fleet with detailed vehicle profiles, maintenance schedules, and real-time status updates.</p>
        </div>

        <div class="feature-card fade-in-up">
            <div class="feature-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <h3>Driver Management</h3>
            <p>Manage driver information, licenses, assignments, and performance metrics all in one place.</p>
        </div>

        <div class="feature-card fade-in-up">
            <div class="feature-icon">
                <i class="fas fa-wrench"></i>
            </div>
            <h3>Maintenance Tracking</h3>
            <p>Never miss a service with automated MOT, insurance, and maintenance reminders.</p>
        </div>

        <div class="feature-card fade-in-up">
            <div class="feature-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3>Advanced Analytics</h3>
            <p>Get insights into fleet performance, costs, and efficiency with comprehensive reports.</p>
        </div>

        <div class="feature-card fade-in-up">
            <div class="feature-icon">
                <i class="fas fa-bell"></i>
            </div>
            <h3>Smart Notifications</h3>
            <p>Receive email and SMS alerts for upcoming renewals, maintenance, and important events.</p>
        </div>

        <div class="feature-card fade-in-up">
            <div class="feature-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h3>Secure & Compliant</h3>
            <p>Bank-level security with role-based access control and complete audit trails.</p>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="pricing" id="pricing">
    <div class="section-header">
        <h2>Simple, Transparent Pricing</h2>
        <p>Choose the perfect plan for your fleet size</p>
    </div>
    <div class="pricing-grid">
        <div class="pricing-card">
            <div class="pricing-header">
                <h3>Basic</h3>
                <div class="pricing-price">£29<span>/month</span></div>
            </div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Up to 5 vehicles</li>
                <li><i class="fas fa-check"></i> 3 users</li>
                <li><i class="fas fa-check"></i> Basic reports</li>
                <li><i class="fas fa-check"></i> Email notifications</li>
                <li><i class="fas fa-check"></i> 30-day trial</li>
            </ul>
            <a href="{{ route('register') }}" class="btn btn-outline" style="width: 100%;">Get Started</a>
        </div>

        <div class="pricing-card featured">
            <div class="pricing-badge">Most Popular</div>
            <div class="pricing-header">
                <h3>Standard</h3>
                <div class="pricing-price">£59<span>/month</span></div>
            </div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Up to 20 vehicles</li>
                <li><i class="fas fa-check"></i> 10 users</li>
                <li><i class="fas fa-check"></i> Advanced reports</li>
                <li><i class="fas fa-check"></i> SMS notifications</li>
                <li><i class="fas fa-check"></i> Priority support</li>
                <li><i class="fas fa-check"></i> 30-day trial</li>
            </ul>
            <a href="{{ route('register') }}" class="btn btn-primary" style="width: 100%;">Get Started</a>
        </div>

        <div class="pricing-card">
            <div class="pricing-header">
                <h3>Premium</h3>
                <div class="pricing-price">£99<span>/month</span></div>
            </div>
            <ul class="pricing-features">
                <li><i class="fas fa-check"></i> Unlimited vehicles</li>
                <li><i class="fas fa-check"></i> Unlimited users</li>
                <li><i class="fas fa-check"></i> Custom reports</li>
                <li><i class="fas fa-check"></i> API access</li>
                <li><i class="fas fa-check"></i> Dedicated manager</li>
                <li><i class="fas fa-check"></i> 30-day trial</li>
            </ul>
            <a href="{{ route('register') }}" class="btn btn-outline" style="width: 100%;">Get Started</a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials" id="testimonials">
    <div class="section-header">
        <h2>Trusted by Fleet Managers Worldwide</h2>
        <p>See what our customers have to say</p>
    </div>
    <div class="testimonials-grid">
        <div class="testimonial-card fade-in-up">
            <div class="testimonial-content">
                "FleetIQ has transformed how we manage our 50+ vehicle fleet. The automated reminders alone have saved us thousands in penalties."
            </div>
            <div class="testimonial-author">
                <div class="author-avatar">JD</div>
                <div class="author-info">
                    <h4>John Davies</h4>
                    <p>Fleet Manager, TransportCo</p>
                </div>
            </div>
        </div>

        <div class="testimonial-card fade-in-up">
            <div class="testimonial-content">
                "The best investment we've made for our business. Real-time tracking and comprehensive reports give us complete visibility."
            </div>
            <div class="testimonial-author">
                <div class="author-avatar">SP</div>
                <div class="author-info">
                    <h4>Sarah Peterson</h4>
                    <p>Operations Director, LogiFlow</p>
                </div>
            </div>
        </div>

        <div class="testimonial-card fade-in-up">
            <div class="testimonial-content">
                "Outstanding customer support and an intuitive platform. We were up and running within hours, not weeks."
            </div>
            <div class="testimonial-author">
                <div class="author-avatar">MK</div>
                <div class="author-info">
                    <h4>Michael Khan</h4>
                    <p>CEO, Swift Logistics</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <h2>Ready to Transform Your Fleet Management?</h2>
    <p>Start your 30-day free trial today. No credit card required.</p>
    <a href="{{ route('register') }}" class="btn btn-white btn-large">
        Start Free Trial <i class="fas fa-arrow-right"></i>
    </a>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-section">
            <img src="{{ asset('app-assets/images/logo/app-logo.png') }}" width="128px;">
            <p>The complete fleet management solution for modern businesses.</p>
        </div>

        <div class="footer-section">
            <h3>Product</h3>
            <ul>
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#">API</a></li>
                <li><a href="#">Integrations</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h3>Company</h3>
            <ul>
                <li><a href="#">About Us</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Contact</a></li>
                <li><a href="#">Blog</a></li>
            </ul>
        </div>

        <div class="footer-section">
            <h3>Support</h3>
            <ul>
                <li><a href="#">Help Center</a></li>
                <li><a href="#">Documentation</a></li>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; {{ date('Y') }} FleetIQ. All rights reserved.</p>
    </div>
</footer>

<script>
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Fade in on scroll animation
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.fade-in-up').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });
</script>
</body>
</html>
