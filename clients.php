<?php
/*
 * Simple CRM endpoint for storing and retrieving client records for the
 * HOAEXP quotation system. Clients are persisted to a JSON file
 * (`clients.json`) located in the same directory as this script. The
 * endpoint accepts POST requests with JSON data to create or update
 * clients, and GET requests to search for clients by name or email.
 *
 * POST /clients.php
 *   Body (JSON or form data): { name, country, email, phone, note }
 *   Response: JSON object of the created or existing client record.
 *
 * GET /clients.php?query=<term>
 *   Response: JSON array of matching client records. If no query
 *   parameter is provided, all clients are returned.
 */

$file = __DIR__ . '/clients.json';
header('Content-Type: application/json');

// Utility to load existing clients from file
function load_clients($file) {
    if (!file_exists($file)) {
        return [];
    }
    $data = @file_get_contents($file);
    $clients = json_decode($data, true);
    return is_array($clients) ? $clients : [];
}

// Utility to save clients back to file
function save_clients($file, $clients) {
    // Write JSON pretty-printed to disk
    $json = json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    // Use file locking to avoid race conditions
    $fh = fopen($file, 'c+');
    if ($fh === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Cannot open client database']);
        exit;
    }
    flock($fh, LOCK_EX);
    ftruncate($fh, 0);
    fwrite($fh, $json);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
}

// Handle POST: create or update a client
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input data (JSON or form-encoded)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        // Fallback to form data
        $data = $_POST;
    }
    // Basic sanitization
    $name = trim($data['name'] ?? '');
    $country = trim($data['country'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $note = trim($data['note'] ?? '');
    if ($email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing email']);
        exit;
    }
    // Load existing clients
    $clients = load_clients($file);
    // Check if client already exists by email (case-insensitive)
    foreach ($clients as $client) {
        if (strcasecmp($client['email'], $email) === 0) {
            echo json_encode($client);
            exit;
        }
    }
    // Create new client record
    $newClient = [
        'id' => uniqid('client_', true),
        'name' => $name,
        'country' => $country,
        'email' => $email,
        'phone' => $phone,
        'note' => $note,
        'created_at' => date('c'),
    ];
    $clients[] = $newClient;
    save_clients($file, $clients);
    echo json_encode($newClient);
    exit;
}

// Handle GET: search or list clients
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$clients = load_clients($file);
if ($query === '') {
    echo json_encode($clients);
    exit;
}
$q = mb_strtolower($query);
$results = [];
foreach ($clients as $client) {
    $name = mb_strtolower($client['name'] ?? '');
    $email = mb_strtolower($client['email'] ?? '');
    if (strpos($name, $q) !== false || strpos($email, $q) !== false) {
        $results[] = $client;
    }
}
echo json_encode($results);
exit;