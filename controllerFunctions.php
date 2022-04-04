<?php

require_once "vendor/autoload.php";
require __DIR__ . '/dbFunctions.php';
require __DIR__ . '/parserFunctions.php';


// запись команды в БД
function writeCommandLog($message, $realCommand){

	$logCheck = null;

	if($realCommand === true){
		$logCheck = writeCommandToDatabase($message->getText(), $message->getChat()->getId());
	} else {
		$logCheck = writeCommandToDatabase('NULL', $message->getChat()->getId());
	}

	if($logCheck === true){
		return true;
	} else {
		return false;
	}
}

// start
function startCommand(){
    $response = "Бот начал работу 🤖 \nДля справки по работе с ботом выполните команду /help";
    return $response;
}
// help
function helpCommand(){
    $response = "Этот бот предназначен для поиска виниловых пластинок в Интернете. Бот находится в стадии разработки, сейчас реализован поиск по сайту plastinka.com \n\nСписок доступных команд:
    \n/start - начать работу с ботом\n/help - список команд и справка по работе с ботом\n/find - поиск пластинок\n/additem - добавить артиста или пластинку для еженедельной проверки ботом\n/showitems - показать список артистов\альбомов для еженедельной проверки";
    return $response;
}

// find
function findCommand(){
    $response = "Введите название исполнителя/группы 👩‍🎤 или альбома 💽\n\nНапример:\n'Кино'\n'Русское поле экспериментов'\n'metallica'\n'sgt peppers lonely hearts club band'\nи тому подобное. \n\nВводить желательно без знаков препинания. Работают как строчные так и прописные буквы.";
    return $response;
}

// checklps
function checklpsCommand($chatId){
    $messages = [];
    $notifications = getNotifications($chatId);

    if(count($notifications) == 0){
        array_push($messages, "Нет пластинок или артистов для проверки. Добавьте их через команду /additem");
    } 
    else 
    {
        foreach($notifications as $notification){

            $findResults = parserTest($notification);
            
            // если результатов поиска больше одного сообщения, то добавляем клавиатуру
            if(count($findResults) > 1){
                
                // кнопки клавиатуры
                $keyboardButtons = [];

                for($i = 0; $i < count($findResults); $i++){
                    $pageNumForButton = $i + 1;
                    if($i == $pageToShow){
                        $pageNumForButton = "- ".strval($pageNumForButton)." -";
                    }
                
                    array_push($keyboardButtons, ['text' => "{$pageNumForButton}", 
                                                'callback_data' => "{$i}[{$notification}]"]);
                }

                $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([$keyboardButtons]);

                $response = [
                    'message' => $findResults[0],
                    'keyboard' => true,
                    'keyboardObject' => $keyboard
                ]; 
                
                array_push($messages, $response);
            } else {
                // если результатов на одно сообщение, то добавляем текст сообщения без клавиатуры
                $response = [
                    'message' => $findResults[0],
                    'keyboard' => false,
                ]; 

                array_push($messages, $response);
            }

        }
    
        if(count($messages) > 0){
            array_unshift($messages, "Вот все пластинки которые мне удалось найти 💽");
        } else {
            array_push($messages, "По запросам не найдено ни одной пластинки.");
        }
    }

    return $messages;
}

// additem
function additemCommand(){
    $response = "Введите название исполнителя/группы 👩‍🎤 или альбома 💽 чтобы добавить его в список уведомлений.\n\nБот будет проверять наличие пластинок раз в неделю и отправлять вам отчёт (воскресение, 00:00 по МСК).";
    return $response;
}

// showitems
function showitemsCommand($chatId){

    $notifications = getNotifications($chatId);
    $response = "Список всех артистов\альбомов для еженедельной проверки ✔\n\n";
    foreach($notifications as $notification){
        $response .= $notification."\n";
    }

    return $response;
}

// generateProductList
// делает парсинг пластинок с сайтов и генерирует готовый ответ
// параметры: searchText - текст для поиска, pageToShow - страница которую нужно показать в сообщении
// showMessageHeader - показать сообщение перед списком пластинок
function generateProductList($searchText, $pageToShow, $showMessageHeader){
    
    // делаем парсинг информации с сайтов, на выходе получаем массив со страницами готового к отправке текста
    // каждый индекс в массиве - страница = сообщение для Telegram
    $parseResults = parserTest($searchText);
    $messageHeader = null;

    if($showMessageHeader === true){
        $messageHeader = "Вот все пластинки которые мне удалось найти 💽";
    }

    // если парсер ничего не нашёл
    if($parseResults == false){
        $response = "По запросу не найдено ни одной пластинки. Используйте команду /find чтобы найти что-то другое 🔎";
        return $response;
    } 
    // если есть результаты
    else {

        // если список товаров помещается в одном сообщении
        // отправляем ответ без клавиатуры
        if(count($parseResults) === 1){
            $response = [
                'messageHeader' => $messageHeader,
                'messageProducts' => $parseResults[0],
                'keyboard' => false
            ];

            return $response;
        } 
        // если список товаров не помещается в одно сообщение
        // отправляем ответ с клавиатурой
        else if (count($parseResults) > 1){
            
            // кнопки клавиатуры
            $keyboardButtons = [];

            for($i = 0; $i < count($parseResults); $i++){
                $pageNumForButton = $i + 1;
                if($i == $pageToShow){
                    $pageNumForButton = "- ".strval($pageNumForButton)." -";
                }
            
                array_push($keyboardButtons, ['text' => "{$pageNumForButton}", 
                                            'callback_data' => "{$i}[{$searchText}]"]);
            }

            $keyboard = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup([$keyboardButtons]);
            
            $response = [
                'messageHeader' => $messageHeader,
                'messageProducts' => $parseResults[$pageToShow],
                'keyboard' => true,
                'keyboardObject' => $keyboard
            ]; 

            return $response;
        }
       
    }
}

// additem - записать аритиста или пластинку в БД
function addNotification($chatId, $item){

    $check = writeNotificationToDatabase($chatId, $item);

    if($check === true){
        $response = "Уведомление сохранено 💾\nТеперь я буду каждую неделю проверять пластинки по запросу <b>{$item}</b>";
        return $response;
    } else {
        $response = "⚠ Не удалось сохранить уведомление ⚠";
    }

}

?>