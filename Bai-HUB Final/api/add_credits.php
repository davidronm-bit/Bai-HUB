<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/json_storage.php';

$payload = json_decode(file_get_contents('php://input'), true);
$gameName = $payload['game'] ?? '';

if (!$gameName) {
    echo json_encode(['success' => false, 'error' => 'Missing game parameter']);
    exit;
}

// Cooldown check (10 minutes = 600 seconds)
$lastClaim = getGlobalData('last_ad_claim');
$currentTime = time();
$cooldownSeconds = 600;


if ($lastClaim && ($currentTime - $lastClaim) < $cooldownSeconds) {
    $remaining = $cooldownSeconds - ($currentTime - $lastClaim);
    $minutes = ceil($remaining / 60);
    echo json_encode([
        'success' => false,
        'error' => "You're too fast! Please wait {$minutes} more minute(s) before claiming again."
    ]);
    exit;
}

$userData = getUserData($gameName);
$creditsToAdd = rand(100, 500);
$userData['balance'] += $creditsToAdd;

saveUserData($gameName, $userData);
saveGlobalData('last_ad_claim', $currentTime);

echo json_encode([
    'success' => true,
    'creditsAdded' => $creditsToAdd,
    'newBalance' => $userData['balance']
]);
