<?php
/**
 * IntePros Federal Celios.AI CRM - Jira API Helper Class
 * 
 * Handles all Jira API interactions including:
 * - OAuth token management
 * - Issue CRUD operations
 * - Board and sprint data
 * - Linking to CRM records
 * 
 * Place this file in: /includes/JiraAPI.php
 */

require_once __DIR__ . '/jira_config.php';

class JiraAPI {
    private $conn;
    private $userId;
    private $accessToken;
    private $cloudId;
    
    /**
     * Constructor
     * 
     * @param PDO|mysqli $conn Database connection
     * @param int $userId Current user's ID
     */
    public function __construct($conn, $userId) {
        $this->conn = $conn;
        $this->userId = $userId;
        $this->loadTokens();
    }
    
    // ========================================================================
    // TOKEN MANAGEMENT
    // ========================================================================
    
    /**
     * Load user's Jira tokens from database
     */
    private function loadTokens() {
        $stmt = $this->conn->prepare("
            SELECT access_token, refresh_token, token_expires_at, cloud_id
            FROM jira_user_tokens
            WHERE user_id = ?
        ");

        if (!$stmt) {
            // Table likely doesn't exist yet - not connected
            return;
        }

        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $this->accessToken = jira_decrypt_token($row['access_token']);
            $this->cloudId = $row['cloud_id'];

            // Check if token needs refresh
            $expiresAt = strtotime($row['token_expires_at']);
            if ($expiresAt - time() < JIRA_TOKEN_REFRESH_BUFFER) {
                try {
                    $this->refreshAccessToken(jira_decrypt_token($row['refresh_token']));
                } catch (\Throwable $e) {
                    // Refresh token is invalid/expired — clear stale tokens
                    // from memory and database so the user can re-authenticate
                    $this->accessToken = null;
                    $this->cloudId = null;
                    $del = $this->conn->prepare("DELETE FROM jira_user_tokens WHERE user_id = ?");
                    if ($del) {
                        $del->bind_param("i", $this->userId);
                        $del->execute();
                        $del->close();
                    }
                    error_log('Jira token refresh failed, cleared stale tokens for user ' . $this->userId . ': ' . $e->getMessage());
                }
            }
        }
        $stmt->close();
    }
    
    /**
     * Check if user is connected to Jira
     */
    public function isConnected() {
        return !empty($this->accessToken) && !empty($this->cloudId);
    }
    
    /**
     * Get OAuth authorization URL for connecting
     */
    public function getAuthUrl() {
        $state = bin2hex(random_bytes(16));
        $_SESSION['jira_oauth_state'] = $state;
        $_SESSION['jira_oauth_user_id'] = $this->userId;
        
        return jira_get_auth_url($state);
    }
    
    /**
     * Exchange authorization code for tokens
     */
    public function handleOAuthCallback($code, $state) {
        // Verify OAuth state parameter to prevent CSRF
        if (!isset($_SESSION['jira_oauth_state']) || !hash_equals($_SESSION['jira_oauth_state'], $state)) {
            throw new Exception('Invalid OAuth state parameter');
        }

        // Use logged-in user ID if OAuth user ID not in session
        if (!isset($_SESSION['jira_oauth_user_id']) && isset($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
        }
        
        // Build token request
        $tokenData = [
            'grant_type' => 'authorization_code',
            'client_id' => JIRA_CLIENT_ID,
            'client_secret' => JIRA_CLIENT_SECRET,
            'code' => $code,
            'redirect_uri' => JIRA_REDIRECT_URI
        ];
        
        // Exchange code for tokens
        $response = $this->httpPostForToken(JIRA_TOKEN_URL, $tokenData);
        
        if (!isset($response['access_token'])) {
            throw new Exception('Failed to obtain access token: ' . json_encode($response));
        }
        
        $this->accessToken = $response['access_token'];
        
        // Get accessible resources (cloud ID) - MUST include auth header
        $resources = $this->httpGet(JIRA_RESOURCES_URL, [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json'
        ]);
        
        if (empty($resources) || !isset($resources[0]['id'])) {
            throw new Exception('No accessible Jira sites found');
        }
        
        $this->cloudId = $resources[0]['id'];
        $siteName = $resources[0]['name'] ?? 'Unknown';
        
        // Get user info
        $userInfo = $this->getCurrentUser();
        
        // Save tokens to database
        $this->saveTokens(
            $response['access_token'],
            $response['refresh_token'],
            $response['expires_in'],
            $userInfo
        );
        
        // Clean up session
        unset($_SESSION['jira_oauth_state']);
        unset($_SESSION['jira_oauth_user_id']);
        
        return [
            'success' => true,
            'site_name' => $siteName,
            'user' => $userInfo
        ];
    }
    
    /**
     * Refresh access token using refresh token
     */
    private function refreshAccessToken($refreshToken) {
        // Use httpPostForToken which sends JSON (Atlassian requires JSON)
        $response = $this->httpPostForToken(JIRA_TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'client_id' => JIRA_CLIENT_ID,
            'client_secret' => JIRA_CLIENT_SECRET,
            'refresh_token' => $refreshToken
        ]);
        
        if (isset($response['access_token'])) {
            $this->accessToken = $response['access_token'];
            $this->saveTokens(
                $response['access_token'],
                $response['refresh_token'] ?? $refreshToken,
                $response['expires_in']
            );
        }
    }
    
    /**
     * Save tokens to database
     */
    private function saveTokens($accessToken, $refreshToken, $expiresIn, $userInfo = null) {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        $encryptedAccess = jira_encrypt_token($accessToken);
        $encryptedRefresh = jira_encrypt_token($refreshToken);
        
        $stmt = $this->conn->prepare("
            INSERT INTO jira_user_tokens 
                (user_id, access_token, refresh_token, token_expires_at, cloud_id, 
                 jira_account_id, jira_display_name, jira_email, jira_avatar_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                access_token = VALUES(access_token),
                refresh_token = VALUES(refresh_token),
                token_expires_at = VALUES(token_expires_at),
                cloud_id = VALUES(cloud_id),
                jira_account_id = COALESCE(VALUES(jira_account_id), jira_account_id),
                jira_display_name = COALESCE(VALUES(jira_display_name), jira_display_name),
                jira_email = COALESCE(VALUES(jira_email), jira_email),
                jira_avatar_url = COALESCE(VALUES(jira_avatar_url), jira_avatar_url),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $accountId = $userInfo['accountId'] ?? null;
        $displayName = $userInfo['displayName'] ?? null;
        $email = $userInfo['emailAddress'] ?? null;
        $avatarUrl = $userInfo['avatarUrls']['48x48'] ?? null;
        
        $stmt->bind_param("issssssss",
            $this->userId,
            $encryptedAccess,
            $encryptedRefresh,
            $expiresAt,
            $this->cloudId,
            $accountId,
            $displayName,
            $email,
            $avatarUrl
        );
        
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Disconnect user from Jira
     */
    public function disconnect() {
        $stmt = $this->conn->prepare("DELETE FROM jira_user_tokens WHERE user_id = ?");
        $stmt->bind_param("i", $this->userId);
        $stmt->execute();
        $stmt->close();
        
        $this->accessToken = null;
        $this->cloudId = null;
        
        return ['success' => true];
    }
    
    // ========================================================================
    // JIRA API CALLS
    // ========================================================================
    
    /**
     * Get current Jira user info
     */
    public function getCurrentUser() {
        return $this->apiGet('/rest/api/3/myself');
    }
    
    /**
     * Get project details
     */
    public function getProject($projectKey = null) {
        $key = $projectKey ?? JIRA_DEFAULT_PROJECT_KEY;
        try {
            return $this->apiGet("/rest/api/3/project/{$key}");
        } catch (Exception $e) {
            error_log("Failed to get project: " . $e->getMessage());
            // Return minimal project data
            return [
                'id' => '',
                'key' => $key,
                'name' => $key,
                'issueTypes' => []
            ];
        }
    }
    
    /**
     * Get board for a project (with fallback)
     */
    public function getBoard($projectKey = null) {
        $key = $projectKey ?? JIRA_DEFAULT_PROJECT_KEY;
        try {
            $boards = $this->apiGet("/rest/agile/1.0/board?projectKeyOrId={$key}");
            return $boards['values'][0] ?? null;
        } catch (Exception $e) {
            // Agile API might not be available or require different scopes
            error_log("Board API failed, will use JQL fallback: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get board configuration (columns/statuses)
     */
    public function getBoardConfiguration($boardId) {
        try {
            return $this->apiGet("/rest/agile/1.0/board/{$boardId}/configuration");
        } catch (Exception $e) {
            error_log("Board configuration API failed: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all issues on a board (or by JQL if board not available)
     */
    public function getBoardIssues($boardId, $startAt = 0, $maxResults = 50) {
        // If no board ID, use JQL search instead
        if (!$boardId) {
            return $this->searchIssues(
                'project = ' . JIRA_DEFAULT_PROJECT_KEY . ' ORDER BY updated DESC',
                $startAt,
                $maxResults
            );
        }
        
        try {
            return $this->apiGet("/rest/agile/1.0/board/{$boardId}/issue", [
                'startAt' => $startAt,
                'maxResults' => $maxResults,
                'fields' => 'summary,status,assignee,priority,issuetype,created,updated,description,comment'
            ]);
        } catch (Exception $e) {
            // Fall back to JQL search if board API fails
            error_log("Board issues API failed, using JQL fallback: " . $e->getMessage());
            return $this->searchIssues(
                'project = ' . JIRA_DEFAULT_PROJECT_KEY . ' ORDER BY updated DESC',
                $startAt,
                $maxResults
            );
        }
    }
    
    /**
     * Get issues by JQL query (using new API endpoint)
     */
    public function searchIssues($jql, $startAt = 0, $maxResults = 50) {
        return $this->apiGet("/rest/api/3/search/jql", [
            'jql' => $jql,
            'startAt' => $startAt,
            'maxResults' => $maxResults,
            'fields' => 'summary,status,assignee,priority,issuetype,created,updated,description,comment,project'
        ]);
    }
    
    /**
     * Get a single issue
     */
    public function getIssue($issueKey) {
        return $this->apiGet("/rest/api/3/issue/{$issueKey}", [
            'fields' => 'summary,status,assignee,priority,issuetype,created,updated,description,comment,project'
        ]);
    }
    
    /**
     * Get available transitions for an issue
     */
    public function getTransitions($issueKey) {
        return $this->apiGet("/rest/api/3/issue/{$issueKey}/transitions");
    }
    
    /**
     * Transition an issue to a new status
     */
    public function transitionIssue($issueKey, $transitionId) {
        return $this->apiPost("/rest/api/3/issue/{$issueKey}/transitions", [
            'transition' => ['id' => $transitionId]
        ]);
    }
    
    /**
     * Update issue fields
     */
    public function updateIssue($issueKey, $fields) {
        return $this->apiPut("/rest/api/3/issue/{$issueKey}", [
            'fields' => $fields
        ]);
    }
    
    /**
     * Assign issue to a user
     */
    public function assignIssue($issueKey, $accountId) {
        return $this->apiPut("/rest/api/3/issue/{$issueKey}/assignee", [
            'accountId' => $accountId
        ]);
    }
    
    /**
     * Add comment to an issue
     */
    public function addComment($issueKey, $body) {
        return $this->apiPost("/rest/api/3/issue/{$issueKey}/comment", [
            'body' => [
                'type' => 'doc',
                'version' => 1,
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => $body]
                        ]
                    ]
                ]
            ]
        ]);
    }
    
    /**
     * Get project statuses
     */
    public function getProjectStatuses($projectKey = null) {
        $key = $projectKey ?? JIRA_DEFAULT_PROJECT_KEY;
        try {
            return $this->apiGet("/rest/api/3/project/{$key}/statuses");
        } catch (Exception $e) {
            error_log("Failed to get project statuses: " . $e->getMessage());
            // Return default statuses
            return [
                ['statuses' => [
                    ['name' => 'To Do', 'statusCategory' => ['key' => 'new']],
                    ['name' => 'In Progress', 'statusCategory' => ['key' => 'indeterminate']],
                    ['name' => 'Done', 'statusCategory' => ['key' => 'done']]
                ]]
            ];
        }
    }
    
    /**
     * Get assignable users for a project
     */
    public function getAssignableUsers($projectKey = null) {
        $key = $projectKey ?? JIRA_DEFAULT_PROJECT_KEY;
        try {
            return $this->apiGet("/rest/api/3/user/assignable/search", [
                'project' => $key,
                'maxResults' => 100
            ]);
        } catch (Exception $e) {
            error_log("Failed to get assignable users: " . $e->getMessage());
            return [];
        }
    }
    
    // ========================================================================
    // CRM LINKING
    // ========================================================================
    
    /**
     * Link a Jira issue to a CRM record
     */
    public function linkIssueToCRM($issueKey, $recordType, $recordId) {
        // Get issue details
        $issue = $this->getIssue($issueKey);
        
        if (!$issue || isset($issue['errorMessages'])) {
            throw new Exception('Issue not found: ' . $issueKey);
        }
        
        // Prepare link data
        $linkData = [
            'jira_issue_id' => $issueKey,
            'jira_issue_key' => $issueKey,
            'jira_issue_summary' => $issue['fields']['summary'] ?? '',
            'jira_issue_status' => $issue['fields']['status']['name'] ?? '',
            'jira_issue_type' => $issue['fields']['issuetype']['name'] ?? '',
            'jira_issue_url' => JIRA_INSTANCE_URL . '/browse/' . $issueKey,
            'linked_by_user_id' => $this->userId
        ];
        
        // Set the appropriate CRM record type
        $column = $recordType . '_id';
        if (!in_array($column, ['opportunity_id', 'proposal_id', 'task_id'])) {
            throw new Exception('Invalid record type: ' . $recordType);
        }
        $linkData[$column] = $recordId;
        
        // Insert link
        $columns = implode(', ', array_keys($linkData));
        $placeholders = implode(', ', array_fill(0, count($linkData), '?'));
        $types = str_repeat('s', count($linkData) - 2) . 'ii';
        
        $stmt = $this->conn->prepare("
            INSERT INTO jira_issue_links ({$columns})
            VALUES ({$placeholders})
            ON DUPLICATE KEY UPDATE
                jira_issue_summary = VALUES(jira_issue_summary),
                jira_issue_status = VALUES(jira_issue_status),
                last_synced_at = CURRENT_TIMESTAMP
        ");
        
        $values = array_values($linkData);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $linkId = $stmt->insert_id ?: $this->getExistingLinkId($issueKey, $recordType, $recordId);
        $stmt->close();
        
        // Add comment to Jira issue about the link
        $this->addCRMLinkComment($issueKey, $recordType, $recordId);
        
        return [
            'success' => true,
            'link_id' => $linkId,
            'issue' => $issue
        ];
    }
    
    /**
     * Get existing link ID
     */
    private function getExistingLinkId($issueKey, $recordType, $recordId) {
        $column = $recordType . '_id';
        $stmt = $this->conn->prepare("
            SELECT id FROM jira_issue_links 
            WHERE jira_issue_id = ? AND {$column} = ?
        ");
        $stmt->bind_param("si", $issueKey, $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['id'] ?? null;
    }
    
    /**
     * Add comment to Jira about CRM link
     */
    private function addCRMLinkComment($issueKey, $recordType, $recordId) {
        $crmUrl = "https://crm.celioscrm.com";
        $recordName = $this->getCRMRecordName($recordType, $recordId);
        
        $comment = "🔗 Linked to Celios CRM {$recordType}: {$recordName}\n" .
                   "View in CRM: {$crmUrl}/?tab={$recordType}s&id={$recordId}";
        
        try {
            $this->addComment($issueKey, $comment);
        } catch (Exception $e) {
            // Log but don't fail the link operation
            error_log("Failed to add Jira comment: " . $e->getMessage());
        }
    }
    
    /**
     * Get CRM record name for comment
     */
    private function getCRMRecordName($recordType, $recordId) {
        $tables = [
            'opportunity' => ['opportunities', 'title'],
            'proposal' => ['proposals', 'title'],
            'task' => ['tasks', 'title']
        ];
        
        if (!isset($tables[$recordType])) {
            return "#{$recordId}";
        }
        
        $table = $tables[$recordType][0];
        $field = $tables[$recordType][1];
        
        $stmt = $this->conn->prepare("SELECT {$field} FROM {$table} WHERE id = ?");
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row[$field] ?? "#{$recordId}";
    }
    
    /**
     * Unlink a Jira issue from a CRM record
     */
    public function unlinkIssueFromCRM($linkId) {
        $stmt = $this->conn->prepare("DELETE FROM jira_issue_links WHERE id = ?");
        $stmt->bind_param("i", $linkId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return ['success' => $affected > 0];
    }
    
    /**
     * Get linked issues for a CRM record
     */
    public function getLinkedIssues($recordType, $recordId) {
        $column = $recordType . '_id';
        
        $stmt = $this->conn->prepare("
            SELECT jil.*, u.username as linked_by_username
            FROM jira_issue_links jil
            LEFT JOIN users u ON jil.linked_by_user_id = u.id
            WHERE jil.{$column} = ?
            ORDER BY jil.linked_at DESC
        ");
        $stmt->bind_param("i", $recordId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $links = [];
        while ($row = $result->fetch_assoc()) {
            // Optionally refresh issue data from Jira
            $links[] = $row;
        }
        $stmt->close();
        
        return $links;
    }
    
    /**
     * Sync all linked issues (refresh data from Jira)
     */
    public function syncLinkedIssues() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT jira_issue_id FROM jira_issue_links
            WHERE last_synced_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 50
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $synced = 0;
        while ($row = $result->fetch_assoc()) {
            try {
                $issue = $this->getIssue($row['jira_issue_id']);
                if ($issue && !isset($issue['errorMessages'])) {
                    $updateStmt = $this->conn->prepare("
                        UPDATE jira_issue_links SET
                            jira_issue_summary = ?,
                            jira_issue_status = ?,
                            last_synced_at = CURRENT_TIMESTAMP
                        WHERE jira_issue_id = ?
                    ");
                    $summary = $issue['fields']['summary'] ?? '';
                    $status = $issue['fields']['status']['name'] ?? '';
                    $updateStmt->bind_param("sss", $summary, $status, $row['jira_issue_id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    $synced++;
                }
            } catch (Exception $e) {
                error_log("Failed to sync issue {$row['jira_issue_id']}: " . $e->getMessage());
            }
        }
        $stmt->close();
        
        return ['synced' => $synced];
    }
    
    // ========================================================================
    // HTTP HELPERS
    // ========================================================================
    
    /**
     * Make authenticated GET request to Jira API
     */
    private function apiGet($endpoint, $params = []) {
        if (!$this->isConnected()) {
            throw new Exception('Not connected to Jira');
        }
        
        $url = JIRA_API_BASE . '/ex/jira/' . $this->cloudId . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->httpGet($url, [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json'
        ]);
    }
    
    /**
     * Make authenticated POST request to Jira API
     */
    private function apiPost($endpoint, $data = []) {
        if (!$this->isConnected()) {
            throw new Exception('Not connected to Jira');
        }
        
        $url = JIRA_API_BASE . '/ex/jira/' . $this->cloudId . $endpoint;
        
        return $this->httpPost($url, $data, [
            'Authorization: Bearer ' . $this->accessToken,
            'Accept: application/json',
            'Content-Type: application/json'
        ], true);
    }
    
    /**
     * Make authenticated PUT request to Jira API
     */
    private function apiPut($endpoint, $data = []) {
        if (!$this->isConnected()) {
            throw new Exception('Not connected to Jira');
        }
        
        $url = JIRA_API_BASE . '/ex/jira/' . $this->cloudId . $endpoint;
        
        $options = [
            'http' => [
                'method' => 'PUT',
                'header' => implode("\r\n", [
                    'Authorization: Bearer ' . $this->accessToken,
                    'Accept: application/json',
                    'Content-Type: application/json'
                ]),
                'content' => json_encode($data),
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        // Get HTTP status code from response headers
        $httpCode = 0;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = intval($matches[1] ?? 0);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('Jira API error: ' . $response);
        }
        
        return empty($response) ? ['success' => true] : json_decode($response, true);
    }
    
    /**
     * HTTP GET request
     */
    private function httpGet($url, $headers = []) {
        $headerString = '';
        if (!empty($headers)) {
            $headerString = implode("\r\n", $headers);
        }
        
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => $headerString,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        // Get HTTP status code from response headers
        $httpCode = 0;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = intval($matches[1] ?? 0);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('HTTP error ' . $httpCode . ': ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * HTTP POST request
     */
    private function httpPost($url, $data, $headers = [], $json = false) {
        $postData = $json ? json_encode($data) : http_build_query($data);
        
        if (empty($headers)) {
            $headers = $json 
                ? ['Content-Type: application/json']
                : ['Content-Type: application/x-www-form-urlencoded'];
        }
        
        $headerString = implode("\r\n", $headers);
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => $headerString,
                'content' => $postData,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        // Get HTTP status code from response headers
        $httpCode = 0;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = intval($matches[1] ?? 0);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('HTTP error ' . $httpCode . ': ' . $response);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * HTTP POST for OAuth token exchange - Atlassian expects JSON
     */
    private function httpPostForToken($url, $data) {
        $postData = json_encode($data);
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json",
                'content' => $postData,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];
        
        error_log("Jira Token Request URL: $url");
        error_log("Jira Token Request Body: $postData");
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        // Get HTTP status code from response headers
        $httpCode = 0;
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = intval($matches[1] ?? 0);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            // Include the error details in the exception
            $errorMsg = $decoded['error_description'] ?? $decoded['error'] ?? $decoded['message'] ?? $response;
            throw new Exception("OAuth token error ($httpCode): $errorMsg");
        }
        
        return $decoded;
    }
    
    // ========================================================================
    // CACHING
    // ========================================================================
    
    /**
     * Get cached project data or fetch from API
     */
    public function getCachedProjectData($projectKey = null) {
        $key = $projectKey ?? JIRA_DEFAULT_PROJECT_KEY;
        
        // Check cache
        $stmt = $this->conn->prepare("
            SELECT * FROM jira_project_cache 
            WHERE cloud_id = ? AND project_key = ? AND expires_at > NOW()
        ");
        $stmt->bind_param("ss", $this->cloudId, $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $row['statuses'] = json_decode($row['statuses'], true);
            $row['issue_types'] = json_decode($row['issue_types'], true);
            $row['users'] = json_decode($row['users'], true);
            $stmt->close();
            return $row;
        }
        $stmt->close();
        
        // Fetch fresh data
        return $this->refreshProjectCache($key);
    }
    
    /**
     * Refresh project cache
     */
    public function refreshProjectCache($projectKey = null) {
        $key = $projectKey ?? JIRA_DEFAULT_PROJECT_KEY;
        
        $project = $this->getProject($key);
        $board = $this->getBoard($key);
        $statuses = $this->getProjectStatuses($key);
        $users = $this->getAssignableUsers($key);
        
        $expiresAt = date('Y-m-d H:i:s', time() + JIRA_CACHE_TTL);
        
        $projectId = $project['id'] ?? '';
        $projectName = $project['name'] ?? '';
        $avatarUrl = $project['avatarUrls']['48x48'] ?? '';
        $boardId = isset($board['id']) ? intval($board['id']) : null;
        $boardName = $board['name'] ?? '';
        $statusesJson = json_encode($statuses);
        $issueTypesJson = json_encode($project['issueTypes'] ?? []);
        $usersJson = json_encode($users);
        
        // Use different SQL based on whether boardId is null
        if ($boardId === null) {
            $stmt = $this->conn->prepare("
                INSERT INTO jira_project_cache 
                    (cloud_id, project_key, project_id, project_name, project_avatar_url,
                     board_id, board_name, statuses, issue_types, users, expires_at)
                VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    project_name = VALUES(project_name),
                    project_avatar_url = VALUES(project_avatar_url),
                    board_id = NULL,
                    board_name = VALUES(board_name),
                    statuses = VALUES(statuses),
                    issue_types = VALUES(issue_types),
                    users = VALUES(users),
                    expires_at = VALUES(expires_at),
                    cached_at = CURRENT_TIMESTAMP
            ");
            $stmt->bind_param("ssssssssss",
                $this->cloudId,
                $key,
                $projectId,
                $projectName,
                $avatarUrl,
                $boardName,
                $statusesJson,
                $issueTypesJson,
                $usersJson,
                $expiresAt
            );
        } else {
            $stmt = $this->conn->prepare("
                INSERT INTO jira_project_cache 
                    (cloud_id, project_key, project_id, project_name, project_avatar_url,
                     board_id, board_name, statuses, issue_types, users, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    project_name = VALUES(project_name),
                    project_avatar_url = VALUES(project_avatar_url),
                    board_id = VALUES(board_id),
                    board_name = VALUES(board_name),
                    statuses = VALUES(statuses),
                    issue_types = VALUES(issue_types),
                    users = VALUES(users),
                    expires_at = VALUES(expires_at),
                    cached_at = CURRENT_TIMESTAMP
            ");
            $stmt->bind_param("sssssisssss",
                $this->cloudId,
                $key,
                $projectId,
                $projectName,
                $avatarUrl,
                $boardId,
                $boardName,
                $statusesJson,
                $issueTypesJson,
                $usersJson,
                $expiresAt
            );
        }
        
        $stmt->execute();
        $stmt->close();
        
        return [
            'cloud_id' => $this->cloudId,
            'project_key' => $key,
            'project_id' => $projectId,
            'project_name' => $projectName,
            'board_id' => $boardId,
            'board_name' => $boardName,
            'statuses' => $statuses,
            'issue_types' => $project['issueTypes'] ?? [],
            'users' => $users
        ];
    }
}
