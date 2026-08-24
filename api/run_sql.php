<?php
$conn = new mysqli('localhost', 'root', '');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$sql = $_POST['sql'] ?? $_GET['sql'] ?? 'SHOW DATABASES;';
if (isset($_GET['db'])) $conn->select_db($_GET['db']);

$result = $conn->query($sql);
if ($result === TRUE) {
    echo json_encode(["status" => "success", "affected_rows" => $conn->affected_rows]);
} elseif ($result === FALSE) {
    echo json_encode(["error" => $conn->error]);
} else {
    $rows = [];
    while($row = $result->fetch_assoc()) $rows[] = $row;
    echo json_encode($rows);
}
$conn->close();
?>
