<?php
session_start();
require_once '../includes/config.php';

if ($_SESSION['role'] !== 'chef') {
    header("Location: ../unauthorized.php");
    exit;
}

$department_id = $_SESSION['department_id'];

// Get filters from URL
$search = $_GET['search'] ?? '';
$semester_filter = $_GET['semester'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query same as dashboard filtering
$query = "SELECT * FROM unités_enseignement WHERE department_id = ?";
$params = [$department_id];
$types = "i";

if ($search) {
    $query .= " AND (code_module LIKE ? OR intitule_module LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($semester_filter && in_array($semester_filter, ['S1', 'S2', 'S3', 'S4'])) {
    $query .= " AND semestre = ?";
    $params[] = $semester_filter;
    $types .= "s";
}

if ($status_filter === 'assigned') {
    $query .= " AND responsable IS NOT NULL";
} elseif ($status_filter === 'unassigned') {
    $query .= " AND responsable IS NULL";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=modules_export.csv');

// Output CSV header
$output = fopen('php://output', 'w');
fputcsv($output, ['Code Module', 'Intitulé Module', 'Semestre', 'Responsable']);

// Fetch and output rows
while ($row = $result->fetch_assoc()) {
    // Get responsible prof name or "Non assigné"
    if ($row['responsable']) {
        $res_stmt = $conn->prepare("SELECT prenom, nom FROM users WHERE id = ?");
        $res_stmt->bind_param("i", $row['responsable']);
        $res_stmt->execute();
        $res_res = $res_stmt->get_result()->fetch_assoc();
        $responsible_name = $res_res ? $res_res['prenom'] . ' ' . $res_res['nom'] : 'Inconnu';
        $res_stmt->close();
    } else {
        $responsible_name = 'Non assigné';
    }

    fputcsv($output, [
        $row['code_module'],
        $row['intitule_module'],
        $row['semestre'],
        $responsible_name
    ]);
}

fclose($output);
exit;
