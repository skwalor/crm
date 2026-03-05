<?php
// Suppress PHP warnings/notices from appearing in output (they would corrupt JSON)
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Start output buffering to catch any stray output
ob_start();

session_start();

// Clean any output that might have occurred during session_start or includes
ob_clean();

header('Content-Type: application/json');

require_once 'db_connect.php';
require_once 'includes/functions.php';

// Clean again after includes
ob_clean();

// --- RBAC HELPER FUNCTION ---
function has_permission($resource, $action) {
    // Admin role always has all permissions
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    
    $valid_actions = ['create', 'view', 'update', 'delete', 'archive'];
    if (!in_array($action, $valid_actions)) {
        return false;
    }
    $permission_key = 'can_' . $action;
    return isset($_SESSION['permissions'][$resource][$permission_key]) && $_SESSION['permissions'][$resource][$permission_key] === true;
}

// Common response function
function send_response($response) {
    global $conn;
    if ($conn && $conn->ping()) {
        $conn->close();
    }
    // Clean any buffered output and end buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Try to encode JSON with error handling
    $json = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);
    if ($json === false) {
        // If encoding fails, try with invalid UTF-8 handling
        $json = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            // Last resort - return error
            echo json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
            exit;
        }
    }
    
    echo $json;
    exit;
}

function send_error($message, $code = 403) {
    http_response_code($code);
    send_response(['error' => $message]);
}

// --- AUTH CHECK FOR ALL API ACTIONS ---
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    send_error('Unauthorized', 401);
}

$action = $_GET['action'] ?? '';

// --- SPECIALTY ROLE HELPER FUNCTIONS ---
// Returns true if current user has specialty role
function is_specialty_user() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'specialty';
}

// Get opportunity IDs that specialty user can access
// (where they are owner, co-owner (user type), or assigned user)
function get_specialty_opportunity_ids($conn, $userId) {
    $ids = [];
    
    // Owner
    $stmt = $conn->prepare("SELECT id FROM opportunities WHERE owner_user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
    
    // Co-owner (user type)
    $stmt = $conn->prepare("SELECT id FROM opportunities WHERE co_owner_contact_type = 'user' AND co_owner_contact_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
    
    // Assigned user
    $stmt = $conn->prepare("SELECT opportunity_id FROM opportunity_users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['opportunity_id'];
    }
    
    return array_unique($ids);
}

// Get proposal IDs that specialty user can access
// (where they are owner or assigned user)
function get_specialty_proposal_ids($conn, $userId) {
    $ids = [];
    
    // Owner
    $stmt = $conn->prepare("SELECT id FROM proposals WHERE owner_user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
    
    // Assigned user
    $stmt = $conn->prepare("SELECT proposal_id FROM proposal_users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['proposal_id'];
    }
    
    return array_unique($ids);
}

// Get task IDs that specialty user can access
// (where they are assigned OR task is linked to their opportunities/proposals)
function get_specialty_task_ids($conn, $userId, $oppIds, $propIds, $eventIds = []) {
    $ids = [];
    
    // Directly assigned
    $stmt = $conn->prepare("SELECT id FROM tasks WHERE assigned_to_user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
    
    // Linked to their opportunities
    if (!empty($oppIds)) {
        $placeholders = implode(',', array_fill(0, count($oppIds), '?'));
        $stmt = $conn->prepare("SELECT id FROM tasks WHERE relatedTo = 'Opportunity' AND related_item_id IN ($placeholders)");
        $types = str_repeat('i', count($oppIds));
        $stmt->bind_param($types, ...$oppIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
    }
    
    // Linked to their proposals
    if (!empty($propIds)) {
        $placeholders = implode(',', array_fill(0, count($propIds), '?'));
        $stmt = $conn->prepare("SELECT id FROM tasks WHERE relatedTo = 'Proposal' AND related_item_id IN ($placeholders)");
        $types = str_repeat('i', count($propIds));
        $stmt->bind_param($types, ...$propIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
    }
    
    // Linked to their events
    if (!empty($eventIds)) {
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $stmt = $conn->prepare("SELECT id FROM tasks WHERE (relatedTo = 'Event' OR relatedTo = 'event') AND related_item_id IN ($placeholders)");
        $types = str_repeat('i', count($eventIds));
        $stmt->bind_param($types, ...$eventIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
    }
    
    return array_unique($ids);
}

// Get contact IDs (federal) that specialty user can access
// (where they are owner)
function get_specialty_contact_ids($conn, $userId) {
    $ids = [];
    $stmt = $conn->prepare("SELECT id FROM contacts WHERE owner_user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
    return $ids;
}

// Get company contact IDs that specialty user can access
// (where they are primary owner or secondary owner)
function get_specialty_company_contact_ids($conn, $userId) {
    $ids = [];
    
    // Primary owner
    $stmt = $conn->prepare("SELECT id FROM company_contacts WHERE primary_owner_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['id'];
    }
    
    // Secondary owner
    $stmt = $conn->prepare("SELECT company_contact_id FROM company_contact_secondary_owners WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['company_contact_id'];
    }
    
    return array_unique($ids);
}

// Get event IDs that specialty user can access
// (where they are owner, assigned user, or assigned contact they own)
function get_specialty_event_ids($conn, $userId, $federalContactIds = [], $companyContactIds = []) {
    $ids = [];
    
    // Check if events table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'events'");
    if (!$tableCheck || $tableCheck->num_rows == 0) {
        return $ids; // Return empty array if table doesn't exist
    }
    
    // Owner
    $stmt = $conn->prepare("SELECT id FROM events WHERE owner_user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['id'];
        }
    }
    
    // Assigned user
    $stmt = $conn->prepare("SELECT event_id FROM event_users WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ids[] = $row['event_id'];
        }
    }
    
    // Events with federal contacts they own
    if (!empty($federalContactIds)) {
        $placeholders = implode(',', array_fill(0, count($federalContactIds), '?'));
        $stmt = $conn->prepare("SELECT DISTINCT event_id FROM event_federal_contacts WHERE contact_id IN ($placeholders)");
        if ($stmt) {
            $types = str_repeat('i', count($federalContactIds));
            $stmt->bind_param($types, ...$federalContactIds);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $ids[] = $row['event_id'];
            }
        }
    }
    
    // Events with company contacts they own
    if (!empty($companyContactIds)) {
        $placeholders = implode(',', array_fill(0, count($companyContactIds), '?'));
        $stmt = $conn->prepare("SELECT DISTINCT event_id FROM event_commercial_contacts WHERE contact_id IN ($placeholders)");
        if ($stmt) {
            $types = str_repeat('i', count($companyContactIds));
            $stmt->bind_param($types, ...$companyContactIds);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $ids[] = $row['event_id'];
            }
        }
    }
    
    return array_unique($ids);
}

// --- API ACTION ROUTER ---
switch ($action) {
    case 'getAllData':
        $data = [];
        
        // Specialty role: Get allowed record IDs
        $isSpecialty = is_specialty_user();
        $specialtyOppIds = [];
        $specialtyPropIds = [];
        $specialtyTaskIds = [];
        $specialtyContactIds = [];
        $specialtyCompanyContactIds = [];
        $specialtyAgencyIds = [];
        $specialtyCompanyIds = [];
        $specialtyEventIds = [];
        
        if ($isSpecialty) {
            $userId = $_SESSION['user_id'];
            $specialtyOppIds = get_specialty_opportunity_ids($conn, $userId);
            $specialtyPropIds = get_specialty_proposal_ids($conn, $userId);
            $specialtyContactIds = get_specialty_contact_ids($conn, $userId);
            $specialtyCompanyContactIds = get_specialty_company_contact_ids($conn, $userId);
            $specialtyEventIds = get_specialty_event_ids($conn, $userId, $specialtyContactIds, $specialtyCompanyContactIds);
            $specialtyTaskIds = get_specialty_task_ids($conn, $userId, $specialtyOppIds, $specialtyPropIds, $specialtyEventIds);
            
            // Get agency IDs from their records
            if (!empty($specialtyOppIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyOppIds), '?'));
                $stmt = $conn->prepare("SELECT DISTINCT agency_id FROM opportunities WHERE id IN ($placeholders) AND agency_id IS NOT NULL");
                $types = str_repeat('i', count($specialtyOppIds));
                $stmt->bind_param($types, ...$specialtyOppIds);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $specialtyAgencyIds[] = $row['agency_id'];
                }
            }
            if (!empty($specialtyPropIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyPropIds), '?'));
                $stmt = $conn->prepare("SELECT DISTINCT agency_id FROM proposals WHERE id IN ($placeholders) AND agency_id IS NOT NULL");
                $types = str_repeat('i', count($specialtyPropIds));
                $stmt->bind_param($types, ...$specialtyPropIds);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $specialtyAgencyIds[] = $row['agency_id'];
                }
            }
            if (!empty($specialtyContactIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyContactIds), '?'));
                $stmt = $conn->prepare("SELECT DISTINCT agency_id FROM contacts WHERE id IN ($placeholders) AND agency_id IS NOT NULL");
                $types = str_repeat('i', count($specialtyContactIds));
                $stmt->bind_param($types, ...$specialtyContactIds);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $specialtyAgencyIds[] = $row['agency_id'];
                }
            }
            $specialtyAgencyIds = array_unique($specialtyAgencyIds);
            
            // Get company IDs from their company contacts
            if (!empty($specialtyCompanyContactIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyCompanyContactIds), '?'));
                $stmt = $conn->prepare("SELECT DISTINCT company_id FROM company_contacts WHERE id IN ($placeholders) AND company_id IS NOT NULL");
                $types = str_repeat('i', count($specialtyCompanyContactIds));
                $stmt->bind_param($types, ...$specialtyCompanyContactIds);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $specialtyCompanyIds[] = $row['company_id'];
                }
            }
            $specialtyCompanyIds = array_unique($specialtyCompanyIds);
        }
        
        if (has_permission('agency', 'view')) {
            if ($isSpecialty && !empty($specialtyAgencyIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyAgencyIds), '?'));
                $stmt = $conn->prepare("SELECT *, (SELECT COUNT(*) FROM contacts WHERE agency_id = agencies.id) AS contactCount FROM agencies WHERE id IN ($placeholders) ORDER BY name ASC");
                $types = str_repeat('i', count($specialtyAgencyIds));
                $stmt->bind_param($types, ...$specialtyAgencyIds);
                $stmt->execute();
                $data['agencies'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else if ($isSpecialty) {
                $data['agencies'] = []; // No agencies if specialty has no records
            } else {
                $result = $conn->query("SELECT *, (SELECT COUNT(*) FROM contacts WHERE agency_id = agencies.id) AS contactCount FROM agencies ORDER BY name ASC");
                $data['agencies'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
        }
        if (has_permission('contact', 'view')) {
            if ($isSpecialty && !empty($specialtyContactIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyContactIds), '?'));
                $stmt = $conn->prepare("
                    SELECT c.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName 
                    FROM contacts c 
                    LEFT JOIN agencies a ON c.agency_id = a.id 
                    LEFT JOIN users u ON c.owner_user_id = u.id
                    WHERE c.id IN ($placeholders)
                    ORDER BY c.lastName ASC
                ");
                $types = str_repeat('i', count($specialtyContactIds));
                $stmt->bind_param($types, ...$specialtyContactIds);
                $stmt->execute();
                $data['contacts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else if ($isSpecialty) {
                $data['contacts'] = [];
            } else {
                // Updated to include owner information with display_name
                $result = $conn->query("
                    SELECT c.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName 
                    FROM contacts c 
                    LEFT JOIN agencies a ON c.agency_id = a.id 
                    LEFT JOIN users u ON c.owner_user_id = u.id
                    ORDER BY c.lastName ASC
                ");
                $data['contacts'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
        }
        if (has_permission('opportunity', 'view')) {
            if ($isSpecialty && !empty($specialtyOppIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyOppIds), '?'));
                $stmt = $conn->prepare("
                    SELECT o.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName
                    FROM opportunities o 
                    LEFT JOIN agencies a ON o.agency_id = a.id 
                    LEFT JOIN users u ON o.owner_user_id = u.id
                    WHERE o.id IN ($placeholders)
                    ORDER BY o.dueDate ASC
                ");
                $types = str_repeat('i', count($specialtyOppIds));
                $stmt->bind_param($types, ...$specialtyOppIds);
                $stmt->execute();
                $opportunities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else if ($isSpecialty) {
                $opportunities = [];
            } else {
                $result = $conn->query("
                    SELECT o.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName
                    FROM opportunities o 
                    LEFT JOIN agencies a ON o.agency_id = a.id 
                    LEFT JOIN users u ON o.owner_user_id = u.id
                    ORDER BY o.dueDate ASC
                ");
                $opportunities = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
            
            // Add co-owner display names based on contact type
            foreach ($opportunities as &$opp) {
                $opp['coOwnerDisplayName'] = null;
                if (!empty($opp['co_owner_contact_id']) && !empty($opp['co_owner_contact_type'])) {
                    $coOwnerId = intval($opp['co_owner_contact_id']);
                    if ($opp['co_owner_contact_type'] === 'federal') {
                        $stmt = $conn->prepare("SELECT CONCAT(firstName, ' ', lastName) AS name FROM contacts WHERE id = ?");
                        $stmt->bind_param("i", $coOwnerId);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        $opp['coOwnerDisplayName'] = $result ? $result['name'] : null;
                    } else if ($opp['co_owner_contact_type'] === 'commercial') {
                        $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM company_contacts WHERE id = ?");
                        $stmt->bind_param("i", $coOwnerId);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        $opp['coOwnerDisplayName'] = $result ? $result['name'] : null;
                    } else if ($opp['co_owner_contact_type'] === 'user') {
                        $stmt = $conn->prepare("SELECT COALESCE(display_name, username) AS name FROM users WHERE id = ?");
                        $stmt->bind_param("i", $coOwnerId);
                        $stmt->execute();
                        $result = $stmt->get_result()->fetch_assoc();
                        $opp['coOwnerDisplayName'] = $result ? $result['name'] : null;
                    }
                }
            }
            unset($opp); // Break the reference
            $data['opportunities'] = $opportunities;
        }
        if (has_permission('proposal', 'view')) {
            if ($isSpecialty && !empty($specialtyPropIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyPropIds), '?'));
                $stmt = $conn->prepare("
                    SELECT p.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName 
                    FROM proposals p 
                    LEFT JOIN agencies a ON p.agency_id = a.id 
                    LEFT JOIN users u ON p.owner_user_id = u.id
                    WHERE p.id IN ($placeholders)
                    ORDER BY p.submitDate DESC
                ");
                $types = str_repeat('i', count($specialtyPropIds));
                $stmt->bind_param($types, ...$specialtyPropIds);
                $stmt->execute();
                $data['proposals'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else if ($isSpecialty) {
                $data['proposals'] = [];
            } else {
                $result = $conn->query("
                    SELECT p.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName 
                    FROM proposals p 
                    LEFT JOIN agencies a ON p.agency_id = a.id 
                    LEFT JOIN users u ON p.owner_user_id = u.id
                    ORDER BY p.submitDate DESC
                ");
                $data['proposals'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
        }
        
        // Events - with error handling in case tables don't exist yet
        $data['events'] = [];
        $data['eventUsers'] = [];
        $data['eventFederalContacts'] = [];
        $data['eventCommercialContacts'] = [];
        
        // Check if events table exists before querying
        $tableCheck = $conn->query("SHOW TABLES LIKE 'events'");
        $eventsTableExists = $tableCheck && $tableCheck->num_rows > 0;
        
        if ($eventsTableExists && has_permission('event', 'view')) {
            if ($isSpecialty && !empty($specialtyEventIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyEventIds), '?'));
                $stmt = $conn->prepare("
                    SELECT e.*, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName 
                    FROM events e 
                    LEFT JOIN users u ON e.owner_user_id = u.id
                    WHERE e.id IN ($placeholders)
                    ORDER BY e.start_datetime DESC
                ");
                if ($stmt) {
                    $types = str_repeat('i', count($specialtyEventIds));
                    $stmt->bind_param($types, ...$specialtyEventIds);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $data['events'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
                }
            } else if ($isSpecialty) {
                $data['events'] = [];
            } else {
                $eventsResult = $conn->query("
                    SELECT e.*, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName 
                    FROM events e 
                    LEFT JOIN users u ON e.owner_user_id = u.id
                    ORDER BY e.start_datetime DESC
                ");
                $data['events'] = $eventsResult ? $eventsResult->fetch_all(MYSQLI_ASSOC) : [];
            }
            
            // Get assigned people counts for each event
            $eventUsersResult = $conn->query("
                SELECT eu.event_id, eu.user_id, COALESCE(u.display_name, u.username) AS display_name
                FROM event_users eu
                JOIN users u ON eu.user_id = u.id
            ");
            if ($eventUsersResult) {
                while ($row = $eventUsersResult->fetch_assoc()) {
                    $data['eventUsers'][$row['event_id']][] = $row;
                }
            }
            
            $eventFedResult = $conn->query("
                SELECT efc.event_id, efc.contact_id, CONCAT(c.firstName, ' ', c.lastName) AS display_name
                FROM event_federal_contacts efc
                JOIN contacts c ON efc.contact_id = c.id
            ");
            if ($eventFedResult) {
                while ($row = $eventFedResult->fetch_assoc()) {
                    $data['eventFederalContacts'][$row['event_id']][] = $row;
                }
            }
            
            $eventCommResult = $conn->query("
                SELECT ecc.event_id, ecc.contact_id, CONCAT(cc.first_name, ' ', cc.last_name) AS display_name
                FROM event_commercial_contacts ecc
                JOIN company_contacts cc ON ecc.contact_id = cc.id
            ");
            if ($eventCommResult) {
                while ($row = $eventCommResult->fetch_assoc()) {
                    $data['eventCommercialContacts'][$row['event_id']][] = $row;
                }
            }
        }
        
        if (has_permission('task', 'view')) {
            if ($isSpecialty && !empty($specialtyTaskIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyTaskIds), '?'));
                $stmt = $conn->prepare("
                    SELECT t.*, u.username AS assignedToUsername, COALESCE(u.display_name, u.username) AS assignedToDisplayName 
                    FROM tasks t 
                    LEFT JOIN users u ON t.assigned_to_user_id = u.id
                    WHERE t.id IN ($placeholders)
                    ORDER BY t.dueDate ASC
                ");
                $types = str_repeat('i', count($specialtyTaskIds));
                $stmt->bind_param($types, ...$specialtyTaskIds);
                $stmt->execute();
                $data['tasks'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else if ($isSpecialty) {
                $data['tasks'] = [];
            } else {
                $result = $conn->query("
                    SELECT t.*, u.username AS assignedToUsername, COALESCE(u.display_name, u.username) AS assignedToDisplayName 
                    FROM tasks t 
                    LEFT JOIN users u ON t.assigned_to_user_id = u.id
                    ORDER BY t.dueDate ASC
                ");
                $data['tasks'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
        }
        
        // Always include users list for dropdowns (with display_name fallback to username)
        $usersResult = $conn->query("SELECT id, username, COALESCE(display_name, username) AS display_name, first_name, last_name, profile_photo FROM users WHERE approved = 1 ORDER BY COALESCE(display_name, username) ASC");
        $data['users'] = $usersResult ? $usersResult->fetch_all(MYSQLI_ASSOC) : [];
        
        // Include divisions
        $divisionsResult = $conn->query("SELECT * FROM divisions ORDER BY agency_id, name ASC");
        $data['divisions'] = $divisionsResult ? $divisionsResult->fetch_all(MYSQLI_ASSOC) : [];
        
        // Companies and Company Contacts (uses contact permission)
        if (has_permission('contact', 'view')) {
            // Get companies with parent company name
            if ($isSpecialty && !empty($specialtyCompanyIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyCompanyIds), '?'));
                $stmt = $conn->prepare("
                    SELECT c.*, 
                           pc.company_name AS parentCompanyName,
                           (SELECT COUNT(*) FROM company_contacts WHERE company_id = c.id) AS contactCount
                    FROM companies c
                    LEFT JOIN companies pc ON c.parent_company_id = pc.id
                    WHERE c.id IN ($placeholders)
                    ORDER BY c.company_name ASC
                ");
                $types = str_repeat('i', count($specialtyCompanyIds));
                $stmt->bind_param($types, ...$specialtyCompanyIds);
                $stmt->execute();
                $data['companies'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else if ($isSpecialty) {
                $data['companies'] = [];
            } else {
                $result = $conn->query("
                    SELECT c.*, 
                           pc.company_name AS parentCompanyName,
                           (SELECT COUNT(*) FROM company_contacts WHERE company_id = c.id) AS contactCount
                    FROM companies c
                    LEFT JOIN companies pc ON c.parent_company_id = pc.id
                    ORDER BY c.company_name ASC
                ");
                $data['companies'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
            
            // Get small business statuses for all companies
            $sbsResult = $conn->query("SELECT company_id, status_type FROM company_small_business_status");
            $smallBusinessStatuses = [];
            if ($sbsResult) {
                while ($row = $sbsResult->fetch_assoc()) {
                    if (!isset($smallBusinessStatuses[$row['company_id']])) {
                        $smallBusinessStatuses[$row['company_id']] = [];
                    }
                    $smallBusinessStatuses[$row['company_id']][] = $row['status_type'];
                }
            }
            $data['companySmallBusinessStatuses'] = $smallBusinessStatuses;
            
            // Get contract vehicles for all companies
            $vehResult = $conn->query("SELECT company_id, vehicle_type FROM company_vehicles");
            $vehicles = [];
            if ($vehResult) {
                while ($row = $vehResult->fetch_assoc()) {
                    if (!isset($vehicles[$row['company_id']])) {
                        $vehicles[$row['company_id']] = [];
                    }
                    $vehicles[$row['company_id']][] = $row['vehicle_type'];
                }
            }
            $data['companyVehicles'] = $vehicles;
            
            // Get core federal customers for all companies
            $coreResult = $conn->query("
                SELECT ccc.company_id, ccc.agency_id, a.name AS agency_name
                FROM company_core_customers ccc
                LEFT JOIN agencies a ON ccc.agency_id = a.id
            ");
            $coreCustomers = [];
            if ($coreResult) {
                while ($row = $coreResult->fetch_assoc()) {
                    if (!isset($coreCustomers[$row['company_id']])) {
                        $coreCustomers[$row['company_id']] = [];
                    }
                    $coreCustomers[$row['company_id']][] = [
                        'agency_id' => $row['agency_id'],
                        'agency_name' => $row['agency_name']
                    ];
                }
            }
            $data['companyCoreCustomers'] = $coreCustomers;
            
            // Get company contacts with company name and primary owner
            if ($isSpecialty && !empty($specialtyCompanyContactIds)) {
                $placeholders = implode(',', array_fill(0, count($specialtyCompanyContactIds), '?'));
                $stmt = $conn->prepare("
                    SELECT cc.*, 
                           c.company_name AS companyName,
                           u.username AS primaryOwnerUsername,
                           COALESCE(u.display_name, u.username) AS primaryOwnerDisplayName
                    FROM company_contacts cc
                    LEFT JOIN companies c ON cc.company_id = c.id
                    LEFT JOIN users u ON cc.primary_owner_id = u.id
                    WHERE cc.id IN ($placeholders)
                    ORDER BY cc.last_name ASC
                ");
                $types = str_repeat('i', count($specialtyCompanyContactIds));
                $stmt->bind_param($types, ...$specialtyCompanyContactIds);
                $stmt->execute();
                $data['companyContacts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            } else if ($isSpecialty) {
                $data['companyContacts'] = [];
            } else {
                $result = $conn->query("
                    SELECT cc.*, 
                           c.company_name AS companyName,
                           u.username AS primaryOwnerUsername,
                           COALESCE(u.display_name, u.username) AS primaryOwnerDisplayName
                    FROM company_contacts cc
                    LEFT JOIN companies c ON cc.company_id = c.id
                    LEFT JOIN users u ON cc.primary_owner_id = u.id
                    ORDER BY cc.last_name ASC
                ");
                $data['companyContacts'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
            }
            
            // Get secondary owners for all company contacts
            $secOwnerResult = $conn->query("
                SELECT ccso.company_contact_id, ccso.user_id, 
                       u.username, COALESCE(u.display_name, u.username) AS display_name
                FROM company_contact_secondary_owners ccso
                LEFT JOIN users u ON ccso.user_id = u.id
            ");
            $companyContactSecondaryOwners = [];
            if ($secOwnerResult) {
                while ($row = $secOwnerResult->fetch_assoc()) {
                    if (!isset($companyContactSecondaryOwners[$row['company_contact_id']])) {
                        $companyContactSecondaryOwners[$row['company_contact_id']] = [];
                    }
                    $companyContactSecondaryOwners[$row['company_contact_id']][] = [
                        'user_id' => $row['user_id'],
                        'username' => $row['username'],
                        'display_name' => $row['display_name']
                    ];
                }
            }
            $data['companyContactSecondaryOwners'] = $companyContactSecondaryOwners;
            
            // Get agencies supported for all company contacts
            $agencyResult = $conn->query("
                SELECT cca.company_contact_id, cca.agency_id, a.name AS agency_name
                FROM company_contact_agencies cca
                LEFT JOIN agencies a ON cca.agency_id = a.id
            ");
            $contactAgencies = [];
            if ($agencyResult) {
                while ($row = $agencyResult->fetch_assoc()) {
                    if (!isset($contactAgencies[$row['company_contact_id']])) {
                        $contactAgencies[$row['company_contact_id']] = [];
                    }
                    $contactAgencies[$row['company_contact_id']][] = [
                        'agency_id' => $row['agency_id'],
                        'agency_name' => $row['agency_name']
                    ];
                }
            }
            $data['companyContactAgencies'] = $contactAgencies;
        }
        
        // Get contact-opportunity associations (federal contacts)
        $coResult = $conn->query("
            SELECT co.*, o.title AS opportunityTitle, o.status AS opportunityStatus
            FROM contact_opportunities co
            LEFT JOIN opportunities o ON co.opportunity_id = o.id
        ");
        $contactOpportunities = [];
        if ($coResult) {
            while ($row = $coResult->fetch_assoc()) {
                if (!isset($contactOpportunities[$row['contact_id']])) {
                    $contactOpportunities[$row['contact_id']] = [];
                }
                $contactOpportunities[$row['contact_id']][] = $row;
            }
        }
        $data['contactOpportunities'] = $contactOpportunities;
        
        // Get contact-proposal associations (federal contacts)
        $cpResult = $conn->query("
            SELECT cp.*, p.title AS proposalTitle, p.status AS proposalStatus
            FROM contact_proposals cp
            LEFT JOIN proposals p ON cp.proposal_id = p.id
        ");
        $contactProposals = [];
        if ($cpResult) {
            while ($row = $cpResult->fetch_assoc()) {
                if (!isset($contactProposals[$row['contact_id']])) {
                    $contactProposals[$row['contact_id']] = [];
                }
                $contactProposals[$row['contact_id']][] = $row;
            }
        }
        $data['contactProposals'] = $contactProposals;
        
        // Get company contact-opportunity associations
        $ccoResult = $conn->query("
            SELECT cco.*, o.title AS opportunityTitle, o.status AS opportunityStatus
            FROM company_contact_opportunities cco
            LEFT JOIN opportunities o ON cco.opportunity_id = o.id
        ");
        $companyContactOpportunities = [];
        if ($ccoResult) {
            while ($row = $ccoResult->fetch_assoc()) {
                if (!isset($companyContactOpportunities[$row['company_contact_id']])) {
                    $companyContactOpportunities[$row['company_contact_id']] = [];
                }
                $companyContactOpportunities[$row['company_contact_id']][] = $row;
            }
        }
        $data['companyContactOpportunities'] = $companyContactOpportunities;
        
        // Get company contact-proposal associations
        $ccpResult = $conn->query("
            SELECT ccp.*, p.title AS proposalTitle, p.status AS proposalStatus
            FROM company_contact_proposals ccp
            LEFT JOIN proposals p ON ccp.proposal_id = p.id
        ");
        $companyContactProposals = [];
        if ($ccpResult) {
            while ($row = $ccpResult->fetch_assoc()) {
                if (!isset($companyContactProposals[$row['company_contact_id']])) {
                    $companyContactProposals[$row['company_contact_id']] = [];
                }
                $companyContactProposals[$row['company_contact_id']][] = $row;
            }
        }
        $data['companyContactProposals'] = $companyContactProposals;
        
        // Get opportunity contacts (for displaying in opportunity records)
        $ocResult = $conn->query("
            SELECT co.opportunity_id, co.contact_id, co.role, 'federal' AS contact_type,
                   CONCAT(c.firstName, ' ', c.lastName) AS contactName, c.title AS contactTitle
            FROM contact_opportunities co
            LEFT JOIN contacts c ON co.contact_id = c.id
        ");
        $opportunityContacts = [];
        if ($ocResult) {
            while ($row = $ocResult->fetch_assoc()) {
                if (!isset($opportunityContacts[$row['opportunity_id']])) {
                    $opportunityContacts[$row['opportunity_id']] = [];
                }
                $opportunityContacts[$row['opportunity_id']][] = $row;
            }
        }
        // Add company contacts to opportunity contacts
        $occResult = $conn->query("
            SELECT cco.opportunity_id, cco.company_contact_id AS contact_id, cco.role, 'company' AS contact_type,
                   CONCAT(cc.first_name, ' ', cc.last_name) AS contactName, cc.title AS contactTitle
            FROM company_contact_opportunities cco
            LEFT JOIN company_contacts cc ON cco.company_contact_id = cc.id
        ");
        if ($occResult) {
            while ($row = $occResult->fetch_assoc()) {
                if (!isset($opportunityContacts[$row['opportunity_id']])) {
                    $opportunityContacts[$row['opportunity_id']] = [];
                }
                $opportunityContacts[$row['opportunity_id']][] = $row;
            }
        }
        $data['opportunityContacts'] = $opportunityContacts;
        
        // Get proposal contacts (for displaying in proposal records)
        $pcResult = $conn->query("
            SELECT cp.proposal_id, cp.contact_id, cp.role, 'federal' AS contact_type,
                   CONCAT(c.firstName, ' ', c.lastName) AS contactName, c.title AS contactTitle
            FROM contact_proposals cp
            LEFT JOIN contacts c ON cp.contact_id = c.id
        ");
        $proposalContacts = [];
        if ($pcResult) {
            while ($row = $pcResult->fetch_assoc()) {
                if (!isset($proposalContacts[$row['proposal_id']])) {
                    $proposalContacts[$row['proposal_id']] = [];
                }
                $proposalContacts[$row['proposal_id']][] = $row;
            }
        }
        // Add company contacts to proposal contacts
        $pccResult = $conn->query("
            SELECT ccp.proposal_id, ccp.company_contact_id AS contact_id, ccp.role, 'company' AS contact_type,
                   CONCAT(cc.first_name, ' ', cc.last_name) AS contactName, cc.title AS contactTitle
            FROM company_contact_proposals ccp
            LEFT JOIN company_contacts cc ON ccp.company_contact_id = cc.id
        ");
        if ($pccResult) {
            while ($row = $pccResult->fetch_assoc()) {
                if (!isset($proposalContacts[$row['proposal_id']])) {
                    $proposalContacts[$row['proposal_id']] = [];
                }
                $proposalContacts[$row['proposal_id']][] = $row;
            }
        }
        $data['proposalContacts'] = $proposalContacts;
        
        $data['permissions'] = $_SESSION['permissions'];
        $data['currentUserId'] = $_SESSION['user_id'];
        $data['currentUsername'] = $_SESSION['username'];
        $data['currentDisplayName'] = $_SESSION['display_name'] ?? $_SESSION['username'];
        $data['currentRole'] = $_SESSION['role'];
        send_response($data);
        break;

    // ==================== CONTACT DETAIL ENDPOINT ====================
    
    case 'getContactDetail':
        if (!has_permission('contact', 'view')) send_error('Permission denied to view contacts.');
        
        $contact_id = intval($_GET['id'] ?? 0);
        if ($contact_id <= 0) send_error('Invalid contact ID', 400);
        
        // For specialty users, verify they have access to this contact
        if (is_specialty_user()) {
            $allowedContactIds = get_specialty_contact_ids($conn, $_SESSION['user_id']);
            if (!in_array($contact_id, $allowedContactIds)) {
                send_error('Permission denied. You do not have access to this contact.', 403);
            }
        }
        
        $stmt = $conn->prepare("
            SELECT c.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName 
            FROM contacts c 
            LEFT JOIN agencies a ON c.agency_id = a.id 
            LEFT JOIN users u ON c.owner_user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $contact_id);
        $stmt->execute();
        $contact = $stmt->get_result()->fetch_assoc();
        
        if (!$contact) send_error('Contact not found', 404);

        send_response(['contact' => $contact]);
        break;

    // ==================== MY TASKS DASHBOARD ENDPOINTS ====================
    
    case 'getMyTasks':
        if (!has_permission('mytasks', 'view')) send_error('Permission denied to view My Tasks.');
        
        $user_id = $_SESSION['user_id'];
        $view_user_id = intval($_GET['user_id'] ?? $user_id);
        
        // Only admins can view other users' tasks
        if ($view_user_id !== $user_id && $_SESSION['role'] !== 'admin') {
            send_error('Permission denied to view other users\' tasks.');
        }
        
        $data = [
            'tasks' => [],
            'opportunities' => [],
            'proposals' => [],
            'summary' => [
                'tasks' => 0,
                'opportunities' => 0,
                'proposals' => 0
            ]
        ];
        
        // Get open tasks assigned to user (all statuses except Done/Completed)
        $stmt = $conn->prepare("
            SELECT t.*, u.username AS assignedToUsername, COALESCE(u.display_name, u.username) AS assignedToDisplayName
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to_user_id = u.id
            WHERE t.assigned_to_user_id = ? 
            AND t.status NOT IN ('Done', 'Completed')
            ORDER BY 
                CASE t.priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 END,
                t.dueDate ASC
        ");
        $stmt->bind_param("i", $view_user_id);
        $stmt->execute();
        $data['tasks'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $data['summary']['tasks'] = count($data['tasks']);
        
        // Get open opportunities owned by user
        $stmt = $conn->prepare("
            SELECT o.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName
            FROM opportunities o
            LEFT JOIN agencies a ON o.agency_id = a.id
            LEFT JOIN users u ON o.owner_user_id = u.id
            WHERE o.owner_user_id = ?
            AND o.status IN ('Open', 'Qualified')
            ORDER BY 
                CASE o.priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 END,
                o.dueDate ASC
        ");
        $stmt->bind_param("i", $view_user_id);
        $stmt->execute();
        $data['opportunities'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $data['summary']['opportunities'] = count($data['opportunities']);
        
        // Get active proposals owned by user
        $stmt = $conn->prepare("
            SELECT p.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName
            FROM proposals p
            LEFT JOIN agencies a ON p.agency_id = a.id
            LEFT JOIN users u ON p.owner_user_id = u.id
            WHERE p.owner_user_id = ?
            AND p.status IN ('Draft', 'Submitted', 'Under Review')
            ORDER BY p.submitDate ASC
        ");
        $stmt->bind_param("i", $view_user_id);
        $stmt->execute();
        $data['proposals'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $data['summary']['proposals'] = count($data['proposals']);
        
        send_response($data);
        break;

    case 'quickUpdateTask':
        if (!has_permission('mytasks', 'update')) send_error('Permission denied to update tasks.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $task_id = intval($data['id'] ?? 0);
        $new_status = $data['status'] ?? '';
        
        if ($task_id <= 0) send_error('Invalid task ID', 400);
        
        if ($_SESSION['role'] !== 'admin') {
            $stmt = $conn->prepare("SELECT assigned_to_user_id FROM tasks WHERE id = ?");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            $task = $stmt->get_result()->fetch_assoc();
            
            if (!$task || $task['assigned_to_user_id'] != $_SESSION['user_id']) {
                send_error('You can only update your own tasks.');
            }
        }
        
        $allowed_statuses = ['To Do', 'Pending', 'In Progress', 'Review', 'Done', 'Completed'];
        if (!in_array($new_status, $allowed_statuses)) {
            send_error('Invalid status', 400);
        }
        
        // Map old 'Completed' to 'Done' for consistency
        if ($new_status === 'Completed') {
            $new_status = 'Done';
        }
        
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $task_id);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;

    case 'quickUpdateOpportunity':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied to update opportunities.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['id'] ?? 0);
        
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        $updates = [];
        $types = "";
        $values = [];
        
        if (isset($data['status'])) { $updates[] = "status = ?"; $types .= "s"; $values[] = $data['status']; }
        if (isset($data['priority'])) { $updates[] = "priority = ?"; $types .= "s"; $values[] = $data['priority']; }
        if (isset($data['dueDate'])) { $updates[] = "dueDate = ?"; $types .= "s"; $values[] = $data['dueDate']; }
        if (isset($data['description'])) { $updates[] = "description = ?"; $types .= "s"; $values[] = $data['description']; }
        
        if (empty($updates)) send_error('No fields to update', 400);
        
        $types .= "i";
        $values[] = $opp_id;
        
        $sql = "UPDATE opportunities SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;

    case 'quickUpdateProposal':
        if (!has_permission('proposal', 'update')) send_error('Permission denied to update proposals.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $prop_id = intval($data['id'] ?? 0);
        
        if ($prop_id <= 0) send_error('Invalid proposal ID', 400);
        
        $updates = [];
        $types = "";
        $values = [];
        
        if (isset($data['status'])) { $updates[] = "status = ?"; $types .= "s"; $values[] = $data['status']; }
        if (isset($data['winProbability'])) { $updates[] = "winProbability = ?"; $types .= "i"; $values[] = intval($data['winProbability']); }
        if (isset($data['submitDate'])) { $updates[] = "submitDate = ?"; $types .= "s"; $values[] = $data['submitDate']; }
        if (isset($data['description'])) { $updates[] = "description = ?"; $types .= "s"; $values[] = $data['description']; }
        
        if (empty($updates)) send_error('No fields to update', 400);
        
        $types .= "i";
        $values[] = $prop_id;
        
        $sql = "UPDATE proposals SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;

    // ==================== EXISTING CRUD ENDPOINTS ====================

    case 'saveAgency':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('agency', $permission)) send_error('Permission denied to ' . $permission . ' agencies.');
        
        if (empty($data['id'])) {
            // INSERT new agency
            $stmt = $conn->prepare("INSERT INTO agencies (name, type, location, status, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $data['name'], $data['type'], $data['location'], $data['status'], $data['description']);
            $stmt->execute();
            $newId = $conn->insert_id;
            send_response(['success' => true, 'id' => $newId]);
        } else {
            // UPDATE existing agency
            $stmt = $conn->prepare("UPDATE agencies SET name = ?, type = ?, location = ?, status = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $data['name'], $data['type'], $data['location'], $data['status'], $data['description'], $data['id']);
            $stmt->execute();
            send_response(['success' => true, 'id' => $data['id']]);
        }
        break;

    case 'saveContact':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('contact', $permission)) send_error('Permission denied to ' . $permission . ' contacts.');

        $owner_user_id = !empty($data['owner_user_id']) ? intval($data['owner_user_id']) : null;
        $division = !empty($data['division']) ? trim($data['division']) : null;
        $agency_id = !empty($data['agency_id']) ? intval($data['agency_id']) : null;
        
        // If division is provided and agency is selected, check if division exists in divisions table
        // If not, add it (promote to divisions table)
        if ($division && $agency_id) {
            $check_stmt = $conn->prepare("SELECT id FROM divisions WHERE agency_id = ? AND name = ?");
            $check_stmt->bind_param("is", $agency_id, $division);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                // Division doesn't exist, add it to divisions table
                $insert_div_stmt = $conn->prepare("INSERT INTO divisions (agency_id, name) VALUES (?, ?)");
                $insert_div_stmt->bind_param("is", $agency_id, $division);
                $insert_div_stmt->execute();
                $insert_div_stmt->close();
            }
            $check_stmt->close();
        }
        
        if (empty($data['id'])) {
            // INSERT new contact
            $stmt = $conn->prepare("INSERT INTO contacts (firstName, lastName, title, division, agency_id, owner_user_id, email, phone, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiissss", $data['firstName'], $data['lastName'], $data['title'], $division, $agency_id, $owner_user_id, $data['email'], $data['phone'], $data['status'], $data['notes']);
            $stmt->execute();
            $newId = $conn->insert_id;
            send_response(['success' => true, 'id' => $newId]);
        } else {
            // UPDATE existing contact
            $stmt = $conn->prepare("UPDATE contacts SET firstName = ?, lastName = ?, title = ?, division = ?, agency_id = ?, owner_user_id = ?, email = ?, phone = ?, status = ?, notes = ? WHERE id = ?");
            $stmt->bind_param("ssssiissssi", $data['firstName'], $data['lastName'], $data['title'], $division, $agency_id, $owner_user_id, $data['email'], $data['phone'], $data['status'], $data['notes'], $data['id']);
            $stmt->execute();
            send_response(['success' => true, 'id' => $data['id']]);
        }
        break;

    // =============================================
    // COMPANY ENDPOINTS
    // =============================================
    
    case 'saveCompany':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('contact', $permission)) send_error('Permission denied to ' . $permission . ' companies.');

        // Handle optional fields - convert empty/missing to NULL
        $parent_company_id = !empty($data['parent_company_id']) ? intval($data['parent_company_id']) : null;
        $company_name = !empty($data['company_name']) ? trim($data['company_name']) : null;
        $company_type = !empty($data['company_type']) ? $data['company_type'] : null;
        $website = !empty($data['website']) ? $data['website'] : null;
        $description = !empty($data['description']) ? $data['description'] : null;
        $primary_naics_codes = !empty($data['primary_naics_codes']) ? $data['primary_naics_codes'] : null;
        $uei = !empty($data['uei']) ? $data['uei'] : null;
        $cage_code = !empty($data['cage_code']) ? $data['cage_code'] : null;
        $strategic_importance = !empty($data['strategic_importance']) ? $data['strategic_importance'] : null;
        $competitive_posture = !empty($data['competitive_posture']) ? $data['competitive_posture'] : null;
        $status = !empty($data['status']) ? $data['status'] : 'Active';
        
        if (!$company_name) send_error('Company name is required', 400);
        
        $conn->begin_transaction();
        
        try {
            if (!empty($data['id'])) {
                // Update existing company
                $stmt = $conn->prepare("
                    UPDATE companies SET 
                        company_name = ?, company_type = ?, parent_company_id = ?, website = ?, description = ?,
                        primary_naics_codes = ?, uei = ?, cage_code = ?,
                        strategic_importance = ?, competitive_posture = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("ssissssssssi",
                    $company_name, $company_type, $parent_company_id, $website, $description,
                    $primary_naics_codes, $uei, $cage_code,
                    $strategic_importance, $competitive_posture, $status, $data['id']
                );
                $stmt->execute();
                $company_id = $data['id'];
            } else {
                // Insert new company
                $stmt = $conn->prepare("
                    INSERT INTO companies 
                    (company_name, company_type, parent_company_id, website, description, 
                     primary_naics_codes, uei, cage_code,
                     strategic_importance, competitive_posture, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssissssssss",
                    $company_name, $company_type, $parent_company_id, $website, $description,
                    $primary_naics_codes, $uei, $cage_code,
                    $strategic_importance, $competitive_posture, $status
                );
                $stmt->execute();
                $company_id = $conn->insert_id;
            }
            
            // Update small business statuses (delete and re-insert)
            $stmt = $conn->prepare("DELETE FROM company_small_business_status WHERE company_id = ?");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            
            if (!empty($data['small_business_statuses']) && is_array($data['small_business_statuses'])) {
                $stmt = $conn->prepare("INSERT INTO company_small_business_status (company_id, status_type) VALUES (?, ?)");
                foreach ($data['small_business_statuses'] as $status) {
                    $stmt->bind_param("is", $company_id, $status);
                    $stmt->execute();
                }
            }
            
            // Update contract vehicles (delete and re-insert)
            $stmt = $conn->prepare("DELETE FROM company_vehicles WHERE company_id = ?");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            
            if (!empty($data['vehicles']) && is_array($data['vehicles'])) {
                $stmt = $conn->prepare("INSERT INTO company_vehicles (company_id, vehicle_type) VALUES (?, ?)");
                foreach ($data['vehicles'] as $vehicle) {
                    $stmt->bind_param("is", $company_id, $vehicle);
                    $stmt->execute();
                }
            }
            
            // Update core federal customers (delete and re-insert)
            $stmt = $conn->prepare("DELETE FROM company_core_customers WHERE company_id = ?");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            
            if (!empty($data['core_customers']) && is_array($data['core_customers'])) {
                $stmt = $conn->prepare("INSERT INTO company_core_customers (company_id, agency_id) VALUES (?, ?)");
                foreach ($data['core_customers'] as $agency_id) {
                    $agency_id_int = intval($agency_id);
                    $stmt->bind_param("ii", $company_id, $agency_id_int);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            send_response(['success' => true, 'id' => $company_id]);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to save company: ' . $e->getMessage(), 500);
        }
        break;

    case 'getCompanyDetail':
        if (!has_permission('contact', 'view')) send_error('Permission denied to view companies.');
        
        $company_id = intval($_GET['id'] ?? 0);
        if ($company_id <= 0) send_error('Invalid company ID', 400);
        
        $stmt = $conn->prepare("
            SELECT c.*, pc.company_name AS parentCompanyName
            FROM companies c
            LEFT JOIN companies pc ON c.parent_company_id = pc.id
            WHERE c.id = ?
        ");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $company = $stmt->get_result()->fetch_assoc();
        
        if (!$company) send_error('Company not found', 404);
        
        // Get small business statuses
        $stmt = $conn->prepare("SELECT status_type FROM company_small_business_status WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $company['small_business_statuses'] = [];
        while ($row = $result->fetch_assoc()) {
            $company['small_business_statuses'][] = $row['status_type'];
        }
        
        // Get vehicles
        $stmt = $conn->prepare("SELECT vehicle_type FROM company_vehicles WHERE company_id = ?");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $company['vehicles'] = [];
        while ($row = $result->fetch_assoc()) {
            $company['vehicles'][] = $row['vehicle_type'];
        }
        
        // Get core federal customers
        $stmt = $conn->prepare("
            SELECT ccc.agency_id, a.name AS agency_name
            FROM company_core_customers ccc
            LEFT JOIN agencies a ON ccc.agency_id = a.id
            WHERE ccc.company_id = ?
        ");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $company['core_customers'] = [];
        while ($row = $result->fetch_assoc()) {
            $company['core_customers'][] = $row;
        }
        
        // Get contacts for this company
        $stmt = $conn->prepare("SELECT * FROM company_contacts WHERE company_id = ? ORDER BY last_name ASC");
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $company['contacts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'company' => $company]);
        break;

    // =============================================
    // COMPANY CONTACT ENDPOINTS
    // =============================================
    
    case 'saveCompanyContact':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('contact', $permission)) send_error('Permission denied to ' . $permission . ' company contacts.');

        $company_id = !empty($data['company_id']) ? intval($data['company_id']) : null;
        if (!$company_id) send_error('Company is required', 400);
        
        // Handle ENUM fields - convert empty strings to NULL
        $functional_role = !empty($data['functional_role']) ? $data['functional_role'] : null;
        $capture_role = !empty($data['capture_role']) ? $data['capture_role'] : null;
        $title = !empty($data['title']) ? $data['title'] : null;
        $email = !empty($data['email']) ? $data['email'] : null;
        $phone = !empty($data['phone']) ? $data['phone'] : null;
        $notes = !empty($data['notes']) ? $data['notes'] : null;
        $status = !empty($data['status']) ? $data['status'] : 'Active';
        $primary_owner_id = !empty($data['primary_owner_id']) ? intval($data['primary_owner_id']) : null;
        
        $conn->begin_transaction();
        
        try {
            if (!empty($data['id'])) {
                // Update existing contact
                $contact_id = intval($data['id']);
                $stmt = $conn->prepare("
                    UPDATE company_contacts SET 
                        first_name = ?, last_name = ?, title = ?, functional_role = ?, company_id = ?,
                        capture_role = ?, email = ?, phone = ?, status = ?, notes = ?, primary_owner_id = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("ssssisssssii",
                    $data['first_name'], $data['last_name'], $title, $functional_role, $company_id,
                    $capture_role, $email, $phone, $status, $notes, $primary_owner_id, $contact_id
                );
                $stmt->execute();
            } else {
                // Insert new contact
                $stmt = $conn->prepare("
                    INSERT INTO company_contacts 
                    (first_name, last_name, title, functional_role, company_id, capture_role, email, phone, status, notes, primary_owner_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssssisssssi",
                    $data['first_name'], $data['last_name'], $title, $functional_role, $company_id,
                    $capture_role, $email, $phone, $status, $notes, $primary_owner_id
                );
                $stmt->execute();
                $contact_id = $conn->insert_id;
            }
            
            // Update secondary owners (delete and re-insert)
            $stmt = $conn->prepare("DELETE FROM company_contact_secondary_owners WHERE company_contact_id = ?");
            $stmt->bind_param("i", $contact_id);
            $stmt->execute();
            
            if (!empty($data['secondary_owner_ids']) && is_array($data['secondary_owner_ids'])) {
                $stmt = $conn->prepare("INSERT INTO company_contact_secondary_owners (company_contact_id, user_id) VALUES (?, ?)");
                foreach ($data['secondary_owner_ids'] as $user_id) {
                    $user_id_int = intval($user_id);
                    $stmt->bind_param("ii", $contact_id, $user_id_int);
                    $stmt->execute();
                }
            }
            
            // Update agencies supported (delete and re-insert)
            $stmt = $conn->prepare("DELETE FROM company_contact_agencies WHERE company_contact_id = ?");
            $stmt->bind_param("i", $contact_id);
            $stmt->execute();
            
            if (!empty($data['agencies_supported']) && is_array($data['agencies_supported'])) {
                $stmt = $conn->prepare("INSERT INTO company_contact_agencies (company_contact_id, agency_id) VALUES (?, ?)");
                foreach ($data['agencies_supported'] as $agency_id) {
                    $agency_id_int = intval($agency_id);
                    $stmt->bind_param("ii", $contact_id, $agency_id_int);
                    $stmt->execute();
                }
            }
            
            $conn->commit();
            send_response(['success' => true, 'id' => $contact_id]);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to save company contact: ' . $e->getMessage(), 500);
        }
        break;

    case 'getCompanyContactDetail':
        if (!has_permission('contact', 'view')) send_error('Permission denied to view company contacts.');
        
        $contact_id = intval($_GET['id'] ?? 0);
        if ($contact_id <= 0) send_error('Invalid contact ID', 400);
        
        $stmt = $conn->prepare("
            SELECT cc.*, c.company_name AS companyName
            FROM company_contacts cc
            LEFT JOIN companies c ON cc.company_id = c.id
            WHERE cc.id = ?
        ");
        $stmt->bind_param("i", $contact_id);
        $stmt->execute();
        $contact = $stmt->get_result()->fetch_assoc();
        
        if (!$contact) send_error('Company contact not found', 404);
        
        // Get agencies supported
        $stmt = $conn->prepare("
            SELECT cca.agency_id, a.name AS agency_name
            FROM company_contact_agencies cca
            LEFT JOIN agencies a ON cca.agency_id = a.id
            WHERE cca.company_contact_id = ?
        ");
        $stmt->bind_param("i", $contact_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $contact['agencies_supported'] = [];
        while ($row = $result->fetch_assoc()) {
            $contact['agencies_supported'][] = $row;
        }
        
        send_response(['success' => true, 'contact' => $contact]);
        break;

    // =============================================
    // COMPANY CONTACT NOTES ENDPOINTS
    // =============================================
    
    case 'getCompanyContactNotes':
        if (!has_permission('contact', 'view')) send_error('Permission denied to view company contacts.');
        
        $contact_id = intval($_GET['contact_id'] ?? 0);
        if ($contact_id <= 0) send_error('Invalid contact ID', 400);
        
        $stmt = $conn->prepare("
            SELECT ccn.*, u.username AS createdByUsername, COALESCE(u.display_name, u.username) AS createdByDisplayName
            FROM company_contact_notes ccn
            LEFT JOIN users u ON ccn.created_by = u.id
            WHERE ccn.company_contact_id = ?
            ORDER BY ccn.created_at DESC
        ");
        $stmt->bind_param("i", $contact_id);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'notes' => $notes]);
        break;

    case 'saveCompanyContactNote':
        if (!has_permission('contact', 'create')) send_error('Permission denied to add notes.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $contact_id = intval($data['company_contact_id'] ?? 0);
        if ($contact_id <= 0) send_error('Invalid contact ID', 400);
        
        if (empty($data['note_text'])) send_error('Note text is required', 400);
        
        $note_type = $data['note_type'] ?? 'General';
        $user_id = $_SESSION['user_id'];
        
        if (!empty($data['id'])) {
            // Update existing note
            $stmt = $conn->prepare("UPDATE company_contact_notes SET note_text = ?, note_type = ? WHERE id = ? AND company_contact_id = ?");
            $stmt->bind_param("ssii", $data['note_text'], $note_type, $data['id'], $contact_id);
        } else {
            // Insert new note
            $stmt = $conn->prepare("INSERT INTO company_contact_notes (company_contact_id, note_text, note_type, created_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $contact_id, $data['note_text'], $note_type, $user_id);
        }
        
        $stmt->execute();
        send_response(['success' => true, 'id' => $data['id'] ?? $conn->insert_id]);
        break;

    case 'deleteCompanyContactNote':
        if (!has_permission('contact', 'delete')) send_error('Permission denied to delete notes.');
        
        $note_id = intval($_GET['id'] ?? 0);
        if ($note_id <= 0) send_error('Invalid note ID', 400);
        
        $stmt = $conn->prepare("DELETE FROM company_contact_notes WHERE id = ?");
        $stmt->bind_param("i", $note_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;

    // ==================== CONTACT-OPPORTUNITY ASSOCIATIONS ====================
    
    case 'saveContactOpportunity':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $contact_id = intval($data['contact_id'] ?? 0);
        $opportunity_id = intval($data['opportunity_id'] ?? 0);
        $role = trim($data['role'] ?? '');
        
        if ($contact_id <= 0 || $opportunity_id <= 0) send_error('Invalid contact or opportunity ID', 400);
        
        $stmt = $conn->prepare("INSERT INTO contact_opportunities (contact_id, opportunity_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = VALUES(role)");
        $stmt->bind_param("iis", $contact_id, $opportunity_id, $role);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'deleteContactOpportunity':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $contact_id = intval($_GET['contact_id'] ?? 0);
        $opportunity_id = intval($_GET['opportunity_id'] ?? 0);
        
        if ($contact_id <= 0 || $opportunity_id <= 0) send_error('Invalid contact or opportunity ID', 400);
        
        $stmt = $conn->prepare("DELETE FROM contact_opportunities WHERE contact_id = ? AND opportunity_id = ?");
        $stmt->bind_param("ii", $contact_id, $opportunity_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;
    
    // ==================== CONTACT-PROPOSAL ASSOCIATIONS ====================
    
    case 'saveContactProposal':
        if (!has_permission('proposal', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $contact_id = intval($data['contact_id'] ?? 0);
        $proposal_id = intval($data['proposal_id'] ?? 0);
        $role = trim($data['role'] ?? '');
        
        if ($contact_id <= 0 || $proposal_id <= 0) send_error('Invalid contact or proposal ID', 400);
        
        $stmt = $conn->prepare("INSERT INTO contact_proposals (contact_id, proposal_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = VALUES(role)");
        $stmt->bind_param("iis", $contact_id, $proposal_id, $role);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'deleteContactProposal':
        if (!has_permission('proposal', 'update')) send_error('Permission denied.');
        
        $contact_id = intval($_GET['contact_id'] ?? 0);
        $proposal_id = intval($_GET['proposal_id'] ?? 0);
        
        if ($contact_id <= 0 || $proposal_id <= 0) send_error('Invalid contact or proposal ID', 400);
        
        $stmt = $conn->prepare("DELETE FROM contact_proposals WHERE contact_id = ? AND proposal_id = ?");
        $stmt->bind_param("ii", $contact_id, $proposal_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;
    
    // ==================== COMPANY CONTACT-OPPORTUNITY ASSOCIATIONS ====================
    
    case 'saveCompanyContactOpportunity':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $company_contact_id = intval($data['company_contact_id'] ?? 0);
        $opportunity_id = intval($data['opportunity_id'] ?? 0);
        $role = trim($data['role'] ?? '');
        
        if ($company_contact_id <= 0 || $opportunity_id <= 0) send_error('Invalid contact or opportunity ID', 400);
        
        $stmt = $conn->prepare("INSERT INTO company_contact_opportunities (company_contact_id, opportunity_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = VALUES(role)");
        $stmt->bind_param("iis", $company_contact_id, $opportunity_id, $role);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'deleteCompanyContactOpportunity':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $company_contact_id = intval($_GET['company_contact_id'] ?? 0);
        $opportunity_id = intval($_GET['opportunity_id'] ?? 0);
        
        if ($company_contact_id <= 0 || $opportunity_id <= 0) send_error('Invalid contact or opportunity ID', 400);
        
        $stmt = $conn->prepare("DELETE FROM company_contact_opportunities WHERE company_contact_id = ? AND opportunity_id = ?");
        $stmt->bind_param("ii", $company_contact_id, $opportunity_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;
    
    // ==================== COMPANY CONTACT-PROPOSAL ASSOCIATIONS ====================
    
    case 'saveCompanyContactProposal':
        if (!has_permission('proposal', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $company_contact_id = intval($data['company_contact_id'] ?? 0);
        $proposal_id = intval($data['proposal_id'] ?? 0);
        $role = trim($data['role'] ?? '');
        
        if ($company_contact_id <= 0 || $proposal_id <= 0) send_error('Invalid contact or proposal ID', 400);
        
        $stmt = $conn->prepare("INSERT INTO company_contact_proposals (company_contact_id, proposal_id, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE role = VALUES(role)");
        $stmt->bind_param("iis", $company_contact_id, $proposal_id, $role);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'deleteCompanyContactProposal':
        if (!has_permission('proposal', 'update')) send_error('Permission denied.');
        
        $company_contact_id = intval($_GET['company_contact_id'] ?? 0);
        $proposal_id = intval($_GET['proposal_id'] ?? 0);
        
        if ($company_contact_id <= 0 || $proposal_id <= 0) send_error('Invalid contact or proposal ID', 400);
        
        $stmt = $conn->prepare("DELETE FROM company_contact_proposals WHERE company_contact_id = ? AND proposal_id = ?");
        $stmt->bind_param("ii", $company_contact_id, $proposal_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;

    case 'saveOpportunity':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('opportunity', $permission)) send_error('Permission denied to ' . $permission . ' opportunities.');

        $owner_user_id = !empty($data['owner_user_id']) ? intval($data['owner_user_id']) : null;
        $division = !empty($data['division']) ? trim($data['division']) : null;
        $agency_id = !empty($data['agency_id']) ? intval($data['agency_id']) : null;
        $co_owner_contact_id = !empty($data['co_owner_contact_id']) ? intval($data['co_owner_contact_id']) : null;
        $co_owner_contact_type = !empty($data['co_owner_contact_type']) ? $data['co_owner_contact_type'] : null;
        
        // If division is provided and agency is selected, check if division exists in divisions table
        // If not, add it (promote to divisions table)
        if ($division && $agency_id) {
            $check_stmt = $conn->prepare("SELECT id FROM divisions WHERE agency_id = ? AND name = ?");
            $check_stmt->bind_param("is", $agency_id, $division);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                // Division doesn't exist, add it to divisions table
                $insert_div_stmt = $conn->prepare("INSERT INTO divisions (agency_id, name) VALUES (?, ?)");
                $insert_div_stmt->bind_param("is", $agency_id, $division);
                $insert_div_stmt->execute();
                $insert_div_stmt->close();
            }
            $check_stmt->close();
        }
        
        // Use UPDATE for existing records to preserve related data (documents, notes, etc.)
        if (!empty($data['id'])) {
            $stmt = $conn->prepare("UPDATE opportunities SET title=?, agency_id=?, division=?, owner_user_id=?, co_owner_contact_id=?, co_owner_contact_type=?, value=?, status=?, dueDate=?, priority=?, description=? WHERE id=?");
            $stmt->bind_param("sisiissssssi", $data['title'], $agency_id, $division, $owner_user_id, $co_owner_contact_id, $co_owner_contact_type, $data['value'], $data['status'], $data['dueDate'], $data['priority'], $data['description'], $data['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO opportunities (title, agency_id, division, owner_user_id, co_owner_contact_id, co_owner_contact_type, value, status, dueDate, priority, description) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sisiissssss", $data['title'], $agency_id, $division, $owner_user_id, $co_owner_contact_id, $co_owner_contact_type, $data['value'], $data['status'], $data['dueDate'], $data['priority'], $data['description']);
        }
        $stmt->execute();
        send_response(['success' => true, 'id' => $data['id'] ?: $conn->insert_id]);
        break;

    case 'saveProposal':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('proposal', $permission)) send_error('Permission denied to ' . $permission . ' proposals.');

        $owner_user_id = !empty($data['owner_user_id']) ? intval($data['owner_user_id']) : null;
        $submitDate = !empty($data['submitDate']) ? $data['submitDate'] : null;
        $dueDate = !empty($data['dueDate']) ? $data['dueDate'] : null;
        $validityDate = !empty($data['validityDate']) ? $data['validityDate'] : null;
        $awardDate = !empty($data['awardDate']) ? $data['awardDate'] : null;
        $winProbability = intval($data['winProbability'] ?? 0);
        
        // Use UPDATE for existing records to preserve converted_from_opportunity_id
        if (!empty($data['id'])) {
            $stmt = $conn->prepare("UPDATE proposals SET title=?, agency_id=?, owner_user_id=?, value=?, status=?, submitDate=?, dueDate=?, validity_date=?, award_date=?, winProbability=?, description=? WHERE id=?");
            $stmt->bind_param("siidsssssisi", $data['title'], $data['agency_id'], $owner_user_id, $data['value'], $data['status'], $submitDate, $dueDate, $validityDate, $awardDate, $winProbability, $data['description'], $data['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO proposals (title, agency_id, owner_user_id, value, status, submitDate, dueDate, validity_date, award_date, winProbability, description) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("siidsssssis", $data['title'], $data['agency_id'], $owner_user_id, $data['value'], $data['status'], $submitDate, $dueDate, $validityDate, $awardDate, $winProbability, $data['description']);
        }
        $stmt->execute();
        send_response(['success' => true, 'id' => $data['id'] ?: $conn->insert_id]);
        break;

    case 'saveTask':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('task', $permission)) send_error('Permission denied to ' . $permission . ' tasks.');

        $assigned_to_user_id = !empty($data['assigned_to_user_id']) ? intval($data['assigned_to_user_id']) : null;
        $related_item_id = !empty($data['related_item_id']) ? intval($data['related_item_id']) : null;
        $related_contact_type = $data['related_contact_type'] ?? null;
        $assignedTo = $data['assignedTo'] ?? '';
        $workspace_phase = !empty($data['workspace_phase']) ? $data['workspace_phase'] : null;

        // Use UPDATE for existing records, INSERT for new
        if (!empty($data['id'])) {
            $stmt = $conn->prepare("UPDATE tasks SET title=?, relatedTo=?, related_item_id=?, related_contact_type=?, dueDate=?, priority=?, status=?, assignedTo=?, assigned_to_user_id=?, description=?, workspace_phase=? WHERE id=?");
            $stmt->bind_param("ssisssssissi", $data['title'], $data['relatedTo'], $related_item_id, $related_contact_type, $data['dueDate'], $data['priority'], $data['status'], $assignedTo, $assigned_to_user_id, $data['description'], $workspace_phase, $data['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO tasks (title, relatedTo, related_item_id, related_contact_type, dueDate, priority, status, assignedTo, assigned_to_user_id, description, workspace_phase) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssisssssiss", $data['title'], $data['relatedTo'], $related_item_id, $related_contact_type, $data['dueDate'], $data['priority'], $data['status'], $assignedTo, $assigned_to_user_id, $data['description'], $workspace_phase);
        }
        $stmt->execute();
        send_response(['success' => true, 'id' => $data['id'] ?: $conn->insert_id]);
        break;

    case 'linkTaskToEvent':
        if (!has_permission('task', 'update')) send_error('Permission denied to update tasks.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $task_id = intval($data['task_id'] ?? 0);
        $event_id = intval($data['event_id'] ?? 0);
        
        if ($task_id <= 0 || $event_id <= 0) {
            send_error('Invalid task or event ID', 400);
        }
        
        $stmt = $conn->prepare("UPDATE tasks SET relatedTo = 'Event', related_item_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $event_id, $task_id);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;

    case 'unlinkTask':
        if (!has_permission('task', 'update')) send_error('Permission denied to update tasks.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $task_id = intval($data['task_id'] ?? 0);
        
        if ($task_id <= 0) {
            send_error('Invalid task ID', 400);
        }
        
        $stmt = $conn->prepare("UPDATE tasks SET relatedTo = '', related_item_id = NULL WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;

    case 'delete':
        $id   = intval($_GET['id'] ?? 0);
        $type = $_GET['type'] ?? '';
        
        // Map types to their permission resource
        $permission_map = [
            'agency' => 'agency',
            'contact' => 'contact',
            'opportunity' => 'opportunity',
            'proposal' => 'proposal',
            'task' => 'task',
            'company' => 'contact',  // Companies use contact permission
            'company_contact' => 'contact'  // Company contacts use contact permission
        ];
        
        $permission_resource = $permission_map[$type] ?? $type;
        if (!has_permission($permission_resource, 'delete')) send_error('Permission denied to delete ' . $type . 's.');
        
        $table_map = [
            'agency'=>'agencies', 
            'contact'=>'contacts', 
            'opportunity'=>'opportunities', 
            'proposal'=>'proposals', 
            'task'=>'tasks',
            'company'=>'companies',
            'company_contact'=>'company_contacts'
        ];
        if (isset($table_map[$type]) && $id > 0) {
            $table = $table_map[$type];
            $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            send_response(['success' => $stmt->affected_rows > 0]);
        } else {
            send_response(['success' => false, 'error' => 'Invalid delete request']);
        }
        break;

    // =============================================
    // CONTACT NOTES ENDPOINTS
    // =============================================
    
    case 'getContactNotes':
        if (!has_permission('contact', 'view')) send_error('Permission denied to view contacts.');
        
        $contact_id = intval($_GET['contact_id'] ?? 0);
        if ($contact_id <= 0) send_error('Invalid contact ID', 400);
        
        // Build query with optional filters
        $where_clauses = ["cn.contact_id = ?"];
        $params = [$contact_id];
        $types = "i";
        
        // Filter by user
        if (!empty($_GET['filter_user_id'])) {
            $where_clauses[] = "cn.user_id = ?";
            $params[] = intval($_GET['filter_user_id']);
            $types .= "i";
        }
        
        // Filter by interaction type (partial match)
        if (!empty($_GET['filter_interaction_type'])) {
            $where_clauses[] = "cn.interaction_type LIKE ?";
            $params[] = "%" . $_GET['filter_interaction_type'] . "%";
            $types .= "s";
        }
        
        // Filter by date range
        if (!empty($_GET['filter_date_from'])) {
            $where_clauses[] = "DATE(cn.created_at) >= ?";
            $params[] = $_GET['filter_date_from'];
            $types .= "s";
        }
        if (!empty($_GET['filter_date_to'])) {
            $where_clauses[] = "DATE(cn.created_at) <= ?";
            $params[] = $_GET['filter_date_to'];
            $types .= "s";
        }
        
        $where_sql = implode(" AND ", $where_clauses);

        $sql = "
            SELECT cn.*,
                   u.username AS createdByUsername
            FROM contact_notes cn
            LEFT JOIN users u ON cn.user_id = u.id
            WHERE {$where_sql}
            ORDER BY cn.created_at DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Format dates for display
        foreach ($notes as &$note) {
            $note['displayDate'] = date('m/d/Y', strtotime($note['created_at']));
            $note['canEdit'] = ($note['user_id'] == $_SESSION['user_id']);
            $note['canDelete'] = ($note['user_id'] == $_SESSION['user_id']);
        }
        
        send_response(['success' => true, 'notes' => $notes]);
        break;
    
    case 'saveContactNote':
        if (!has_permission('contact', 'view')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['contact_id'])) send_error('Contact ID is required', 400);
        if (empty($data['note_text'])) send_error('Note text is required', 400);
        
        $note_id = !empty($data['id']) ? intval($data['id']) : null;
        $contact_id = intval($data['contact_id']);
        $note_date = !empty($data['note_date']) ? $data['note_date'] : null;
        $interaction_type = $data['interaction_type'] ?? '';
        $note_text = $data['note_text'];
        $user_id = $_SESSION['user_id'];

        if ($note_id) {
            // Update existing note - check ownership
            $check_stmt = $conn->prepare("SELECT user_id FROM contact_notes WHERE id = ?");
            $check_stmt->bind_param("i", $note_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();

            if (!$existing) send_error('Note not found', 404);
            if ($existing['user_id'] != $_SESSION['user_id']) send_error('You can only edit your own notes', 403);

            $stmt = $conn->prepare("UPDATE contact_notes SET note_date = ?, interaction_type = ?, note_text = ? WHERE id = ?");
            $stmt->bind_param("sssi", $note_date, $interaction_type, $note_text, $note_id);
        } else {
            // Insert new note
            $stmt = $conn->prepare("INSERT INTO contact_notes (contact_id, note_date, user_id, interaction_type, note_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $contact_id, $note_date, $user_id, $interaction_type, $note_text);
        }
        
        if ($stmt->execute()) {
            $new_id = $note_id ?: $conn->insert_id;
            send_response(['success' => true, 'id' => $new_id]);
        } else {
            send_error('Failed to save note', 500);
        }
        break;
    
    case 'deleteContactNote':
        if (!has_permission('contact', 'view')) send_error('Permission denied.');
        
        $note_id = intval($_GET['id'] ?? 0);
        if ($note_id <= 0) send_error('Invalid note ID', 400);
        
        // Check ownership
        $check_stmt = $conn->prepare("SELECT user_id FROM contact_notes WHERE id = ?");
        $check_stmt->bind_param("i", $note_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if (!$existing) send_error('Note not found', 404);
        if ($existing['user_id'] != $_SESSION['user_id']) send_error('You can only delete your own notes', 403);
        
        $stmt = $conn->prepare("DELETE FROM contact_notes WHERE id = ?");
        $stmt->bind_param("i", $note_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;

    // =============================================
    // DIVISION ENDPOINTS
    // =============================================
    
    case 'getDivisionsByAgency':
        $agency_id = intval($_GET['agency_id'] ?? 0);
        if ($agency_id <= 0) send_error('Invalid agency ID', 400);
        
        // Get divisions from divisions table for this agency
        $stmt = $conn->prepare("SELECT id, name FROM divisions WHERE agency_id = ? ORDER BY name ASC");
        $stmt->bind_param("i", $agency_id);
        $stmt->execute();
        $divisions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'divisions' => $divisions]);
        break;
    
    case 'saveDivision':
        // Check division create permission
        if (!has_permission('division', 'create')) send_error('Permission denied to add divisions', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['agency_id'])) send_error('Agency ID is required', 400);
        if (empty($data['name'])) send_error('Division name is required', 400);
        
        $agency_id = intval($data['agency_id']);
        $name = trim($data['name']);
        
        // Check if division already exists for this agency
        $check_stmt = $conn->prepare("SELECT id FROM divisions WHERE agency_id = ? AND name = ?");
        $check_stmt->bind_param("is", $agency_id, $name);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            send_error('A division with this name already exists for this agency', 400);
        }
        
        $stmt = $conn->prepare("INSERT INTO divisions (agency_id, name) VALUES (?, ?)");
        $stmt->bind_param("is", $agency_id, $name);
        
        if ($stmt->execute()) {
            send_response(['success' => true, 'id' => $conn->insert_id]);
        } else {
            send_error('Failed to save division', 500);
        }
        break;
    
    case 'deleteDivision':
        // Check division delete permission
        if (!has_permission('division', 'delete')) send_error('Permission denied to delete divisions', 403);
        
        $division_id = intval($_GET['id'] ?? 0);
        if ($division_id <= 0) send_error('Invalid division ID', 400);
        
        $stmt = $conn->prepare("DELETE FROM divisions WHERE id = ?");
        $stmt->bind_param("i", $division_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;

    // =============================================
    // OPPORTUNITY DETAILS & ASSIGNMENTS
    // =============================================
    
    case 'getOpportunityDetails':
        if (!has_permission('opportunity', 'view')) send_error('Permission denied.');
        
        $opp_id = intval($_GET['id'] ?? 0);
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // For specialty users, verify they have access to this opportunity
        if (is_specialty_user()) {
            $allowedOppIds = get_specialty_opportunity_ids($conn, $_SESSION['user_id']);
            if (!in_array($opp_id, $allowedOppIds)) {
                send_error('Permission denied. You do not have access to this opportunity.', 403);
            }
        }
        
        // Get opportunity details
        $stmt = $conn->prepare("
            SELECT o.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName
            FROM opportunities o
            LEFT JOIN agencies a ON o.agency_id = a.id
            LEFT JOIN users u ON o.owner_user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $opp_id);
        $stmt->execute();
        $opportunity = $stmt->get_result()->fetch_assoc();
        
        if (!$opportunity) send_error('Opportunity not found', 404);
        
        // Get co-owner display name if set
        $opportunity['coOwnerDisplayName'] = null;
        if (!empty($opportunity['co_owner_contact_id']) && !empty($opportunity['co_owner_contact_type'])) {
            $coOwnerId = intval($opportunity['co_owner_contact_id']);
            if ($opportunity['co_owner_contact_type'] === 'federal') {
                $co_stmt = $conn->prepare("SELECT CONCAT(firstName, ' ', lastName) AS name FROM contacts WHERE id = ?");
                $co_stmt->bind_param("i", $coOwnerId);
                $co_stmt->execute();
                $co_result = $co_stmt->get_result()->fetch_assoc();
                $opportunity['coOwnerDisplayName'] = $co_result ? $co_result['name'] : null;
            } else if ($opportunity['co_owner_contact_type'] === 'commercial') {
                $co_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM company_contacts WHERE id = ?");
                $co_stmt->bind_param("i", $coOwnerId);
                $co_stmt->execute();
                $co_result = $co_stmt->get_result()->fetch_assoc();
                $opportunity['coOwnerDisplayName'] = $co_result ? $co_result['name'] : null;
            } else if ($opportunity['co_owner_contact_type'] === 'user') {
                $co_stmt = $conn->prepare("SELECT COALESCE(display_name, username) AS name FROM users WHERE id = ?");
                $co_stmt->bind_param("i", $coOwnerId);
                $co_stmt->execute();
                $co_result = $co_stmt->get_result()->fetch_assoc();
                $opportunity['coOwnerDisplayName'] = $co_result ? $co_result['name'] : null;
            }
        }
        
        // Get assigned users
        $users_stmt = $conn->prepare("
            SELECT ou.*, u.username, COALESCE(u.display_name, u.username) AS display_name
            FROM opportunity_users ou
            JOIN users u ON ou.user_id = u.id
            WHERE ou.opportunity_id = ?
            ORDER BY COALESCE(u.display_name, u.username) ASC
        ");
        $users_stmt->bind_param("i", $opp_id);
        $users_stmt->execute();
        $opportunity['assignedUsers'] = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get assigned federal contacts
        $federal_stmt = $conn->prepare("
            SELECT oc.*, 'federal' as contact_type, c.firstName, c.lastName, c.email, c.title, a.name AS agencyName
            FROM opportunity_contacts oc
            JOIN contacts c ON oc.contact_id = c.id
            LEFT JOIN agencies a ON c.agency_id = a.id
            WHERE oc.opportunity_id = ? AND (oc.contact_type = 'federal' OR oc.contact_type IS NULL)
            ORDER BY c.lastName ASC
        ");
        $federal_stmt->bind_param("i", $opp_id);
        $federal_stmt->execute();
        $federalContacts = $federal_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get assigned commercial contacts  
        $commercial_stmt = $conn->prepare("
            SELECT oc.*, 'commercial' as contact_type, cc.first_name AS firstName, cc.last_name AS lastName, cc.email, cc.title, co.company_name AS companyName
            FROM opportunity_contacts oc
            JOIN company_contacts cc ON oc.contact_id = cc.id
            LEFT JOIN companies co ON cc.company_id = co.id
            WHERE oc.opportunity_id = ? AND oc.contact_type = 'commercial'
            ORDER BY cc.last_name ASC
        ");
        $commercial_stmt->bind_param("i", $opp_id);
        $commercial_stmt->execute();
        $commercialContacts = $commercial_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Merge both contact types
        $opportunity['assignedContacts'] = array_merge($federalContacts, $commercialContacts);
        
        send_response(['success' => true, 'opportunity' => $opportunity]);
        break;
    
    case 'saveOpportunityAssignments':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        $assigned_by = $_SESSION['user_id'];
        
        // Update assigned users
        if (isset($data['user_ids'])) {
            // Remove existing assignments
            $conn->query("DELETE FROM opportunity_users WHERE opportunity_id = $opp_id");
            
            // Add new assignments
            if (!empty($data['user_ids'])) {
                $stmt = $conn->prepare("INSERT INTO opportunity_users (opportunity_id, user_id, assigned_by) VALUES (?, ?, ?)");
                foreach ($data['user_ids'] as $user_id) {
                    $user_id = intval($user_id);
                    if ($user_id > 0) {
                        $stmt->bind_param("iii", $opp_id, $user_id, $assigned_by);
                        $stmt->execute();
                    }
                }
            }
        }
        
        // Update assigned contacts (supports both federal and commercial)
        if (isset($data['contacts'])) {
            // Remove existing assignments
            $conn->query("DELETE FROM opportunity_contacts WHERE opportunity_id = $opp_id");
            
            // Add new assignments with contact type
            if (!empty($data['contacts'])) {
                $stmt = $conn->prepare("INSERT INTO opportunity_contacts (opportunity_id, contact_id, contact_type, assigned_by) VALUES (?, ?, ?, ?)");
                foreach ($data['contacts'] as $contact) {
                    $contact_id = intval($contact['id']);
                    $contact_type = $contact['type'] ?? 'federal';
                    if ($contact_id > 0) {
                        $stmt->bind_param("iisi", $opp_id, $contact_id, $contact_type, $assigned_by);
                        $stmt->execute();
                    }
                }
            }
        }
        
        send_response(['success' => true]);
        break;

    // =============================================
    // OPPORTUNITY NOTES ENDPOINTS
    // =============================================
    
    case 'getOpportunityNotes':
        if (!has_permission('opportunity', 'view')) send_error('Permission denied.');
        
        $opp_id = intval($_GET['opportunity_id'] ?? 0);
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // For specialty users, verify they have access to this opportunity
        if (is_specialty_user()) {
            $allowedOppIds = get_specialty_opportunity_ids($conn, $_SESSION['user_id']);
            if (!in_array($opp_id, $allowedOppIds)) {
                send_error('Permission denied. You do not have access to this opportunity.', 403);
            }
        }
        
        // Build query with optional filters
        $where_clauses = ["on2.opportunity_id = ?"];
        $params = [$opp_id];
        $types = "i";
        
        // Filter by user
        if (!empty($_GET['filter_user_id'])) {
            $where_clauses[] = "on2.user_id = ?";
            $params[] = intval($_GET['filter_user_id']);
            $types .= "i";
        }
        
        // Filter by interaction type
        if (!empty($_GET['filter_interaction_type'])) {
            $where_clauses[] = "on2.interaction_type LIKE ?";
            $params[] = "%" . $_GET['filter_interaction_type'] . "%";
            $types .= "s";
        }
        
        // Filter by date range
        if (!empty($_GET['filter_date_from'])) {
            $where_clauses[] = "DATE(on2.created_at) >= ?";
            $params[] = $_GET['filter_date_from'];
            $types .= "s";
        }
        if (!empty($_GET['filter_date_to'])) {
            $where_clauses[] = "DATE(on2.created_at) <= ?";
            $params[] = $_GET['filter_date_to'];
            $types .= "s";
        }
        
        $where_sql = implode(" AND ", $where_clauses);

        $sql = "
            SELECT on2.*,
                   u.username AS createdByUsername
            FROM opportunity_notes on2
            LEFT JOIN users u ON on2.user_id = u.id
            WHERE {$where_sql}
            ORDER BY on2.created_at DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Format dates for display
        foreach ($notes as &$note) {
            $note['displayDate'] = date('m/d/Y', strtotime($note['created_at']));
            $note['canEdit'] = ($note['user_id'] == $_SESSION['user_id']);
            $note['canDelete'] = ($note['user_id'] == $_SESSION['user_id']);
        }
        
        send_response(['success' => true, 'notes' => $notes]);
        break;
    
    case 'saveOpportunityNote':
        if (!has_permission('opportunity', 'view')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['opportunity_id'])) send_error('Opportunity ID is required', 400);
        if (empty($data['note_text'])) send_error('Note text is required', 400);
        
        $note_id = !empty($data['id']) ? intval($data['id']) : null;
        $opp_id = intval($data['opportunity_id']);
        
        // For specialty users, verify they have access to this opportunity
        if (is_specialty_user()) {
            $allowedOppIds = get_specialty_opportunity_ids($conn, $_SESSION['user_id']);
            if (!in_array($opp_id, $allowedOppIds)) {
                send_error('Permission denied. You do not have access to this opportunity.', 403);
            }
        }
        
        $note_date = !empty($data['note_date']) ? $data['note_date'] : null;
        $interaction_type = $data['interaction_type'] ?? '';
        $note_text = $data['note_text'];
        $user_id = $_SESSION['user_id'];

        if ($note_id) {
            // Update existing note - check ownership
            $check_stmt = $conn->prepare("SELECT user_id FROM opportunity_notes WHERE id = ?");
            $check_stmt->bind_param("i", $note_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();

            if (!$existing) send_error('Note not found', 404);
            if ($existing['user_id'] != $_SESSION['user_id']) send_error('You can only edit your own notes', 403);

            $stmt = $conn->prepare("UPDATE opportunity_notes SET note_date = ?, interaction_type = ?, note_text = ? WHERE id = ?");
            $stmt->bind_param("sssi", $note_date, $interaction_type, $note_text, $note_id);
        } else {
            // Insert new note
            $stmt = $conn->prepare("INSERT INTO opportunity_notes (opportunity_id, note_date, user_id, interaction_type, note_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $opp_id, $note_date, $user_id, $interaction_type, $note_text);
        }
        
        if ($stmt->execute()) {
            $new_id = $note_id ?: $conn->insert_id;
            send_response(['success' => true, 'id' => $new_id]);
        } else {
            send_error('Failed to save note', 500);
        }
        break;
    
    case 'deleteOpportunityNote':
        if (!has_permission('opportunity', 'view')) send_error('Permission denied.');
        
        $note_id = intval($_GET['id'] ?? 0);
        if ($note_id <= 0) send_error('Invalid note ID', 400);
        
        // Check ownership
        $check_stmt = $conn->prepare("SELECT user_id FROM opportunity_notes WHERE id = ?");
        $check_stmt->bind_param("i", $note_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if (!$existing) send_error('Note not found', 404);
        if ($existing['user_id'] != $_SESSION['user_id']) send_error('You can only delete your own notes', 403);
        
        $stmt = $conn->prepare("DELETE FROM opportunity_notes WHERE id = ?");
        $stmt->bind_param("i", $note_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;

    // =============================================
    // PROPOSAL PANEL ENDPOINTS
    // =============================================
    
    case 'getProposalDetails':
        if (!has_permission('proposal', 'view')) send_error('Permission denied.');
        
        $prop_id = intval($_GET['id'] ?? 0);
        if ($prop_id <= 0) send_error('Invalid proposal ID', 400);
        
        // For specialty users, verify they have access to this proposal
        if (is_specialty_user()) {
            $allowedPropIds = get_specialty_proposal_ids($conn, $_SESSION['user_id']);
            if (!in_array($prop_id, $allowedPropIds)) {
                send_error('Permission denied. You do not have access to this proposal.', 403);
            }
        }
        
        // Get proposal details
        $stmt = $conn->prepare("
            SELECT p.*, a.name AS agencyName, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName
            FROM proposals p
            LEFT JOIN agencies a ON p.agency_id = a.id
            LEFT JOIN users u ON p.owner_user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $prop_id);
        $stmt->execute();
        $proposal = $stmt->get_result()->fetch_assoc();
        
        if (!$proposal) send_error('Proposal not found', 404);
        
        // Get assigned users
        $users_stmt = $conn->prepare("
            SELECT pu.*, u.username, COALESCE(u.display_name, u.username) AS display_name
            FROM proposal_users pu
            JOIN users u ON pu.user_id = u.id
            WHERE pu.proposal_id = ?
            ORDER BY COALESCE(u.display_name, u.username) ASC
        ");
        $users_stmt->bind_param("i", $prop_id);
        $users_stmt->execute();
        $proposal['assignedUsers'] = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get assigned contacts
        $contacts_stmt = $conn->prepare("
            SELECT pc.*, c.firstName, c.lastName, c.email, c.title
            FROM proposal_contacts pc
            JOIN contacts c ON pc.contact_id = c.id
            WHERE pc.proposal_id = ?
            ORDER BY c.lastName ASC
        ");
        $contacts_stmt->bind_param("i", $prop_id);
        $contacts_stmt->execute();
        $proposal['assignedContacts'] = $contacts_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'proposal' => $proposal]);
        break;
    
    case 'saveProposalAssignments':
        if (!has_permission('proposal', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $prop_id = intval($data['proposal_id'] ?? 0);
        
        if ($prop_id <= 0) send_error('Invalid proposal ID', 400);
        
        $assigned_by = $_SESSION['user_id'];
        
        // Update assigned users
        if (isset($data['user_ids'])) {
            // Remove existing assignments
            $conn->query("DELETE FROM proposal_users WHERE proposal_id = $prop_id");
            
            // Add new assignments
            if (!empty($data['user_ids'])) {
                $stmt = $conn->prepare("INSERT INTO proposal_users (proposal_id, user_id, assigned_by) VALUES (?, ?, ?)");
                foreach ($data['user_ids'] as $user_id) {
                    $user_id = intval($user_id);
                    if ($user_id > 0) {
                        $stmt->bind_param("iii", $prop_id, $user_id, $assigned_by);
                        $stmt->execute();
                    }
                }
            }
        }
        
        // Update assigned contacts
        if (isset($data['contact_ids'])) {
            // Remove existing assignments
            $conn->query("DELETE FROM proposal_contacts WHERE proposal_id = $prop_id");
            
            // Add new assignments
            if (!empty($data['contact_ids'])) {
                $stmt = $conn->prepare("INSERT INTO proposal_contacts (proposal_id, contact_id, assigned_by) VALUES (?, ?, ?)");
                foreach ($data['contact_ids'] as $contact_id) {
                    $contact_id = intval($contact_id);
                    if ($contact_id > 0) {
                        $stmt->bind_param("iii", $prop_id, $contact_id, $assigned_by);
                        $stmt->execute();
                    }
                }
            }
        }
        
        send_response(['success' => true]);
        break;

    // =============================================
    // PROPOSAL NOTES ENDPOINTS
    // =============================================
    
    case 'getProposalNotes':
        if (!has_permission('proposal', 'view')) send_error('Permission denied.');
        
        $prop_id = intval($_GET['proposal_id'] ?? 0);
        if ($prop_id <= 0) send_error('Invalid proposal ID', 400);
        
        // For specialty users, verify they have access to this proposal
        if (is_specialty_user()) {
            $allowedPropIds = get_specialty_proposal_ids($conn, $_SESSION['user_id']);
            if (!in_array($prop_id, $allowedPropIds)) {
                send_error('Permission denied. You do not have access to this proposal.', 403);
            }
        }
        
        // Build query with optional filters
        $where_clauses = ["pn.proposal_id = ?"];
        $params = [$prop_id];
        $types = "i";
        
        // Filter by user
        if (!empty($_GET['filter_user_id'])) {
            $where_clauses[] = "pn.user_id = ?";
            $params[] = intval($_GET['filter_user_id']);
            $types .= "i";
        }
        
        // Filter by interaction type
        if (!empty($_GET['filter_interaction_type'])) {
            $where_clauses[] = "pn.interaction_type LIKE ?";
            $params[] = "%" . $_GET['filter_interaction_type'] . "%";
            $types .= "s";
        }
        
        // Filter by date range
        if (!empty($_GET['filter_date_from'])) {
            $where_clauses[] = "DATE(pn.created_at) >= ?";
            $params[] = $_GET['filter_date_from'];
            $types .= "s";
        }
        if (!empty($_GET['filter_date_to'])) {
            $where_clauses[] = "DATE(pn.created_at) <= ?";
            $params[] = $_GET['filter_date_to'];
            $types .= "s";
        }
        
        $where_sql = implode(" AND ", $where_clauses);

        $sql = "
            SELECT pn.*,
                   u.username AS createdByUsername
            FROM proposal_notes pn
            LEFT JOIN users u ON pn.user_id = u.id
            WHERE {$where_sql}
            ORDER BY pn.created_at DESC
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Format dates for display
        foreach ($notes as &$note) {
            $note['displayDate'] = date('m/d/Y', strtotime($note['created_at']));
            $note['canEdit'] = ($note['user_id'] == $_SESSION['user_id']);
            $note['canDelete'] = ($note['user_id'] == $_SESSION['user_id']);
        }
        
        send_response(['success' => true, 'notes' => $notes]);
        break;
    
    case 'saveProposalNote':
        if (!has_permission('proposal', 'view')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['proposal_id'])) send_error('Proposal ID is required', 400);
        if (empty($data['note_text'])) send_error('Note text is required', 400);
        
        $note_id = !empty($data['id']) ? intval($data['id']) : null;
        $prop_id = intval($data['proposal_id']);
        
        // For specialty users, verify they have access to this proposal
        if (is_specialty_user()) {
            $allowedPropIds = get_specialty_proposal_ids($conn, $_SESSION['user_id']);
            if (!in_array($prop_id, $allowedPropIds)) {
                send_error('Permission denied. You do not have access to this proposal.', 403);
            }
        }
        
        $note_date = !empty($data['note_date']) ? $data['note_date'] : null;
        $interaction_type = $data['interaction_type'] ?? '';
        $note_text = $data['note_text'];
        $user_id = $_SESSION['user_id'];

        if ($note_id) {
            // Update existing note - check ownership
            $check_stmt = $conn->prepare("SELECT user_id FROM proposal_notes WHERE id = ?");
            $check_stmt->bind_param("i", $note_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();

            if (!$existing) send_error('Note not found', 404);
            if ($existing['user_id'] != $_SESSION['user_id']) send_error('You can only edit your own notes', 403);

            $stmt = $conn->prepare("UPDATE proposal_notes SET note_date = ?, interaction_type = ?, note_text = ? WHERE id = ?");
            $stmt->bind_param("sssi", $note_date, $interaction_type, $note_text, $note_id);
        } else {
            // Insert new note
            $stmt = $conn->prepare("INSERT INTO proposal_notes (proposal_id, note_date, user_id, interaction_type, note_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $prop_id, $note_date, $user_id, $interaction_type, $note_text);
        }
        
        if ($stmt->execute()) {
            $new_id = $note_id ?: $conn->insert_id;
            send_response(['success' => true, 'id' => $new_id]);
        } else {
            send_error('Failed to save note', 500);
        }
        break;
    
    case 'deleteProposalNote':
        if (!has_permission('proposal', 'view')) send_error('Permission denied.');
        
        $note_id = intval($_GET['id'] ?? 0);
        if ($note_id <= 0) send_error('Invalid note ID', 400);
        
        // Check ownership
        $check_stmt = $conn->prepare("SELECT user_id FROM proposal_notes WHERE id = ?");
        $check_stmt->bind_param("i", $note_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if (!$existing) send_error('Note not found', 404);
        if ($existing['user_id'] != $_SESSION['user_id']) send_error('You can only delete your own notes', 403);
        
        $stmt = $conn->prepare("DELETE FROM proposal_notes WHERE id = ?");
        $stmt->bind_param("i", $note_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;

    // =============================================
    // EVENT ENDPOINTS
    // =============================================
    
    case 'getEventDetails':
        if (!has_permission('event', 'view')) send_error('Permission denied.');
        
        $event_id = intval($_GET['id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        // For specialty users, verify they have access to this event
        if (is_specialty_user()) {
            $userId = $_SESSION['user_id'];
            $specialtyContactIds = get_specialty_contact_ids($conn, $userId);
            $specialtyCompanyContactIds = get_specialty_company_contact_ids($conn, $userId);
            $allowedEventIds = get_specialty_event_ids($conn, $userId, $specialtyContactIds, $specialtyCompanyContactIds);
            if (!in_array($event_id, $allowedEventIds)) {
                send_error('Permission denied. You do not have access to this event.', 403);
            }
        }
        
        // Get event details
        $stmt = $conn->prepare("
            SELECT e.*, u.username AS ownerUsername, COALESCE(u.display_name, u.username) AS ownerDisplayName
            FROM events e
            LEFT JOIN users u ON e.owner_user_id = u.id
            WHERE e.id = ?
        ");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        
        if (!$event) send_error('Event not found', 404);
        
        // Get assigned users
        $event['assignedUsers'] = [];
        $users_stmt = $conn->prepare("
            SELECT eu.*, u.username, COALESCE(u.display_name, u.username) AS display_name
            FROM event_users eu
            JOIN users u ON eu.user_id = u.id
            WHERE eu.event_id = ?
            ORDER BY COALESCE(u.display_name, u.username) ASC
        ");
        if ($users_stmt) {
            $users_stmt->bind_param("i", $event_id);
            $users_stmt->execute();
            $event['assignedUsers'] = $users_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get assigned federal contacts
        $event['assignedFederalContacts'] = [];
        $fed_stmt = $conn->prepare("
            SELECT efc.*, c.firstName, c.lastName, CONCAT(c.firstName, ' ', c.lastName) AS display_name, c.title, a.name AS agencyName
            FROM event_federal_contacts efc
            JOIN contacts c ON efc.contact_id = c.id
            LEFT JOIN agencies a ON c.agency_id = a.id
            WHERE efc.event_id = ?
            ORDER BY c.lastName, c.firstName ASC
        ");
        if ($fed_stmt) {
            $fed_stmt->bind_param("i", $event_id);
            $fed_stmt->execute();
            $event['assignedFederalContacts'] = $fed_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get assigned commercial contacts
        $event['assignedCommercialContacts'] = [];
        $comm_stmt = $conn->prepare("
            SELECT ecc.*, cc.first_name, cc.last_name, CONCAT(cc.first_name, ' ', cc.last_name) AS display_name, cc.title, comp.company_name AS companyName
            FROM event_commercial_contacts ecc
            JOIN company_contacts cc ON ecc.contact_id = cc.id
            LEFT JOIN companies comp ON cc.company_id = comp.id
            WHERE ecc.event_id = ?
            ORDER BY cc.last_name, cc.first_name ASC
        ");
        if ($comm_stmt) {
            $comm_stmt->bind_param("i", $event_id);
            $comm_stmt->execute();
            $event['assignedCommercialContacts'] = $comm_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get related tasks
        $event['relatedTasks'] = [];
        $tasks_stmt = $conn->prepare("
            SELECT t.*, u.username AS assignedToUsername, COALESCE(u.display_name, u.username) AS assignedToDisplayName
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to_user_id = u.id
            WHERE (t.relatedTo = 'Event' OR t.relatedTo = 'event') AND t.related_item_id = ?
            ORDER BY t.dueDate ASC
        ");
        if ($tasks_stmt) {
            $tasks_stmt->bind_param("i", $event_id);
            $tasks_stmt->execute();
            $event['relatedTasks'] = $tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get notes
        $event['notes'] = [];
        $notes_stmt = $conn->prepare("
            SELECT en.*, COALESCE(u.display_name, u.username) AS created_by_name
            FROM event_notes en
            LEFT JOIN users u ON en.user_id = u.id
            WHERE en.event_id = ?
            ORDER BY en.created_at DESC
        ");
        if ($notes_stmt) {
            $notes_stmt->bind_param("i", $event_id);
            $notes_stmt->execute();
            $event['notes'] = $notes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get documents
        $event['documents'] = [];
        $docs_stmt = $conn->prepare("
            SELECT ed.*, COALESCE(u.display_name, u.username) AS uploadedByDisplayName
            FROM event_documents ed
            LEFT JOIN users u ON ed.uploaded_by = u.id
            WHERE ed.event_id = ?
            ORDER BY ed.created_at DESC
        ");
        if ($docs_stmt) {
            $docs_stmt->bind_param("i", $event_id);
            $docs_stmt->execute();
            $event['documents'] = $docs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        send_response(['success' => true, 'event' => $event]);
        break;
    
    case 'saveEvent':
        $data = json_decode(file_get_contents('php://input'), true);
        $permission = empty($data['id']) ? 'create' : 'update';
        if (!has_permission('event', $permission)) send_error('Permission denied to ' . $permission . ' events.');

        $owner_user_id = !empty($data['owner_user_id']) ? intval($data['owner_user_id']) : null;
        
        if (empty($data['id'])) {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO events (name, description, start_datetime, end_datetime, location, virtual_link, event_type, status, priority, owner_user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $end_datetime = !empty($data['end_datetime']) ? $data['end_datetime'] : null;
            $stmt->bind_param("sssssssssi",
                $data['name'],
                $data['description'],
                $data['start_datetime'],
                $end_datetime,
                $data['location'],
                $data['virtual_link'],
                $data['event_type'],
                $data['status'],
                $data['priority'],
                $owner_user_id
            );
            $stmt->execute();
            $new_id = $conn->insert_id;
            send_response(['success' => true, 'id' => $new_id]);
        } else {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE events SET
                    name = ?,
                    description = ?,
                    start_datetime = ?,
                    end_datetime = ?,
                    location = ?,
                    virtual_link = ?,
                    event_type = ?,
                    status = ?,
                    priority = ?,
                    owner_user_id = ?
                WHERE id = ?
            ");
            $end_datetime = !empty($data['end_datetime']) ? $data['end_datetime'] : null;
            $stmt->bind_param("sssssssssii",
                $data['name'],
                $data['description'],
                $data['start_datetime'],
                $end_datetime,
                $data['location'],
                $data['virtual_link'],
                $data['event_type'],
                $data['status'],
                $data['priority'],
                $owner_user_id,
                $data['id']
            );
            $stmt->execute();
            send_response(['success' => true, 'id' => $data['id']]);
        }
        break;
    
    case 'deleteEvent':
        if (!has_permission('event', 'delete')) send_error('Permission denied to delete events.');
        
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) send_error('Invalid event ID', 400);
        
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        send_response(['success' => $stmt->affected_rows > 0]);
        break;
    
    case 'archiveEvent':
        if (!has_permission('event', 'archive')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $event_id = intval($data['id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Archive the event
            $stmt = $conn->prepare("
                INSERT INTO events_archive (id, name, description, start_datetime, end_datetime, location, virtual_link, event_type, status, priority, owner_user_id, created_at, updated_at, archived_by)
                SELECT id, name, description, start_datetime, end_datetime, location, virtual_link, event_type, status, priority, owner_user_id, created_at, updated_at, ?
                FROM events WHERE id = ?
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $event_id);
            $stmt->execute();
            
            // Archive related notes
            $stmt = $conn->prepare("
                INSERT INTO event_notes_archive (id, event_id, user_id, sprint_id, interaction_type, note_text, created_at, updated_at)
                SELECT id, event_id, user_id, sprint_id, interaction_type, note_text, created_at, updated_at
                FROM event_notes WHERE event_id = ?
            ");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
            // Archive related documents
            $stmt = $conn->prepare("
                INSERT INTO event_documents_archive (id, event_id, file_name, file_path, file_size, file_type, uploaded_by, created_at)
                SELECT id, event_id, file_name, file_path, file_size, file_type, uploaded_by, created_at
                FROM event_documents WHERE event_id = ?
            ");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
            // Archive related tasks (mark them as archived with this event)
            $stmt = $conn->prepare("
                INSERT INTO tasks_archive (id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at, archived_by, archived_with_type, archived_with_id)
                SELECT id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at, ?, 'Event', ?
                FROM tasks WHERE (relatedTo = 'Event' OR relatedTo = 'event') AND related_item_id = ?
            ");
            $stmt->bind_param("iii", $_SESSION['user_id'], $event_id, $event_id);
            $stmt->execute();
            
            // Delete tasks related to event
            $stmt = $conn->prepare("DELETE FROM tasks WHERE (relatedTo = 'Event' OR relatedTo = 'event') AND related_item_id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
            // Delete notes
            $stmt = $conn->prepare("DELETE FROM event_notes WHERE event_id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
            // Delete documents (records only, keep files)
            $stmt = $conn->prepare("DELETE FROM event_documents WHERE event_id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
            // Delete assignments
            $stmt = $conn->prepare("DELETE FROM event_users WHERE event_id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $stmt = $conn->prepare("DELETE FROM event_federal_contacts WHERE event_id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $stmt = $conn->prepare("DELETE FROM event_commercial_contacts WHERE event_id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
            // Delete the event
            $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Event archived successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to archive event: ' . $e->getMessage(), 500);
        }
        break;
    
    case 'saveEventAssignments':
        if (!has_permission('event', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $event_id = intval($data['event_id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        // Log incoming data for debugging
        error_log("saveEventAssignments - event_id: $event_id, data: " . json_encode($data));
        
        $conn->begin_transaction();
        
        try {
            // Clear existing assignments
            $conn->query("DELETE FROM event_users WHERE event_id = $event_id");
            $conn->query("DELETE FROM event_federal_contacts WHERE event_id = $event_id");
            $conn->query("DELETE FROM event_commercial_contacts WHERE event_id = $event_id");
            
            $savedUsers = 0;
            $savedFederal = 0;
            $savedCommercial = 0;
            
            // Insert new user assignments
            if (!empty($data['user_ids'])) {
                $stmt = $conn->prepare("INSERT INTO event_users (event_id, user_id) VALUES (?, ?)");
                foreach ($data['user_ids'] as $user_id) {
                    $user_id = intval($user_id);
                    if ($user_id > 0) {
                        $stmt->bind_param("ii", $event_id, $user_id);
                        $stmt->execute();
                        $savedUsers++;
                    }
                }
            }
            
            // Insert new federal contact assignments
            if (!empty($data['federal_contact_ids'])) {
                $stmt = $conn->prepare("INSERT INTO event_federal_contacts (event_id, contact_id) VALUES (?, ?)");
                foreach ($data['federal_contact_ids'] as $contact_id) {
                    $contact_id = intval($contact_id);
                    if ($contact_id > 0) {
                        $stmt->bind_param("ii", $event_id, $contact_id);
                        $stmt->execute();
                        $savedFederal++;
                    }
                }
            }
            
            // Insert new commercial contact assignments
            if (!empty($data['commercial_contact_ids'])) {
                $stmt = $conn->prepare("INSERT INTO event_commercial_contacts (event_id, contact_id) VALUES (?, ?)");
                foreach ($data['commercial_contact_ids'] as $contact_id) {
                    $contact_id = intval($contact_id);
                    if ($contact_id > 0) {
                        $stmt->bind_param("ii", $event_id, $contact_id);
                        $stmt->execute();
                        $savedCommercial++;
                    }
                }
            }
            
            $conn->commit();
            error_log("saveEventAssignments - saved: users=$savedUsers, federal=$savedFederal, commercial=$savedCommercial");
            send_response([
                'success' => true,
                'saved' => [
                    'users' => $savedUsers,
                    'federal_contacts' => $savedFederal,
                    'commercial_contacts' => $savedCommercial
                ]
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to save assignments: ' . $e->getMessage(), 500);
        }
        break;
    
    // =============================================
    // EVENT NOTES ENDPOINTS
    // =============================================
    
    case 'getEventNotes':
        if (!has_permission('event', 'view')) send_error('Permission denied.');
        
        $event_id = intval($_GET['event_id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        // For specialty users, verify they have access to this event
        if (is_specialty_user()) {
            $userId = $_SESSION['user_id'];
            $specialtyContactIds = get_specialty_contact_ids($conn, $userId);
            $specialtyCompanyContactIds = get_specialty_company_contact_ids($conn, $userId);
            $allowedEventIds = get_specialty_event_ids($conn, $userId, $specialtyContactIds, $specialtyCompanyContactIds);
            if (!in_array($event_id, $allowedEventIds)) {
                send_error('Permission denied. You do not have access to this event.', 403);
            }
        }
        
        // Build query with optional filters
        $where_clauses = ["en.event_id = ?"];
        $params = [$event_id];
        $types = "i";
        
        // Filter by user
        if (!empty($_GET['filter_user_id'])) {
            $where_clauses[] = "en.user_id = ?";
            $params[] = intval($_GET['filter_user_id']);
            $types .= "i";
        }
        
        // Filter by interaction type
        if (!empty($_GET['filter_interaction_type'])) {
            $where_clauses[] = "en.interaction_type LIKE ?";
            $params[] = "%" . $_GET['filter_interaction_type'] . "%";
            $types .= "s";
        }
        
        // Filter by date range
        if (!empty($_GET['filter_date_from'])) {
            $where_clauses[] = "DATE(en.created_at) >= ?";
            $params[] = $_GET['filter_date_from'];
            $types .= "s";
        }
        if (!empty($_GET['filter_date_to'])) {
            $where_clauses[] = "DATE(en.created_at) <= ?";
            $params[] = $_GET['filter_date_to'];
            $types .= "s";
        }
        
        $where_sql = implode(" AND ", $where_clauses);
        
        $stmt = $conn->prepare("
            SELECT en.*, u.username, COALESCE(u.display_name, u.username) AS display_name
            FROM event_notes en
            JOIN users u ON en.user_id = u.id
            WHERE $where_sql
            ORDER BY en.created_at DESC
        ");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $notes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'notes' => $notes]);
        break;
    
    case 'saveEventNote':
        if (!has_permission('event', 'view')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['event_id'])) send_error('Event ID is required', 400);
        if (empty($data['note_text'])) send_error('Note text is required', 400);
        
        $note_id = !empty($data['id']) ? intval($data['id']) : null;
        $event_id = intval($data['event_id']);
        
        // For specialty users, verify they have access to this event
        if (is_specialty_user()) {
            $userId = $_SESSION['user_id'];
            $specialtyContactIds = get_specialty_contact_ids($conn, $userId);
            $specialtyCompanyContactIds = get_specialty_company_contact_ids($conn, $userId);
            $allowedEventIds = get_specialty_event_ids($conn, $userId, $specialtyContactIds, $specialtyCompanyContactIds);
            if (!in_array($event_id, $allowedEventIds)) {
                send_error('Permission denied. You do not have access to this event.', 403);
            }
        }
        
        $note_date = !empty($data['note_date']) ? $data['note_date'] : null;
        $interaction_type = $data['interaction_type'] ?? '';
        $note_text = $data['note_text'];
        $user_id = $_SESSION['user_id'];

        if ($note_id) {
            // Update existing note - check ownership
            $check_stmt = $conn->prepare("SELECT user_id FROM event_notes WHERE id = ?");
            $check_stmt->bind_param("i", $note_id);
            $check_stmt->execute();
            $existing = $check_stmt->get_result()->fetch_assoc();

            if (!$existing) send_error('Note not found', 404);
            if ($existing['user_id'] != $_SESSION['user_id']) send_error('You can only edit your own notes', 403);

            $stmt = $conn->prepare("UPDATE event_notes SET note_date = ?, interaction_type = ?, note_text = ? WHERE id = ?");
            $stmt->bind_param("sssi", $note_date, $interaction_type, $note_text, $note_id);
        } else {
            // Insert new note
            $stmt = $conn->prepare("INSERT INTO event_notes (event_id, note_date, user_id, interaction_type, note_text) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiss", $event_id, $note_date, $user_id, $interaction_type, $note_text);
        }
        
        if ($stmt->execute()) {
            $new_id = $note_id ?: $conn->insert_id;
            send_response(['success' => true, 'id' => $new_id]);
        } else {
            send_error('Failed to save note: ' . $stmt->error, 500);
        }
        break;
    
    case 'deleteEventNote':
        if (!has_permission('event', 'view')) send_error('Permission denied.');
        
        $note_id = intval($_GET['id'] ?? 0);
        if ($note_id <= 0) send_error('Invalid note ID', 400);
        
        // Check ownership
        $check_stmt = $conn->prepare("SELECT user_id FROM event_notes WHERE id = ?");
        $check_stmt->bind_param("i", $note_id);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if (!$existing) send_error('Note not found', 404);
        if ($existing['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] !== 'admin') {
            send_error('You can only delete your own notes', 403);
        }
        
        $stmt = $conn->prepare("DELETE FROM event_notes WHERE id = ?");
        $stmt->bind_param("i", $note_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;
    
    // =============================================
    // EVENT DOCUMENTS ENDPOINTS
    // =============================================
    
    case 'getEventDocuments':
        if (!has_permission('event', 'view')) send_error('Permission denied.');
        
        $event_id = intval($_GET['event_id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        $stmt = $conn->prepare("
            SELECT ed.*, u.username AS uploadedByUsername, COALESCE(u.display_name, u.username) AS uploadedByDisplayName
            FROM event_documents ed
            LEFT JOIN users u ON ed.uploaded_by = u.id
            WHERE ed.event_id = ?
            ORDER BY ed.created_at DESC
        ");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'documents' => $documents]);
        break;
    
    case 'uploadEventDocument':
        if (!has_permission('event', 'view')) send_error('Permission denied.', 403);
        
        $event_id = intval($_POST['event_id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        // For specialty users, verify they have access to this event
        if (is_specialty_user()) {
            $userId = $_SESSION['user_id'];
            $specialtyContactIds = get_specialty_contact_ids($conn, $userId);
            $specialtyCompanyContactIds = get_specialty_company_contact_ids($conn, $userId);
            $allowedEventIds = get_specialty_event_ids($conn, $userId, $specialtyContactIds, $specialtyCompanyContactIds);
            if (!in_array($event_id, $allowedEventIds)) {
                send_error('Permission denied. You do not have access to this event.', 403);
            }
        }
        
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            send_error('No file uploaded or upload error', 400);
        }
        
        $file = $_FILES['document'];
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'csv', 'zip'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            send_error('File type not allowed. Allowed types: ' . implode(', ', $allowed_types), 400);
        }
        
        // Create directory if it doesn't exist
        $upload_dir = "/volume1/web/crm/uploads/events/{$event_id}/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename to prevent overwrites
        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $unique_filename = time() . '_' . $safe_filename;
        $file_path = $upload_dir . $unique_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            send_error('Failed to save file', 500);
        }
        
        // Insert record into database
        $stmt = $conn->prepare("
            INSERT INTO event_documents (event_id, file_name, file_path, file_size, file_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            send_error('Database prepare error: ' . $conn->error, 500);
        }
        $stmt->bind_param("issisi", $event_id, $file['name'], $file_path, $file['size'], $file['type'], $_SESSION['user_id']);
        if (!$stmt->execute()) {
            send_error('Database insert error: ' . $stmt->error, 500);
        }
        
        $document_id = $conn->insert_id;
        
        send_response([
            'success' => true, 
            'message' => 'Document uploaded successfully',
            'document_id' => $document_id
        ]);
        break;
    
    case 'deleteEventDocument':
        if (!has_permission('event', 'update')) send_error('Permission denied.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $document_id = intval($data['id'] ?? 0);
        if ($document_id <= 0) send_error('Invalid document ID', 400);
        
        // Get file path before deleting
        $stmt = $conn->prepare("SELECT file_path FROM event_documents WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        
        if (!$doc) send_error('Document not found', 404);
        
        // Delete file from disk
        if (file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }
        
        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM event_documents WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        
        send_response(['success' => true, 'message' => 'Document deleted successfully']);
        break;

    // =============================================
    // USER PROFILE ENDPOINTS
    // =============================================
    
    case 'getProfile':
        // Any logged-in user can view their own profile
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("
            SELECT id, username, first_name, last_name, display_name, 
                   email, work_phone, mobile_phone, job_title, profile_photo, role
            FROM users 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        
        if (!$profile) send_error('User not found', 404);
        
        // Remove sensitive data
        unset($profile['password_hash']);
        
        send_response(['success' => true, 'profile' => $profile]);
        break;
    
    case 'saveProfile':
        // Any logged-in user can update their own profile
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $_SESSION['user_id'];
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            send_error('First name and last name are required', 400);
        }
        
        $first_name = trim($data['first_name']);
        $last_name = trim($data['last_name']);
        $email = trim($data['email'] ?? '');
        $work_phone = trim($data['work_phone'] ?? '');
        $mobile_phone = trim($data['mobile_phone'] ?? '');
        $job_title = trim($data['job_title'] ?? '');
        
        // Check if display name is unique (excluding current user)
        $check_display = $first_name . ' ' . $last_name;
        $check_stmt = $conn->prepare("
            SELECT id FROM users 
            WHERE CONCAT(first_name, ' ', last_name) = ? AND id != ?
        ");
        $check_stmt->bind_param("si", $check_display, $user_id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            send_error('This display name is already taken by another user', 400);
        }
        
        // Update profile
        $stmt = $conn->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, email = ?, 
                work_phone = ?, mobile_phone = ?, job_title = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssssi", $first_name, $last_name, $email, $work_phone, $mobile_phone, $job_title, $user_id);
        
        if ($stmt->execute()) {
            // Update session with new display name
            $_SESSION['display_name'] = $first_name . ' ' . $last_name;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            
            send_response(['success' => true, 'display_name' => $_SESSION['display_name']]);
        } else {
            send_error('Failed to update profile', 500);
        }
        break;
    
    case 'uploadProfilePhoto':
        // Handle profile photo upload
        $user_id = $_SESSION['user_id'];
        
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            send_error('No file uploaded or upload error', 400);
        }
        
        $file = $_FILES['photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            send_error('Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.', 400);
        }
        
        // Max file size: 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            send_error('File size too large. Maximum size is 2MB.', 400);
        }
        
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old photo if exists
            $old_stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
            $old_stmt->bind_param("i", $user_id);
            $old_stmt->execute();
            $old_result = $old_stmt->get_result()->fetch_assoc();
            if ($old_result && $old_result['profile_photo'] && file_exists($old_result['profile_photo'])) {
                unlink($old_result['profile_photo']);
            }
            
            // Update database with new photo path
            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
            $stmt->bind_param("si", $filepath, $user_id);
            $stmt->execute();
            
            send_response(['success' => true, 'photo_url' => $filepath]);
        } else {
            send_error('Failed to save uploaded file', 500);
        }
        break;
    
    case 'deleteProfilePhoto':
        $user_id = $_SESSION['user_id'];
        
        // Get current photo path
        $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['profile_photo'] && file_exists($result['profile_photo'])) {
            unlink($result['profile_photo']);
        }
        
        // Clear photo in database
        $stmt = $conn->prepare("UPDATE users SET profile_photo = NULL WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'changePassword':
        $data = json_decode(file_get_contents('php://input'), true);
        $user_id = $_SESSION['user_id'];
        
        // Validate input
        if (empty($data['current_password']) || empty($data['new_password']) || empty($data['confirm_password'])) {
            send_error('All password fields are required', 400);
        }
        
        if ($data['new_password'] !== $data['confirm_password']) {
            send_error('New passwords do not match', 400);
        }
        
        if (strlen($data['new_password']) < 6) {
            send_error('New password must be at least 6 characters', 400);
        }
        
        // Verify current password
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!password_verify($data['current_password'], $user['password_hash'])) {
            send_error('Current password is incorrect', 400);
        }
        
        // Update password
        $new_hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->bind_param("si", $new_hash, $user_id);
        
        if ($stmt->execute()) {
            send_response(['success' => true]);
        } else {
            send_error('Failed to update password', 500);
        }
        break;

    // =============================================
    // DEPARTMENT CALENDAR ENDPOINTS
    // =============================================
    
    case 'getDeptEvents':
        // All logged-in users can view department events
        $start_date = $_GET['start'] ?? date('Y-m-01');
        $end_date = $_GET['end'] ?? date('Y-m-t');
        
        // Get all events (including recurring) that could appear in this date range
        $stmt = $conn->prepare("
            SELECT e.*, u.username AS createdByUsername, COALESCE(u.display_name, u.username) AS createdByDisplayName
            FROM department_events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE (
                (e.is_recurring = 0 AND e.start_date <= ? AND e.end_date >= ?)
                OR (e.is_recurring = 1 AND e.start_date <= ? AND (e.recurrence_end_date IS NULL OR e.recurrence_end_date >= ?))
                OR (e.parent_event_id IS NOT NULL AND e.start_date <= ? AND e.end_date >= ?)
            )
            AND e.is_deleted_instance = 0
            ORDER BY e.start_date ASC
        ");
        $stmt->bind_param("ssssss", $end_date, $start_date, $end_date, $start_date, $end_date, $start_date);
        $stmt->execute();
        $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Expand recurring events into individual instances
        $expanded_events = [];
        foreach ($events as $event) {
            if ($event['is_recurring'] && !$event['parent_event_id']) {
                // Generate instances for recurring events
                $instances = generateRecurringInstances($event, $start_date, $end_date);
                $expanded_events = array_merge($expanded_events, $instances);
            } else {
                $expanded_events[] = $event;
            }
        }
        
        send_response(['success' => true, 'events' => $expanded_events]);
        break;
    
    case 'getDeptEvent':
        $event_id = intval($_GET['id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        $stmt = $conn->prepare("
            SELECT e.*, u.username AS createdByUsername, COALESCE(u.display_name, u.username) AS createdByDisplayName
            FROM department_events e
            LEFT JOIN users u ON e.created_by = u.id
            WHERE e.id = ?
        ");
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        
        if (!$event) send_error('Event not found', 404);
        
        send_response(['success' => true, 'event' => $event]);
        break;
    
    case 'saveDeptEvent':
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (empty($data['title'])) send_error('Event title is required', 400);
        if (empty($data['start_date'])) send_error('Start date is required', 400);
        
        $event_id = !empty($data['id']) ? intval($data['id']) : null;
        $title = trim($data['title']);
        $description = trim($data['description'] ?? '');
        $event_color = $data['event_color'] ?? '#667eea';
        $start_date = $data['start_date'];
        $end_date = $data['end_date'] ?? $start_date;
        
        // Recurring fields
        $is_recurring = !empty($data['is_recurring']) ? 1 : 0;
        $recurrence_type = $is_recurring ? ($data['recurrence_type'] ?? 'weekly') : null;
        $recurrence_end_type = $is_recurring ? ($data['recurrence_end_type'] ?? 'date') : null;
        $recurrence_end_date = ($is_recurring && $recurrence_end_type === 'date') ? ($data['recurrence_end_date'] ?? null) : null;
        $recurrence_count = ($is_recurring && $recurrence_end_type === 'count') ? intval($data['recurrence_count'] ?? 10) : null;
        
        if ($event_id) {
            // Update existing event
            // Params: title, description, event_color, start_date, end_date, is_recurring, recurrence_type, recurrence_end_type, recurrence_end_date, recurrence_count, id
            // Types:  s      s            s            s           s         i            s                s                   s                    i                 i
            $stmt = $conn->prepare("
                UPDATE department_events 
                SET title = ?, description = ?, event_color = ?,
                    start_date = ?, end_date = ?,
                    is_recurring = ?, recurrence_type = ?, recurrence_end_type = ?,
                    recurrence_end_date = ?, recurrence_count = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sssssisssii", 
                $title, $description, $event_color,
                $start_date, $end_date,
                $is_recurring, $recurrence_type, $recurrence_end_type,
                $recurrence_end_date, $recurrence_count, $event_id
            );
        } else {
            // Create new event
            // Params: title, description, event_color, start_date, end_date, is_recurring, recurrence_type, recurrence_end_type, recurrence_end_date, recurrence_count, created_by
            // Types:  s      s            s            s           s         i            s                s                   s                    i                 i
            $created_by = $_SESSION['user_id'];
            $stmt = $conn->prepare("
                INSERT INTO department_events 
                (title, description, event_color, is_all_day, start_date, end_date,
                 is_recurring, recurrence_type, recurrence_end_type, recurrence_end_date, recurrence_count, created_by)
                VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sssssisssii",
                $title, $description, $event_color,
                $start_date, $end_date,
                $is_recurring, $recurrence_type, $recurrence_end_type,
                $recurrence_end_date, $recurrence_count, $created_by
            );
        }
        
        if ($stmt->execute()) {
            $new_id = $event_id ?: $conn->insert_id;
            send_response(['success' => true, 'id' => $new_id]);
        } else {
            send_error('Failed to save event: ' . $stmt->error, 500);
        }
        break;
    
    case 'saveDeptEventInstance':
        // Save an exception for a single instance of a recurring event
        $data = json_decode(file_get_contents('php://input'), true);
        
        $parent_id = intval($data['parent_event_id'] ?? 0);
        $original_date = $data['original_date'] ?? null;
        
        if ($parent_id <= 0 || !$original_date) send_error('Invalid parameters', 400);
        
        // Check if exception already exists
        $check_stmt = $conn->prepare("SELECT id FROM department_events WHERE parent_event_id = ? AND original_date = ?");
        $check_stmt->bind_param("is", $parent_id, $original_date);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        $title = trim($data['title']);
        $description = trim($data['description'] ?? '');
        $event_color = $data['event_color'] ?? '#667eea';
        $start_date = $data['start_date'];
        $end_date = $data['end_date'] ?? $start_date;
        $created_by = $_SESSION['user_id'];
        
        if ($existing) {
            // Update existing exception
            $stmt = $conn->prepare("
                UPDATE department_events 
                SET title = ?, description = ?, event_color = ?, is_all_day = 1,
                    start_date = ?, end_date = ?, is_exception = 1
                WHERE id = ?
            ");
            $stmt->bind_param("sssssi", 
                $title, $description, $event_color,
                $start_date, $end_date, $existing['id']
            );
        } else {
            // Create new exception
            $stmt = $conn->prepare("
                INSERT INTO department_events 
                (title, description, event_color, is_all_day, start_date, end_date,
                 parent_event_id, original_date, is_exception, created_by)
                VALUES (?, ?, ?, 1, ?, ?, ?, ?, 1, ?)
            ");
            $stmt->bind_param("sssssisi",
                $title, $description, $event_color,
                $start_date, $end_date,
                $parent_id, $original_date, $created_by
            );
        }
        
        if ($stmt->execute()) {
            send_response(['success' => true]);
        } else {
            send_error('Failed to save event instance: ' . $stmt->error, 500);
        }
        break;
    
    case 'updateFutureInstances':
        // Update this instance and all future instances of a recurring event
        $data = json_decode(file_get_contents('php://input'), true);
        
        $parent_id = intval($data['parent_event_id'] ?? 0);
        $from_date = $data['from_date'] ?? null;
        
        if ($parent_id <= 0 || !$from_date) send_error('Invalid parameters', 400);
        
        // Get parent event
        $parent_stmt = $conn->prepare("SELECT * FROM department_events WHERE id = ?");
        $parent_stmt->bind_param("i", $parent_id);
        $parent_stmt->execute();
        $parent = $parent_stmt->get_result()->fetch_assoc();
        
        if (!$parent) send_error('Parent event not found', 404);
        
        // End the original recurring event the day before
        $end_before = date('Y-m-d', strtotime($from_date . ' -1 day'));
        $stmt = $conn->prepare("UPDATE department_events SET recurrence_end_date = ?, recurrence_end_type = 'date' WHERE id = ?");
        $stmt->bind_param("si", $end_before, $parent_id);
        $stmt->execute();
        
        // Create a new recurring event starting from this date
        $title = trim($data['title']);
        $description = trim($data['description'] ?? '');
        $event_color = $data['event_color'] ?? '#667eea';
        $recurrence_type = $data['recurrence_type'] ?? $parent['recurrence_type'];
        $recurrence_end_type = $data['recurrence_end_type'] ?? $parent['recurrence_end_type'];
        $recurrence_end_date = $data['recurrence_end_date'] ?? $parent['recurrence_end_date'];
        $recurrence_count = isset($data['recurrence_count']) ? intval($data['recurrence_count']) : (isset($parent['recurrence_count']) ? intval($parent['recurrence_count']) : null);
        $created_by = $_SESSION['user_id'];
        
        // Params: title, description, event_color, start_date, end_date, recurrence_type, recurrence_end_type, recurrence_end_date, recurrence_count, created_by
        // Types:  s      s            s            s           s         s                s                   s                    i                 i
        $stmt = $conn->prepare("
            INSERT INTO department_events 
            (title, description, event_color, is_all_day, start_date, end_date,
             is_recurring, recurrence_type, recurrence_end_type, recurrence_end_date, recurrence_count, created_by)
            VALUES (?, ?, ?, 1, ?, ?, 1, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssssssii",
            $title, $description, $event_color,
            $from_date, $from_date,
            $recurrence_type, $recurrence_end_type, $recurrence_end_date, $recurrence_count, $created_by
        );
        
        if ($stmt->execute()) {
            send_response(['success' => true, 'id' => $conn->insert_id]);
        } else {
            send_error('Failed to update future instances: ' . $stmt->error, 500);
        }
        break;
    
    case 'deleteDeptEvent':
        $event_id = intval($_GET['id'] ?? 0);
        if ($event_id <= 0) send_error('Invalid event ID', 400);
        
        // Delete the event and all its exceptions
        $stmt = $conn->prepare("DELETE FROM department_events WHERE id = ? OR parent_event_id = ?");
        $stmt->bind_param("ii", $event_id, $event_id);
        $stmt->execute();
        
        send_response(['success' => $stmt->affected_rows > 0]);
        break;
    
    case 'deleteDeptEventInstance':
        // Mark a single instance of a recurring event as deleted
        $data = json_decode(file_get_contents('php://input'), true);
        
        $parent_id = intval($data['parent_event_id'] ?? 0);
        $original_date = $data['original_date'] ?? null;
        
        if ($parent_id <= 0 || !$original_date) send_error('Invalid parameters', 400);
        
        // Check if exception already exists
        $check_stmt = $conn->prepare("SELECT id FROM department_events WHERE parent_event_id = ? AND original_date = ?");
        $check_stmt->bind_param("is", $parent_id, $original_date);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing to mark as deleted
            $stmt = $conn->prepare("UPDATE department_events SET is_deleted_instance = 1 WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
        } else {
            // Create a new deleted instance marker
            $created_by = $_SESSION['user_id'];
            $stmt = $conn->prepare("
                INSERT INTO department_events 
                (title, start_date, end_date, parent_event_id, original_date, is_deleted_instance, created_by)
                VALUES ('', ?, ?, ?, ?, 1, ?)
            ");
            $stmt->bind_param("ssisi", $original_date, $original_date, $parent_id, $original_date, $created_by);
        }
        
        if ($stmt->execute()) {
            send_response(['success' => true]);
        } else {
            send_error('Failed to delete instance', 500);
        }
        break;

    case 'convertOpportunityToProposal':
        // Convert an Opportunity to a Proposal when status is set to "Bid"
        // Requires both opportunity.update AND proposal.create permissions
        if (!has_permission('opportunity', 'update')) send_error('Permission denied to update opportunities.', 403);
        if (!has_permission('proposal', 'create')) send_error('Permission denied to create proposals.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $opportunity_id = intval($data['opportunity_id'] ?? 0);
        if ($opportunity_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Get the opportunity data
        $stmt = $conn->prepare("SELECT * FROM opportunities WHERE id = ?");
        $stmt->bind_param("i", $opportunity_id);
        $stmt->execute();
        $opportunity = $stmt->get_result()->fetch_assoc();
        
        if (!$opportunity) send_error('Opportunity not found', 404);
        if ($opportunity['status'] === 'Converted') send_error('This opportunity has already been converted', 400);
        
        // Get proposal-specific fields from the request
        $win_probability = intval($data['winProbability'] ?? 0);
        $submit_date = $data['submitDate'] ?? date('Y-m-d');
        $due_date = $data['dueDate'] ?? null;
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create the new Proposal
            $stmt = $conn->prepare("
                INSERT INTO proposals 
                (title, agency_id, value, status, submitDate, dueDate, winProbability, description, owner_user_id, converted_from_opportunity_id)
                VALUES (?, ?, ?, 'Draft', ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("sidssisii",
                $opportunity['title'],
                $opportunity['agency_id'],
                $opportunity['value'],
                $submit_date,
                $due_date,
                $win_probability,
                $opportunity['description'],
                $opportunity['owner_user_id'],
                $opportunity_id
            );
            $stmt->execute();
            $new_proposal_id = $conn->insert_id;
            
            // Move documents from opportunity to proposal
            $result = $conn->query("SHOW TABLES LIKE 'opportunity_documents'");
            if ($result->num_rows > 0) {
                // Get all documents for this opportunity
                $stmt = $conn->prepare("SELECT * FROM opportunity_documents WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
                $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Create proposal uploads directory
                $new_upload_dir = "/volume1/web/crm/uploads/proposals/{$new_proposal_id}/";
                if (!is_dir($new_upload_dir) && count($documents) > 0) {
                    mkdir($new_upload_dir, 0755, true);
                }
                
                foreach ($documents as $doc) {
                    // Move physical file
                    $old_path = $doc['file_path'];
                    $new_path = $new_upload_dir . basename($old_path);
                    
                    if (file_exists($old_path)) {
                        rename($old_path, $new_path);
                    }
                    
                    // Insert into proposal_documents
                    $stmt = $conn->prepare("
                        INSERT INTO proposal_documents (proposal_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param("issisis", $new_proposal_id, $doc['file_name'], $new_path, $doc['file_size'], $doc['file_type'], $doc['uploaded_by'], $doc['uploaded_at']);
                    $stmt->execute();
                }
                
                // Delete from opportunity_documents
                $stmt = $conn->prepare("DELETE FROM opportunity_documents WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
                
                // Try to remove old directory if empty
                $old_upload_dir = "/volume1/web/crm/uploads/opportunities/{$opportunity_id}/";
                if (is_dir($old_upload_dir)) {
                    @rmdir($old_upload_dir); // Will only succeed if empty
                }
            }
            
            // Update the Opportunity status to "Converted"
            $stmt = $conn->prepare("UPDATE opportunities SET status = 'Converted' WHERE id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            send_response([
                'success' => true, 
                'proposal_id' => $new_proposal_id,
                'message' => 'Opportunity successfully converted to Proposal'
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to convert opportunity: ' . $e->getMessage(), 500);
        }
        break;

    // =============================================
    // ARCHIVE SYSTEM ENDPOINTS (Admin Only)
    // =============================================

    case 'archiveOpportunity':
        // Check archive permission
        if (!has_permission('opportunity', 'archive')) send_error('Permission denied to archive opportunities.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opportunity_id = intval($data['id'] ?? 0);
        if ($opportunity_id <= 0) send_error('Invalid opportunity ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Get the opportunity
            $stmt = $conn->prepare("SELECT * FROM opportunities WHERE id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            $opportunity = $stmt->get_result()->fetch_assoc();
            
            if (!$opportunity) {
                throw new Exception('Opportunity not found');
            }
            
            // Insert into archive
            $stmt = $conn->prepare("
                INSERT INTO opportunities_archive 
                (id, title, agency_id, division, owner_user_id, value, status, dueDate, priority, description, created_at, updated_at, archived_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isisidssssssi",
                $opportunity['id'],
                $opportunity['title'],
                $opportunity['agency_id'],
                $opportunity['division'],
                $opportunity['owner_user_id'],
                $opportunity['value'],
                $opportunity['status'],
                $opportunity['dueDate'],
                $opportunity['priority'],
                $opportunity['description'],
                $opportunity['created_at'],
                $opportunity['updated_at'],
                $_SESSION['user_id']
            );
            $stmt->execute();
            
            // Archive related tasks
            $stmt = $conn->prepare("
                INSERT INTO tasks_archive 
                (id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at, archived_by, archived_with_type, archived_with_id)
                SELECT id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at, ?, 'opportunity', ?
                FROM tasks WHERE relatedTo = 'Opportunity' AND related_item_id = ?
            ");
            $stmt->bind_param("iii", $_SESSION['user_id'], $opportunity_id, $opportunity_id);
            $stmt->execute();
            
            // Archive related notes (if table exists)
            $result = $conn->query("SHOW TABLES LIKE 'opportunity_notes'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO opportunity_notes_archive 
                    (id, opportunity_id, sprint_id, user_id, interaction_type, note_text, created_at, updated_at, archived_by)
                    SELECT id, opportunity_id, sprint_id, user_id, interaction_type, note_text, created_at, updated_at, ?
                    FROM opportunity_notes WHERE opportunity_id = ?
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $opportunity_id);
                $stmt->execute();
                
                // Delete notes from original table
                $stmt = $conn->prepare("DELETE FROM opportunity_notes WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
            }
            
            // Archive related documents (if table exists)
            $result = $conn->query("SHOW TABLES LIKE 'opportunity_documents'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO opportunity_documents_archive 
                    (id, opportunity_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at, archived_by)
                    SELECT id, opportunity_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at, ?
                    FROM opportunity_documents WHERE opportunity_id = ?
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $opportunity_id);
                $stmt->execute();
                
                // Delete documents from original table (files stay on disk)
                $stmt = $conn->prepare("DELETE FROM opportunity_documents WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
            }
            
            // Delete related tasks from original table
            $stmt = $conn->prepare("DELETE FROM tasks WHERE relatedTo = 'Opportunity' AND related_item_id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            // Delete opportunity from original table
            $stmt = $conn->prepare("DELETE FROM opportunities WHERE id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Opportunity archived successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to archive opportunity: ' . $e->getMessage(), 500);
        }
        break;

    case 'archiveTask':
        // Only admins and users can archive tasks (not managers)
        if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'user') {
            send_error('Only administrators and users can archive tasks.', 403);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $task_id = intval($data['id'] ?? 0);
        if ($task_id <= 0) send_error('Invalid task ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Get the task
            $stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            $task = $stmt->get_result()->fetch_assoc();
            
            if (!$task) {
                throw new Exception('Task not found');
            }
            
            // Sanitize datetime values - use current timestamp if invalid
            $created_at = (!empty($task['created_at']) && strtotime($task['created_at'])) 
                ? date('Y-m-d H:i:s', strtotime($task['created_at'])) 
                : date('Y-m-d H:i:s');
            $updated_at = (!empty($task['updated_at']) && strtotime($task['updated_at'])) 
                ? date('Y-m-d H:i:s', strtotime($task['updated_at'])) 
                : date('Y-m-d H:i:s');
            
            // Insert into archive (standalone task, not archived with opportunity/proposal)
            $stmt = $conn->prepare("
                INSERT INTO tasks_archive 
                (id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at, archived_by, archived_with_type, archived_with_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)
            ");
            $stmt->bind_param("ississssisssi",
                $task['id'],
                $task['title'],
                $task['relatedTo'],
                $task['related_item_id'],
                $task['dueDate'],
                $task['priority'],
                $task['status'],
                $task['assignedTo'],
                $task['assigned_to_user_id'],
                $task['description'],
                $created_at,
                $updated_at,
                $_SESSION['user_id']
            );
            $stmt->execute();
            
            // Delete task from original table
            $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Task archived successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to archive task: ' . $e->getMessage(), 500);
        }
        break;

    case 'archiveProposal':
        // Check archive permission
        if (!has_permission('proposal', 'archive')) send_error('Permission denied to archive proposals.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $proposal_id = intval($data['id'] ?? 0);
        if ($proposal_id <= 0) send_error('Invalid proposal ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Get the proposal
            $stmt = $conn->prepare("SELECT * FROM proposals WHERE id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            $proposal = $stmt->get_result()->fetch_assoc();
            
            if (!$proposal) {
                throw new Exception('Proposal not found');
            }
            
            // Insert into archive
            $stmt = $conn->prepare("
                INSERT INTO proposals_archive 
                (id, title, agency_id, owner_user_id, value, status, submitDate, dueDate, validity_date, award_date, winProbability, description, converted_from_opportunity_id, created_at, updated_at, archived_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isiidsssssisissi",
                $proposal['id'],
                $proposal['title'],
                $proposal['agency_id'],
                $proposal['owner_user_id'],
                $proposal['value'],
                $proposal['status'],
                $proposal['submitDate'],
                $proposal['dueDate'],
                $proposal['validity_date'],
                $proposal['award_date'],
                $proposal['winProbability'],
                $proposal['description'],
                $proposal['converted_from_opportunity_id'],
                $proposal['created_at'],
                $proposal['updated_at'],
                $_SESSION['user_id']
            );
            $stmt->execute();
            
            // Archive related tasks
            $stmt = $conn->prepare("
                INSERT INTO tasks_archive 
                (id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at, archived_by, archived_with_type, archived_with_id)
                SELECT id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at, ?, 'proposal', ?
                FROM tasks WHERE relatedTo = 'Proposal' AND related_item_id = ?
            ");
            $stmt->bind_param("iii", $_SESSION['user_id'], $proposal_id, $proposal_id);
            $stmt->execute();
            
            // Archive related notes (if table exists)
            $result = $conn->query("SHOW TABLES LIKE 'proposal_notes'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO proposal_notes_archive 
                    (id, proposal_id, sprint_id, user_id, interaction_type, note_text, created_at, updated_at, archived_by)
                    SELECT id, proposal_id, sprint_id, user_id, interaction_type, note_text, created_at, updated_at, ?
                    FROM proposal_notes WHERE proposal_id = ?
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $proposal_id);
                $stmt->execute();
                
                // Delete notes from original table
                $stmt = $conn->prepare("DELETE FROM proposal_notes WHERE proposal_id = ?");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
            }
            
            // Archive related documents (if table exists)
            $result = $conn->query("SHOW TABLES LIKE 'proposal_documents'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO proposal_documents_archive 
                    (id, proposal_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at, archived_by)
                    SELECT id, proposal_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at, ?
                    FROM proposal_documents WHERE proposal_id = ?
                ");
                $stmt->bind_param("ii", $_SESSION['user_id'], $proposal_id);
                $stmt->execute();
                
                // Delete documents from original table (files stay on disk)
                $stmt = $conn->prepare("DELETE FROM proposal_documents WHERE proposal_id = ?");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
            }
            
            // Delete related tasks from original table
            $stmt = $conn->prepare("DELETE FROM tasks WHERE relatedTo = 'Proposal' AND related_item_id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            // Delete proposal from original table
            $stmt = $conn->prepare("DELETE FROM proposals WHERE id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Proposal archived successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to archive proposal: ' . $e->getMessage(), 500);
        }
        break;

    case 'getArchivedRecords':
        // Only admins can view archived records
        if ($_SESSION['role'] !== 'admin') send_error('Only administrators can view archived records.', 403);
        
        $archived = [];
        
        // Get archived opportunities
        $result = $conn->query("
            SELECT oa.*, a.name AS agencyName, u.username AS archivedByUsername, 
                   COALESCE(u.display_name, u.username) AS archivedByDisplayName,
                   (SELECT COUNT(*) FROM tasks_archive WHERE archived_with_type = 'opportunity' AND archived_with_id = oa.id) AS archivedTasksCount
            FROM opportunities_archive oa
            LEFT JOIN agencies a ON oa.agency_id = a.id
            LEFT JOIN users u ON oa.archived_by = u.id
            ORDER BY oa.archived_at DESC
        ");
        $archived['opportunities'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Get archived proposals
        $result = $conn->query("
            SELECT pa.*, a.name AS agencyName, u.username AS archivedByUsername,
                   COALESCE(u.display_name, u.username) AS archivedByDisplayName,
                   (SELECT COUNT(*) FROM tasks_archive WHERE archived_with_type = 'proposal' AND archived_with_id = pa.id) AS archivedTasksCount
            FROM proposals_archive pa
            LEFT JOIN agencies a ON pa.agency_id = a.id
            LEFT JOIN users u ON pa.archived_by = u.id
            ORDER BY pa.archived_at DESC
        ");
        $archived['proposals'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Get standalone archived tasks (not archived with opportunity/proposal)
        $result = $conn->query("
            SELECT ta.*, u.username AS archivedByUsername,
                   COALESCE(u.display_name, u.username) AS archivedByDisplayName,
                   au.username AS assignedToUsername,
                   COALESCE(au.display_name, au.username) AS assignedToDisplayName
            FROM tasks_archive ta
            LEFT JOIN users u ON ta.archived_by = u.id
            LEFT JOIN users au ON ta.assigned_to_user_id = au.id
            WHERE ta.archived_with_type IS NULL
            ORDER BY ta.archived_at DESC
        ");
        $archived['tasks'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        send_response(['success' => true, 'archived' => $archived]);
        break;

    case 'restoreOpportunity':
        // Only admins can restore
        if ($_SESSION['role'] !== 'admin') send_error('Only administrators can restore archived records.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opportunity_id = intval($data['id'] ?? 0);
        if ($opportunity_id <= 0) send_error('Invalid opportunity ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Get from archive
            $stmt = $conn->prepare("SELECT * FROM opportunities_archive WHERE id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            $opportunity = $stmt->get_result()->fetch_assoc();
            
            if (!$opportunity) {
                throw new Exception('Archived opportunity not found');
            }
            
            // Restore to main table
            $stmt = $conn->prepare("
                INSERT INTO opportunities 
                (id, title, agency_id, division, owner_user_id, value, status, dueDate, priority, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("isisidsssss",
                $opportunity['id'],
                $opportunity['title'],
                $opportunity['agency_id'],
                $opportunity['division'],
                $opportunity['owner_user_id'],
                $opportunity['value'],
                $opportunity['status'],
                $opportunity['dueDate'],
                $opportunity['priority'],
                $opportunity['description'],
                $opportunity['created_at']
            );
            $stmt->execute();
            
            // Restore related tasks
            $stmt = $conn->prepare("
                INSERT INTO tasks (id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at)
                SELECT id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, NOW()
                FROM tasks_archive WHERE archived_with_type = 'opportunity' AND archived_with_id = ?
            ");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            // Restore notes if they exist
            $result = $conn->query("SHOW TABLES LIKE 'opportunity_notes'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO opportunity_notes (id, opportunity_id, sprint_id, user_id, interaction_type, note_text, created_at, updated_at)
                    SELECT id, opportunity_id, sprint_id, user_id, interaction_type, note_text, created_at, NOW()
                    FROM opportunity_notes_archive WHERE opportunity_id = ?
                ");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
                
                // Delete from notes archive
                $stmt = $conn->prepare("DELETE FROM opportunity_notes_archive WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
            }
            
            // Restore documents if they exist
            $result = $conn->query("SHOW TABLES LIKE 'opportunity_documents'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO opportunity_documents (id, opportunity_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at)
                    SELECT id, opportunity_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at
                    FROM opportunity_documents_archive WHERE opportunity_id = ?
                ");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
                
                // Delete from documents archive
                $stmt = $conn->prepare("DELETE FROM opportunity_documents_archive WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
            }
            
            // Delete from tasks archive
            $stmt = $conn->prepare("DELETE FROM tasks_archive WHERE archived_with_type = 'opportunity' AND archived_with_id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            // Delete from opportunity archive
            $stmt = $conn->prepare("DELETE FROM opportunities_archive WHERE id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Opportunity restored successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to restore opportunity: ' . $e->getMessage(), 500);
        }
        break;

    case 'restoreProposal':
        // Only admins can restore
        if ($_SESSION['role'] !== 'admin') send_error('Only administrators can restore archived records.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $proposal_id = intval($data['id'] ?? 0);
        if ($proposal_id <= 0) send_error('Invalid proposal ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Get from archive
            $stmt = $conn->prepare("SELECT * FROM proposals_archive WHERE id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            $proposal = $stmt->get_result()->fetch_assoc();
            
            if (!$proposal) {
                throw new Exception('Archived proposal not found');
            }
            
            // Restore to main table
            $stmt = $conn->prepare("
                INSERT INTO proposals 
                (id, title, agency_id, owner_user_id, value, status, submitDate, dueDate, validity_date, award_date, winProbability, description, converted_from_opportunity_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("isiidsssssisis",
                $proposal['id'],
                $proposal['title'],
                $proposal['agency_id'],
                $proposal['owner_user_id'],
                $proposal['value'],
                $proposal['status'],
                $proposal['submitDate'],
                $proposal['dueDate'],
                $proposal['validity_date'],
                $proposal['award_date'],
                $proposal['winProbability'],
                $proposal['description'],
                $proposal['converted_from_opportunity_id'],
                $proposal['created_at']
            );
            $stmt->execute();
            
            // Restore related tasks
            $stmt = $conn->prepare("
                INSERT INTO tasks (id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at)
                SELECT id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, NOW()
                FROM tasks_archive WHERE archived_with_type = 'proposal' AND archived_with_id = ?
            ");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            // Restore notes if they exist
            $result = $conn->query("SHOW TABLES LIKE 'proposal_notes'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO proposal_notes (id, proposal_id, sprint_id, user_id, interaction_type, note_text, created_at, updated_at)
                    SELECT id, proposal_id, sprint_id, user_id, interaction_type, note_text, created_at, NOW()
                    FROM proposal_notes_archive WHERE proposal_id = ?
                ");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
                
                // Delete from notes archive
                $stmt = $conn->prepare("DELETE FROM proposal_notes_archive WHERE proposal_id = ?");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
            }
            
            // Restore documents if they exist
            $result = $conn->query("SHOW TABLES LIKE 'proposal_documents'");
            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO proposal_documents (id, proposal_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at)
                    SELECT id, proposal_id, file_name, file_path, file_size, file_type, uploaded_by, uploaded_at
                    FROM proposal_documents_archive WHERE proposal_id = ?
                ");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
                
                // Delete from documents archive
                $stmt = $conn->prepare("DELETE FROM proposal_documents_archive WHERE proposal_id = ?");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
            }
            
            // Delete from tasks archive
            $stmt = $conn->prepare("DELETE FROM tasks_archive WHERE archived_with_type = 'proposal' AND archived_with_id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            // Delete from proposal archive
            $stmt = $conn->prepare("DELETE FROM proposals_archive WHERE id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Proposal restored successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to restore proposal: ' . $e->getMessage(), 500);
        }
        break;

    case 'restoreTask':
        // Only admins can restore archived tasks
        if ($_SESSION['role'] !== 'admin') send_error('Only administrators can restore archived records.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $task_id = intval($data['id'] ?? 0);
        if ($task_id <= 0) send_error('Invalid task ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Get from archive (only standalone tasks, not those archived with opportunity/proposal)
            $stmt = $conn->prepare("SELECT * FROM tasks_archive WHERE id = ? AND archived_with_type IS NULL");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            $task = $stmt->get_result()->fetch_assoc();
            
            if (!$task) {
                throw new Exception('Archived task not found or was archived with an opportunity/proposal');
            }
            
            // Sanitize datetime value
            $created_at = (!empty($task['created_at']) && strtotime($task['created_at'])) 
                ? date('Y-m-d H:i:s', strtotime($task['created_at'])) 
                : date('Y-m-d H:i:s');
            
            // Restore to main table
            $stmt = $conn->prepare("
                INSERT INTO tasks 
                (id, title, relatedTo, related_item_id, dueDate, priority, status, assignedTo, assigned_to_user_id, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ississssiss",
                $task['id'],
                $task['title'],
                $task['relatedTo'],
                $task['related_item_id'],
                $task['dueDate'],
                $task['priority'],
                $task['status'],
                $task['assignedTo'],
                $task['assigned_to_user_id'],
                $task['description'],
                $created_at
            );
            $stmt->execute();
            
            // Delete from task archive
            $stmt = $conn->prepare("DELETE FROM tasks_archive WHERE id = ?");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Task restored successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to restore task: ' . $e->getMessage(), 500);
        }
        break;

    case 'permanentlyDeleteOpportunity':
        // Only admins can permanently delete
        if ($_SESSION['role'] !== 'admin') send_error('Only administrators can permanently delete records.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opportunity_id = intval($data['id'] ?? 0);
        if ($opportunity_id <= 0) send_error('Invalid opportunity ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Delete related notes from archive
            $stmt = $conn->prepare("DELETE FROM opportunity_notes_archive WHERE opportunity_id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            // Delete related tasks from archive
            $stmt = $conn->prepare("DELETE FROM tasks_archive WHERE archived_with_type = 'opportunity' AND archived_with_id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            // Delete related documents from archive and disk
            $result = $conn->query("SHOW TABLES LIKE 'opportunity_documents_archive'");
            if ($result->num_rows > 0) {
                // Get file paths before deleting records
                $stmt = $conn->prepare("SELECT file_path FROM opportunity_documents_archive WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
                $docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Delete files from disk
                foreach ($docs as $doc) {
                    if (file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
                
                // Delete document records from archive
                $stmt = $conn->prepare("DELETE FROM opportunity_documents_archive WHERE opportunity_id = ?");
                $stmt->bind_param("i", $opportunity_id);
                $stmt->execute();
                
                // Try to remove directory if empty
                $upload_dir = "/volume1/web/crm/uploads/opportunities/{$opportunity_id}/";
                if (is_dir($upload_dir)) {
                    @rmdir($upload_dir);
                }
            }
            
            // Delete opportunity from archive
            $stmt = $conn->prepare("DELETE FROM opportunities_archive WHERE id = ?");
            $stmt->bind_param("i", $opportunity_id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Archived opportunity not found');
            }
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Opportunity permanently deleted']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to delete opportunity: ' . $e->getMessage(), 500);
        }
        break;

    case 'permanentlyDeleteProposal':
        // Only admins can permanently delete
        if ($_SESSION['role'] !== 'admin') send_error('Only administrators can permanently delete records.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $proposal_id = intval($data['id'] ?? 0);
        if ($proposal_id <= 0) send_error('Invalid proposal ID', 400);
        
        $conn->begin_transaction();
        
        try {
            // Delete related notes from archive
            $stmt = $conn->prepare("DELETE FROM proposal_notes_archive WHERE proposal_id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            // Delete related tasks from archive
            $stmt = $conn->prepare("DELETE FROM tasks_archive WHERE archived_with_type = 'proposal' AND archived_with_id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            // Delete related documents from archive and disk
            $result = $conn->query("SHOW TABLES LIKE 'proposal_documents_archive'");
            if ($result->num_rows > 0) {
                // Get file paths before deleting records
                $stmt = $conn->prepare("SELECT file_path FROM proposal_documents_archive WHERE proposal_id = ?");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
                $docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Delete files from disk
                foreach ($docs as $doc) {
                    if (file_exists($doc['file_path'])) {
                        unlink($doc['file_path']);
                    }
                }
                
                // Delete document records from archive
                $stmt = $conn->prepare("DELETE FROM proposal_documents_archive WHERE proposal_id = ?");
                $stmt->bind_param("i", $proposal_id);
                $stmt->execute();
                
                // Try to remove directory if empty
                $upload_dir = "/volume1/web/crm/uploads/proposals/{$proposal_id}/";
                if (is_dir($upload_dir)) {
                    @rmdir($upload_dir);
                }
            }
            
            // Delete proposal from archive
            $stmt = $conn->prepare("DELETE FROM proposals_archive WHERE id = ?");
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Archived proposal not found');
            }
            
            $conn->commit();
            send_response(['success' => true, 'message' => 'Proposal permanently deleted']);
            
        } catch (Exception $e) {
            $conn->rollback();
            send_error('Failed to delete proposal: ' . $e->getMessage(), 500);
        }
        break;

    case 'permanentlyDeleteTask':
        // Only admins can permanently delete
        if ($_SESSION['role'] !== 'admin') send_error('Only administrators can permanently delete records.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $task_id = intval($data['id'] ?? 0);
        if ($task_id <= 0) send_error('Invalid task ID', 400);
        
        try {
            // Delete task from archive (only standalone tasks)
            $stmt = $conn->prepare("DELETE FROM tasks_archive WHERE id = ? AND archived_with_type IS NULL");
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                send_error('Archived task not found or was archived with an opportunity/proposal', 404);
            }
            
            send_response(['success' => true, 'message' => 'Task permanently deleted']);
            
        } catch (Exception $e) {
            send_error('Failed to delete task: ' . $e->getMessage(), 500);
        }
        break;

    // =============================================
    // DOCUMENT MANAGEMENT ENDPOINTS
    // =============================================
    
    case 'getOpportunityDocuments':
        if (!has_permission('opportunity', 'view')) send_error('Permission denied.', 403);
        
        $opportunity_id = intval($_GET['opportunity_id'] ?? 0);
        if ($opportunity_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Debug: Check raw count first
        $count_result = $conn->query("SELECT COUNT(*) as cnt FROM opportunity_documents WHERE opportunity_id = " . $opportunity_id);
        $count_row = $count_result->fetch_assoc();
        $debug_count = $count_row['cnt'];
        
        $stmt = $conn->prepare("
            SELECT SQL_NO_CACHE d.*, u.username AS uploadedByUsername, COALESCE(u.display_name, u.username) AS uploadedByDisplayName
            FROM opportunity_documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.opportunity_id = ?
            ORDER BY d.uploaded_at DESC
        ");
        $stmt->bind_param("i", $opportunity_id);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'documents' => $documents, 'debug_count' => $debug_count, 'debug_opp_id' => $opportunity_id]);
        break;
    
    case 'getProposalDocuments':
        if (!has_permission('proposal', 'view')) send_error('Permission denied.', 403);
        
        $proposal_id = intval($_GET['proposal_id'] ?? 0);
        if ($proposal_id <= 0) send_error('Invalid proposal ID', 400);
        
        $stmt = $conn->prepare("
            SELECT SQL_NO_CACHE d.*, u.username AS uploadedByUsername, COALESCE(u.display_name, u.username) AS uploadedByDisplayName
            FROM proposal_documents d
            LEFT JOIN users u ON d.uploaded_by = u.id
            WHERE d.proposal_id = ?
            ORDER BY d.uploaded_at DESC
        ");
        $stmt->bind_param("i", $proposal_id);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response(['success' => true, 'documents' => $documents]);
        break;
    
    case 'uploadOpportunityDocument':
        if (!has_permission('opportunity', 'view')) send_error('Permission denied.', 403);
        
        $opportunity_id = intval($_POST['opportunity_id'] ?? 0);
        if ($opportunity_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // For specialty users, verify they have access to this opportunity
        if (is_specialty_user()) {
            $allowedOppIds = get_specialty_opportunity_ids($conn, $_SESSION['user_id']);
            if (!in_array($opportunity_id, $allowedOppIds)) {
                send_error('Permission denied. You do not have access to this opportunity.', 403);
            }
        }
        
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            send_error('No file uploaded or upload error', 400);
        }
        
        $file = $_FILES['document'];
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'csv', 'zip'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            send_error('File type not allowed. Allowed types: ' . implode(', ', $allowed_types), 400);
        }
        
        // Create directory if it doesn't exist
        $upload_dir = "/volume1/web/crm/uploads/opportunities/{$opportunity_id}/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename to prevent overwrites
        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $unique_filename = time() . '_' . $safe_filename;
        $file_path = $upload_dir . $unique_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            send_error('Failed to save file', 500);
        }
        
        // Insert record into database
        $stmt = $conn->prepare("
            INSERT INTO opportunity_documents (opportunity_id, file_name, file_path, file_size, file_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            send_error('Database prepare error: ' . $conn->error, 500);
        }
        $stmt->bind_param("issisi", $opportunity_id, $file['name'], $file_path, $file['size'], $file['type'], $_SESSION['user_id']);
        if (!$stmt->execute()) {
            send_error('Database insert error: ' . $stmt->error, 500);
        }
        
        $document_id = $conn->insert_id;
        
        // Explicitly commit to ensure data is persisted
        $conn->commit();
        
        send_response([
            'success' => true, 
            'message' => 'Document uploaded successfully',
            'document_id' => $document_id
        ]);
        break;
    
    case 'uploadProposalDocument':
        if (!has_permission('proposal', 'view')) send_error('Permission denied.', 403);
        
        $proposal_id = intval($_POST['proposal_id'] ?? 0);
        if ($proposal_id <= 0) send_error('Invalid proposal ID', 400);
        
        // For specialty users, verify they have access to this proposal
        if (is_specialty_user()) {
            $allowedPropIds = get_specialty_proposal_ids($conn, $_SESSION['user_id']);
            if (!in_array($proposal_id, $allowedPropIds)) {
                send_error('Permission denied. You do not have access to this proposal.', 403);
            }
        }
        
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            send_error('No file uploaded or upload error', 400);
        }
        
        $file = $_FILES['document'];
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'csv', 'zip'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            send_error('File type not allowed. Allowed types: ' . implode(', ', $allowed_types), 400);
        }
        
        // Create directory if it doesn't exist
        $upload_dir = "/volume1/web/crm/uploads/proposals/{$proposal_id}/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename to prevent overwrites
        $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $unique_filename = time() . '_' . $safe_filename;
        $file_path = $upload_dir . $unique_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            send_error('Failed to save file', 500);
        }
        
        // Insert record into database
        $stmt = $conn->prepare("
            INSERT INTO proposal_documents (proposal_id, file_name, file_path, file_size, file_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            send_error('Database prepare error: ' . $conn->error, 500);
        }
        $stmt->bind_param("issisi", $proposal_id, $file['name'], $file_path, $file['size'], $file['type'], $_SESSION['user_id']);
        if (!$stmt->execute()) {
            send_error('Database insert error: ' . $stmt->error, 500);
        }
        
        $document_id = $conn->insert_id;
        
        // Explicitly commit to ensure data is persisted
        $conn->commit();
        
        send_response([
            'success' => true, 
            'message' => 'Document uploaded successfully',
            'document_id' => $document_id
        ]);
        break;
    
    case 'deleteOpportunityDocument':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $document_id = intval($data['id'] ?? 0);
        if ($document_id <= 0) send_error('Invalid document ID', 400);
        
        // Get file path before deleting
        $stmt = $conn->prepare("SELECT SQL_NO_CACHE file_path FROM opportunity_documents WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        
        if (!$doc) send_error('Document not found', 404);
        
        // Delete file from disk
        if (file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }
        
        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM opportunity_documents WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        
        send_response(['success' => true, 'message' => 'Document deleted successfully']);
        break;
    
    case 'deleteProposalDocument':
        if (!has_permission('proposal', 'update')) send_error('Permission denied.', 403);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $document_id = intval($data['id'] ?? 0);
        if ($document_id <= 0) send_error('Invalid document ID', 400);
        
        // Get file path before deleting
        $stmt = $conn->prepare("SELECT SQL_NO_CACHE file_path FROM proposal_documents WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        
        if (!$doc) send_error('Document not found', 404);
        
        // Delete file from disk
        if (file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }
        
        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM proposal_documents WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        
        send_response(['success' => true, 'message' => 'Document deleted successfully']);
        break;
    
    case 'downloadDocument':
        $type = $_GET['type'] ?? '';
        $document_id = intval($_GET['id'] ?? 0);
        
        if (!in_array($type, ['opportunity', 'proposal'])) send_error('Invalid document type', 400);
        if ($document_id <= 0) send_error('Invalid document ID', 400);
        
        // Check permission
        if (!has_permission($type, 'view')) send_error('Permission denied.', 403);
        
        $table = $type === 'opportunity' ? 'opportunity_documents' : 'proposal_documents';
        $stmt = $conn->prepare("SELECT file_name, file_path, file_type FROM {$table} WHERE id = ?");
        $stmt->bind_param("i", $document_id);
        $stmt->execute();
        $doc = $stmt->get_result()->fetch_assoc();
        
        if (!$doc) send_error('Document not found', 404);
        if (!file_exists($doc['file_path'])) send_error('File not found on server', 404);
        
        // Send file for download
        header('Content-Type: ' . ($doc['file_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
        header('Content-Length: ' . filesize($doc['file_path']));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($doc['file_path']);
        exit;
        break;

    // =====================================================
    // OPPORTUNITY WORKSPACE API ENDPOINTS
    // =====================================================
    
    case 'getOpportunityWorkspace':
        if (!has_permission('opportunity', 'view')) send_error('Permission denied.');
        
        $opp_id = intval($_GET['id'] ?? 0);
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Get main opportunity data
        $stmt = $conn->prepare("
            SELECT o.*, a.name as agency_name,
                   u.display_name as owner_name, u.username as owner_username
            FROM opportunities o
            LEFT JOIN agencies a ON o.agency_id = a.id
            LEFT JOIN users u ON o.owner_user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $opp_id);
        $stmt->execute();
        $opportunity = $stmt->get_result()->fetch_assoc();
        
        if (!$opportunity) send_error('Opportunity not found', 404);
        
        // Initialize empty arrays/nulls for workspace data
        $qualification = null;
        $qual_contacts = [];
        $capture = null;
        $competitors = [];
        $partners = [];
        $bid_decision = null;
        $risks = [];
        $tasks = [];
        
        // Get qualification data (with error handling for missing table)
        $result = $conn->query("SELECT * FROM opportunity_qualification WHERE opportunity_id = " . intval($opp_id));
        if ($result) {
            $qualification = $result->fetch_assoc();
        }
        
        // Get qualification contacts (decision makers/influencers) - both federal and commercial
        $qual_contacts = [];
        
        // Federal contacts
        $result = $conn->query("
            SELECT oqc.*, 'federal' as contact_type, c.firstName, c.lastName, c.title, c.email, a.name as agencyName
            FROM opportunity_qualification_contacts oqc
            JOIN contacts c ON oqc.contact_id = c.id
            LEFT JOIN agencies a ON c.agency_id = a.id
            WHERE oqc.opportunity_id = " . intval($opp_id) . "
            AND (oqc.contact_type = 'federal' OR oqc.contact_type IS NULL)
        ");
        if ($result) {
            $qual_contacts = array_merge($qual_contacts, $result->fetch_all(MYSQLI_ASSOC));
        }
        
        // Commercial contacts
        $result = $conn->query("
            SELECT oqc.*, 'commercial' as contact_type, cc.first_name as firstName, cc.last_name as lastName, cc.title, cc.email, co.company_name as companyName
            FROM opportunity_qualification_contacts oqc
            JOIN company_contacts cc ON oqc.contact_id = cc.id
            LEFT JOIN companies co ON cc.company_id = co.id
            WHERE oqc.opportunity_id = " . intval($opp_id) . "
            AND oqc.contact_type = 'commercial'
        ");
        if ($result) {
            $qual_contacts = array_merge($qual_contacts, $result->fetch_all(MYSQLI_ASSOC));
        }
        
        // Get capture data
        $result = $conn->query("SELECT * FROM opportunity_capture WHERE opportunity_id = " . intval($opp_id));
        if ($result) {
            $capture = $result->fetch_assoc();
        }
        
        // Get competitors
        $result = $conn->query("SELECT * FROM opportunity_competitors WHERE opportunity_id = " . intval($opp_id) . " ORDER BY is_incumbent DESC, win_probability DESC");
        if ($result) {
            $competitors = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get teaming partners
        $result = $conn->query("
            SELECT otp.*, cc.first_name, cc.last_name, co.company_name, cc.email
            FROM opportunity_teaming_partners otp
            LEFT JOIN company_contacts cc ON otp.company_contact_id = cc.id
            LEFT JOIN companies co ON cc.company_id = co.id
            WHERE otp.opportunity_id = " . intval($opp_id) . "
            ORDER BY otp.status DESC
        ");
        if ($result) {
            $partners = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get bid decision data
        $result = $conn->query("
            SELECT obd.*, pm.display_name as proposal_manager_name, pm.username as proposal_manager_username,
                   da.display_name as decision_authority_name
            FROM opportunity_bid_decision obd
            LEFT JOIN users pm ON obd.proposal_manager_id = pm.id
            LEFT JOIN users da ON obd.decision_authority_user_id = da.id
            WHERE obd.opportunity_id = " . intval($opp_id)
        );
        if ($result) {
            $bid_decision = $result->fetch_assoc();
        }
        
        // Get risks
        $result = $conn->query("
            SELECT r.*, u.display_name as owner_name
            FROM opportunity_risks r
            LEFT JOIN users u ON r.owner_user_id = u.id
            WHERE r.opportunity_id = " . intval($opp_id) . "
            ORDER BY 
                CASE r.probability WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END,
                CASE r.impact WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END
        ");
        if ($result) {
            $risks = $result->fetch_all(MYSQLI_ASSOC);
        }
        
        // Get related tasks
        $stmt = $conn->prepare("
            SELECT t.*, t.workspace_phase, u.display_name as assigned_to_name
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to_user_id = u.id
            WHERE t.relatedTo = 'Opportunity' AND t.related_item_id = ?
            ORDER BY t.dueDate ASC
        ");
        $stmt->bind_param("i", $opp_id);
        $stmt->execute();
        $tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        send_response([
            'success' => true,
            'opportunity' => $opportunity,
            'qualification' => $qualification,
            'qualification_contacts' => $qual_contacts,
            'capture' => $capture,
            'competitors' => $competitors,
            'teaming_partners' => $partners,
            'bid_decision' => $bid_decision,
            'risks' => $risks,
            'tasks' => $tasks
        ]);
        break;
    
    case 'saveOpportunityQualification':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Calculate weighted qualification score
        $weights = [
            'score_know_customer' => 0.15,
            'score_worked_before' => 0.10,
            'score_decision_maker_access' => 0.10,
            'score_funded' => 0.15,
            'score_understand_scope' => 0.10,
            'score_realistic_timeline' => 0.05,
            'score_know_incumbent' => 0.10,
            'score_can_beat_competition' => 0.10,
            'score_technical_capability' => 0.10,
            'score_past_performance' => 0.05
        ];
        
        $total_score = 0;
        foreach ($weights as $field => $weight) {
            $score = intval($data[$field] ?? 0);
            $total_score += ($score / 10) * $weight * 100;
        }
        $data['qualification_score'] = round($total_score, 2);
        
        // Check if record exists
        $stmt = $conn->prepare("SELECT id FROM opportunity_qualification WHERE opportunity_id = ?");
        $stmt->bind_param("i", $opp_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        
        if ($exists) {
            // Update
            $stmt = $conn->prepare("
                UPDATE opportunity_qualification SET
                    solicitation_number = ?,
                    naics_code = ?,
                    set_aside_type = ?,
                    contract_type = ?,
                    contract_vehicle = ?,
                    expected_rfp_date = ?,
                    expected_award_date = ?,
                    period_of_performance = ?,
                    score_know_customer = ?,
                    score_worked_before = ?,
                    score_decision_maker_access = ?,
                    score_funded = ?,
                    score_understand_scope = ?,
                    score_realistic_timeline = ?,
                    score_know_incumbent = ?,
                    score_can_beat_competition = ?,
                    score_technical_capability = ?,
                    score_past_performance = ?,
                    qualification_score = ?,
                    customer_pain_points = ?,
                    hot_buttons = ?,
                    evaluation_priorities = ?,
                    incumbent_issues = ?,
                    qualification_decision = ?,
                    decision_notes = ?,
                    decision_date = ?,
                    decision_by_user_id = ?
                WHERE opportunity_id = ?
            ");
            
            $decision_date = ($data['qualification_decision'] ?? 'pending') !== 'pending' ? date('Y-m-d H:i:s') : null;
            $decision_by = ($data['qualification_decision'] ?? 'pending') !== 'pending' ? $_SESSION['user_id'] : null;
            
            // Prepare variables for bind_param (can't use expressions directly)
            $solicitation_number = $data['solicitation_number'] ?? null;
            $naics_code = $data['naics_code'] ?? null;
            $set_aside_type = $data['set_aside_type'] ?? null;
            $contract_type = $data['contract_type'] ?? null;
            $contract_vehicle = $data['contract_vehicle'] ?? null;
            $expected_rfp_date = !empty($data['expected_rfp_date']) ? $data['expected_rfp_date'] : null;
            $expected_award_date = !empty($data['expected_award_date']) ? $data['expected_award_date'] : null;
            $period_of_performance = $data['period_of_performance'] ?? null;
            $score_know_customer = intval($data['score_know_customer'] ?? 0);
            $score_worked_before = intval($data['score_worked_before'] ?? 0);
            $score_decision_maker_access = intval($data['score_decision_maker_access'] ?? 0);
            $score_funded = intval($data['score_funded'] ?? 0);
            $score_understand_scope = intval($data['score_understand_scope'] ?? 0);
            $score_realistic_timeline = intval($data['score_realistic_timeline'] ?? 0);
            $score_know_incumbent = intval($data['score_know_incumbent'] ?? 0);
            $score_can_beat_competition = intval($data['score_can_beat_competition'] ?? 0);
            $score_technical_capability = intval($data['score_technical_capability'] ?? 0);
            $score_past_performance = intval($data['score_past_performance'] ?? 0);
            $qualification_score = $data['qualification_score'];
            $customer_pain_points = $data['customer_pain_points'] ?? null;
            $hot_buttons = $data['hot_buttons'] ?? null;
            $evaluation_priorities = $data['evaluation_priorities'] ?? null;
            $incumbent_issues = $data['incumbent_issues'] ?? null;
            $qualification_decision = $data['qualification_decision'] ?? 'pending';
            $decision_notes = $data['decision_notes'] ?? null;
            
            $stmt->bind_param("ssssssssiiiiiiiiiidsssssssii",
                $solicitation_number,
                $naics_code,
                $set_aside_type,
                $contract_type,
                $contract_vehicle,
                $expected_rfp_date,
                $expected_award_date,
                $period_of_performance,
                $score_know_customer,
                $score_worked_before,
                $score_decision_maker_access,
                $score_funded,
                $score_understand_scope,
                $score_realistic_timeline,
                $score_know_incumbent,
                $score_can_beat_competition,
                $score_technical_capability,
                $score_past_performance,
                $qualification_score,
                $customer_pain_points,
                $hot_buttons,
                $evaluation_priorities,
                $incumbent_issues,
                $qualification_decision,
                $decision_notes,
                $decision_date,
                $decision_by,
                $opp_id
            );
        } else {
            // Insert
            $stmt = $conn->prepare("
                INSERT INTO opportunity_qualification (
                    opportunity_id, solicitation_number, naics_code, set_aside_type, contract_type,
                    contract_vehicle, expected_rfp_date, expected_award_date, period_of_performance,
                    score_know_customer, score_worked_before, score_decision_maker_access, score_funded,
                    score_understand_scope, score_realistic_timeline, score_know_incumbent,
                    score_can_beat_competition, score_technical_capability, score_past_performance,
                    qualification_score, customer_pain_points, hot_buttons, evaluation_priorities,
                    incumbent_issues, qualification_decision, decision_notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Prepare variables for bind_param (can't use expressions directly)
            $solicitation_number = $data['solicitation_number'] ?? null;
            $naics_code = $data['naics_code'] ?? null;
            $set_aside_type = $data['set_aside_type'] ?? null;
            $contract_type = $data['contract_type'] ?? null;
            $contract_vehicle = $data['contract_vehicle'] ?? null;
            $expected_rfp_date = !empty($data['expected_rfp_date']) ? $data['expected_rfp_date'] : null;
            $expected_award_date = !empty($data['expected_award_date']) ? $data['expected_award_date'] : null;
            $period_of_performance = $data['period_of_performance'] ?? null;
            $score_know_customer = intval($data['score_know_customer'] ?? 0);
            $score_worked_before = intval($data['score_worked_before'] ?? 0);
            $score_decision_maker_access = intval($data['score_decision_maker_access'] ?? 0);
            $score_funded = intval($data['score_funded'] ?? 0);
            $score_understand_scope = intval($data['score_understand_scope'] ?? 0);
            $score_realistic_timeline = intval($data['score_realistic_timeline'] ?? 0);
            $score_know_incumbent = intval($data['score_know_incumbent'] ?? 0);
            $score_can_beat_competition = intval($data['score_can_beat_competition'] ?? 0);
            $score_technical_capability = intval($data['score_technical_capability'] ?? 0);
            $score_past_performance = intval($data['score_past_performance'] ?? 0);
            $qualification_score = $data['qualification_score'];
            $customer_pain_points = $data['customer_pain_points'] ?? null;
            $hot_buttons = $data['hot_buttons'] ?? null;
            $evaluation_priorities = $data['evaluation_priorities'] ?? null;
            $incumbent_issues = $data['incumbent_issues'] ?? null;
            $qualification_decision = $data['qualification_decision'] ?? 'pending';
            $decision_notes = $data['decision_notes'] ?? null;
            
            $stmt->bind_param("issssssssiiiiiiiiiidssssss",
                $opp_id,
                $solicitation_number,
                $naics_code,
                $set_aside_type,
                $contract_type,
                $contract_vehicle,
                $expected_rfp_date,
                $expected_award_date,
                $period_of_performance,
                $score_know_customer,
                $score_worked_before,
                $score_decision_maker_access,
                $score_funded,
                $score_understand_scope,
                $score_realistic_timeline,
                $score_know_incumbent,
                $score_can_beat_competition,
                $score_technical_capability,
                $score_past_performance,
                $qualification_score,
                $customer_pain_points,
                $hot_buttons,
                $evaluation_priorities,
                $incumbent_issues,
                $qualification_decision,
                $decision_notes
            );
        }
        
        if (!$stmt->execute()) {
            send_error('Failed to save qualification: ' . $stmt->error, 500);
        }
        
        // Update opportunity phase and status if qualification decision is "pursue"
        if (($data['qualification_decision'] ?? 'pending') === 'pursue') {
            $stmt = $conn->prepare("UPDATE opportunities SET workspace_phase = 'capture', status = 'Capture' WHERE id = ? AND workspace_phase = 'qualification'");
            $stmt->bind_param("i", $opp_id);
            $stmt->execute();
        }
        
        send_response(['success' => true, 'qualification_score' => $data['qualification_score']]);
        break;
    
    case 'saveQualificationContact':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        $contact_id = intval($data['contact_id'] ?? 0);
        $contact_type = $data['contact_type'] ?? 'federal';
        
        if ($opp_id <= 0 || $contact_id <= 0) send_error('Invalid IDs', 400);
        
        $stmt = $conn->prepare("
            INSERT INTO opportunity_qualification_contacts (opportunity_id, contact_id, contact_type, contact_role, notes)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE contact_role = VALUES(contact_role), notes = VALUES(notes)
        ");
        $stmt->bind_param("iisss", $opp_id, $contact_id, $contact_type, $data['contact_role'], $data['notes']);
        
        if (!$stmt->execute()) {
            send_error('Failed to save contact: ' . $stmt->error, 500);
        }
        
        send_response(['success' => true]);
        break;
    
    case 'deleteQualificationContact':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        $contact_id = intval($data['contact_id'] ?? 0);
        $contact_type = $data['contact_type'] ?? 'federal';
        
        $stmt = $conn->prepare("DELETE FROM opportunity_qualification_contacts WHERE opportunity_id = ? AND contact_id = ? AND contact_type = ?");
        $stmt->bind_param("iis", $opp_id, $contact_id, $contact_type);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'saveOpportunityCapture':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Calculate capture readiness score based on completeness
        $readiness_fields = [
            'win_theme_1_title', 'win_theme_1_message', 'technical_approach',
            'management_approach', 'teaming_strategy', 'price_to_win'
        ];
        $filled = 0;
        foreach ($readiness_fields as $field) {
            if (!empty($data[$field])) $filled++;
        }
        $capture_readiness = round(($filled / count($readiness_fields)) * 100, 2);
        
        // Prepare all variables for bind_param (can't use expressions)
        $win_theme_1_title = $data['win_theme_1_title'] ?? null;
        $win_theme_1_message = $data['win_theme_1_message'] ?? null;
        $win_theme_2_title = $data['win_theme_2_title'] ?? null;
        $win_theme_2_message = $data['win_theme_2_message'] ?? null;
        $win_theme_3_title = $data['win_theme_3_title'] ?? null;
        $win_theme_3_message = $data['win_theme_3_message'] ?? null;
        $discriminators = $data['discriminators'] ?? null;
        $ghosting_strategy = $data['ghosting_strategy'] ?? null;
        $technical_approach = $data['technical_approach'] ?? null;
        $management_approach = $data['management_approach'] ?? null;
        $key_personnel_requirements = $data['key_personnel_requirements'] ?? null;
        $teaming_strategy = $data['teaming_strategy'] ?? null;
        $price_to_win = !empty($data['price_to_win']) ? floatval($data['price_to_win']) : null;
        $pricing_strategy = $data['pricing_strategy'] ?? null;
        $margin_target = !empty($data['margin_target']) ? floatval($data['margin_target']) : null;
        $cost_drivers = $data['cost_drivers'] ?? null;
        $milestone_draft_rfp_review = !empty($data['milestone_draft_rfp_review']) ? 1 : 0;
        $milestone_industry_day = !empty($data['milestone_industry_day']) ? 1 : 0;
        $milestone_questions_submitted = !empty($data['milestone_questions_submitted']) ? 1 : 0;
        $milestone_pink_team = !empty($data['milestone_pink_team']) ? 1 : 0;
        $milestone_teaming_signed = !empty($data['milestone_teaming_signed']) ? 1 : 0;
        $milestone_pricing_approved = !empty($data['milestone_pricing_approved']) ? 1 : 0;
        $proceed_to_bid = $data['proceed_to_bid'] ?? 'pending';
        $capture_decision_notes = $data['capture_decision_notes'] ?? null;
        $decision_date = $proceed_to_bid !== 'pending' ? date('Y-m-d H:i:s') : null;
        $decision_by = $proceed_to_bid !== 'pending' ? $_SESSION['user_id'] : null;
        
        // Check if record exists
        $stmt = $conn->prepare("SELECT id FROM opportunity_capture WHERE opportunity_id = ?");
        $stmt->bind_param("i", $opp_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        
        if ($exists) {
            $stmt = $conn->prepare("
                UPDATE opportunity_capture SET
                    win_theme_1_title = ?, win_theme_1_message = ?,
                    win_theme_2_title = ?, win_theme_2_message = ?,
                    win_theme_3_title = ?, win_theme_3_message = ?,
                    discriminators = ?, ghosting_strategy = ?,
                    technical_approach = ?, management_approach = ?,
                    key_personnel_requirements = ?, teaming_strategy = ?,
                    price_to_win = ?, pricing_strategy = ?,
                    margin_target = ?, cost_drivers = ?,
                    milestone_draft_rfp_review = ?, milestone_industry_day = ?,
                    milestone_questions_submitted = ?, milestone_pink_team = ?,
                    milestone_teaming_signed = ?, milestone_pricing_approved = ?,
                    capture_readiness_score = ?,
                    proceed_to_bid = ?, capture_decision_notes = ?,
                    capture_decision_date = ?, capture_decision_by_user_id = ?
                WHERE opportunity_id = ?
            ");
            
            $stmt->bind_param("ssssssssssssdsdsiiiiiidsssii",
                $win_theme_1_title, $win_theme_1_message,
                $win_theme_2_title, $win_theme_2_message,
                $win_theme_3_title, $win_theme_3_message,
                $discriminators, $ghosting_strategy,
                $technical_approach, $management_approach,
                $key_personnel_requirements, $teaming_strategy,
                $price_to_win, $pricing_strategy,
                $margin_target, $cost_drivers,
                $milestone_draft_rfp_review,
                $milestone_industry_day,
                $milestone_questions_submitted,
                $milestone_pink_team,
                $milestone_teaming_signed,
                $milestone_pricing_approved,
                $capture_readiness,
                $proceed_to_bid,
                $capture_decision_notes,
                $decision_date, $decision_by,
                $opp_id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO opportunity_capture (
                    opportunity_id, win_theme_1_title, win_theme_1_message,
                    win_theme_2_title, win_theme_2_message,
                    win_theme_3_title, win_theme_3_message,
                    discriminators, ghosting_strategy,
                    technical_approach, management_approach,
                    key_personnel_requirements, teaming_strategy,
                    price_to_win, pricing_strategy, margin_target, cost_drivers,
                    capture_readiness_score
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("issssssssssssdsdsd",
                $opp_id,
                $win_theme_1_title, $win_theme_1_message,
                $win_theme_2_title, $win_theme_2_message,
                $win_theme_3_title, $win_theme_3_message,
                $discriminators, $ghosting_strategy,
                $technical_approach, $management_approach,
                $key_personnel_requirements, $teaming_strategy,
                $price_to_win, $pricing_strategy,
                $margin_target, $cost_drivers,
                $capture_readiness
            );
        }
        
        if (!$stmt->execute()) {
            send_error('Failed to save capture: ' . $stmt->error, 500);
        }
        
        // Update opportunity phase and status if capture is complete
        if ($proceed_to_bid === 'yes') {
            $stmt = $conn->prepare("UPDATE opportunities SET workspace_phase = 'bid_decision', status = 'Bid' WHERE id = ? AND workspace_phase = 'capture'");
            $stmt->bind_param("i", $opp_id);
            $stmt->execute();
        }
        
        send_response(['success' => true, 'capture_readiness_score' => $capture_readiness]);
        break;
    
    case 'saveCompetitor':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        $id = intval($data['id'] ?? 0);
        
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Prepare variables for bind_param
        $competitor_name = $data['competitor_name'] ?? null;
        $strengths = $data['strengths'] ?? null;
        $weaknesses = $data['weaknesses'] ?? null;
        $ghost_strategy = $data['ghost_strategy'] ?? null;
        $win_probability = intval($data['win_probability'] ?? 0);
        $is_incumbent = !empty($data['is_incumbent']) ? 1 : 0;
        
        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE opportunity_competitors SET
                    competitor_name = ?, strengths = ?, weaknesses = ?,
                    ghost_strategy = ?, win_probability = ?, is_incumbent = ?
                WHERE id = ? AND opportunity_id = ?
            ");
            $stmt->bind_param("ssssiiii",
                $competitor_name, $strengths, $weaknesses,
                $ghost_strategy, $win_probability, $is_incumbent,
                $id, $opp_id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO opportunity_competitors (opportunity_id, competitor_name, strengths, weaknesses, ghost_strategy, win_probability, is_incumbent)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssiii",
                $opp_id, $competitor_name, $strengths, $weaknesses,
                $ghost_strategy, $win_probability, $is_incumbent
            );
        }
        
        if (!$stmt->execute()) {
            send_error('Failed to save competitor: ' . $stmt->error, 500);
        }
        
        $new_id = $id > 0 ? $id : $conn->insert_id;
        send_response(['success' => true, 'id' => $new_id]);
        break;
    
    case 'deleteCompetitor':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM opportunity_competitors WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'saveTeamingPartner':
      try {
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            send_error('Invalid JSON input', 400);
        }
        $opp_id = intval($data['opportunity_id'] ?? 0);
        $id = intval($data['id'] ?? 0);

        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);

        // Table columns: id, opportunity_id, company_contact_id(int), partner_name(varchar255),
        // role enum('prime','sub','mentor','protege','jv_partner'),
        // capability(text), status enum('prospect','engaged','committed','signed'),
        // nda_date(date), mou_date(date), teaming_agreement_date(date), notes(text)
        $partner_name = $data['partner_name'] ?? '';
        $role = $data['role'] ?? 'sub';
        $capability = $data['capability'] ?? '';
        $status = $data['status'] ?? 'prospect';
        $nda_date = !empty($data['nda_date']) ? $data['nda_date'] : null;
        $mou_date = !empty($data['mou_date']) ? $data['mou_date'] : null;
        $teaming_agreement_date = !empty($data['teaming_agreement_date']) ? $data['teaming_agreement_date'] : null;
        $notes = $data['notes'] ?? null;

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE opportunity_teaming_partners SET partner_name=?, role=?, capability=?, status=?, nda_date=?, mou_date=?, teaming_agreement_date=?, notes=? WHERE id=? AND opportunity_id=?");
            if (!$stmt) { send_error('Prepare failed: ' . $conn->error, 500); }
            $stmt->bind_param("ssssssssii", $partner_name, $role, $capability, $status, $nda_date, $mou_date, $teaming_agreement_date, $notes, $id, $opp_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO opportunity_teaming_partners (opportunity_id, partner_name, role, capability, status, nda_date, mou_date, teaming_agreement_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) { send_error('Prepare failed: ' . $conn->error, 500); }
            $stmt->bind_param("issssssss", $opp_id, $partner_name, $role, $capability, $status, $nda_date, $mou_date, $teaming_agreement_date, $notes);
        }

        if (!$stmt->execute()) {
            send_error('Execute failed: ' . $stmt->error, 500);
        }

        $new_id = $id > 0 ? $id : $conn->insert_id;
        send_response(['success' => true, 'id' => $new_id]);
      } catch (\Throwable $e) {
        send_error('Exception: ' . $e->getMessage(), 500);
      }
      break;
    
    case 'deleteTeamingPartner':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM opportunity_teaming_partners WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'saveOpportunityBidDecision':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Calculate weighted bid decision score
        $weights = [
            // Strategic Fit (15%)
            'score_strategic_alignment' => 0.075,
            'score_new_market' => 0.075,
            // Win Probability (25%)
            'score_competitive_position' => 0.085,
            'score_customer_relationship' => 0.085,
            'score_solution_readiness' => 0.08,
            // Resource Availability (20%)
            'score_proposal_team' => 0.07,
            'score_key_personnel' => 0.065,
            'score_smes_available' => 0.065,
            // Risk Assessment (20%)
            'score_technical_risk' => 0.07,
            'score_cost_schedule_risk' => 0.065,
            'score_contract_terms' => 0.065,
            // Financial (20%)
            'score_acceptable_margin' => 0.07,
            'score_bp_budget' => 0.065,
            'score_revenue_potential' => 0.065
        ];
        
        $total_score = 0;
        foreach ($weights as $field => $weight) {
            $score = intval($data[$field] ?? 0);
            $total_score += ($score / 5) * $weight * 100;
        }
        $bid_decision_score = round($total_score, 2);
        
        // Prepare all variables for bind_param (can't use expressions)
        $score_strategic_alignment = intval($data['score_strategic_alignment'] ?? 0);
        $score_new_market = intval($data['score_new_market'] ?? 0);
        $score_competitive_position = intval($data['score_competitive_position'] ?? 0);
        $score_customer_relationship = intval($data['score_customer_relationship'] ?? 0);
        $score_solution_readiness = intval($data['score_solution_readiness'] ?? 0);
        $score_proposal_team = intval($data['score_proposal_team'] ?? 0);
        $score_key_personnel = intval($data['score_key_personnel'] ?? 0);
        $score_smes_available = intval($data['score_smes_available'] ?? 0);
        $score_technical_risk = intval($data['score_technical_risk'] ?? 0);
        $score_cost_schedule_risk = intval($data['score_cost_schedule_risk'] ?? 0);
        $score_contract_terms = intval($data['score_contract_terms'] ?? 0);
        $score_acceptable_margin = intval($data['score_acceptable_margin'] ?? 0);
        $score_bp_budget = intval($data['score_bp_budget'] ?? 0);
        $score_revenue_potential = intval($data['score_revenue_potential'] ?? 0);
        $proposal_manager_id = !empty($data['proposal_manager_id']) ? intval($data['proposal_manager_id']) : null;
        $volume_leads = $data['volume_leads'] ?? null;
        $technical_writers_needed = intval($data['technical_writers_needed'] ?? 0);
        $technical_writers_available = intval($data['technical_writers_available'] ?? 0);
        $smes_needed = intval($data['smes_needed'] ?? 0);
        $smes_available = intval($data['smes_available'] ?? 0);
        $graphics_hours_needed = intval($data['graphics_hours_needed'] ?? 0);
        $graphics_hours_available = intval($data['graphics_hours_available'] ?? 0);
        $bp_budget_needed = !empty($data['bp_budget_needed']) ? floatval($data['bp_budget_needed']) : null;
        $bp_budget_available = !empty($data['bp_budget_available']) ? floatval($data['bp_budget_available']) : null;
        $recommendation = $data['recommendation'] ?? null;
        $conditions = $data['conditions'] ?? null;
        $final_decision = $data['final_decision'] ?? 'pending';
        $justification = $data['justification'] ?? null;
        $lessons_learned = $data['lessons_learned'] ?? null;
        $revisit_date = !empty($data['revisit_date']) ? $data['revisit_date'] : null;
        $decision_date = $final_decision !== 'pending' ? date('Y-m-d H:i:s') : null;
        $decision_by = $final_decision !== 'pending' ? $_SESSION['user_id'] : null;
        
        // Check if record exists
        $stmt = $conn->prepare("SELECT id FROM opportunity_bid_decision WHERE opportunity_id = ?");
        $stmt->bind_param("i", $opp_id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        
        if ($exists) {
            $stmt = $conn->prepare("
                UPDATE opportunity_bid_decision SET
                    score_strategic_alignment = ?, score_new_market = ?,
                    score_competitive_position = ?, score_customer_relationship = ?,
                    score_solution_readiness = ?, score_proposal_team = ?,
                    score_key_personnel = ?, score_smes_available = ?,
                    score_technical_risk = ?, score_cost_schedule_risk = ?,
                    score_contract_terms = ?, score_acceptable_margin = ?,
                    score_bp_budget = ?, score_revenue_potential = ?,
                    bid_decision_score = ?,
                    proposal_manager_id = ?, volume_leads = ?,
                    technical_writers_needed = ?, technical_writers_available = ?,
                    smes_needed = ?, smes_available = ?,
                    graphics_hours_needed = ?, graphics_hours_available = ?,
                    bp_budget_needed = ?, bp_budget_available = ?,
                    recommendation = ?, conditions = ?,
                    final_decision = ?, decision_authority_user_id = ?,
                    decision_date = ?, justification = ?,
                    lessons_learned = ?, revisit_date = ?
                WHERE opportunity_id = ?
            ");
            
            $stmt->bind_param("iiiiiiiiiiiiiidisiiiiiiddsssissssi",
                $score_strategic_alignment, $score_new_market,
                $score_competitive_position, $score_customer_relationship,
                $score_solution_readiness, $score_proposal_team,
                $score_key_personnel, $score_smes_available,
                $score_technical_risk, $score_cost_schedule_risk,
                $score_contract_terms, $score_acceptable_margin,
                $score_bp_budget, $score_revenue_potential,
                $bid_decision_score,
                $proposal_manager_id, $volume_leads,
                $technical_writers_needed, $technical_writers_available,
                $smes_needed, $smes_available,
                $graphics_hours_needed, $graphics_hours_available,
                $bp_budget_needed, $bp_budget_available,
                $recommendation, $conditions,
                $final_decision, $decision_by,
                $decision_date, $justification,
                $lessons_learned, $revisit_date,
                $opp_id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO opportunity_bid_decision (
                    opportunity_id,
                    score_strategic_alignment, score_new_market,
                    score_competitive_position, score_customer_relationship,
                    score_solution_readiness, score_proposal_team,
                    score_key_personnel, score_smes_available,
                    score_technical_risk, score_cost_schedule_risk,
                    score_contract_terms, score_acceptable_margin,
                    score_bp_budget, score_revenue_potential,
                    bid_decision_score
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param("iiiiiiiiiiiiiiid",
                $opp_id,
                $score_strategic_alignment, $score_new_market,
                $score_competitive_position, $score_customer_relationship,
                $score_solution_readiness, $score_proposal_team,
                $score_key_personnel, $score_smes_available,
                $score_technical_risk, $score_cost_schedule_risk,
                $score_contract_terms, $score_acceptable_margin,
                $score_bp_budget, $score_revenue_potential,
                $bid_decision_score
            );
        }
        
        if (!$stmt->execute()) {
            send_error('Failed to save bid decision: ' . $stmt->error, 500);
        }
        
        // Update opportunity phase based on final decision
        // Note: 'go' decision is handled by the frontend calling convertOpportunityToProposal
        if ($final_decision === 'go') {
            // Just update workspace_phase - conversion will handle the rest
            $stmt = $conn->prepare("UPDATE opportunities SET workspace_phase = 'won' WHERE id = ?");
            $stmt->bind_param("i", $opp_id);
            $stmt->execute();
        } elseif ($final_decision === 'no_go') {
            $stmt = $conn->prepare("UPDATE opportunities SET workspace_phase = 'no_bid', status = 'No bid' WHERE id = ?");
            $stmt->bind_param("i", $opp_id);
            $stmt->execute();
        }
        
        send_response(['success' => true, 'bid_decision_score' => $bid_decision_score]);
        break;
    
    case 'saveOpportunityRisk':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        $id = intval($data['id'] ?? 0);
        
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Prepare variables for bind_param
        $risk_description = $data['risk_description'] ?? null;
        $probability = $data['probability'] ?? 'medium';
        $impact = $data['impact'] ?? 'medium';
        $mitigation = $data['mitigation'] ?? null;
        $owner_user_id = !empty($data['owner_user_id']) ? intval($data['owner_user_id']) : null;
        $status = $data['status'] ?? 'open';
        
        if ($id > 0) {
            $stmt = $conn->prepare("
                UPDATE opportunity_risks SET
                    risk_description = ?, probability = ?, impact = ?,
                    mitigation = ?, owner_user_id = ?, status = ?
                WHERE id = ? AND opportunity_id = ?
            ");
            $stmt->bind_param("ssssisii",
                $risk_description, $probability, $impact,
                $mitigation, $owner_user_id, $status,
                $id, $opp_id
            );
        } else {
            $stmt = $conn->prepare("
                INSERT INTO opportunity_risks (opportunity_id, risk_description, probability, impact, mitigation, owner_user_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issssis",
                $opp_id, $risk_description, $probability, $impact,
                $mitigation, $owner_user_id, $status
            );
        }
        
        if (!$stmt->execute()) {
            send_error('Failed to save risk: ' . $stmt->error, 500);
        }
        
        $new_id = $id > 0 ? $id : $conn->insert_id;
        send_response(['success' => true, 'id' => $new_id]);
        break;
    
    case 'deleteOpportunityRisk':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id'] ?? 0);
        
        $stmt = $conn->prepare("DELETE FROM opportunity_risks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        send_response(['success' => true]);
        break;
    
    case 'createCaptureMilestoneTasks':
        if (!has_permission('opportunity', 'update')) send_error('Permission denied.');
        
        $data = json_decode(file_get_contents('php://input'), true);
        $opp_id = intval($data['opportunity_id'] ?? 0);
        
        if ($opp_id <= 0) send_error('Invalid opportunity ID', 400);
        
        // Get opportunity title for task naming
        $stmt = $conn->prepare("SELECT title FROM opportunities WHERE id = ?");
        $stmt->bind_param("i", $opp_id);
        $stmt->execute();
        $opp = $stmt->get_result()->fetch_assoc();
        $opp_title = $opp['title'] ?? 'Opportunity';
        
        // Define milestone tasks
        $milestones = [
            ['title' => "Draft RFP Review - {$opp_title}", 'field' => 'milestone_draft_rfp_review'],
            ['title' => "Industry Day Attendance - {$opp_title}", 'field' => 'milestone_industry_day'],
            ['title' => "Submit Questions - {$opp_title}", 'field' => 'milestone_questions_submitted'],
            ['title' => "Pink Team Review - {$opp_title}", 'field' => 'milestone_pink_team'],
            ['title' => "Teaming Agreements Signed - {$opp_title}", 'field' => 'milestone_teaming_signed'],
            ['title' => "Final Pricing Approved - {$opp_title}", 'field' => 'milestone_pricing_approved']
        ];
        
        $created = 0;
        foreach ($milestones as $milestone) {
            // Check if task already exists
            $stmt = $conn->prepare("SELECT id FROM tasks WHERE relatedTo = 'Opportunity' AND related_item_id = ? AND title = ?");
            $stmt->bind_param("is", $opp_id, $milestone['title']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                // Create task
                $stmt = $conn->prepare("
                    INSERT INTO tasks (title, relatedTo, related_item_id, status, priority, created_by_user_id, workspace_phase)
                    VALUES (?, 'Opportunity', ?, 'To Do', 'Medium', ?, 'capture')
                ");
                $stmt->bind_param("sii", $milestone['title'], $opp_id, $_SESSION['user_id']);
                $stmt->execute();
                $created++;
            }
        }
        
        send_response(['success' => true, 'tasks_created' => $created]);
        break;

    default:
        send_error('Invalid action', 400);
}

// Helper function to generate recurring event instances
function generateRecurringInstances($event, $range_start, $range_end) {
    $instances = [];
    $start = new DateTime($event['start_date']);
    $range_start_dt = new DateTime($range_start);
    $range_end_dt = new DateTime($range_end);
    
    // Determine end of recurrence
    $recurrence_end = null;
    if ($event['recurrence_end_type'] === 'date' && $event['recurrence_end_date']) {
        $recurrence_end = new DateTime($event['recurrence_end_date']);
    }
    
    $max_count = ($event['recurrence_end_type'] === 'count') ? $event['recurrence_count'] : 365; // Safety limit
    $count = 0;
    
    // Calculate interval based on recurrence type
    $interval = null;
    switch ($event['recurrence_type']) {
        case 'daily': $interval = new DateInterval('P1D'); break;
        case 'weekly': $interval = new DateInterval('P1W'); break;
        case 'biweekly': $interval = new DateInterval('P2W'); break;
        case 'monthly': $interval = new DateInterval('P1M'); break;
        case 'yearly': $interval = new DateInterval('P1Y'); break;
        default: $interval = new DateInterval('P1W');
    }
    
    $current = clone $start;
    
    while ($count < $max_count) {
        // Check if we've passed the recurrence end date
        if ($recurrence_end && $current > $recurrence_end) break;
        
        // Check if we've passed the view range
        if ($current > $range_end_dt) break;
        
        // If this instance is in the visible range
        if ($current >= $range_start_dt && $current <= $range_end_dt) {
            $instance = $event;
            $instance['instance_date'] = $current->format('Y-m-d');
            $instance['start_date'] = $current->format('Y-m-d');
            $instance['end_date'] = $current->format('Y-m-d');
            $instance['is_instance'] = true;
            $instances[] = $instance;
        }
        
        $current->add($interval);
        $count++;
    }
    
    return $instances;
}
?>
