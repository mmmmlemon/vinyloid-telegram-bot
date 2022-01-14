<?php

require_once "vendor/autoload.php";
require __DIR__ . '/parserFunctions.php';

function executeByLatestCommand($latestCommand, $message, $bot, $id){

    if($latestCommand === '/find'){
        $bot->sendMessage($id, "Здесь будет парсинг пластинок.");
        $r = parserTest($message->getText());

        if(count($r) > 0){
            foreach($r as $artist){
                
                $b = $artist->find('a');
        
                $b = "plastinka.com" . $b->href;
                
                $bot->sendMessage($id, $b);
            }

        }else {
            $bot->sendMessage($id, "По запросу не найдено ни одной пластинки.");
        }
         

    } else {
        $bot->sendMessage($id, 'Пожалуйста, напишите команду. Команды начинаются со знака /');
    }

}

?>