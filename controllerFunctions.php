<?php

require_once "vendor/autoload.php";
require __DIR__ . '/parserFunctions.php';

function executeByLatestCommand($latestCommand, $message, $bot, $id){

    if($latestCommand === '/find'){
        $r = parserTest($message->getText());


        if($r == false){
            $bot->sendMessage($id, "По запросу не найдено ни одной пластинки.", 'HTML', true);
        } else {
            if(count($r) > 0){
                $bot->sendMessage($id, "Вот все пластинки которые мне удалось найти.");
                foreach($r as $message){
                    $bot->sendMessage($id, $message, 'HTML', true);
                }
            } else if($r === false){
                $bot->sendMessage($id, "По запросу не найдено ни одной пластинки.", 'HTML', true);
            }
        }

        

        

    } else {
        $bot->sendMessage($id, 'Пожалуйста, напишите команду. Команды начинаются со знака /');
    }

}

?>