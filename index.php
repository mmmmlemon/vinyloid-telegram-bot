<?php

require_once "vendor/autoload.php";
require __DIR__ . '/dbFunctions.php';
require __DIR__ . '/token.php';

try {
    
    $token = getTelegramBotAPIToken();
    $bot = new \TelegramBot\Api\Client($token);


    // команда start
    $bot->command('start', function ($message) use ($bot) {
        $answer = 'Бот начал работу';
        $bot->sendMessage($message->getChat()->getId(), $answer);
        //записать команду в лог
        writeCommandLog($message, $bot);
    });

    // команда help
    $bot->command('help', function ($message) use ($bot) {
        $answer = 'Здесь будет справка по работе с ботом';
        $bot->sendMessage($message->getChat()->getId(), $answer);
        //записать команду в лог
        writeCommandLog($message, $bot);
    });


    $bot->command('find', function($message) use ($bot){
        $answer = "Введите название исполнителя или альбома.\n\nДопустимые варианты:\n'Кино'\n'Звезда по имени Солнце'\n'Кино звезда по имени солнце'\n\nВводить нужно без знаков препинания. Работают как строчные так и прописные буквы.";
        $bot->sendMessage($message->getChat()->getId(),  $answer);
        //записать команду в лог
       writeCommandLog($message, $bot);
    });

    $bot->command('hello', function($message) use ($bot){   
       //записать команду в лог
       writeCommandLog($message, $bot);

    });

    
    //Текстовые сообщения
    $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {
        $message = $update->getMessage();
        $id = $message->getChat()->getId();


        $latestCommand = getLatestCommand($id);
        $bot->sendMessage($id, "Последняя команда была - {$latestCommand}");

        // $bot->sendMessage($id, 'Пожалуйста, напишите команду. Команды начинаются со знака /');

    }, function () {
        return true;
    });


    $bot->run();


} catch (\TelegramBot\Api\Exception $e) {
    $e->getMessage();
}

?>