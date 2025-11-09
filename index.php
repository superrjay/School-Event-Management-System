<?php
require_once 'config.php';

$conn = getDBConnection();

// Initialize events array
$events = [];

// Get events for calendar and listing
if ($conn) {
    $current_month = date('Y-m');
    $events_query = "SELECT id, title, description, venue, start_datetime, end_datetime, capacity, status 
                     FROM events 
                     WHERE is_public = 1 AND status != 'cancelled' 
                     ORDER BY start_datetime ASC";
    $events_result = $conn->query($events_query);

    if ($events_result) {
        while ($row = $events_result->fetch_assoc()) {
            $events[] = $row;
        }
    } else {
        // Log error or handle query failure
        error_log("Events query failed: " . $conn->error);
    }
    
    $conn->close();
} else {
    error_log("Database connection failed");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Event Management System | SEMS</title>
    <!-- UIKIT CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.16.0/dist/css/uikit.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #10b981;
            --accent: #f59e0b;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
            overflow-x: hidden;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar-scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-sm);
        }

        .logo {
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-link {
            font-weight: 500;
            color: var(--dark);
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            border-radius: var(--radius);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid var(--primary);
            border-radius: var(--radius);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .hero-section {
            background: var(--gradient-primary);
            color: white;
            padding: 160px 0 100px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" preserveAspectRatio="none"><path fill="rgba(255,255,255,0.05)" d="M0,0 L1000,0 L1000,1000 L0,1000 Z M0,0 Q500,200 1000,0 L1000,1000 L0,1000 Z"></path></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
        }

        .floating-element {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }

        .section {
            padding: 100px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-align: center;
        }

        .section-subtitle {
            font-size: 1.125rem;
            color: var(--gray);
            text-align: center;
            max-width: 700px;
            margin: 0 auto 4rem;
        }

        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .feature-card {
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            background: var(--gradient-primary);
            color: white;
            font-size: 1.75rem;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .event-card {
            height: 100%;
        }

        .event-image {
            height: 200px;
            background: var(--gradient-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }

        .event-content {
            padding: 1.5rem;
        }

        .event-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .event-meta {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            color: var(--gray);
            font-size: 0.875rem;
        }

        .event-meta i {
            margin-right: 0.5rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-upcoming {
            background: rgba(16, 185, 129, 0.1);
            color: var(--secondary);
        }

        .status-ongoing {
            background: rgba(245, 158, 11, 0.1);
            color: var(--accent);
        }

        .status-completed {
            background: rgba(100, 116, 139, 0.1);
            color: var(--gray);
        }

        .stats-section {
            background: var(--gradient-accent);
            color: white;
            padding: 80px 0;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.125rem;
            opacity: 0.9;
        }

        .testimonial-card {
            padding: 2rem;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin: 1rem;
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .testimonial-text::before {
            content: '"';
            font-size: 4rem;
            position: absolute;
            top: -1.5rem;
            left: -1rem;
            opacity: 0.1;
            color: var(--primary);
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            margin-right: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .cta-section {
            background: var(--gradient-secondary);
            color: white;
            padding: 100px 0;
            text-align: center;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .footer {
            background: var(--dark);
            color: white;
            padding: 80px 0 40px;
        }

        .footer-logo {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .footer-links h4 {
            font-size: 1.125rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .footer-links a:hover {
            color: white;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .social-link:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .section {
                padding: 60px 0;
            }
            
            .section-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="uk-container">
            <div class="uk-navbar-container uk-navbar-transparent" uk-navbar>
                <div class="uk-navbar-left">
                    <a class="uk-navbar-item uk-logo logo" href="#">
                        <i class="fas fa-graduation-cap"></i> SEMS
                    </a>
                </div>
                <div class="uk-navbar-right">
                    <ul class="uk-navbar-nav">
                        <li><a href="#features" class="nav-link">Features</a></li>
                        <li><a href="#events" class="nav-link">Events</a></li>
                        <li><a href="#testimonials" class="nav-link">Testimonials</a></li>
                        <li><a href="login.php" class="btn-primary">
                            <i class="fas fa-sign-in-alt uk-margin-small-right"></i>Login
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="uk-container">
            <div class="uk-grid-match uk-child-width-1-2@m" uk-grid>
                <div class="hero-content" data-aos="fade-right">
                    <h1 class="hero-title">School Event Management System</h1>
                    <p class="hero-subtitle">Streamline your school events with our comprehensive management platform. Plan, organize, and track all your events in one place with powerful tools designed for educational institutions.</p>
                    <div class="uk-grid-small uk-child-width-auto" uk-grid>
                        <div>
                            <a href="public_events.php" class="btn-primary">
                                <span class="uk-icon uk-margin-small-right" uk-icon="icon: calendar"></span>
                                View Events
                            </a>
                        </div>
                        <div>
                            <a href="login.php" class="btn-primary">
                                <span class="uk-icon uk-margin-small-right" uk-icon="icon: sign-in"></span>
                                Get Started
                            </a>
                        </div>
                    </div>
                </div>
                <div data-aos="fade-left" data-aos-delay="200">
                    <div class="uk-text-center floating-element">
                        <i class="fas fa-calendar-alt" style="font-size: 200px; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="uk-container">
            <div class="uk-grid-match uk-child-width-1-4@m uk-text-center" uk-grid data-aos="fade-up">
                <div>
                    <div class="stat-number">500+</div>
                    <div class="stat-label">Events Managed</div>
                </div>
                <div>
                    <div class="stat-number">15K+</div>
                    <div class="stat-label">Participants</div>
                </div>
                <div>
                    <div class="stat-number">200+</div>
                    <div class="stat-label">Schools</div>
                </div>
                <div>
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="section">
        <div class="uk-container">
            <h2 class="section-title" data-aos="fade-up">Powerful Features</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Our comprehensive platform provides everything you need to manage school events efficiently</p>
            
            <div class="uk-grid-match uk-child-width-1-3@m" uk-grid>
                <div data-aos="fade-up" data-aos-delay="200">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h3 class="feature-title">Event Planning</h3>
                        <p>Create and manage events with detailed information, dates, venues, and capacity limits. Set up recurring events and automated reminders.</p>
                    </div>
                </div>
                <div data-aos="fade-up" data-aos-delay="300">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="feature-title">Registration Management</h3>
                        <p>Handle participant registrations, track attendance, and manage event capacity with our intuitive registration system.</p>
                    </div>
                </div>
                <div data-aos="fade-up" data-aos-delay="400">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3 class="feature-title">Budget Tracking</h3>
                        <p>Monitor event expenses and income with comprehensive budget management tools and generate detailed financial reports.</p>
                    </div>
                </div>
            </div>
            
            <div class="uk-grid-match uk-child-width-1-3@m uk-margin-medium-top" uk-grid>
                <div data-aos="fade-up" data-aos-delay="500">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <h3 class="feature-title">Promotion Tools</h3>
                        <p>Promote your events with customizable templates, email campaigns, and social media integration to maximize attendance.</p>
                    </div>
                </div>
                <div data-aos="fade-up" data-aos-delay="600">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                        <h3 class="feature-title">Reporting & Analytics</h3>
                        <p>Gain insights with comprehensive reports on attendance, engagement, and event performance to improve future planning.</p>
                    </div>
                </div>
                <div data-aos="fade-up" data-aos-delay="700">
                    <div class="card feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Mobile Friendly</h3>
                        <p>Access the system from any device with our responsive design and dedicated mobile app for on-the-go event management.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section id="events" class="section uk-section-muted">
        <div class="uk-container">
            <h2 class="section-title" data-aos="fade-up">Upcoming Events</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Discover and register for upcoming school events</p>
            
            <?php if (count($events) > 0): ?>
                <div class="uk-grid-match uk-child-width-1-3@m" uk-grid>
                    <?php foreach ($events as $event): ?>
                        <div data-aos="fade-up" data-aos-delay="<?php echo $event['id'] * 100; ?>">
                            <div class="card event-card">
                                <div class="event-image">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="event-content">
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <div class="event-meta">
                                        <div class="uk-margin-small-right">
                                            <i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($event['start_datetime'])); ?>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['start_datetime'])); ?>
                                        </div>
                                    </div>
                                    <p class="uk-margin-small-bottom">
                                        <span class="status-badge status-<?php echo $event['status']; ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </p>
                                    <?php if ($event['description']): ?>
                                        <p class="uk-text-small uk-margin-small-top"><?php echo htmlspecialchars(substr($event['description'], 0, 120)); ?>...</p>
                                    <?php endif; ?>
                                    <div class="uk-grid-small uk-child-width-1-2 uk-margin-top" uk-grid>
                                        <div>
                                            <div class="uk-text-small"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></div>
                                        </div>
                                        <div>
                                            <div class="uk-text-small"><i class="fas fa-users"></i> <?php echo $event['capacity']; ?> spots</div>
                                        </div>
                                    </div>
                                    <div class="uk-margin-top">
                                        <a href="#event-modal-<?php echo $event['id']; ?>" class="btn-primary uk-width-1-1 uk-text-center" uk-toggle>
                                            View Details & Register
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Event Modal -->
                        <div id="event-modal-<?php echo $event['id']; ?>" uk-modal>
                            <div class="uk-modal-dialog">
                                <button class="uk-modal-close-default" type="button" uk-close></button>
                                <div class="uk-modal-header">
                                    <h2 class="uk-modal-title"><?php echo htmlspecialchars($event['title']); ?></h2>
                                </div>
                                <div class="uk-modal-body">
                                    <div class="uk-grid-small" uk-grid>
                                        <div class="uk-width-1-3">
                                            <strong>Description:</strong>
                                        </div>
                                        <div class="uk-width-2-3">
                                            <?php echo htmlspecialchars($event['description']); ?>
                                        </div>
                                        
                                        <div class="uk-width-1-3">
                                            <strong>Start:</strong>
                                        </div>
                                        <div class="uk-width-2-3">
                                            <?php echo date('F j, Y g:i A', strtotime($event['start_datetime'])); ?>
                                        </div>
                                        
                                        <div class="uk-width-1-3">
                                            <strong>End:</strong>
                                        </div>
                                        <div class="uk-width-2-3">
                                            <?php echo date('F j, Y g:i A', strtotime($event['end_datetime'])); ?>
                                        </div>
                                        
                                        <div class="uk-width-1-3">
                                            <strong>Venue:</strong>
                                        </div>
                                        <div class="uk-width-2-3">
                                            <?php echo htmlspecialchars($event['venue']); ?>
                                        </div>
                                        
                                        <div class="uk-width-1-3">
                                            <strong>Capacity:</strong>
                                        </div>
                                        <div class="uk-width-2-3">
                                            <?php echo $event['capacity']; ?> attendees
                                        </div>
                                        
                                        <div class="uk-width-1-3">
                                            <strong>Status:</strong>
                                        </div>
                                        <div class="uk-width-2-3">
                                            <span class="status-badge status-<?php echo $event['status']; ?>">
                                                <?php echo ucfirst($event['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="uk-modal-footer uk-text-right">
                                    <button class="btn-secondary uk-modal-close" type="button">Close</button>
                                    <a href="login.php" class="btn-primary">Register for Event</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="uk-text-center uk-padding-large" data-aos="fade-up">
                    <i class="fas fa-calendar-times" style="font-size: 4em; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No Upcoming Events</h3>
                    <p class="uk-text-muted">Check back later for new events!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="section">
        <div class="uk-container">
            <h2 class="section-title" data-aos="fade-up">What Schools Say</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Hear from educational institutions using our platform</p>
            
            <div class="uk-grid-match uk-child-width-1-3@m" uk-grid>
                <div data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card">
                        <div class="testimonial-text">
                            SEMS has transformed how we manage our school events. The registration process is seamless, and the reporting features save us hours of work.
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">JS</div>
                            <div>
                                <div class="author-name">Jennifer Smith</div>
                                <div class="author-role">Event Coordinator, Lincoln High</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div data-aos="fade-up" data-aos-delay="300">
                    <div class="testimonial-card">
                        <div class="testimonial-text">
                            The budget tracking feature alone is worth the investment. We've saved 15% on event costs since implementing SEMS.
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">MR</div>
                            <div>
                                <div class="author-name">Michael Rodriguez</div>
                                <div class="author-role">Principal, Oakwood Academy</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div data-aos="fade-up" data-aos-delay="400">
                    <div class="testimonial-card">
                        <div class="testimonial-text">
                            Our parent engagement has increased significantly since using SEMS. The mobile-friendly interface makes it easy for everyone to participate.
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">AK</div>
                            <div>
                                <div class="author-name">Amanda Kim</div>
                                <div class="author-role">PTA President, Westfield School</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="uk-container">
            <h2 class="cta-title" data-aos="fade-up">Ready to Transform Your School Events?</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Join hundreds of schools already using SEMS to streamline their event management</p>
            <div class="uk-margin-medium-top" data-aos="fade-up" data-aos-delay="200">
                <a href="login.php" class="btn-primary uk-button-large">
                    <span class="uk-icon uk-margin-small-right" uk-icon="icon: sign-in"></span>
                    Get Started Today
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="uk-container">
            <div class="uk-grid-match uk-child-width-1-4@m" uk-grid>
                <div>
                    <div class="footer-logo">
                        <i class="fas fa-graduation-cap"></i> SEMS
                    </div>
                    <p>Comprehensive event management solution for educational institutions of all sizes.</p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#events">Events</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Updates</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Tutorials</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h4>Contact</h4>
                    <ul>
                        <li><i class="fas fa-envelope"></i> events@schoolsystem.edu</li>
                        <li><i class="fas fa-phone"></i> (555) 123-4567</li>
                        <li><i class="fas fa-map-marker-alt"></i> 123 School Street, Education City</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 School Event Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- UIKIT JS -->
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.16.0/dist/js/uikit.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/uikit@3.16.0/dist/js/uikit-icons.min.js"></script>
    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 600,
            once: true,
            offset: 50,
            delay: 0,
            throttleDelay: 50
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });

        // Calendar functionality
        let currentCalendarDate = new Date();

        function generateCalendar() {
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"];
            
            const year = currentCalendarDate.getFullYear();
            const month = currentCalendarDate.getMonth();
            
            // Update month/year display
            document.getElementById('calendar-month-year').textContent = `${monthNames[month]} ${year}`;
            
            const events = <?php echo json_encode($events); ?>;
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            // Day headers
            let calendarHTML = `
                <div class="uk-grid uk-grid-small uk-text-center uk-text-bold uk-margin-small-bottom">
                    <div class="uk-width-1-7">Sun</div>
                    <div class="uk-width-1-7">Mon</div>
                    <div class="uk-width-1-7">Tue</div>
                    <div class="uk-width-1-7">Wed</div>
                    <div class="uk-width-1-7">Thu</div>
                    <div class="uk-width-1-7">Fri</div>
                    <div class="uk-width-1-7">Sat</div>
                </div>
                <div class="uk-grid uk-grid-small uk-text-center" id="calendar-days">
            `;
            
            // Add empty cells for days before the first day of the month
            for (let i = 0; i < firstDay; i++) {
                calendarHTML += `<div class="uk-width-1-7 calendar-day other-month">&nbsp;</div>`;
            }
            
            // Add days of the month
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const currentDate = new Date(year, month, day);
                const isToday = currentDate.getTime() === today.getTime();
                
                const dayEvents = events.filter(event => {
                    const eventDate = event.start_datetime.split(' ')[0];
                    return eventDate === dateStr;
                });
                
                calendarHTML += `<div class="uk-width-1-7 calendar-day current-month ${isToday ? 'uk-background-primary uk-light' : ''}">`;
                calendarHTML += `<div class="uk-text-bold ${isToday ? 'uk-text-white' : ''}">${day}</div>`;
                
                dayEvents.forEach(event => {
                    calendarHTML += `
                        <div class="calendar-event" onclick="UIkit.modal('#event-modal-${event.id}').show()" title="${event.title}">
                            ${event.title.substring(0, 8)}...
                        </div>
                    `;
                });
                
                calendarHTML += `</div>`;
            }
            
            calendarHTML += `</div>`;
            document.getElementById('calendar-view').innerHTML = calendarHTML;
        }

        function changeMonth(direction) {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
            generateCalendar();
        }

        // Initialize calendar
        document.addEventListener('DOMContentLoaded', function() {
            generateCalendar();
        });
    </script>
</body>
</html>