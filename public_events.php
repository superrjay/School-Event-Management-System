<?php
require_once 'config.php';

$conn = getDBConnection();

// Get all public events
$query = "SELECT e.*, 
          u.full_name as organizer_name,
          (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'registered') as registration_count
          FROM events e
          LEFT JOIN users u ON e.organizer_id = u.id
          WHERE e.is_public = 1 AND e.status != 'cancelled'
          ORDER BY e.event_date ASC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Public Events - School Event Management System</title>
    <meta http-equiv="refresh" content="30">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .header-actions {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-actions a {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 10px;
        }
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .event-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #f9f9f9;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .event-title {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .event-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status-upcoming { background: #d4edda; color: #155724; }
        .status-ongoing { background: #fff3cd; color: #856404; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .event-info {
            margin: 10px 0;
            line-height: 1.8;
        }
        .event-info strong {
            color: #667eea;
            display: inline-block;
            width: 120px;
        }
        .event-description {
            margin: 15px 0;
            color: #555;
            font-style: italic;
        }
        .no-events {
            text-align: center;
            padding: 50px;
            color: #999;
            font-size: 18px;
        }
        .last-updated {
            text-align: center;
            color: #999;
            margin-top: 30px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 School Event Management System</h1>
        <p class="subtitle">Public Events Dashboard - Real-Time Updates</p>
        
        <div class="header-actions">
            <a href="index.php">Back to Home</a>
            <a href="login.php">Login to System</a>
            <a href="javascript:location.reload()">Refresh Events</a>
        </div>
        
        <hr>
        
        <div class="events-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($event = $result->fetch_assoc()): ?>
                    <div class="event-card">
                        <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                        <span class="event-status status-<?php echo $event['status']; ?>">
                            <?php echo strtoupper($event['status']); ?>
                        </span>
                        
                        <?php if ($event['description']): ?>
                            <div class="event-description">
                                <?php echo htmlspecialchars($event['description']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="event-info">
                            <strong>📅 Start:</strong> <?php echo date('F d, Y g:i A', strtotime($event['start_datetime'])); ?><br>
                            <strong>🕐 End:</strong> <?php echo date('F d, Y g:i A', strtotime($event['end_datetime'])); ?><br>
                            <strong>📍 Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?><br>
                            <strong>👥 Capacity:</strong> <?php echo $event['capacity']; ?> people<br>
                            <strong>✅ Registered:</strong> <?php echo $event['registration_count']; ?> people<br>
                            <strong>👤 Organizer:</strong> <?php echo htmlspecialchars($event['organizer_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-events">
                    <h2>No public events available at the moment</h2>
                    <p>Check back later for upcoming events!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="last-updated">
            Last updated: <?php echo date('F d, Y g:i:s A'); ?> | Auto-refresh every 30 seconds
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>