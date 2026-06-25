<?php

$config = require 'config/database.php';
try {
    $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    echo "=== STAFF DEBUG SCRIPT ===\n\n";
// 1. Check all staff users
    echo "1. ALL STAFF USERS:\n";
    $stmt = $pdo->query("SELECT u.id, u.email, u.full_name, u.role, sm.id as staff_id, sm.title, sm.department FROM users u LEFT JOIN staff_members sm ON u.id = sm.user_id WHERE u.role = 'staff'");
    $staffUsers = $stmt->fetchAll();
    foreach ($staffUsers as $user) {
        echo "User ID: {$user['id']}, Email: {$user['email']}, Name: {$user['full_name']}, Staff ID: {$user['staff_id']}, Title: {$user['title']}, Dept: {$user['department']}\n";
    }

    echo "\n2. ALL STAFF MEMBERS:\n";
    $stmt = $pdo->query("SELECT sm.id, sm.user_id, sm.title, sm.department, u.email, u.full_name FROM staff_members sm JOIN users u ON sm.user_id = u.id WHERE sm.is_active = TRUE");
    $staffMembers = $stmt->fetchAll();
    foreach ($staffMembers as $staff) {
        echo "Staff ID: {$staff['id']}, User ID: {$staff['user_id']}, Title: {$staff['title']}, Dept: {$staff['department']}, Email: {$staff['email']}, Name: {$staff['full_name']}\n";
    }

    echo "\n3. MODULES AND THEIR LEADERS:\n";
    $stmt = $pdo->query("SELECT m.id, m.title, m.module_leader_id, p.title as programme_title, sm.title as leader_title, u.full_name as leader_name FROM modules m LEFT JOIN programmes p ON m.programme_id = p.id LEFT JOIN staff_members sm ON m.module_leader_id = sm.id LEFT JOIN users u ON sm.user_id = u.id WHERE m.is_active = TRUE");
    $modules = $stmt->fetchAll();
    foreach ($modules as $module) {
        echo "Module: {$module['title']}, Leader ID: {$module['module_leader_id']}, Leader: {$module['leader_title']} {$module['leader_name']}, Programme: {$module['programme_title']}\n";
    }

    echo "\n4. TEST FOR A SPECIFIC STAFF:\n";
// Let's test with the first staff found
    if (!empty($staffMembers)) {
        $testStaffId = $staffMembers[0]['id'];
        $testUserId = $staffMembers[0]['user_id'];
        echo "Testing with Staff ID: {$testStaffId}, User ID: {$testUserId}\n";
// Search modules for this staff
        $stmt = $pdo->prepare("SELECT m.*, p.title AS programme_title, p.level AS programme_level FROM modules m JOIN programmes p ON m.programme_id = p.id WHERE m.module_leader_id = ? AND m.is_active = TRUE");
        $stmt->execute([$testStaffId]);
        $testModules = $stmt->fetchAll();
        echo "Modules found: " . count($testModules) . "\n";
        foreach ($testModules as $module) {
            echo "- {$module['title']} ({$module['programme_title']})\n";
        }

        // Search programmes for this staff
        $stmt = $pdo->prepare("SELECT DISTINCT p.*, COUNT(m.id) as module_count FROM programmes p JOIN modules m ON p.id = m.programme_id WHERE m.module_leader_id = ? AND m.is_active = TRUE AND p.is_published = TRUE GROUP BY p.id");
        $stmt->execute([$testStaffId]);
        $testProgrammes = $stmt->fetchAll();
        echo "Programmes found: " . count($testProgrammes) . "\n";
        foreach ($testProgrammes as $programme) {
            echo "- {$programme['title']} ({$programme['module_count']} modules)\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
