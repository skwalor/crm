<?php
// Diagnostic script - place in your CRM folder and access via browser
// DELETE THIS FILE AFTER TESTING

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Opportunity Workspace Diagnostic</h2>";

// Test database connection
require_once 'db_connect.php';

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
echo "<p>✅ Database connection OK</p>";

// Check if workspace tables exist
$tables = [
    'opportunity_qualification',
    'opportunity_qualification_contacts', 
    'opportunity_capture',
    'opportunity_competitors',
    'opportunity_teaming_partners',
    'opportunity_bid_decision',
    'opportunity_risks'
];

echo "<h3>Table Check:</h3>";
$missing_tables = [];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<p>✅ Table '$table' exists</p>";
    } else {
        echo "<p>❌ Table '$table' MISSING</p>";
        $missing_tables[] = $table;
    }
}

// Check if workspace_phase column exists in opportunities
echo "<h3>Opportunities Table Check:</h3>";
$result = $conn->query("SHOW COLUMNS FROM opportunities LIKE 'workspace_phase'");
if ($result && $result->num_rows > 0) {
    echo "<p>✅ Column 'workspace_phase' exists in opportunities table</p>";
} else {
    echo "<p>❌ Column 'workspace_phase' MISSING from opportunities table</p>";
}

// Test a simple query
echo "<h3>Query Test:</h3>";
$result = $conn->query("SELECT id, title FROM opportunities LIMIT 1");
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
        echo "<p>✅ Found opportunity: ID={$row['id']}, Title={$row['title']}</p>";
        
        // Now test the actual workspace query
        $opp_id = $row['id'];
        echo "<h3>Testing Workspace Query for Opportunity ID: $opp_id</h3>";
        
        $query = "SELECT o.*, a.name as agency_name, a.acronym as agency_acronym,
                   u.display_name as owner_name, u.username as owner_username
            FROM opportunities o
            LEFT JOIN agencies a ON o.agency_id = a.id
            LEFT JOIN users u ON o.owner_user_id = u.id
            WHERE o.id = $opp_id";
        
        $result2 = $conn->query($query);
        if ($result2) {
            $opp = $result2->fetch_assoc();
            echo "<p>✅ Main opportunity query works</p>";
            echo "<pre>" . print_r($opp, true) . "</pre>";
        } else {
            echo "<p>❌ Main opportunity query failed: " . $conn->error . "</p>";
        }
        
        // Test each workspace table query
        if (empty($missing_tables)) {
            foreach ($tables as $table) {
                $test_query = "SELECT * FROM $table WHERE opportunity_id = $opp_id LIMIT 1";
                $result3 = $conn->query($test_query);
                if ($result3) {
                    echo "<p>✅ Query on '$table' works</p>";
                } else {
                    echo "<p>❌ Query on '$table' failed: " . $conn->error . "</p>";
                }
            }
        }
    } else {
        echo "<p>⚠️ No opportunities found in database</p>";
    }
} else {
    echo "<p>❌ Query failed: " . $conn->error . "</p>";
}

// Check for PHP errors in api.php syntax
echo "<h3>PHP Syntax Check:</h3>";
$output = [];
$return_var = 0;
exec("php -l api.php 2>&1", $output, $return_var);
if ($return_var === 0) {
    echo "<p>✅ api.php has valid PHP syntax</p>";
} else {
    echo "<p>❌ api.php has syntax errors:</p>";
    echo "<pre>" . implode("\n", $output) . "</pre>";
}

echo "<h3>Summary:</h3>";
if (!empty($missing_tables)) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ You need to run the migration script! Missing tables: " . implode(", ", $missing_tables) . "</p>";
    echo "<p>Run: <code>mysql -u root -p crm &lt; opportunity_workspace_migration.sql</code></p>";
} else {
    echo "<p style='color: green;'>All tables exist. Check browser console for the actual error response.</p>";
}

$conn->close();
?>
