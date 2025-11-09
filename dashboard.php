<?php
require_once 'config.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - School Event Management System</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #3a3f9e;
            --secondary-color: #5a2d9c;
            --accent-color: #e68a2e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --text-dark: #2d3748;
            --text-medium: #4a5568;
            --text-light: #6c757d;
            --border-color: #dee2e6;
            --sidebar-width: 280px;
            --header-height: 70px;
            --bg-gradient: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-dark);
            overflow-x: hidden;
        }
        
        .dashboard-container { 
            display: flex; 
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar { 
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white; 
            padding: 0;
            overflow-y: auto;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            transition: transform 0.3s ease;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sidebar-header h2 i {
            color: var(--accent-color);
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .user-info p {
            margin: 5px 0;
            font-size: 0.9rem;
        }
        
        .user-info strong {
            color: var(--accent-color);
            font-weight: 600;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar ul { 
            list-style: none; 
        }
        
        .sidebar li { 
            margin: 5px 15px;
        }
        
        .sidebar a { 
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none; 
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .sidebar a i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar a:hover { 
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar a.active { 
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .sidebar hr {
            border: none;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 15px 20px;
        }
        
        /* Main Content */
        .main-content { 
            flex: 1;
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Top Header */
        .top-header {
            background: white;
            padding: 0 30px;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .burger-menu {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: none;
        }
        
        .burger-menu:hover {
            background: var(--light-color);
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }
        
        .header-title p {
            font-size: 0.85rem;
            color: var(--text-medium);
            margin: 0;
            font-weight: 500;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px;
        }
        
        #message-container {
            margin-bottom: 20px;
        }
        
        .tab-content { 
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active { 
            display: block; 
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Cards */
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .content-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .content-card h2 i {
            color: var(--accent-color);
        }
        
        /* Stats Grid */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 20px; 
            margin: 20px 0; 
        }
        
        .stat-card { 
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card h3 { 
            margin: 0 0 15px 0;
            color: var(--text-medium);
            font-size: 0.95rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number { 
            font-size: 2.5em;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        /* Tables */
        table { 
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        table th, table td { 
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        table th { 
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        table tbody tr {
            transition: all 0.2s ease;
        }
        
        table tbody tr:hover {
            background: var(--light-color);
        }
        
        table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Forms */
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin: 20px 0;
        }
        
        .form-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group { 
            margin: 20px 0;
        }
        
        .form-group label { 
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 0.95rem;
        }
        
        input, select, textarea { 
            padding: 12px 15px;
            margin: 5px 0;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 500px;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(58, 63, 158, 0.1);
        }
        
        /* Buttons */
        button { 
            padding: 12px 24px;
            margin: 5px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        button[type="submit"], .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        button[type="button"], .btn-secondary {
            background: var(--text-medium);
            color: white;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }
        
        /* Alerts */
        .success, .error { 
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success { 
            color: var(--success-color);
            background: rgba(25, 135, 84, 0.1);
            border-left: 4px solid var(--success-color);
        }
        
        .error { 
            color: var(--danger-color);
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid var(--danger-color);
        }
        
        /* Profile Info */
        .profile-info { 
            background: linear-gradient(135deg, rgba(58, 63, 158, 0.05), rgba(90, 45, 156, 0.05));
            padding: 25px;
            border-radius: 12px;
            margin: 20px 0;
            border: 2px solid var(--border-color);
        }
        
        /* Media Gallery */
        .media-gallery { 
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .media-item { 
            border: 2px solid var(--border-color);
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            background: white;
            transition: all 0.3s ease;
        }
        
        .media-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .media-item img { 
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        /* Payment Status */
        .payment-status { 
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .payment-pending { 
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
        }
        
        .payment-completed { 
            background: rgba(25, 135, 84, 0.2);
            color: var(--success-color);
        }
        
        .payment-failed { 
            background: rgba(220, 53, 69, 0.2);
            color: var(--danger-color);
        }
        
        /* Budget Type Badges */
        .budget-type-income {
            background: rgba(25, 135, 84, 0.2);
            color: var(--success-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .budget-type-expense {
            background: rgba(220, 53, 69, 0.2);
            color: var(--danger-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        /* Overlay for mobile menu */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .burger-menu {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            :root {
                --sidebar-width: 280px;
            }
            
            .top-header {
                padding: 0 15px;
            }
            
            .content-area {
                padding: 15px;
            }
            
            .header-title h1 {
                font-size: 1.2rem;
            }
            
            .header-title p {
                display: none;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content-card {
                padding: 20px;
            }
            
            table {
                font-size: 0.85rem;
            }
            
            table th, table td {
                padding: 10px;
            }
            
            input, select, textarea {
                max-width: 100%;
            }
            
            .form-group label {
                margin-bottom: 5px;
            }
            
            button {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .header-title h1 {
                font-size: 1rem;
            }
            
            .user-avatar {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .media-gallery {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> SEMS</h2>
                <div class="user-info">
                    <p><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></p>
                    <p><i class="fas fa-user-tag"></i> <?php echo htmlspecialchars($user['role']); ?></p>
                </div>
            </div>
            
            <div class="sidebar-nav">
                <ul>
                    <!-- Dashboard Overview - Admin/Staff only -->
                    <?php if (hasRole(['Admin'])): ?>
                    <li><a href="#" onclick="showTab('overview')" class="active"><i class="fas fa-chart-line"></i> Dashboard Overview</a></li>
                    <?php endif; ?>
                    
                    <!-- Profile - All users -->
                    <li><a href="#" onclick="showTab('profile')" class="<?php echo !hasRole(['Admin', 'Staff']) ? 'active' : ''; ?>"><i class="fas fa-user-circle"></i> My Profile</a></li>
                    
                    <!-- Event Management - Admin/Teacher -->
                    <?php if (hasRole(['Admin', 'Teacher'])): ?>
                    <li><a href="#" onclick="showTab('events')"><i class="fas fa-calendar-alt"></i> Event Management</a></li>
                    <?php endif; ?>
                    
                    <!-- Browse Events & Registrations - Student/Guest -->
                    <?php if (hasRole(['Student'])): ?>
                    <li><a href="#" onclick="showTab('browse-events')"><i class="fas fa-search"></i> Browse Events</a></li>
                    <li><a href="#" onclick="showTab('my-registrations')"><i class="fas fa-ticket-alt"></i> My Registrations</a></li>
                    <?php endif; ?>
                    
                    <!-- Attendance Management - Admin/Staff/Teacher -->
                    <?php if (hasRole(['Admin', 'Teacher'])): ?>
                    <li><a href="#" onclick="showTab('attendance')"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
                    <?php endif; ?>
                    
                    <!-- Budget Management - Admin/Teacher only -->
                    <?php if (hasRole(['Admin', 'Teacher'])): ?>
                    <li><a href="#" onclick="showTab('budget')"><i class="fas fa-dollar-sign"></i> Budget Management</a></li>
                    <?php endif; ?>
                    
                    <!-- Feedback & Reports -->
                    <?php if (hasRole(['Student', 'Admin', 'Teacher'])): ?>
                    <li><a href="#" onclick="showTab('feedback')"><i class="fas fa-comments"></i> Feedback & Reports</a></li>
                    <?php endif; ?>
                    
                    <!-- User Management - Admin only -->
                    <?php if (hasRole(['Admin'])): ?>
                    <li><a href="#" onclick="showTab('users')"><i class="fas fa-users-cog"></i> User Management</a></li>
                    <?php endif; ?>
                    
                    <li><hr></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Header -->
            <div class="top-header">
                <div class="header-left">
                    <button class="burger-menu" id="burgerMenu" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="header-title">
                        <h1>School Event Management System</h1>
                        <p>Dashboard - <?php echo htmlspecialchars($user['role']); ?> Panel</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="user-avatar" title="<?php echo htmlspecialchars($user['full_name']); ?>">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="content-area">
                <div id="message-container"></div>
                
                <!-- Dashboard Overview Tab -->
                <?php if (hasRole(['Admin'])): ?>
                <div id="overview" class="tab-content active">
                    <div class="content-card">
                        <h2><i class="fas fa-chart-line"></i> Dashboard Overview</h2>
                        <div id="overview-stats" class="stats-grid">
                            <!-- Stats will be loaded dynamically -->
                        </div>
                    </div>

                    <!-- Add this in the overview tab after the stats grid -->
                    <div class="content-card">
                        <h2><i class="fas fa-chart-bar"></i> Event Analytics</h2>
                        <div class="form-group">
                            <label>Select Time Period:</label>
                            <select id="analytics-period" onchange="loadAnalytics()">
                                <option value="7">Last 7 Days</option>
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 90 Days</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="icon"><i class="fas fa-users"></i></div>
                                <h3>Total Participants</h3>
                                <div class="number" id="total-participants">0</div>
                            </div>
                            <div class="stat-card">
                                <div class="icon"><i class="fas fa-check-circle"></i></div>
                                <h3>Attendance Rate</h3>
                                <div class="number" id="attendance-rate">0%</div>
                            </div>
                            <div class="stat-card">
                                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                                <h3>Total Budget</h3>
                                <div class="number" id="total-budget">₱0</div>
                            </div>
                            <div class="stat-card">
                                <div class="icon"><i class="fas fa-star"></i></div>
                                <h3>Avg Rating</h3>
                                <div class="number" id="avg-rating">0.0</div>
                            </div>
                        </div>
                        <div class="charts-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                            <div>
                                <canvas id="eventsChart" width="400" height="200"></canvas>
                            </div>
                            <div>
                                <canvas id="attendanceChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Profile Tab -->
                <div id="profile" class="tab-content <?php echo !hasRole(['Admin', 'Staff']) ? 'active' : ''; ?>">
                    <div class="content-card">
                        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
                        <div class="profile-info">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Username:</label>
                                <span id="profile_username"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-id-card"></i> Full Name:</label>
                                <input type="text" id="profile_full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-envelope"></i> Email:</label>
                                <input type="email" id="profile_email" value="<?php echo htmlspecialchars($user['email']); ?>">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-user-tag"></i> Role:</label>
                                <span><?php echo htmlspecialchars($user['role']); ?></span>
                            </div>
                            <button onclick="updateProfile()" class="btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                        </div>
                    </div>
                </div>
                
                <!-- Event Management Tab -->
                <?php if (hasRole(['Admin', 'Teacher'])): ?>
                <div id="events" class="tab-content">
                    <div class="content-card">
                        <h2><i class="fas fa-calendar-alt"></i> Event Management</h2>
                        <button onclick="showEventForm()" class="btn-primary"><i class="fas fa-plus"></i> Create New Event</button>
                        <button onclick="showActivityFlow()" class="btn-warning" id="activityFlowBtn" style="display:none;"><i class="fas fa-tasks"></i> Activity Flow</button>
                        <button onclick="showBudgetSection()" class="btn-success" id="budgetBtn" style="display:none;"><i class="fas fa-dollar-sign"></i> Budget Management</button>
                        
                        <div id="event-form" style="display: none;" class="form-section">
                            <h3><i class="fas fa-edit"></i> Event Form</h3>
                            <form id="eventForm">
                                <input type="hidden" id="event_id" name="event_id">
                                <div class="form-group">
                                    <label>Title:</label>
                                    <input type="text" id="title" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label>Description:</label>
                                    <textarea id="description" name="description" rows="4"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Venue:</label>
                                    <input type="text" id="venue" name="venue">
                                </div>
                                <div class="form-group">
                                    <label>Start Date & Time:</label>
                                    <input type="datetime-local" id="start_datetime" name="start_datetime" required>
                                </div>
                                <div class="form-group">
                                    <label>End Date & Time:</label>
                                    <input type="datetime-local" id="end_datetime" name="end_datetime" required>
                                </div>
                                <div class="form-group">
                                    <label>Capacity:</label>
                                    <input type="number" id="capacity" name="capacity" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Status:</label>
                                    <select id="status" name="status">
                                        <option value="upcoming">Upcoming</option>
                                        <option value="ongoing">Ongoing</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" id="is_public" name="is_public" checked>
                                        Public Event
                                    </label>
                                </div>
                                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Event</button>
                                <button type="button" onclick="hideEventForm()" class="btn-secondary"><i class="fas fa-times"></i> Cancel</button>
                            </form>
                        </div>
                        
                        <div id="events-list"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Browse Events Tab -->
                <?php if (hasRole(['Student'])): ?>
                <div id="browse-events" class="tab-content">
                    <div class="content-card">
                        <h2><i class="fas fa-search"></i> Browse Events</h2>
                        <div id="browse-events-list"></div>
                    </div>
                </div>
                
                <div id="my-registrations" class="tab-content">
                    <div class="content-card">
                        <h2><i class="fas fa-ticket-alt"></i> My Registrations</h2>
                        <div id="my-registrations-list"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Attendance Management Tab -->
                <?php if (hasRole(['Admin', 'Teacher', 'Staff'])): ?>
                <div id="attendance" class="tab-content">
                    <div class="content-card">
                        <h2><i class="fas fa-clipboard-check"></i> Attendance Management</h2>
                        <div class="form-group">
                            <label>Select Event:</label>
                            <select id="attendance-event-select" onchange="loadAttendanceList()">
                                <option value="">-- Select Event --</option>
                            </select>
                        </div>
                        <div id="attendance-list"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Budget Management Tab -->
                <?php if (hasRole(['Admin', 'Teacher'])): ?>
                <div id="budget" class="tab-content">
                    <div class="content-card">
                        <h2><i class="fas fa-dollar-sign"></i> Budget Management</h2>
                        <div class="form-group">
                            <label>Select Event:</label>
                            <select id="budget-event-select" onchange="loadBudgetList()">
                                <option value="">-- Select Event --</option>
                            </select>
                        </div>
                        
                        <button onclick="showBudgetForm()" class="btn-primary"><i class="fas fa-plus"></i> Add Budget Item</button>
                        
                        <div id="budget-form" style="display: none;" class="form-section">
                            <h3><i class="fas fa-edit"></i> Budget Item Form</h3>
                            <form id="budgetForm">
                                <input type="hidden" id="budget_id" name="budget_id">
                                <input type="hidden" id="budget_event_id" name="event_id">
                                <div class="form-group">
                                    <label>Item Name:</label>
                                    <input type="text" id="item_name" name="item_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Type:</label>
                                    <select id="budget_type" name="type" required>
                                        <option value="Expense">Expense</option>
                                        <option value="Income">Income</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Description:</label>
                                    <textarea id="budget_description" name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Allocated Amount:</label>
                                    <input type="number" step="0.01" id="allocated_amount" name="allocated_amount" required>
                                </div>
                                <div class="form-group">
                                    <label>Spent Amount:</label>
                                    <input type="number" step="0.01" id="spent_amount" name="spent_amount" value="0">
                                </div>
                                <div class="form-group">
                                    <label>Notes:</label>
                                    <textarea id="budget_notes" name="notes" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Budget Item</button>
                                <button type="button" onclick="hideBudgetForm()" class="btn-secondary"><i class="fas fa-times"></i> Cancel</button>
                            </form>
                        </div>
                        
                        <div id="budget-list"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Feedback & Reports Tab -->
                <?php if (hasRole(['Student', 'Admin', 'Teacher'])): ?>
                <div id="feedback" class="tab-content">
                    <div class="content-card">
                        <h2><i class="fas fa-comments"></i> Feedback & Reports</h2>
                        
                        <?php if (hasRole(['Student'])): ?>
                        <div class="form-group">
                            <label>Select Event to Rate:</label>
                            <select id="feedback-event-select">
                                <option value="">-- Select Event --</option>
                            </select>
                        </div>
                        
                        <div id="feedback-form-container" style="display: none;" class="form-section">
                            <h3><i class="fas fa-star"></i> Submit Feedback</h3>
                            <form id="feedbackForm">
                                <input type="hidden" id="feedback_event_id" name="event_id">
                                <div class="form-group">
                                    <label>Rating (1-5):</label>
                                    <select id="rating" name="rating" required>
                                        <option value="">-- Select --</option>
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Good</option>
                                        <option value="3">3 - Average</option>
                                        <option value="2">2 - Poor</option>
                                        <option value="1">1 - Very Poor</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Comment:</label>
                                    <textarea id="feedback_comment" name="comment" rows="4"></textarea>
                                </div>
                                <button type="submit" class="btn-primary"><i class="fas fa-paper-plane"></i> Submit Feedback</button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (hasRole(['Admin', 'Teacher'])): ?>
                        <div class="form-group">
                            <label>View Feedback for Event:</label>
                            <select id="view-feedback-event-select" onchange="loadFeedbackList()">
                                <option value="">-- Select Event --</option>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div id="feedback-list"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- User Management Tab -->
                <?php if (hasRole(['Admin'])): ?>
                <div id="users" class="tab-content">
                    <div class="content-card">
                        <h2><i class="fas fa-users-cog"></i> User Management</h2>
                        <button onclick="showUserForm()" class="btn-primary"><i class="fas fa-user-plus"></i> Create New User</button>
                        
                        <div id="user-form" style="display: none;" class="form-section">
                            <h3><i class="fas fa-edit"></i> User Form</h3>
                            <form id="userForm">
                                <input type="hidden" id="user_id" name="user_id">
                                <div class="form-group">
                                    <label>Username:</label>
                                    <input type="text" id="user_username" name="username" required>
                                </div>
                                <div class="form-group">
                                    <label>Email:</label>
                                    <input type="email" id="user_email" name="email" required>
                                </div>
                                <div class="form-group">
                                    <label>Full Name:</label>
                                    <input type="text" id="user_full_name" name="full_name" required>
                                </div>
                                <div class="form-group">
                                    <label>Role:</label>
                                    <select id="user_role" name="role" required>
                                        <option value="Student">Student</option>
                                        <option value="Teacher">Teacher</option>
                                        <option value="Staff">Staff</option>
                                        <option value="Guest">Guest</option>
                                        <option value="Admin">Admin</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Password:</label>
                                    <input type="password" id="user_password" name="password">
                                    <small style="color: var(--text-light); display: block; margin-top: 5px;">(Leave blank to keep current password)</small>
                                </div>
                                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save User</button>
                                <button type="button" onclick="hideUserForm()" class="btn-secondary"><i class="fas fa-times"></i> Cancel</button>
                            </form>
                        </div>
                        
                        <div id="users-list"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="dashboard.js"></script>
    <script>
        // Toggle Sidebar Function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            
            if (window.innerWidth > 1024) {
                sidebar.classList.toggle('hidden');
                mainContent.classList.toggle('expanded');
            }
        }   
        // Close sidebar when clicking on a link on mobile
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 1024) {
                    const sidebar = document.getElementById('sidebar');
                    const overlay = document.getElementById('overlay');
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            
            if (window.innerWidth > 1024) {
                overlay.classList.remove('active');
                if (!sidebar.classList.contains('hidden')) {
                    sidebar.classList.remove('active');
                }
            } else {
                sidebar.classList.remove('hidden');
                mainContent.classList.remove('expanded');
            }
        });

        // Analytics Functions
        function loadAnalytics() {
            const period = document.getElementById('analytics-period').value;
            
            fetch(`api.php?action=get_analytics&period=${period}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateAnalyticsDisplay(data.data);
                        createCharts(data.data);
                    }
                })
                .catch(error => {
                    console.error('Error loading analytics:', error);
                });
        }
        function updateAnalyticsDisplay(analytics) {
            document.getElementById('total-participants').textContent = analytics.total_participants;
            document.getElementById('attendance-rate').textContent = analytics.attendance_rate + '%';
            document.getElementById('total-budget').textContent = '₱' + analytics.total_budget;
            document.getElementById('avg-rating').textContent = analytics.avg_rating;
        }
        function createCharts(analytics) {
            // Events by Status Chart
            const eventsCtx = document.getElementById('eventsChart').getContext('2d');
            new Chart(eventsCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(analytics.events_by_status),
                    datasets: [{
                        data: Object.values(analytics.events_by_status),
                        backgroundColor: ['#28a745', '#ffc107', '#17a2b8', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Events by Status'
                        }
                    }
                }
            });
            
            // Attendance Trend Chart
            const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(attendanceCtx, {
                type: 'line',
                data: {
                    labels: analytics.attendance_trend.map(item => item.date),
                    datasets: [
                        {
                            label: 'Registered',
                            data: analytics.attendance_trend.map(item => item.registered),
                            borderColor: '#007bff',
                            backgroundColor: 'rgba(0, 123, 255, 0.1)'
                        },
                        {
                            label: 'Attended',
                            data: analytics.attendance_trend.map(item => item.attended),
                            borderColor: '#28a745',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Attendance Trend'
                        }
                    }
                }
            });
        }

        // Attendance Management
function loadAttendanceSection() {
  loadEventsForDropdown('attendance-event-select');
}

function loadAttendanceList() {
  const eventId = document.getElementById("attendance-event-select").value;
  if (!eventId) {
    document.getElementById("attendance-list").innerHTML = "";
    return;
  }

  fetch(`api.php?action=get_attendance&event_id=${eventId}`)
    .then((res) => res.json())
    .then((data) => {
      let html = `
        <table>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Time</th>
            <th>Action</th>
          </tr>
      `;
      data.forEach((att) => {
        const isPresent = att.attendance_status === "present";
        html += `<tr>
          <td>${att.full_name}</td>
          <td>${att.email}</td>
          <td>${att.attendance_status || "Not Marked"}</td>
          <td>${att.attendance_time || "N/A"}</td>
          <td>
            ${isPresent
              ? `<button onclick="unmarkAttendance(${eventId}, ${att.user_id})">Unmark</button>`
              : `<button onclick="markAttendance(${eventId}, ${att.user_id})">Mark Present</button>`
            }
          </td>
        </tr>`;
      });
      html += "</table>";
      document.getElementById("attendance-list").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading attendance:', error);
      showMessage('Error loading attendance list', 'error');
    });
}

function markAttendance(eventId, userId) {
  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=mark_attendance&event_id=${eventId}&user_id=${userId}`,
  })
    .then((res) => res.json())
    .then((data) => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) loadAttendanceList();
    })
    .catch(error => {
      console.error('Error marking attendance:', error);
      showMessage('Error marking attendance', 'error');
    });
}

function unmarkAttendance(eventId, userId) {
  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=unmark_attendance&event_id=${eventId}&user_id=${userId}`,
  })
    .then((res) => res.json())
    .then((data) => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) loadAttendanceList();
    })
    .catch(error => {
      console.error('Error unmarking attendance:', error);
      showMessage('Error unmarking attendance', 'error');
    });
}

// Budget Management
function loadBudgetSection() {
  loadEventsForDropdown('budget-event-select');
}

function loadBudgetList() {
  const eventId = document.getElementById("budget-event-select").value;
  if (!eventId) {
    document.getElementById("budget-list").innerHTML = "";
    return;
  }

  fetch(`api.php?action=get_budget&event_id=${eventId}`)
    .then((res) => res.json())
    .then((data) => {
      let html = `
        <table>
          <tr>
            <th>Item</th>
            <th>Type</th>
            <th>Description</th>
            <th>Allocated</th>
            <th>Spent</th>
            <th>Remaining</th>
            <th>Notes</th>
            <th>Actions</th>
          </tr>
      `;
      let totalIncome = 0, totalExpense = 0, totalSpent = 0;
      data.forEach((item) => {
        const remaining = item.allocated_amount - item.spent_amount;
        const typeClass = item.type === 'Income' ? 'budget-type-income' : 'budget-type-expense';
        
        if (item.type === 'Income') {
          totalIncome += parseFloat(item.allocated_amount);
        } else {
          totalExpense += parseFloat(item.allocated_amount);
        }
        totalSpent += parseFloat(item.spent_amount);
        
        html += `<tr>
          <td>${item.item_name}</td>
          <td><span class="${typeClass}">${item.type}</span></td>
          <td>${item.description || "N/A"}</td>
          <td>₱${parseFloat(item.allocated_amount).toFixed(2)}</td>
          <td>₱${parseFloat(item.spent_amount).toFixed(2)}</td>
          <td>₱${remaining.toFixed(2)}</td>
          <td>${item.notes || "N/A"}</td>
          <td>
            <button onclick="editBudget(${item.id})">Edit</button>
            <button onclick="deleteBudget(${item.id})">Delete</button>
          </td>
        </tr>`;
      });
      
      const netBalance = totalIncome - totalExpense;
      const totalRemaining = netBalance - totalSpent;
      
      html += `<tr style="font-weight: bold; background: #f8f9fa;">
        <td colspan="3">TOTALS</td>
        <td>Income: ₱${totalIncome.toFixed(2)}<br>Expense: ₱${totalExpense.toFixed(2)}</td>
        <td>₱${totalSpent.toFixed(2)}</td>
        <td>₱${totalRemaining.toFixed(2)}</td>
        <td>Net Balance: ₱${netBalance.toFixed(2)}</td>
        <td></td>
      </tr>`;
      html += "</table>";
      document.getElementById("budget-list").innerHTML = html;
    })
    .catch(error => {
      console.error('Error loading budget:', error);
      showMessage('Error loading budget items', 'error');
    });
}

document.getElementById("budgetForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  formData.append(
    "action",
    document.getElementById("budget_id").value
      ? "update_budget"
      : "create_budget"
  );

  fetch("api.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        showMessage(data.message);
        hideBudgetForm();
        loadBudgetList();
      } else {
        showMessage(data.message, "error");
      }
    })
    .catch(error => {
      console.error('Error saving budget item:', error);
      showMessage('Error saving budget item', 'error');
    });
});

function editBudget(id) {
  fetch(`api.php?action=get_budget_item&id=${id}`)
    .then((res) => res.json())
    .then((item) => {
      document.getElementById("budget_id").value = item.id;
      document.getElementById("budget_event_id").value = item.event_id;
      document.getElementById("item_name").value = item.item_name;
      document.getElementById("budget_type").value = item.type || 'Expense';
      document.getElementById("budget_description").value = item.description || '';
      document.getElementById("allocated_amount").value = item.allocated_amount;
      document.getElementById("spent_amount").value = item.spent_amount;
      document.getElementById("budget_notes").value = item.notes || '';
      showBudgetForm();
    })
    .catch(error => {
      console.error('Error loading budget item:', error);
      showMessage('Error loading budget item', 'error');
    });
}

function deleteBudget(id) {
  if (!confirm("Delete this budget item?")) return;

  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=delete_budget&id=${id}`,
  })
    .then((res) => res.json())
    .then((data) => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) loadBudgetList();
    })
    .catch(error => {
      console.error('Error deleting budget item:', error);
      showMessage('Error deleting budget item', 'error');
    });
}

// Enhanced Attendance Functions
function loadAttendanceSection() {
    loadEventsForDropdown('attendance-event-select');
    
    // Add search functionality
    const searchHTML = `
        <div class="form-group">
            <label>Search Participants:</label>
            <input type="text" id="attendance-search" onkeyup="filterAttendanceList()" placeholder="Search by name or email...">
        </div>
        <div class="form-group">
            <button onclick="markAllAttendance()" class="btn-success"><i class="fas fa-check-double"></i> Mark All Present</button>
            <button onclick="unmarkAllAttendance()" class="btn-danger"><i class="fas fa-times-circle"></i> Unmark All</button>
        </div>
    `;
    document.getElementById("attendance-list").innerHTML = searchHTML;
}

function filterAttendanceList() {
    const searchTerm = document.getElementById('attendance-search').value.toLowerCase();
    const rows = document.querySelectorAll('#attendance-table tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

function markAllAttendance() {
    const eventId = document.getElementById("attendance-event-select").value;
    if (!eventId) {
        showMessage('Please select an event first', 'error');
        return;
    }
    
    if (!confirm('Mark all participants as present?')) return;
    
    fetch(`api.php?action=get_attendance&event_id=${eventId}`)
        .then(res => res.json())
        .then(data => {
            const promises = data.map(participant => {
                if (participant.attendance_status !== 'present') {
                    return fetch("api.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `action=mark_attendance&event_id=${eventId}&user_id=${participant.user_id}`
                    });
                }
                return Promise.resolve();
            });
            
            Promise.all(promises).then(() => {
                showMessage('All participants marked as present!', 'success');
                loadAttendanceList();
            });
        })
        .catch(error => {
            console.error('Error marking all attendance:', error);
            showMessage('Error marking attendance', 'error');
        });
}

function unmarkAllAttendance() {
    const eventId = document.getElementById("attendance-event-select").value;
    if (!eventId) {
        showMessage('Please select an event first', 'error');
        return;
    }
    
    if (!confirm('Unmark all attendance?')) return;
    
    fetch(`api.php?action=get_attendance&event_id=${eventId}`)
        .then(res => res.json())
        .then(data => {
            const promises = data.map(participant => {
                if (participant.attendance_status === 'present') {
                    return fetch("api.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `action=unmark_attendance&event_id=${eventId}&user_id=${participant.user_id}`
                    });
                }
                return Promise.resolve();
            });
            
            Promise.all(promises).then(() => {
                showMessage('All attendance unmarked!', 'success');
                loadAttendanceList();
            });
        })
        .catch(error => {
            console.error('Error unmarking all attendance:', error);
            showMessage('Error unmarking attendance', 'error');
        });
}

// Update the loadAttendanceList function
function loadAttendanceList() {
    const eventId = document.getElementById("attendance-event-select").value;
    if (!eventId) {
        document.getElementById("attendance-list").innerHTML = "";
        return;
    }

    fetch(`api.php?action=get_attendance&event_id=${eventId}`)
        .then((res) => res.json())
        .then((data) => {
            let html = `
                <div class="form-group">
                    <label>Search Participants:</label>
                    <input type="text" id="attendance-search" onkeyup="filterAttendanceList()" placeholder="Search by name or email...">
                </div>
                <div class="form-group">
                    <button onclick="markAllAttendance()" class="btn-success"><i class="fas fa-check-double"></i> Mark All Present</button>
                    <button onclick="unmarkAllAttendance()" class="btn-danger"><i class="fas fa-times-circle"></i> Unmark All</button>
                </div>
                <table id="attendance-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach((att, index) => {
                const isPresent = att.attendance_status === "present";
                html += `<tr>
                    <td>${index + 1}</td>
                    <td>${att.full_name}</td>
                    <td>${att.email}</td>
                    <td>${att.attendance_status || "Not Marked"}</td>
                    <td>${att.attendance_time || "N/A"}</td>
                    <td>
                        ${isPresent
                            ? `<button onclick="unmarkAttendance(${eventId}, ${att.user_id})">Unmark</button>`
                            : `<button onclick="markAttendance(${eventId}, ${att.user_id})">Mark Present</button>`
                        }
                    </td>
                </tr>`;
            });
            html += `</tbody></table>`;
            document.getElementById("attendance-list").innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading attendance:', error);
            showMessage('Error loading attendance list', 'error');
        });
}

function loadBrowseEvents() {
    fetch("api.php?action=browse_events")
        .then((res) => res.json())
        .then((data) => {
            let html = `
                <table id="browse-events-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Venue</th>
                            <th>Capacity</th>
                            <th>Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.forEach((event) => {
                const isRegistered = event.user_registered == 1;
                html += `<tr>
                    <td>${event.title}</td>
                    <td>${event.description || "N/A"}</td>
                    <td>${event.start_datetime ? new Date(event.start_datetime).toLocaleString() : event.event_date + ' ' + event.event_time}</td>
                    <td>${event.end_datetime ? new Date(event.end_datetime).toLocaleString() : 'N/A'}</td>
                    <td>${event.venue || "N/A"}</td>
                    <td>${event.capacity}</td>
                    <td>${event.registration_count || 0}</td>
                    <td>
                        ${isRegistered
                            ? `<span class="status-badge status-completed">Registered</span>`
                            : `<button onclick="registerEvent(${event.id})">Register</button>`
                        }
                    </td>
                </tr>`;
            });
            html += `</tbody></table>`;
            document.getElementById("browse-events-list").innerHTML = html;
            
            // Initialize DataTable
            if ($.fn.DataTable) {
                $('#browse-events-table').DataTable({
                    "pageLength": 10,
                    "ordering": true,
                    "searching": true
                });
            }
        })
        .catch(error => {
            console.error('Error loading browse events:', error);
            showMessage('Error loading events', 'error');
        });
}


// Event Management - FIXED VERSION
function loadEvents() {
  fetch("api.php?action=get_events")
    .then((res) => res.json())
    .then((data) => {
      // Clear the select dropdown first
      const select = document.getElementById("events-event-select");
      if (select) {
        select.innerHTML = '<option value="">-- Select Event --</option>';
      }
      
      let html = `
        <div class="form-group">
          <label>Select Event for Management:</label>
          <select id="events-event-select" onchange="toggleEventButtons()">
            <option value="">-- Select Event --</option>
          </select>
        </div>
        <table>
          <tr>
            <th>Title</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Venue</th>
            <th>Status</th>
            <th>Capacity</th>
            <th>Registrations</th>
            <th>Actions</th>
          </tr>
      `;
      
      // Build table rows and collect options
      let optionsHTML = '<option value="">-- Select Event --</option>';
      
      data.forEach((event) => {
        // Add to dropdown options
        optionsHTML += `<option value="${event.id}">${event.title}</option>`;
        
        // Format dates safely
        let startDate = 'N/A';
        let endDate = 'N/A';
        
        if (event.start_datetime) {
          try {
            startDate = new Date(event.start_datetime).toLocaleString();
          } catch (e) {
            startDate = event.event_date + ' ' + event.event_time;
          }
        } else if (event.event_date && event.event_time) {
          startDate = event.event_date + ' ' + event.event_time;
        }
        
        if (event.end_datetime) {
          try {
            endDate = new Date(event.end_datetime).toLocaleString();
          } catch (e) {
            endDate = 'N/A';
          }
        }
        
        // Add to table
        html += `<tr>
          <td>${event.title}</td>
          <td>${startDate}</td>
          <td>${endDate}</td>
          <td>${event.venue || 'N/A'}</td>
          <td><span class="status-badge status-${event.status}">${event.status}</span></td>
          <td>${event.capacity}</td>
          <td>${event.registration_count || 0}</td>
          <td>
            <button onclick="editEvent(${event.id})" class="btn-primary"><i class="fas fa-edit"></i> Edit</button>
            <button onclick="deleteEvent(${event.id})" class="btn-danger"><i class="fas fa-trash"></i> Delete</button>
          </td>
        </tr>`;
      });
      
      html += "</table>";
      
      // Update the events list
      document.getElementById("events-list").innerHTML = html;
      
      // Now update the select dropdown
      const selectElement = document.getElementById("events-event-select");
      if (selectElement) {
        selectElement.innerHTML = optionsHTML;
      }
    })
    .catch(error => {
      console.error('Error loading events:', error);
      showMessage('Error loading events', 'error');
    });
}

function toggleEventButtons() {
  const eventId = document.getElementById("events-event-select")?.value;
  const activityBtn = document.getElementById("activityFlowBtn");
  const budgetBtn = document.getElementById("budgetBtn");
  
  if (activityBtn && budgetBtn) {
    if (eventId) {
      activityBtn.style.display = "inline-block";
      budgetBtn.style.display = "inline-block";
    } else {
      activityBtn.style.display = "none";
      budgetBtn.style.display = "none";
    }
  }
}

// Event Form Submission - FIXED
document.getElementById("eventForm")?.addEventListener("submit", function (e) {
  e.preventDefault();
  
  // Validate datetime fields
  const startDatetime = document.getElementById("start_datetime").value;
  const endDatetime = document.getElementById("end_datetime").value;
  
  if (!startDatetime || !endDatetime) {
    showMessage("Please fill in both start and end date/time", "error");
    return;
  }
  
  if (new Date(startDatetime) >= new Date(endDatetime)) {
    showMessage("End date/time must be after start date/time", "error");
    return;
  }
  
  const formData = new FormData(this);
  const eventId = document.getElementById("event_id").value;
  
  formData.append("action", eventId ? "update_event" : "create_event");
  formData.append("is_public", document.getElementById("is_public").checked ? 1 : 0);
  
  if (eventId) {
    formData.append("id", eventId);
  }

  fetch("api.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        showMessage(data.message);
        hideEventForm();
        loadEvents();
      } else {
        showMessage(data.message, "error");
      }
    })
    .catch(error => {
      console.error('Error saving event:', error);
      showMessage('Error saving event', 'error');
    });
});

// Edit Event - FIXED
function editEvent(id) {
  fetch(`api.php?action=get_event&id=${id}`)
    .then((res) => res.json())
    .then((event) => {
      if (!event) {
        showMessage("Event not found", "error");
        return;
      }
      
      document.getElementById("event_id").value = event.id;
      document.getElementById("title").value = event.title || '';
      document.getElementById("description").value = event.description || '';
      document.getElementById("venue").value = event.venue || '';
      
      // Handle datetime fields with proper formatting
      if (event.start_datetime) {
        // Convert to local datetime format (YYYY-MM-DDTHH:MM)
        const startDate = new Date(event.start_datetime);
        const startFormatted = formatDateTimeLocal(startDate);
        document.getElementById("start_datetime").value = startFormatted;
      } else if (event.event_date && event.event_time) {
        document.getElementById("start_datetime").value = event.event_date + 'T' + event.event_time.substring(0, 5);
      }
      
      if (event.end_datetime) {
        const endDate = new Date(event.end_datetime);
        const endFormatted = formatDateTimeLocal(endDate);
        document.getElementById("end_datetime").value = endFormatted;
      } else if (event.event_date && event.event_time) {
        // Default to 2 hours after start time
        const startDateTime = new Date(event.event_date + 'T' + event.event_time);
        const endDateTime = new Date(startDateTime.getTime() + 2 * 60 * 60 * 1000);
        document.getElementById("end_datetime").value = formatDateTimeLocal(endDateTime);
      }
      
      document.getElementById("capacity").value = event.capacity || 0;
      document.getElementById("status").value = event.status || 'upcoming';
      document.getElementById("is_public").checked = event.is_public == 1;
      
      showEventForm();
    })
    .catch(error => {
      console.error('Error loading event:', error);
      showMessage('Error loading event details', 'error');
    });
}

// Helper function to format date for datetime-local input
function formatDateTimeLocal(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  const hours = String(date.getHours()).padStart(2, '0');
  const minutes = String(date.getMinutes()).padStart(2, '0');
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function deleteEvent(id) {
  if (!confirm("Are you sure you want to delete this event?")) return;

  fetch("api.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `action=delete_event&id=${id}`,
  })
    .then((res) => res.json())
    .then((data) => {
      showMessage(data.message, data.success ? "success" : "error");
      if (data.success) loadEvents();
    })
    .catch(error => {
      console.error('Error deleting event:', error);
      showMessage('Error deleting event', 'error');
    });
}
    </script>
</body>
</html>