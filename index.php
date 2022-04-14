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

    // команда deleteitem
    $bot->command('deleteitem', function($message) use ($bot){
        // записать команду в лог
        $check = writeCommandLog($message, true);

        if($check){
            $response = deleteitemCommand($message->getChat()->getId());

            foreach($response as $deleteItem){
                $bot->sendMessage($message->getChat()->getId(), $deleteItem);    
            }
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
            
            $id = $message->getChat()->getId();

            $notifications = checklpsCommand($id);

            $count = 0;
            $countOld = 0;

            if($notifications != false){

                $bot->sendMessage($id, "Начинаю поиск пластинок по списку... 🔎\n\n🔽Нижё - всё что я найду🔽");  

                foreach($notifications as $notification){
                    $countOld = $count;
                    
                    // получаем результаты с plastinka.com и выводим
                    $plastinkaResponse = generateProductList($notification, "plastinka", 0, true);
                    
                    if($plastinkaResponse != false)
                    $bot->sendMessage($id, "plastinka.com - <b>{$notification}</b>", 'HTML');  

                    if($plastinkaResponse != false){
                        // если ответ пришёл без клавиатуры
                        if($plastinkaResponse['keyboard'] === false){
                            $bot->sendMessage($id, $plastinkaResponse['messageProducts'], 'HTML', true);  
                        } 
                        // если ответ пришёл с клавиатурой
                        else if($plastinkaResponse['keyboard'] === true){
                            $bot->sendMessage($id, $plastinkaResponse['messageProducts'], 'HTML', true, null, $plastinkaResponse['keyboardObject']);  
                        } else {
                            $bot->sendMessage($id, $plastinkaResponse);  
                        }
                        $count += 1;
                    } 

                    // получаем результаты с vinylbox.ru
                    // получаем результаты с plastinka.com и выводим
                    $vinylboxResponse = generateProductList($notification, "vinylbox", 0, true);

                    if($vinylboxResponse != false)
                    $bot->sendMessage($id, "vinylbox.ru - <b>{$notification}</b>", 'HTML');  

                    if($vinylboxResponse != false){
                        // если ответ пришёл без клавиатуры
                        if($vinylboxResponse['keyboard'] === false){
                            $bot->sendMessage($id, $vinylboxResponse['messageProducts'], 'HTML', true);  
                        } 
                        // если ответ пришёл с клавиатурой
                        else if($vinylboxResponse['keyboard'] === true){
                            $bot->sendMessage($id, $vinylboxResponse['messageProducts'], 'HTML', true, null, $vinylboxResponse['keyboardObject']);  
                        } else {
                            $bot->sendMessage($id, $vinylboxResponse);  
                        }
                        $count += 1;
                    }

                    if($countOld === $count){
                        if($plastinkaResponse == false && $vinylboxResponse == false){
                            $bot->sendMessage($id, "<b>{$notification}</b>\n\nПластинок не найдено.", 'HTML');
                        }    
                    }
                    
                }

                
            } else {
                $bot->sendMessage($id, "Нет пластинок или артистов для проверки. Добавьте их через команду /additem");  
            }

            if($count == 0 && $notifications != false){
                $bot->sendMessage($id, "Не найдено ни одной пластинки из списка. Используйте команду /find чтобы найти что-то другое 🔎");  
            } else if ($count > 0 && $notifications != false){
                $bot->sendMessage($id, "🔼 Это все что удалось найти 🔼");  
            }

            // // получаем результаты
            // $response = checklpsCommand($message->getChat()->getId());

            // // выводим все сообщения
            // foreach($response as $msg){
                
            //     // если это текстовое сообщение
            //     if(gettype($msg) == 'string'){
            //         $bot->sendMessage($message->getChat()->getId(), $msg, 'HTML', true, null);
            //     }

            //     // если это сообщение с информацией о пластинках
            //     else if(gettype($msg) == 'array'){
            //         // выводим сообщение с клавиатурой или без неё
            //         if($msg['keyboard'] === false){
            //             $bot->sendMessage($message->getChat()->getId(), $msg['message'], 'HTML', true, null);
            //         } else {
            //             $bot->sendMessage($message->getChat()->getId(), $msg['message'], 'HTML', true,  null , $msg['keyboardObject']);
            //         }  
            //     }    
            // } 
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
                $site = str_replace(')', '', $site);

                // генерируем список товаров на основе введённого текста
                $response = generateProductList($textForSearch, $site, $pageToShow, true);

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
            
                    // получаем результаты с plastinka.com и выводим
                    $plastinkaResponse = generateProductList($message->getText(), "plastinka", 0, true);

                    if($plastinkaResponse != false){
                        // если ответ пришёл без клавиатуры
                        if($plastinkaResponse['keyboard'] === false){
                            if($plastinkaResponse['messageHeader'] != null){
                                $bot->sendMessage($id, $plastinkaResponse['messageHeader']);
                            }
                            $bot->sendMessage($id, $plastinkaResponse['messageProducts'], 'HTML', true);  
                        } 
                        // если ответ пришёл с клавиатурой
                        else if($plastinkaResponse['keyboard'] === true){
                            if($plastinkaResponse['messageHeader'] != null){
                                $bot->sendMessage($id, $plastinkaResponse['messageHeader']);
                            }
                            $bot->sendMessage($id, $plastinkaResponse['messageProducts'], 'HTML', true, null, $plastinkaResponse['keyboardObject']);  
                        } else {
                            $bot->sendMessage($id, $plastinkaResponse);  
                        }
                    }

                    // получаем результаты с vinylbox.ru
                    // получаем результаты с plastinka.com и выводим
                    $vinylboxResponse = generateProductList($message->getText(), "vinylbox", 0, true);

                    if($vinylboxResponse != false){
                        // если ответ пришёл без клавиатуры
                        if($vinylboxResponse['keyboard'] === false){
                            if($vinylboxResponse['messageHeader'] != null){
                                $bot->sendMessage($id, $vinylboxResponse['messageHeader']);
                            }
                            $bot->sendMessage($id, $vinylboxResponse['messageProducts'], 'HTML', true);  
                        } 
                        // если ответ пришёл с клавиатурой
                        else if($vinylboxResponse['keyboard'] === true){
                            if($vinylboxResponse['messageHeader'] != null){
                                $bot->sendMessage($id, $vinylboxResponse['messageHeader']);
                            }
                            $bot->sendMessage($id, $vinylboxResponse['messageProducts'], 'HTML', true, null, $vinylboxResponse['keyboardObject']);  
                        } else {
                            $bot->sendMessage($id, $vinylboxResponse);  
                        }
                    }

                    if($vinylboxResponse == false && $plastinkaResponse == false){
                        $bot->sendMessage($id, "По запросу не найдено ни одной пластинки. Используйте команду /find чтобы найти что-то другое 🔎");  
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