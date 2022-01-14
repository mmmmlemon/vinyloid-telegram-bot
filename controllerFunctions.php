<?php

require_once "vendor/autoload.php";

function executeByLatestCommand($latestCommand, $bot, $id){

    if($latestCommand === '/find'){
        $bot->sendMessage($id, "Здесь будет парсинг пластинок");

    } else {
        $bot->sendMessage($id, 'Пожалуйста, напишите команду. Команды начинаются со знака /');
    }

}

?>