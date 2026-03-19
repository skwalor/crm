<?php
/**
 * IntePros Federal Celios.AI CRM - Jira API Endpoints
 * 
 * Place this file in: /volume1/web/crm/jira_api.php
 */

ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/includes/session_config.php';
header('Content-Type: application/json');

// Check authentication (same as main api.php)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Database connection - use same db_connect.php as main api.php
require_once 'db_connect.php';

// Include Jira files
require_once __DIR__ . '/includes/jira_config.php';
require_once __DIR__ . '/includes/JiraAPI.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'user';

// Initialize Jira API
try {
    $jira = new JiraAPI($conn, $userId);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Failed to initialize Jira API: ' . $e->getMessage()]);
    exit;
}

// Get action from request
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        
        // ====================================================================
        // CONNECTION MANAGEMENT
        // ====================================================================
        
        case 'getConnectionStatus':
            $connected = $jira->isConnected();
            $response = ['success' => true, 'connected' => $connected];
            
            if ($connected) {
                try {
                    $user = $jira->getCurrentUser();
                    $response['user'] = [
                        'displayName' => $user['displayName'] ?? 'Unknown',
                        'email' => $user['emailAddress'] ?? '',
                        'avatarUrl' => $user['avatarUrls']['48x48'] ?? ''
                    ];
                } catch (Exception $e) {
                    // Token might be expired
                    $response['connected'] = false;
                }
            }
            
            echo json_encode($response);
            break;
            
        case 'getAuthUrl':
            $authUrl = $jira->getAuthUrl();
            echo json_encode(['success' => true, 'authUrl' => $authUrl]);
            break;
            
        case 'disconnect':
            $result = $jira->disconnect();
            echo json_encode($result);
            break;
            
        // ====================================================================
        // PROJECT & BOARD DATA
        // ====================================================================
        
        case 'getProjectData':
            requireJiraConnection($jira);
            $projectKey = $_REQUEST['projectKey'] ?? JIRA_DEFAULT_PROJECT_KEY;
            $data = $jira->getCachedProjectData($projectKey);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'refreshProjectCache':
            requireJiraConnection($jira);
            $projectKey = $_REQUEST['projectKey'] ?? JIRA_DEFAULT_PROJECT_KEY;
            $data = $jira->refreshProjectCache($projectKey);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'getBoardIssues':
            requireJiraConnection($jira);
            $projectKey = $_REQUEST['projectKey'] ?? JIRA_DEFAULT_PROJECT_KEY;
            
            // Get board ID from cache or fresh
            $projectData = $jira->getCachedProjectData($projectKey);
            $boardId = $projectData['board_id'] ?? null;
            
            $startAt = intval($_REQUEST['startAt'] ?? 0);
            $maxResults = intval($_REQUEST['maxResults'] ?? 50);
            
            // Use JQL search if no board available (requires fewer permissions)
            if (!$boardId) {
                $issues = $jira->searchIssues(
                    'project = ' . $projectKey . ' ORDER BY updated DESC',
                    $startAt,
                    $maxResults
                );
            } else {
                $issues = $jira->getBoardIssues($boardId, $startAt, $maxResults);
            }
            
            // Enrich with CRM link data
            $enrichedIssues = enrichIssuesWithCRMLinks($conn, $issues['issues'] ?? []);
            
            echo json_encode([
                'success' => true,
                'issues' => $enrichedIssues,
                'total' => $issues['total'] ?? 0,
                'startAt' => $issues['startAt'] ?? 0,
                'maxResults' => $issues['maxResults'] ?? 50
            ]);
            break;
            
        case 'searchIssues':
            requireJiraConnection($jira);
            $jql = $_REQUEST['jql'] ?? 'project = ' . JIRA_DEFAULT_PROJECT_KEY . ' ORDER BY updated DESC';
            $startAt = intval($_REQUEST['startAt'] ?? 0);
            $maxResults = intval($_REQUEST['maxResults'] ?? 50);
            
            $issues = $jira->searchIssues($jql, $startAt, $maxResults);
            $enrichedIssues = enrichIssuesWithCRMLinks($conn, $issues['issues'] ?? []);
            
            echo json_encode([
                'success' => true,
                'issues' => $enrichedIssues,
                'total' => $issues['total'] ?? 0
            ]);
            break;
            
        // ====================================================================
        // ISSUE OPERATIONS
        // ====================================================================
        
        case 'getIssue':
            requireJiraConnection($jira);
            $issueKey = $_REQUEST['issueKey'] ?? '';
            
            if (empty($issueKey)) {
                throw new Exception('Issue key is required');
            }
            
            $issue = $jira->getIssue($issueKey);
            $transitions = $jira->getTransitions($issueKey);
            
            // Get CRM links for this issue
            $links = getCRMLinksForIssue($conn, $issueKey);
            
            echo json_encode([
                'success' => true,
                'issue' => $issue,
                'transitions' => $transitions['transitions'] ?? [],
                'crmLinks' => $links
            ]);
            break;
            
        case 'transitionIssue':
            requireJiraConnection($jira);
            $issueKey = $_REQUEST['issueKey'] ?? '';
            $transitionId = $_REQUEST['transitionId'] ?? '';
            
            if (empty($issueKey) || empty($transitionId)) {
                throw new Exception('Issue key and transition ID are required');
            }
            
            $result = $jira->transitionIssue($issueKey, $transitionId);
            
            // Sync the updated status to our links table
            syncIssueToCRM($conn, $jira, $issueKey);
            
            echo json_encode(['success' => true, 'result' => $result]);
            break;
            
        case 'assignIssue':
            requireJiraConnection($jira);
            $issueKey = $_REQUEST['issueKey'] ?? '';
            $accountId = $_REQUEST['accountId'] ?? '';
            
            if (empty($issueKey)) {
                throw new Exception('Issue key is required');
            }
            
            $result = $jira->assignIssue($issueKey, $accountId ?: null);
            echo json_encode(['success' => true, 'result' => $result]);
            break;
            
        case 'addComment':
            requireJiraConnection($jira);
            $issueKey = $_REQUEST['issueKey'] ?? '';
            $comment = $_REQUEST['comment'] ?? '';
            
            if (empty($issueKey) || empty($comment)) {
                throw new Exception('Issue key and comment are required');
            }
            
            $result = $jira->addComment($issueKey, $comment);
            echo json_encode(['success' => true, 'result' => $result]);
            break;
            
        case 'updateIssue':
            requireJiraConnection($jira);
            $issueKey = $_REQUEST['issueKey'] ?? '';
            $fields = json_decode($_REQUEST['fields'] ?? '{}', true);
            
            if (empty($issueKey)) {
                throw new Exception('Issue key is required');
            }
            
            $result = $jira->updateIssue($issueKey, $fields);
            syncIssueToCRM($conn, $jira, $issueKey);
            
            echo json_encode(['success' => true, 'result' => $result]);
            break;
            
        // ====================================================================
        // CRM LINKING
        // ====================================================================
        
        case 'linkIssueToCRM':
            requireJiraConnection($jira);
            $issueKey = $_REQUEST['issueKey'] ?? '';
            $recordType = $_REQUEST['recordType'] ?? '';
            $recordId = intval($_REQUEST['recordId'] ?? 0);
            
            if (empty($issueKey) || empty($recordType) || $recordId <= 0) {
                throw new Exception('Issue key, record type, and record ID are required');
            }
            
            $result = $jira->linkIssueToCRM($issueKey, $recordType, $recordId);
            echo json_encode($result);
            break;
            
        case 'unlinkIssueFromCRM':
            requireJiraConnection($jira);
            $linkId = intval($_REQUEST['linkId'] ?? 0);
            
            if ($linkId <= 0) {
                throw new Exception('Link ID is required');
            }
            
            $result = $jira->unlinkIssueFromCRM($linkId);
            echo json_encode($result);
            break;
            
        case 'getLinkedIssues':
            requireJiraConnection($jira);
            $recordType = $_REQUEST['recordType'] ?? '';
            $recordId = intval($_REQUEST['recordId'] ?? 0);
            
            if (empty($recordType) || $recordId <= 0) {
                throw new Exception('Record type and record ID are required');
            }
            
            $links = $jira->getLinkedIssues($recordType, $recordId);
            echo json_encode(['success' => true, 'links' => $links]);
            break;
            
        case 'syncLinkedIssues':
            requireJiraConnection($jira);
            $result = $jira->syncLinkedIssues();
            echo json_encode(['success' => true, 'result' => $result]);
            break;
            
        // ====================================================================
        // CRM RECORDS FOR LINKING UI
        // ====================================================================
        
        case 'getCRMRecordsForLinking':
            $recordType = $_REQUEST['recordType'] ?? '';
            $search = $_REQUEST['search'] ?? '';
            
            $records = getCRMRecordsForLinking($conn, $recordType, $search, $userId, $userRole);
            echo json_encode(['success' => true, 'records' => $records]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
    }
    
} catch (Exception $e) {
    error_log('Jira API Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function requireJiraConnection($jira) {
    if (!$jira->isConnected()) {
        throw new Exception('Not connected to Jira. Please connect your account first.');
    }
}

function enrichIssuesWithCRMLinks($conn, $issues) {
    if (empty($issues)) {
        return [];
    }
    
    $issueKeys = array_map(function($issue) {
        return $issue['key'];
    }, $issues);
    
    $placeholders = implode(',', array_fill(0, count($issueKeys), '?'));
    $types = str_repeat('s', count($issueKeys));
    
    $stmt = $conn->prepare("
        SELECT jira_issue_id, 
               opportunity_id, proposal_id, task_id,
               o.title as opportunity_title,
               p.title as proposal_title,
               t.title as task_title
        FROM jira_issue_links jil
        LEFT JOIN opportunities o ON jil.opportunity_id = o.id
        LEFT JOIN proposals p ON jil.proposal_id = p.id
        LEFT JOIN tasks t ON jil.task_id = t.id
        WHERE jira_issue_id IN ({$placeholders})
    ");
    
    $stmt->bind_param($types, ...$issueKeys);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $linksMap = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($linksMap[$row['jira_issue_id']])) {
            $linksMap[$row['jira_issue_id']] = [];
        }
        $linksMap[$row['jira_issue_id']][] = $row;
    }
    $stmt->close();
    
    // Enrich issues with link data
    foreach ($issues as &$issue) {
        $issue['crmLinks'] = $linksMap[$issue['key']] ?? [];
    }
    
    return $issues;
}

function getCRMLinksForIssue($conn, $issueKey) {
    $stmt = $conn->prepare("
        SELECT jil.*,
               o.title as opportunity_title,
               p.title as proposal_title,
               t.title as task_title,
               u.username as linked_by_username
        FROM jira_issue_links jil
        LEFT JOIN opportunities o ON jil.opportunity_id = o.id
        LEFT JOIN proposals p ON jil.proposal_id = p.id
        LEFT JOIN tasks t ON jil.task_id = t.id
        LEFT JOIN users u ON jil.linked_by_user_id = u.id
        WHERE jil.jira_issue_id = ?
    ");
    $stmt->bind_param("s", $issueKey);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $links = [];
    while ($row = $result->fetch_assoc()) {
        $links[] = $row;
    }
    $stmt->close();
    
    return $links;
}

function syncIssueToCRM($conn, $jira, $issueKey) {
    try {
        $issue = $jira->getIssue($issueKey);
        if ($issue && !isset($issue['errorMessages'])) {
            $stmt = $conn->prepare("
                UPDATE jira_issue_links SET
                    jira_issue_summary = ?,
                    jira_issue_status = ?,
                    last_synced_at = CURRENT_TIMESTAMP
                WHERE jira_issue_id = ?
            ");
            $summary = $issue['fields']['summary'] ?? '';
            $status = $issue['fields']['status']['name'] ?? '';
            $stmt->bind_param("sss", $summary, $status, $issueKey);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Failed to sync issue {$issueKey}: " . $e->getMessage());
    }
}

function getCRMRecordsForLinking($conn, $recordType, $search, $userId, $userRole) {
    $records = [];
    $search = '%' . $search . '%';
    
    switch ($recordType) {
        case 'opportunity':
            $sql = "SELECT id, title, status FROM opportunities WHERE title LIKE ? ORDER BY updated_at DESC LIMIT 50";
            break;
        case 'proposal':
            $sql = "SELECT id, title, status FROM proposals WHERE title LIKE ? ORDER BY updated_at DESC LIMIT 50";
            break;
        case 'task':
            $sql = "SELECT id, title, status FROM tasks WHERE title LIKE ? ORDER BY updated_at DESC LIMIT 50";
            break;
        default:
            return [];
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    return $records;
}
