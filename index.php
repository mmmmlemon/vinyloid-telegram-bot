<?php

require_once "vendor/autoload.php";
require __DIR__ . '/token.php';
require __DIR__ . '/helpers.php';
// require __DIR__ . '/dbFunctions.php';
require __DIR__ . '/controllerFunctions.php';

try {
    
    // API Токен
    $token = getTelegramBotAPIToken();

    // Telegram-бот
    $bot = new \TelegramBot\Api\Client($token);

    // ОБРАБОТКА КОМАНД
    // команда start
    $bot->command('start', function ($message) use ($bot) {

         //записать команду в лог
         $check = writeCommandLog($message, true);

         if($check){
            // получаем ответ
            $response = startCommand();
            // ответ
            $bot->sendMessage($message->getChat()->getId(), $response);
         } else {
             // ответ c ошибкой
            $bot->sendMessage($message->getChat()->getId(), "⚠ Ошибка записи команды в лог ⚠");
         }

    });

    // команда help
    $bot->command('help', function ($message) use ($bot) {
        //записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            // получаем ответ
            $response = helpCommand();
            // ответ
            $bot->sendMessage($message->getChat()->getId(), $response);
         } else {
             // ответ c ошибкой
            $bot->sendMessage($message->getChat()->getId(), "⚠ Ошибка записи команды в лог ⚠");
         }
    });

    // команда find
    $bot->command('find', function($message) use ($bot){ 
        //записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = findCommand();
            $bot->sendMessage($message->getChat()->getId(),  $response);
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    // команда additem
    $bot->command('additem', function($message) use ($bot){ 
        //записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = additemCommand();
            $bot->sendMessage($message->getChat()->getId(),  $response, 'HTML');
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    $bot->command('deleteitem', function($message) use ($bot){
        // записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = deleteitemCommand($message->getChat()->getId());

            foreach($response as $msg){
                $bot->sendMessage($message->getChat()->getId(), $msg);    
            }

            
            // $bot->sendMessage($message->getChat()->getId(), $response['message'], null, false, null, $response['keyboardObject']);
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    // команда showitems
    $bot->command('showitems', function($message) use ($bot){
        //записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = showitemsCommand($message->getChat()->getId());
            $bot->sendMessage($message->getChat()->getId(),  $response, 'HTML');
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

      // команда checklps
      $bot->command('checklps', function($message) use ($bot){
        $check = writeCommandLog($message, true);

        if($check){
            // получаем результаты
            $response = checklpsCommand($message->getChat()->getId());

            // выводим все сообщения
            foreach($response as $msg){
                
                // если это текстовое сообщение
                if(gettype($msg) == 'string'){
                    $bot->sendMessage($message->getChat()->getId(), $msg, 'HTML', true, null);
                }

                // если это сообщение с информацией о пластинках
                else if(gettype($msg) == 'array'){
                    // выводим сообщение с клавиатурой или без неё
                    if($msg['keyboard'] === false){
                        $bot->sendMessage($message->getChat()->getId(), $msg['message'], 'HTML', true, null);
                    } else {
                        $bot->sendMessage($message->getChat()->getId(), $msg['message'], 'HTML', true,  null , $msg['keyboardObject']);
                    }  
                }    
            } 
        } else {
            $bot->sendMessage($message->getChat()->getId(), "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    
    //Текстовые сообщения и коллбэки
    $bot->on(function (\TelegramBot\Api\Types\Update $update) use ($bot) {

        // проверяем коллбэки
        $callbackQuery = $update->getCallbackQuery();

        // проверка коллбэков, если нажата кнопка страницы, значит был отправлен callback
        if ($callbackQuery !== null) {

            //записать команду в лог
            $check = writeCommandLog($callbackQuery->getMessage(), false);

            if($check){
                
                // если коллбэк есть, то отправляем запрос на парсинг и показываем страницу соответствующую кнопке
                $index = strpos($callbackQuery->getData(), '[');
                // страница
                $pageToShow = substr($callbackQuery->getData(), 0, $index);
                // текст для поиска
                $textForSearch = substr($callbackQuery->getData(), $index + 1, strlen($callbackQuery->getData()) - 3);   
                
                // генерируем список товаров на основе введённого текста
                $response = generateProductList($textForSearch, $pageToShow, false);

                $id = $callbackQuery->getMessage()->getChat()->getId();

                // если ответ пришёл без клавиатуры
                if($response['keyboard'] === false){
                    if($response['messageHeader'] != null){
                        $bot->sendMessage($id, $response['messageHeader']);
                    }
                    $bot->sendMessage($id, $response['messageProducts'], 'HTML', true);  
                } 
                // если ответ пришёл с клавиатурой
                else if($response['keyboard'] === true){
                    if($response['messageHeader'] != null){
                        $bot->sendMessage($id, $response['messageHeader']);
                    }
                    $bot->sendMessage($id, $response['messageProducts'], 'HTML', true, null, $response['keyboardObject']);  
                } else {
                    $bot->sendMessage($id, $response);  
                }

                $bot->answerCallbackQuery(
                    $callbackQuery->getId()
                );
            } else {
                $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
            }
        }
        // если нет коллбэков, проверяем отправленное текстовое сообщение
        else { 
            // получение сообщения
            $message = $update->getMessage();

            // получение id чата
            $id = $message->getChat()->getId();

            // получение последней использованной команды
            $latestCommand = getLatestCommand($id);

            // выполнения действия с введённым текстом в зависимости от последней записанной в лог команды
            // если последняя команда была /find, то ищем пластинки на основе введённого текста
            if($latestCommand === "/find"){
                // запись команды NULL в лог
                $check = writeCommandLog($message, false);
                
                if($check){
                    // генерируем список товаров на основе введённого текста
                    $response = generateProductList($message->getText(), 0, true);

                    // если ответ пришёл без клавиатуры
                    if($response['keyboard'] === false){
                        if($response['messageHeader'] != null){
                            $bot->sendMessage($id, $response['messageHeader']);
                        }
                        $bot->sendMessage($id, $response['messageProducts'], 'HTML', true);  
                    } 
                    // если ответ пришёл с клавиатурой
                    else if($response['keyboard'] === true){
                        if($response['messageHeader'] != null){
                            $bot->sendMessage($id, $response['messageHeader']);
                        }
                        $bot->sendMessage($id, $response['messageProducts'], 'HTML', true, null, $response['keyboardObject']);  
                    } else {
                        $bot->sendMessage($id, $response);  
                    }
                } else {
                    $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
                }
            } 
            
            else if($latestCommand === "/additem"){
                // запись команды NULL в лог
                $check = writeCommandLog($message, false);

                if($check){
                    // генерируем список товаров на основе введённого текста
                    $response = addNotification($message->getChat()->getId(), $message->getText());
                    $bot->sendMessage($message->getChat()->getId(), $response, 'HTML');
                }
            }

            else if ($latestCommand === "/deleteitem"){

                // запись команды NULL в лог
                $check = writeCommandLog($message, false);

                if($check){
                    // генерируем список товаров на основе введённого текста
                    $response = deleteNotification($message->getChat()->getId(), $message->getText());
                    $bot->sendMessage($message->getChat()->getId(), $response, 'HTML');
                }
         
            }
            
            else {
                // запись команды NULL в лог
                writeCommandLog($message, false);
                $bot->sendMessage($id, 'Пожалуйста, напишите команду. Список команд можно посмотреть в /help');
            }

        }
    
    }, function () {
        return true;
    });

    $bot->run();

} catch (\TelegramBot\Api\Exception $e) {
    $e->getMessage();

}

?>