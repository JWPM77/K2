<?php
session_start();
include("../../db.php");

$gameId = $_SESSION['gameId'];
$myTeam = $_SESSION['myTeam'];

$query = 'SELECT gamePhase, gameCurrentTeam, gameBattleSection FROM GAMES WHERE gameId = ?';
$preparedQuery = $db->prepare($query);
$preparedQuery->bind_param("i", $gameId);
$preparedQuery->execute();
$results = $preparedQuery->get_result();
$r = $results->fetch_assoc();

$gamePhase = $r['gamePhase'];
$gameCurrentTeam = $r['gameCurrentTeam'];
$gameBattleSection = $r['gameBattleSection'];

if ($myTeam != $gameCurrentTeam) {
    echo "It is not your team's turn.";
    exit;
}
if ($gamePhase != 2) {
    echo "It is not the right phase for this.";
    exit;
}
if ($gameBattleSection != "none") {
    echo "No battles must be occurring for this.";
    exit;
}

$newBattleSection = "selectPos";
$query = 'UPDATE games SET gameBattleSection = ? WHERE gameId = ?';
$query = $db->prepare($query);
$query->bind_param("si", $newBattleSection, $gameId);
$query->execute();

$updateType = "getBoard";
$query = 'INSERT INTO updates (updateGameId, updateType) VALUES (?, ?)';  //need to make board look like selecting stuff
$query = $db->prepare($query);
$query->bind_param("is", $gameId, $updateType);
$query->execute();

echo "Select Position for Battle";
exit;
