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
            $bot->sendMessage($message->getChat()->getId(),  $response, 'HTML');
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    // команда additem
    $bot->command('addlist', function($message) use ($bot){ 
        //записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = addlistCommand();
            $bot->sendMessage($message->getChat()->getId(),  $response, 'HTML');
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    // команда deleteitem
    $bot->command('deletelist', function($message) use ($bot){
        // записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = deletelistCommand($message->getChat()->getId());

            foreach($response as $deleteItem){
                $bot->sendMessage($message->getChat()->getId(), $deleteItem, 'HTML');    
            }
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    // команда showitems
    $bot->command('showlist', function($message) use ($bot){
        //записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = showlistCommand($message->getChat()->getId());
            $bot->sendMessage($message->getChat()->getId(),  $response, 'HTML');
        } else {
            $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
        }
    });

    // команда findlist
    $bot->command('findlist', function($message) use ($bot){

        // записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            
        //     // id
            $chatId = $message->getChat()->getId();

            // получаем список исполнителей и альбомов для поиска
            $searchList = checklpsCommand($chatId);

            // переменные для подсчёта найденных пластинок
            $count = 0;
            $countCopy = 0;

            // если список не пустой
            if($searchList != false){
                
                // сообщение в начале
                $bot->sendMessage($chatId, "Начинаю поиск пластинок по списку... 🔎\n\n🔽Нижё - всё что я найду🔽");  

                // проверяем каждый пункт списка по всем сайтам
                foreach($searchList as $listItem){

                    $countCopy = $count;
                    
                    // получаем результаты с plastinka.com и выводим
                    $plastinkaResponse = generateProductList($listItem, "plastinka", 0, true);
                    
                    if($plastinkaResponse != false)
                    { $bot->sendMessage($chatId, "plastinka.com - <b>{$listItem}</b>", 'HTML'); }

                    if($plastinkaResponse != false){
                        switch($plastinkaResponse['keyboard']){
                             // если ответ пришёл без клавиатуры
                            case false:
                                $bot->sendMessage($chatId, $plastinkaResponse['messageProducts'], 'HTML', true);  
                                break;
                            // если с клавиатурой
                            case true:
                                $bot->sendMessage($chatId, $plastinkaResponse['messageProducts'], 'HTML', true, null, $plastinkaResponse['keyboardObject']);  
                                break;
                            default:
                                $bot->sendMessage($chatId, $plastinkaResponse);  
                        }

                        $count += 1;
                    } 

                    // получаем результаты с vinylbox.ru и выводим
                    $vinylboxResponse = generateProductList($listItem, "vinylbox", 0, true);

                    if($vinylboxResponse != false)
                    { $bot->sendMessage($chatId, "vinylbox.ru - <b>{$listItem}</b>", 'HTML'); }

                    if($vinylboxResponse != false){
                        switch($vinylboxResponse['keyboard']){
                            // если ответ пришёл без клавиатуры
                            case false:
                                $bot->sendMessage($chatId, $vinylboxResponse['messageProducts'], 'HTML', true);  
                                break;
                            // если ответ пришёл с клавиатурой
                            case true:
                                $bot->sendMessage($chatId, $vinylboxResponse['messageProducts'], 'HTML', true, null, $vinylboxResponse['keyboardObject']);  
                                break;
                            default:
                                $bot->sendMessage($chatId, $vinylboxResponse); 
                        }
                        $count += 1;
                    }

                    if($countCopy === $count){
                        if($plastinkaResponse == false && $vinylboxResponse == false){
                            $bot->sendMessage($chatId, "<b>{$listItem}</b>\n\nПластинок не найдено.", 'HTML');
                        }    
                    }  
                }

            } else {
                $bot->sendMessage($chatId, "Нет пластинок или артистов для проверки. Добавьте их через команду /addlist");  
            }
            
            // сообщение в конце
            if($count == 0 && $searchList != false){
                $bot->sendMessage($chatId, "Не найдено ни одной пластинки из списка. Используйте команду /find чтобы найти что-то другое 🔎");  
            } else if ($count > 0 && $searchList != false){
                $bot->sendMessage($chatId, "🔼 Это все что удалось найти. 🔼\n");  
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
                $indexEnd = strpos($callbackQuery->getData(), ']');
                $indexTwo = strpos($callbackQuery->getData(), '(');
                $indexTwoEnd = strpos($callbackQuery->getData(), ')');
                // страница
                $pageToShow = substr($callbackQuery->getData(), 0, $index);
                // текст для поиска
                $textForSearch = substr($callbackQuery->getData(), $index+1, $indexEnd-2);   
                // сайт
                $site = substr($callbackQuery->getData(), $indexTwo+1, $indexTwoEnd);   
                // wtf, последняя скобка в конце почему-то всегда попадает в substr какой-бы последний индекс я не указывал, поэтому стираю через str_replace
                $site = str_replace(')', '', $site);

                // генерируем список товаров на основе введённого текста
                $response = generateProductList($textForSearch, $site, $pageToShow, true);

                $chatId = $callbackQuery->getMessage()->getChat()->getId();

                switch($response['keyboard']){
                    // если ответ без клавиатуры
                    case false:
                        if($response['messageHeader'] != null){
                            $bot->sendMessage($chatId, $response['messageHeader']);
                        }
                        $bot->sendMessage($chatId, $response['messageProducts'], 'HTML', true); 
                        break;
                    // если ответ c клавиатурой
                    case true:
                        if($response['messageHeader'] != null){
                            $bot->sendMessage($chatId, $response['messageHeader']);
                        }
                        $bot->sendMessage($chatId, $response['messageProducts'], 'HTML', true, null, $response['keyboardObject']);  
                        break;
                    default:
                        $bot->sendMessage($chatId, $response); 
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
            $chatId = $message->getChat()->getId();

            // получение последней использованной команды
            $latestCommand = getLatestCommand($chatId);

            // выполнения действия с введённым текстом в зависимости от последней записанной в лог команды
            // если последняя команда была /find, то ищем пластинки на основе введённого текста
            if($latestCommand === "/find"){
                
                // сообщение в начале
                $bot->sendMessage($chatId, "Начинаю поиск пластинок... 🔎\n\n🔽Нижё - всё что я найду🔽");  

                // запись команды text_input в лог
                $check = writeCommandLog($message, false);

                if($check){
                    // получаем результаты с plastinka.com и выводим
                    $plastinkaResponse = generateProductList($message->getText(), "plastinka", 0, true);

                    if($plastinkaResponse != false){
                        switch($plastinkaResponse['keyboard']){
                            // если ответ пришёл без клавиатуры
                            case false:
                                if($plastinkaResponse['messageHeader'] != null){
                                    $bot->sendMessage($chatId, $plastinkaResponse['messageHeader']);
                                }
                                $bot->sendMessage($chatId, $plastinkaResponse['messageProducts'], 'HTML', true);  
                                break;
                            // если ответ пришёл с клавиатурой
                            case true:
                                if($plastinkaResponse['messageHeader'] != null){
                                    $bot->sendMessage($chatId, $plastinkaResponse['messageHeader']);
                                }
                                $bot->sendMessage($chatId, $plastinkaResponse['messageProducts'], 'HTML', true, null, $plastinkaResponse['keyboardObject']);  
                                break;
                            default:
                                $bot->sendMessage($chatId, $plastinkaResponse);  
                                break;
                        }
                    }

                    // получаем результаты с vinylbox.ru и выводим
                    $vinylboxResponse = generateProductList($message->getText(), "vinylbox", 0, true);

                    if($vinylboxResponse != false){

                        switch($vinylboxResponse['keyboard']){
                            // если ответ пришёл без клавиатуры
                            case false:
                                if($vinylboxResponse['messageHeader'] != null){
                                    $bot->sendMessage($chatId, $vinylboxResponse['messageHeader']);
                                }
                                $bot->sendMessage($chatId, $vinylboxResponse['messageProducts'], 'HTML', true);  
                                break;
                            // если ответ пришёл с клавиатурой
                            case true:
                                if($vinylboxResponse['messageHeader'] != null){
                                    $bot->sendMessage($chatId, $vinylboxResponse['messageHeader']);
                                }
                                $bot->sendMessage($chatId, $vinylboxResponse['messageProducts'], 'HTML', true, null, $vinylboxResponse['keyboardObject']);  
                                break;
                            default:
                                $bot->sendMessage($chatId, $vinylboxResponse);  
                        }
                    }

                    // получаем результаты с vinyl.ru и выводим
                    // $vinylruResponse = generateProductList($message->getText(), "vinylru", 0, true);
                    // // $bot->sendMessage($chatId, $vinylruResponse[0], 'HTML', true);
                    // if($vinylruResponse != false){
                    //     switch($vinylruResponse['keyboard']){
                    //         // если ответ пришёл без клавиатуры
                    //         case false:
                    //             if($vinylruResponse['messageHeader'] != null){
                    //                 $bot->sendMessage($chatId, $vinylruResponse['messageHeader']);
                    //             }
                    //             $bot->sendMessage($chatId, $vinylruResponse['messageProducts'], 'HTML', true);  
                    //             break;
                    //         // если ответ пришёл с клавиатурой
                    //         case true:
                    //             if($vinylruResponse['messageHeader'] != null){
                    //                 $bot->sendMessage($chatId, $vinylruResponse['messageHeader']);
                    //             }
                    //             $bot->sendMessage($chatId, $vinylruResponse['messageProducts'], 'HTML', true, null, $vinylruResponse['keyboardObject']);  
                    //             break;
                    //         default:
                    //             $bot->sendMessage($chatId, $vinylruResponse);  
                    //     }
                    // }
                    

                    if($vinylboxResponse == false && $plastinkaResponse == false && $vinylruResponse == false){
                        $bot->sendMessage($chatId, "По запросу не найдено ни одной пластинки. Используйте команду /find чтобы найти что-то другое 🔎");  
                    } else {
                        $bot->sendMessage($chatId, "🔼 Это все что удалось найти. 🔼\n\n");  
                    }
                } else {
                    $bot->sendMessage($message->getChat()->getId(),  "⚠ Ошибка записи команды в лог ⚠");
                }
            } 
            
            else if($latestCommand === "/addlist"){
                // запись команды text_input в лог
                $check = writeCommandLog($message, false);

                if($check){
                    // запсиваем новый пункт списка в БД
                    $response = addItemToList($message->getChat()->getId(), $message->getText());
                    $bot->sendMessage($message->getChat()->getId(), $response, 'HTML');
                }
            }

            else if ($latestCommand === "/deletelist"){
                // запись команды text_input в лог
                $check = writeCommandLog($message, false);

                if($check){
                    // удаляем пункт списка из БД
                    $response = deleteItemFromList($message->getChat()->getId(), $message->getText());
                    $bot->sendMessage($message->getChat()->getId(), $response, 'HTML');
                }
            }  
            else {
                // запись команды text_input в лог
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