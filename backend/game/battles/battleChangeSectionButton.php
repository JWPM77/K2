<?php
session_start();
include("../../db.php");

$gameId = $_SESSION['gameId'];
$myTeam = $_SESSION['myTeam'];

$query = 'SELECT gamePhase, gameCurrentTeam, gameBattleSection, gameBattleSubSection FROM GAMES WHERE gameId = ?';
$preparedQuery = $db->prepare($query);
$preparedQuery->bind_param("i", $gameId);
$preparedQuery->execute();
$results = $preparedQuery->get_result();
$r = $results->fetch_assoc();

$gamePhase = $r['gamePhase'];
$gameCurrentTeam = $r['gameCurrentTeam'];
$gameBattleSection = $r['gameBattleSection'];
$gameBattleSubSection = $r['gameBattleSubSection'];

if ($gamePhase != 2) {
    echo "It is not the right phase for this.";
    exit;
}
if ($gameBattleSubSection != "choosing_pieces" || $gameBattleSection == "none" || $gameBattleSection == "selectPos" || $gameBattleSection == "selectPieces") {
    echo "Unable to change section, wrong subsection/section.";
    exit;
}
if ((($gameBattleSection == "attack" || $gameBattleSection == "askRepeat") && $myTeam != $gameCurrentTeam) || ($gameBattleSection == "counter" && $myTeam == $gameCurrentTeam)) {
    echo "Not your turn to change section.";
    exit;
}

if ($gameBattleSection == "attack" || $gameBattleSection == "counter") {
    $query3 = "SELECT battlePieceId, battlePieceState FROM battlePieces WHERE battleGameId = ? AND (battlePieceState = 5 OR battlePieceState = 6)";
    $preparedQuery3 = $db->prepare($query3);
    $preparedQuery3->bind_param("i", $gameId);
    $preparedQuery3->execute();
    $results3 = $preparedQuery3->get_result();
    $numResults3 = $results3->num_rows;
    for ($i = 0; $i < $numResults3; $i++) {
        $r = $results3->fetch_assoc();
        $battlePieceId = $r['battlePieceId'];
        $battlePieceState = $r['battlePieceState'];

        $query = 'UPDATE battlePieces SET battlePieceState = battlePieceState - 4 WHERE battlePieceId = ?';
        $preparedQuery = $db->prepare($query);
        $preparedQuery->bind_param("i", $battlePieceId);
        $preparedQuery->execute();

        $updateType = "battleMove";
        $newPositionId = $battlePieceState - 4;
        $query = 'INSERT INTO updates (updateGameId, updateType, updatePlacementId, updateNewPositionId) VALUES (?, ?, ?, ?)';
        $query = $db->prepare($query);
        $query->bind_param("isii", $gameId, $updateType, $battlePieceId, $newPositionId);
        $query->execute();
    }

    if ($gameBattleSection == "attack") {
        $newSection = "counter";
    } else {
        $newSection = "askRepeat";
    }

    $query3 = "SELECT battlePieceId, battlePieceState FROM battlePieces WHERE battleGameId = ? AND (battlePieceState = 3 OR battlePieceState = 4)";
    $preparedQuery3 = $db->prepare($query3);
    $preparedQuery3->bind_param("i", $gameId);
    $preparedQuery3->execute();
    $results3 = $preparedQuery3->get_result();
    $numResults3 = $results3->num_rows;
    for ($i = 0; $i < $numResults3; $i++) {
        $r = $results3->fetch_assoc();
        $battlePieceId = $r['battlePieceId'];
        $battlePieceState = $r['battlePieceState'];

        $query = 'UPDATE battlePieces SET battlePieceState = battlePieceState - 2 WHERE battlePieceId = ?';
        $preparedQuery = $db->prepare($query);
        $preparedQuery->bind_param("i", $battlePieceId);
        $preparedQuery->execute();

        $updateType = "battleMove";
        $newPositionId = $battlePieceState - 2;
        $query = 'INSERT INTO updates (updateGameId, updateType, updatePlacementId, updateNewPositionId) VALUES (?, ?, ?, ?)';
        $query = $db->prepare($query);
        $query->bind_param("isii", $gameId, $updateType, $battlePieceId, $newPositionId);
        $query->execute();
    }

    $query = 'UPDATE games SET gameBattleSection = ? WHERE gameId = ?';
    $preparedQuery = $db->prepare($query);
    $preparedQuery->bind_param("si", $newSection, $gameId);
    $preparedQuery->execute();

    $updateType = "getBoard";
    $query = 'INSERT INTO updates (updateGameId, updateType) VALUES (?, ?)';  //need to make board look like selecting stuff
    $query = $db->prepare($query);
    $query->bind_param("is", $gameId, $updateType);
    $query->execute();

    echo "Switched Battle Turn.";
    exit;
} else {  //askRepeat, clicks to exit the game
    $query = 'DELETE FROM battlePieces WHERE battleGameId = ?';  //handled in html when getBoard section is none?
    $query = $db->prepare($query);
    $query->bind_param("i", $gameId);
    $query->execute();

    $newSection = "none";
    $newBattleSubSection = "choosing_pieces";
    $newBattleLastMessage = "Reset Message";
    $query = 'UPDATE games SET gameBattleSection = ?, gameBattleSubSection = ?, gameBattlePosSelected = -1, gameBattleLastRoll = 1, gameBattleTurn = 0, gameBattleLastMessage = ? WHERE gameId = ?';
    $preparedQuery = $db->prepare($query);
    $preparedQuery->bind_param("sssi", $newSection, $newBattleSubSection, $newBattleLastMessage, $gameId);
    $preparedQuery->execute();

    $updateType = "getBoard";
    $query = 'INSERT INTO updates (updateGameId, updateType) VALUES (?, ?)';  //need to make board look like selecting stuff
    $query = $db->prepare($query);
    $query->bind_param("is", $gameId, $updateType);
    $query->execute();

    echo "Battle Ended.";
    exit;
}



