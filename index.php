<?php

require_once "vendor/autoload.php";
require __DIR__ . '/token.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/dbFunctions.php';
require __DIR__ . '/controllerFunctions.php';

try {
    
    $token = getTelegramBotAPIToken();
    $bot = new \TelegramBot\Api\Client($token);


    // команда start
    $bot->command('start', function ($message) use ($bot) {
        $answer = 'Бот начал работу';
        $bot->sendMessage($message->getChat()->getId(), $answer);
        //записать команду в лог
        writeCommandLog($message, $bot, true);
    });

    // команда help
    $bot->command('help', function ($message) use ($bot) {
        $answer = 'Здесь будет справка по работе с ботом';
        $bot->sendMessage($message->getChat()->getId(), $answer);
        //записать команду в лог
        writeCommandLog($message, $bot, true);
    });


    $bot->command('find', function($message) use ($bot){ 
        //записать команду в лог
        $check = writeCommandLog($message, $bot, true);

        if($check){
            $answer = "Введите название исполнителя или альбома.\n\nДопустимые варианты:\n'Кино'\n'Звезда по имени Солнце'\n'metallica'\n'sgt peppers lonely hearts club band'\n\nВводить желательно без знаков препинания. Работают как строчные так и прописные буквы.";
            $bot->sendMessage($message->getChat()->getId(),  $answer);
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    $bot->command('hello', function($message) use ($bot){   
       //записать команду в лог
       writeCommandLog($message, $bot, true);

    });

    
    //Текстовые сообщения
    $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {
        $message = $update->getMessage();
        $id = $message->getChat()->getId();

        $latestCommand = getLatestCommand($id);

        writeCommandLog($message, $bot, false);

        executeByLatestCommand($latestCommand, $message, $bot, $id);

        // $bot->sendMessage($id, 'Пожалуйста, напишите команду. Команды начинаются со знака /');

    }, function () {
        return true;
    });


    $bot->run();


} catch (\TelegramBot\Api\Exception $e) {
    $e->getMessage();
}

?>