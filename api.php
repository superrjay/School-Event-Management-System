<?php
require_once 'config.php';
requireLogin();

header('Content-Type: application/json');

$conn = getDBConnection();
$user = getCurrentUser();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Overview Statistics
if ($action === 'overview') {
    $stats = [];
    
    // Total events
    $result = $conn->query("SELECT COUNT(*) as count FROM events");
    $stats['total_events'] = $result->fetch_assoc()['count'];
    
    // Upcoming events
    $result = $conn->query("SELECT COUNT(*) as count FROM events WHERE status = 'upcoming'");
    $stats['upcoming_events'] = $result->fetch_assoc()['count'];
    
    // My registrations
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE user_id = ? AND status = 'registered'");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $stats['my_registrations'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Total users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    echo json_encode($stats);
}

// Get Analytics Data - FIXED SQL INJECTION
elseif ($action === 'get_analytics') {
    if (!hasRole(['Admin', 'Teacher'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Sanitize period input
    $period = intval($_GET['period'] ?? 30);
    if ($period < 1 || $period > 3650) {
        $period = 30;
    }
    
    $analytics = [];
    
    // Total participants
    $result = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM registrations WHERE status = 'registered'");
    $analytics['total_participants'] = $result->fetch_assoc()['count'];
    
    // Attendance rate
    $result = $conn->query("
        SELECT 
            COUNT(DISTINCT r.user_id) as total_registered,
            COUNT(DISTINCT a.user_id) as attended
        FROM registrations r
        LEFT JOIN attendance a ON r.event_id = a.event_id AND r.user_id = a.user_id
        WHERE r.status = 'registered'
    ");
    $attendance_data = $result->fetch_assoc();
    $analytics['attendance_rate'] = $attendance_data['total_registered'] > 0 ? 
        round(($attendance_data['attended'] / $attendance_data['total_registered']) * 100, 2) : 0;
    
    // Total budget
    $result = $conn->query("
        SELECT 
            SUM(CASE WHEN type = 'Income' THEN allocated_amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'Expense' THEN allocated_amount ELSE 0 END) as total_expense
        FROM budget
    ");
    $budget_data = $result->fetch_assoc();
    $analytics['total_budget'] = $budget_data['total_income'] - $budget_data['total_expense'];
    
    // Average rating
    $result = $conn->query("SELECT AVG(rating) as avg_rating FROM feedback");
    $analytics['avg_rating'] = round($result->fetch_assoc()['avg_rating'] ?? 0, 1);
    
    // Events by status (for chart) - FIXED WITH PREPARED STATEMENT
    $stmt = $conn->prepare("
        SELECT status, COUNT(*) as count 
        FROM events 
        WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY status
    ");
    $stmt->bind_param("i", $period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events_by_status = [];
    while ($row = $result->fetch_assoc()) {
        $events_by_status[$row['status']] = $row['count'];
    }
    $analytics['events_by_status'] = $events_by_status;
    
    // Attendance trend (for chart) - FIXED WITH PREPARED STATEMENT
    $stmt = $conn->prepare("
        SELECT 
            DATE(e.event_date) as date,
            COUNT(DISTINCT r.user_id) as registered,
            COUNT(DISTINCT a.user_id) as attended
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id AND r.status = 'registered'
        LEFT JOIN attendance a ON e.id = a.event_id
        WHERE e.event_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(e.event_date)
        ORDER BY e.event_date
    ");
    $stmt->bind_param("i", $period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance_trend = [];
    while ($row = $result->fetch_assoc()) {
        $attendance_trend[] = $row;
    }
    $analytics['attendance_trend'] = $attendance_trend;
    
    echo json_encode(['success' => true, 'data' => $analytics]);
}

// Get All Events (for dropdowns)
elseif ($action === 'get_all_events') {
    $query = "SELECT id, title, event_date FROM events ORDER BY event_date DESC";
    $result = $conn->query($query);
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    echo json_encode($events);
}

// Event Management - Get Events
elseif ($action === 'get_events') {
    $query = "SELECT e.*, 
              (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'registered') as registration_count
              FROM events e ";
    
    if ($user['role'] === 'Teacher') {
        $query .= "WHERE e.organizer_id = " . intval($user['id']);
    }
    
    $query .= " ORDER BY e.event_date DESC";
    
    $result = $conn->query($query);
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    echo json_encode($events);
}

// Get Single Event - FIXED
elseif ($action === 'get_event') {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid event ID']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $event = $result->fetch_assoc();
    
    if (!$event) {
        echo json_encode(['error' => 'Event not found']);
        exit;
    }
    
    echo json_encode($event);
}

// Create Event
elseif ($action === 'create_event') {
    if (!hasRole(['Admin', 'Teacher'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $start_datetime = $_POST['start_datetime'] ?? '';
    $end_datetime = $_POST['end_datetime'] ?? '';
    $capacity = intval($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'upcoming';
    $is_public = intval($_POST['is_public'] ?? 0);
    
    // Validation
    if (empty($title) || empty($start_datetime) || empty($end_datetime)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    // Validate status
    $valid_statuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'upcoming';
    }
    
    // Extract date and time from datetime for backward compatibility
    $event_date = date('Y-m-d', strtotime($start_datetime));
    $event_time = date('H:i:s', strtotime($start_datetime));
    
    $stmt = $conn->prepare("INSERT INTO events (title, description, venue, event_date, event_time, start_datetime, end_datetime, capacity, status, is_public, organizer_id) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssisii", $title, $description, $venue, $event_date, $event_time, $start_datetime, $end_datetime, $capacity, $status, $is_public, $user['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event created successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating event: ' . $stmt->error]);
    }
}

// Update Event - FIXED
elseif ($action === 'update_event') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check if user has permission to update this event
    if (!hasRole(['Admin'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event) {
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            exit;
        }
        
        // Allow Teachers to edit their own events
        if ($event['organizer_id'] != $user['id'] || !hasRole(['Teacher'])) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $start_datetime = $_POST['start_datetime'] ?? '';
    $end_datetime = $_POST['end_datetime'] ?? '';
    $capacity = intval($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'upcoming';
    $is_public = intval($_POST['is_public'] ?? 0);
    
    // Validation
    if (empty($title) || empty($start_datetime) || empty($end_datetime)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    // Validate status
    $valid_statuses = ['upcoming', 'ongoing', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'upcoming';
    }
    
    // Extract date and time from datetime for backward compatibility
    $event_date = date('Y-m-d', strtotime($start_datetime));
    $event_time = date('H:i:s', strtotime($start_datetime));
    
    $stmt = $conn->prepare("UPDATE events SET title=?, description=?, venue=?, event_date=?, event_time=?, start_datetime=?, end_datetime=?, capacity=?, status=?, is_public=? WHERE id=?");
    $stmt->bind_param("sssssssisii", $title, $description, $venue, $event_date, $event_time, $start_datetime, $end_datetime, $capacity, $status, $is_public, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating event: ' . $stmt->error]);
    }
}

// Delete Event
elseif ($action === 'delete_event') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check if user has permission to delete this event
    if (!hasRole(['Admin'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting event']);
    }
}

// Browse Events (Student/Guest)
elseif ($action === 'browse_events') {
    $query = "SELECT e.*, 
              (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND status = 'registered') as registration_count,
              (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND user_id = ? AND status = 'registered') as user_registered
              FROM events e 
              WHERE e.is_public = 1 AND e.status != 'cancelled'
              ORDER BY e.event_date ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    echo json_encode($events);
}

// Register for Event
elseif ($action === 'register_event') {
    $event_id = intval($_POST['event_id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check if event exists and is available for registration
    $stmt = $conn->prepare("SELECT capacity, status FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    
    if (!$event || $event['status'] !== 'upcoming') {
        echo json_encode(['success' => false, 'message' => 'Event not available for registration']);
        exit;
    }
    
    // Check capacity
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM registrations WHERE event_id = ? AND status = 'registered'");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $registration_count = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($registration_count >= $event['capacity']) {
        echo json_encode(['success' => false, 'message' => 'Event is at full capacity']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'registered')");
    $stmt->bind_param("ii", $event_id, $user['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully registered for event!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. You may already be registered.']);
    }
}

// Unregister from Event
elseif ($action === 'unregister_event') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $target_user_id = intval($_POST['user_id'] ?? $user['id']);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check permissions
    if ($target_user_id != $user['id'] && !hasRole(['Admin', 'Staff'])) {
        echo json_encode(['success' => false, 'message' => 'You can only cancel your own registrations']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE registrations SET status = 'cancelled' WHERE event_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $event_id, $target_user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Successfully unregistered from event!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unregistration failed']);
    }
}

// My Registrations
elseif ($action === 'my_registrations') {
    $query = "SELECT r.*, e.title as event_title, e.event_date, e.event_time, e.start_datetime, e.end_datetime, e.venue, e.status as event_status
              FROM registrations r
              JOIN events e ON r.event_id = e.id
              WHERE r.user_id = ? AND r.status = 'registered'
              ORDER BY e.event_date ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    echo json_encode($registrations);
}

// Get Activity Flow
elseif ($action === 'get_activity_flow') {
    $event_id = intval($_GET['event_id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check if user has permission to view activity flow
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("SELECT * FROM activity_flow WHERE event_id = ? ORDER BY start_time ASC");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    echo json_encode($activities);
}

// Create Activity
elseif ($action === 'create_activity') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    if ($event_id <= 0 || empty($description) || empty($start_time) || empty($end_time)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    // Check permissions
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO activity_flow (event_id, description, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $event_id, $description, $start_time, $end_time);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding activity']);
    }
}

// Delete Activity
elseif ($action === 'delete_activity') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid activity ID']);
        exit;
    }
    
    // Check permissions
    $stmt = $conn->prepare("SELECT af.*, e.organizer_id FROM activity_flow af JOIN events e ON af.event_id = e.id WHERE af.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $activity = $result->fetch_assoc();
    
    if (!$activity) {
        echo json_encode(['success' => false, 'message' => 'Activity not found']);
        exit;
    }
    
    if (!hasRole(['Admin', 'Staff']) && $activity['organizer_id'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM activity_flow WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting activity']);
    }
}

// Get Attendance List
elseif ($action === 'get_attendance') {
    $event_id = intval($_GET['event_id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check if user has permission to view attendance
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $query = "SELECT u.id as user_id, u.full_name, u.email, a.status as attendance_status, a.attendance_time
              FROM registrations r
              JOIN users u ON r.user_id = u.id
              LEFT JOIN attendance a ON a.event_id = r.event_id AND a.user_id = r.user_id
              WHERE r.event_id = ? AND r.status = 'registered'
              ORDER BY u.full_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attendance = [];
    while ($row = $result->fetch_assoc()) {
        $attendance[] = $row;
    }
    
    echo json_encode($attendance);
}

// Mark Attendance
elseif ($action === 'mark_attendance') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($event_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Check if user has permission to mark attendance
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO attendance (event_id, user_id, marked_by, status) VALUES (?, ?, ?, 'present')
                           ON DUPLICATE KEY UPDATE status = 'present', marked_by = ?, attendance_time = CURRENT_TIMESTAMP");
    $stmt->bind_param("iiii", $event_id, $user_id, $user['id'], $user['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance marked successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error marking attendance']);
    }
}

// Unmark Attendance
elseif ($action === 'unmark_attendance') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($event_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    // Check if user has permission to unmark attendance
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM attendance WHERE event_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $event_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Attendance unmarked successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error unmarking attendance']);
    }
}

// Get Budget List
elseif ($action === 'get_budget') {
    $event_id = intval($_GET['event_id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check if user has permission to view budget
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $stmt = $conn->prepare("SELECT * FROM budget WHERE event_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $budget = [];
    while ($row = $result->fetch_assoc()) {
        $budget[] = $row;
    }
    
    echo json_encode($budget);
}

// Get Single Budget Item
elseif ($action === 'get_budget_item') {
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid budget ID']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT b.*, e.organizer_id FROM budget b JOIN events e ON b.event_id = e.id WHERE b.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget_item = $result->fetch_assoc();
    
    if (!$budget_item) {
        echo json_encode(['success' => false, 'message' => 'Budget item not found']);
        exit;
    }
    
    // Check if user has permission to view this budget item
    if (!hasRole(['Admin', 'Staff']) && $budget_item['organizer_id'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    echo json_encode($budget_item);
}

// Create Budget
elseif ($action === 'create_budget') {
    $event_id = intval($_POST['event_id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    // Check if user has permission to create budget
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    $item_name = trim($_POST['item_name'] ?? '');
    $type = $_POST['type'] ?? 'Expense';
    $description = trim($_POST['description'] ?? '');
    $allocated_amount = floatval($_POST['allocated_amount'] ?? 0);
    $spent_amount = floatval($_POST['spent_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($item_name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit;
    }
    
    // Validate type
    if (!in_array($type, ['Income', 'Expense'])) {
        $type = 'Expense';
    }
    
    $stmt = $conn->prepare("INSERT INTO budget (event_id, item_name, type, description, allocated_amount, spent_amount, notes) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdds", $event_id, $item_name, $type, $description, $allocated_amount, $spent_amount, $notes);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Budget item added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding budget item']);
    }
}

// Update Budget
elseif ($action === 'update_budget') {
    $id = intval($_POST['budget_id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid budget ID']);
        exit;
    }
    
    // Check if user has permission to update budget
    $stmt = $conn->prepare("SELECT b.*, e.organizer_id FROM budget b JOIN events e ON b.event_id = e.id WHERE b.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget_item = $result->fetch_assoc();
    
    if (!$budget_item) {
        echo json_encode(['success' => false, 'message' => 'Budget item not found']);
        exit;
    }
    
    if (!hasRole(['Admin', 'Staff']) && $budget_item['organizer_id'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $item_name = trim($_POST['item_name'] ?? '');
    $type = $_POST['type'] ?? 'Expense';
    $description = trim($_POST['description'] ?? '');
    $allocated_amount = floatval($_POST['allocated_amount'] ?? 0);
    $spent_amount = floatval($_POST['spent_amount'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($item_name)) {
        echo json_encode(['success' => false, 'message' => 'Item name is required']);
        exit;
    }
    
    // Validate type
    if (!in_array($type, ['Income', 'Expense'])) {
        $type = 'Expense';
    }
    
    $stmt = $conn->prepare("UPDATE budget SET item_name=?, type=?, description=?, allocated_amount=?, spent_amount=?, notes=? WHERE id=?");
    $stmt->bind_param("sssddsi", $item_name, $type, $description, $allocated_amount, $spent_amount, $notes, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Budget item updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating budget item']);
    }
}

// Delete Budget
elseif ($action === 'delete_budget') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid budget ID']);
        exit;
    }
    
    // Check if user has permission to delete budget
    $stmt = $conn->prepare("SELECT b.*, e.organizer_id FROM budget b JOIN events e ON b.event_id = e.id WHERE b.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $budget_item = $result->fetch_assoc();
    
    if (!$budget_item) {
        echo json_encode(['success' => false, 'message' => 'Budget item not found']);
        exit;
    }
    
    if (!hasRole(['Admin', 'Staff']) && $budget_item['organizer_id'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM budget WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Budget item deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting budget item']);
    }
}

// Submit Feedback
elseif ($action === 'submit_feedback') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    if ($event_id <= 0 || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    // Check if user is registered for the event
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE event_id = ? AND user_id = ? AND status = 'registered'");
    $stmt->bind_param("ii", $event_id, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'You must be registered for the event to submit feedback']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO feedback (event_id, user_id, rating, comment) VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE rating = ?, comment = ?");
    $stmt->bind_param("iiisis", $event_id, $user['id'], $rating, $comment, $rating, $comment);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Feedback submitted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error submitting feedback']);
    }
}

// Get Feedback
elseif ($action === 'get_feedback') {
    $event_id = intval($_GET['event_id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT f.*, u.full_name FROM feedback f 
                           JOIN users u ON f.user_id = u.id 
                           WHERE f.event_id = ? 
                           ORDER BY f.submitted_at DESC");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $feedback = [];
    while ($row = $result->fetch_assoc()) {
        $feedback[] = $row;
    }
    
    echo json_encode($feedback);
}

// Get Media List
elseif ($action === 'get_media') {
    $event_id = intval($_GET['event_id'] ?? 0);
    
    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT m.*, u.full_name as uploaded_by_name FROM media m 
                           LEFT JOIN users u ON m.uploaded_by = u.id 
                           WHERE m.event_id = ? 
                           ORDER BY m.uploaded_at DESC");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $media = [];
    while ($row = $result->fetch_assoc()) {
        $media[] = $row;
    }
    
    echo json_encode($media);
}

// Create Media
elseif ($action === 'create_media') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $media_type = $_POST['media_type'] ?? 'photo';
    $file_url = trim($_POST['file_url'] ?? '');
    
    if ($event_id <= 0 || empty($title) || empty($file_url)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    // Check if user has permission to add media
    if (!hasRole(['Admin', 'Staff', 'Teacher'])) {
        $stmt = $conn->prepare("SELECT organizer_id FROM events WHERE id = ?");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if (!$event || $event['organizer_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    // Validate media type
    if (!in_array($media_type, ['photo', 'video', 'document'])) {
        $media_type = 'photo';
    }
    
    $stmt = $conn->prepare("INSERT INTO media (event_id, uploaded_by, media_type, file_url, title) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $event_id, $user['id'], $media_type, $file_url, $title);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Media added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding media']);
    }
}

// Delete Media
elseif ($action === 'delete_media') {
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid media ID']);
        exit;
    }
    
    // Check if user has permission to delete media
    $stmt = $conn->prepare("SELECT m.*, e.organizer_id FROM media m JOIN events e ON m.event_id = e.id WHERE m.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $media = $result->fetch_assoc();
    
    if (!$media) {
        echo json_encode(['success' => false, 'message' => 'Media not found']);
        exit;
    }
    
    if (!hasRole(['Admin', 'Staff']) && $media['organizer_id'] != $user['id'] && $media['uploaded_by'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM media WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Media deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting media']);
    }
}

// User Management - Get Users
elseif ($action === 'get_users') {
    if (!hasRole(['Admin', 'Staff'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $result = $conn->query("SELECT id, username, email, full_name, role FROM users ORDER BY id DESC");
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode($users);
}

// Get Single User
elseif ($action === 'get_user') {
    if (!hasRole(['Admin', 'Staff'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $id = intval($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, username, email, full_name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $user_data = $result->fetch_assoc();
    
    if (!$user_data) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    echo json_encode($user_data);
}

// Create User
elseif ($action === 'create_user') {
    if (!hasRole(['Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'Student';
    $password = $_POST['password'] ?? 'admin123';
    
    if (empty($username) || empty($email) || empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    // Validate role
    $valid_roles = ['Admin', 'Teacher', 'Student', 'Staff', 'Guest'];
    if (!in_array($role, $valid_roles)) {
        $role = 'Student';
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, full_name, role, password) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $full_name, $role, $password_hash);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User created successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating user. Username or email may already exist.']);
    }
}

// Update User
elseif ($action === 'update_user') {
    if (!hasRole(['Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $id = intval($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'Student';
    
    if ($id <= 0 || empty($username) || empty($email) || empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    // Validate role
    $valid_roles = ['Admin', 'Teacher', 'Student', 'Staff', 'Guest'];
    if (!in_array($role, $valid_roles)) {
        $role = 'Student';
    }
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("sssssi", $username, $email, $full_name, $role, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $username, $email, $full_name, $role, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user']);
    }
}

// Delete User
elseif ($action === 'delete_user') {
    if (!hasRole(['Admin'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $id = intval($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }
    
    if ($id == $user['id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account!']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting user']);
    }
}

// Get user profile
elseif ($action === 'get_profile') {
    echo json_encode($user);
}

// Update user profile
elseif ($action === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($full_name) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Required fields missing']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $full_name, $email, $user['id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating profile']);
    }
}

// Staff: Register user for event (walk-in registration)
elseif ($action === 'register_user_event') {
    if (!hasRole(['Admin', 'Staff'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if ($event_id <= 0 || $user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;
    }
    
    $stmt = $conn->prepare("INSERT INTO registrations (event_id, user_id, status) VALUES (?, ?, 'registered')");
    $stmt->bind_param("ii", $event_id, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User registered for event successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. User may already be registered.']);
    }
}

// Staff: Get all users for walk-in registration
elseif ($action === 'get_all_users') {
    if (!hasRole(['Admin', 'Staff'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $result = $conn->query("SELECT id, username, full_name, email, role FROM users ORDER BY full_name");
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    echo json_encode($users);
}

// Automated Status Update
elseif ($action === 'update_event_statuses') {
    if (!hasRole(['Admin', 'Teacher'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    $current_datetime = date('Y-m-d H:i:s');
    
    // Update ongoing events
    $stmt = $conn->prepare("UPDATE events SET status = 'ongoing' WHERE start_datetime <= ? AND end_datetime >= ? AND status = 'upcoming'");
    $stmt->bind_param("ss", $current_datetime, $current_datetime);
    $stmt->execute();
    
    // Update completed events
    $stmt = $conn->prepare("UPDATE events SET status = 'completed' WHERE end_datetime < ? AND status IN ('upcoming', 'ongoing')");
    $stmt->bind_param("s", $current_datetime);
    $stmt->execute();
    
    $updated = $conn->affected_rows;
    echo json_encode(['success' => true, 'message' => "Updated $updated event statuses", 'updated' => $updated]);
}

else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>