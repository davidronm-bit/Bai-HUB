<?php
// testing/api/json_storage.php

function getUserId()
{
    if (!isset($_COOKIE['user_id'])) {
        $userId = 'user_' . uniqid() . '_' . rand(1000, 9999);
        setcookie('user_id', $userId, time() + (86400 * 30), "/"); // 30 days
        $_COOKIE['user_id'] = $userId;
    }
    return $_COOKIE['user_id'];
}

function getGameData($gameName)
{
    $file = __DIR__ . "/../data/{$gameName}.json";
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

function saveGameData($gameName, $data)
{
    $dir = __DIR__ . "/../data";
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $file = "{$dir}/{$gameName}.json";
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function getUserData($gameName)
{
    $userId = getUserId();
    $allData = getGameData($gameName);
    if (!isset($allData[$userId])) {
        $allData[$userId] = [
            'balance' => 100.00, // Default starting balance
            'history' => []
        ];
        saveGameData($gameName, $allData);
    }
    return $allData[$userId];
}

function saveUserData($gameName, $userData)
{
    $userId = getUserId();
    $allData = getGameData($gameName);
    $allData[$userId] = $userData;
    saveGameData($gameName, $allData);
}

function getGlobalData($key)
{
    $userId = getUserId();
    $allData = getGameData('global_meta');
    return $allData[$userId][$key] ?? null;
}

function saveGlobalData($key, $value)
{
    $userId = getUserId();
    $allData = getGameData('global_meta');
    if (!isset($allData[$userId])) {
        $allData[$userId] = [];
    }
    $allData[$userId][$key] = $value;
    saveGameData('global_meta', $allData);
}
