<?php

require_once "vendor/autoload.php";
require __DIR__ . '/parserFunctions.php';

function executeByLatestCommand($latestCommand, $message, $bot, $id){

    if($latestCommand === '/find'){
        $r = parserTest($message->getText());
        $bot->sendMessage($id, "Вот все пластинки которые мне удалось найти.");
        $bot->sendMessage($id, $r, 'HTML', true);

    } else {
        $bot->sendMessage($id, 'Пожалуйста, напишите команду. Команды начинаются со знака /');
    }

}

?>