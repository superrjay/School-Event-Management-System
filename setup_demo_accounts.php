<?php
// This file creates demo accounts for testing
// Run this ONCE after creating the database

require_once 'config.php';

// Create database connection
$conn = getDBConnection();

// Demo accounts to create
$demo_accounts = [
    [
        'username' => 'admin',
        'password' => 'admin123',
        'full_name' => 'System Administrator',
        'email' => 'admin@schoolsystem.com',
        'role' => 'Admin'
    ],
    [
        'username' => 'teacher1',
        'password' => 'admin123',
        'full_name' => 'John Smith',
        'email' => 'teacher1@schoolsystem.com',
        'role' => 'Teacher'
    ],
    [
        'username' => 'teacher2',
        'password' => 'admin123',
        'full_name' => 'Sarah Johnson',
        'email' => 'teacher2@schoolsystem.com',
        'role' => 'Teacher'
    ],
    [
        'username' => 'student1',
        'password' => 'admin123',
        'full_name' => 'Michael Brown',
        'email' => 'student1@schoolsystem.com',
        'role' => 'Student'
    ],
    [
        'username' => 'student2',
        'password' => 'admin123',
        'full_name' => 'Emily Davis',
        'email' => 'student2@schoolsystem.com',
        'role' => 'Student'
    ],
    [
        'username' => 'staff1',
        'password' => 'admin123',
        'full_name' => 'Robert Wilson',
        'email' => 'staff1@schoolsystem.com',
        'role' => 'Staff'
    ],
    [
        'username' => 'guest1',
        'password' => 'admin123',
        'full_name' => 'Guest User',
        'email' => 'guest1@schoolsystem.com',
        'role' => 'Guest'
    ]
];

echo "<h2>Creating Demo Accounts</h2>";

$created_count = 0;
$skipped_count = 0;

foreach ($demo_accounts as $account) {
    // Check if account already exists
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $account['username']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $exists = $result->fetch_assoc()['count'];
    $check_stmt->close();

    if ($exists > 0) {
        echo "<p style='color: orange;'>⚠️ Account '{$account['username']}' already exists - skipped</p>";
        $skipped_count++;
        continue;
    }

    // Create account
    $hashed_password = password_hash($account['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $account['username'], $hashed_password, $account['full_name'], $account['email'], $account['role']);

    if ($stmt->execute()) {
        echo "<p style='color: green;'>✅ Created account: {$account['username']} ({$account['role']})</p>";
        $created_count++;
    } else {
        echo "<p style='color: red;'>❌ Error creating account: {$account['username']} - " . $conn->error . "</p>";
    }
    
    $stmt->close();
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p><strong>Accounts Created:</strong> {$created_count}</p>";
echo "<p><strong>Accounts Skipped:</strong> {$skipped_count}</p>";
echo "<p><strong>Total Accounts:</strong> " . ($created_count + $skipped_count) . "</p>";

echo "<hr>";
echo "<h3>Login Credentials:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr><th>Username</th><th>Password</th><th>Role</th></tr>";
foreach ($demo_accounts as $account) {
    echo "<tr>";
    echo "<td>{$account['username']}</td>";
    echo "<td>{$account['password']}</td>";
    echo "<td>{$account['role']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
echo "<p><strong>SECURITY WARNING:</strong> Delete this file after use!</p>";

$conn->close();
?>