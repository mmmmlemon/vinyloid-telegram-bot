<?php

// ФУНКЦИИ ДЛЯ РАБОТЫ С БАЗОЙ ДАННЫХ

// connectToDatabase
// соединиться с БД
function connectToDatabase(){

	// данные длоя логина
	$servername = "localhost";
	$username = $_ENV['DB_USERNAME'];
	$password = $_ENV['DB_PASSWORD'];
	$dbname = $_ENV['DB_NAME'];

	// Создание соединения
	$connection = mysqli_connect($servername, $username, $password, $dbname);
	// Check connection
	if (!$connection) {
		die("Connection failed: " . mysqli_connect_error());
	} else {
		return $connection;
	}	
}

// записть лога в БД
function writeCommandToDatabase($command_name, $chat_id){

	$connection = connectToDatabase();
	$sql = "INSERT INTO command_log (command_name, chat_id)
	VALUES ('{$command_name}', '{$chat_id}')";

	if ($connection->query($sql) === TRUE) {
		return true;
	} else {
		return false;
	}
	
	$connection->close();
}

// записать уведомление в БД
function addListItemToDatabase($chatId, $item){

	$connection = connectToDatabase();
	$sql = "INSERT INTO notifications (chat_id, search_text)
			VALUES ('{$chatId}', '{$item}')";
	if($connection->query($sql) === TRUE){
		return true;
	} else {
		return false;
	}

	$connection->close();
}

// проверить наличие уведомления перед удалением
function checkListItemBeforeDelete($chatId, $item){
    $connection = connectToDatabase();
	$sql = "SELECT chat_id, search_text FROM notifications WHERE chat_id = '{$chatId}' AND search_text = '{$item}'";
	$response = $connection->query($sql);
	
	$results = $response->fetch_assoc();

	$connection->close();

	if(count($results) === 0){
		return false;
	} else {
		return true;
	}
}

// удалить уведомление из БД
function deleteListItemFromDatabase($chatId, $item){
	$connection = connectToDatabase();
	$sql = "DELETE FROM notifications WHERE chat_id = '{$chatId}' AND search_text = '{$item}'";
	if($connection->query($sql) === TRUE){
		return true;
	} else {
		return false;
	}

	$connection->close();
}

// получить последнюю команду из БД
function getLatestCommand($chat_id){
	$connection = connectToDatabase();
	$sql = "SELECT command_name, chat_id FROM command_log WHERE chat_id = {$chat_id} ORDER BY timestamp DESC";
	$response = $connection->query($sql);
	$latestCommandName = $response->fetch_assoc()['command_name'];
	return $latestCommandName;
}

// получить уведомления о пластинках
function getList($chatId){

	$connection = connectToDatabase();
	$sql = "SELECT chat_id, search_text FROM notifications WHERE chat_id = '{$chatId}'";

	$response = $connection->query($sql);
	$result = [];
	while($row = $response->fetch_assoc()){
		array_push($result, $row['search_text']);
	}

	return $result;
}

?>