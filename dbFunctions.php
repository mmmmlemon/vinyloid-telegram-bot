<?php

// ФУНКЦИИ ДЛЯ РАБОТЫ С БАЗОЙ ДАННЫХ

// connectToDatabase
// соединиться с БД
function connectToDatabase(){

	// данные длоя логина
	$servername = "localhost";
	$username = "mmmmlemon_vinyl";
	$password = "CPPX4SrU";
	$dbname = "mmmmlemon_vinyl";

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
	$time = date("Y-m-d",$t);
	$sql = "INSERT INTO command_log (command_name, chat_id)
	VALUES ('{$command_name}', '{$chat_id}')";

	if ($connection->query($sql) === TRUE) {
		return true;
	} else {
		return false;
	}
	
	$connection->close();
}

// запись команды в БД
function writeCommandLog($message, $bot){
	$logCheck = writeCommandToDatabase($message->getText(), $message->getChat()->getId());
	$commandName = $message->getText();
	$responseText = '';
	if($logCheck === true){
		$responseText = "Команда {$commandName} была записана в лог!"; 
	} else {
		$responseText = "ОШИБКА! Команда {$commandName} не была записана в лог!";
	}

	$bot->sendMessage($message->getChat()->getId(), $responseText);
}


function getLatestCommand($chat_id){

	$connection = connectToDatabase();
	
	$sql = "SELECT command_name, chat_id FROM command_log WHERE chat_id = {$chat_id} ORDER BY timestamp DESC";

	$response = $connection->query($sql);

	$latestCommandName = $response->fetch_assoc()['command_name'];

	return $latestCommandName;
}


?>