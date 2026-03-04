<?php
/**
 * Fetches all permissions for a given role from the database.
 *
 * @param mysqli $conn The database connection object.
 * @param string $role The role to fetch permissions for.
 * @return array A structured array of permissions.
 */
function get_permissions_for_role($conn, $role) {
    $permissions = [];
    $sql = "SELECT resource, can_create, can_view, can_update, can_delete, can_archive FROM roles_permissions WHERE role = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $role);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $permissions[$row['resource']] = [
                'can_create'  => (bool)$row['can_create'],
                'can_view'    => (bool)$row['can_view'],
                'can_update'  => (bool)$row['can_update'],
                'can_delete'  => (bool)$row['can_delete'],
                'can_archive' => (bool)($row['can_archive'] ?? false),
            ];
        }
        $stmt->close();
    }
    return $permissions;
}
?>
