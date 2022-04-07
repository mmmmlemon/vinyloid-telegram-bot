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
		$logCheck = writeCommandToDatabase('text_input', $message->getChat()->getId());
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
    $response = "Этот бот предназначен для поиска виниловых пластинок в Интернете. Бот находится в стадии разработки, сейчас реализован поиск по сайту plastinka.com. \n\nВы можете искать пластинки по имени группы\исполнителя либо по названию альбома. Поиск можно производить вручную, либо создать список из исполнителей и альбомов и проверять все сразу одной командой. \n\nСписок доступных команд:
    \n/start - начать работу с ботом\n/help - список команд и справка по работе с ботом\n\n/find - поиск пластинок (ручной)\n\n/checklps - проверить артистов\пластинки из списка\n/additem - добавить артиста или пластинку для проверки ботом\n/deleteitem - удалить артиста или пластинку для проверки ботом\n/showitems - показать список артистов\альбомов для проверки";
    return $response;
}

// find
function findCommand(){
    $response = "Введите название исполнителя/группы 👩‍🎤 или альбома 💽\n\nНапример:\n'Кино'\n'Русское поле экспериментов'\n'metallica'\n'Sgt. Pepper's Lonely Hearts Club Band'\nи тому подобное. \n\n❗<i>Вводить желательно как можно точнее, со знаками препинания и всем остальным.</i>\n\n❗<i>Вводить желательно либо только имя исполнителя либо только название альбома.</i>";
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

            $findResults = parserPlastinkaCom($notification);
            
            // если результатов поиска больше одного сообщения, то добавляем клавиатуру
            if($findResults === false) {
                //
            }
            else if(count($findResults) > 1){
                
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
    $response = "Введите название исполнителя/группы 👩‍🎤 или <i>название</i> альбома 💽 чтобы добавить его в список уведомлений.\n\n❗<i>Вводить желательно как можно точнее, со знаками препинания и всем остальным.</i>\n\n❗<i>Вводить желательно либо только имя исполнителя либо только название альбома.</i>";
    return $response;
}

// delete item
function deleteitemCommand($chatId){
    // получаем список пластинок\артистов для проверки и возвращаем его в виде кнопок
    $notifications = getNotifications($chatId);

    $response = [];

    if(count($notifications) > 0){
        $response = ["Вот полный список всех исполнителей/групп 👩‍🎤 и альбомов 💽 для проверки. \nЧтобы удалить введите название одного из них (или перешлите сообщение боту)🗑️\n\n"];
    } else {
        $response = ["В списке для проверки пока ничего нет. Добавьте исполнителей или альбомы через команду /additem"];
    }

    foreach($notifications as $notification){
        array_push($response, $notification);
    }

    return $response;
}


// showitems
function showitemsCommand($chatId){

    $notifications = getNotifications($chatId);

    if(count($notifications) == 0) {
        $response = "В списке для проверки пока ничего нет. Добавьте исполнителей или альбомы через команду /additem";
    } else {
        $response = "Список всех исполнителей/групп 👩‍🎤 и альбомов 💽 для проверки ✔ через команду /checklps\n\n";
        foreach($notifications as $notification){
            $response .= $notification."\n";
        }
    }

    return $response;
}

// generateProductList
// делает парсинг пластинок с сайтов и генерирует готовый ответ
// параметры: searchText - текст для поиска, $site - сайт для парсинга ('plastinka', 'vinylbox'), pageToShow - страница которую нужно показать в сообщении
// showMessageHeader - показать сообщение перед списком пластинок
function generateProductList($searchText, $site, $pageToShow, $showMessageHeader){
    
    // делаем парсинг информации с сайтов, на выходе получаем массив со страницами готового к отправке текста
    // каждый индекс в массиве - страница = сообщение для Telegram
    $parseResults = null;

    if($site == 'plastinka'){
        $parseResults = parserPlastinkaCom($searchText);
    } else if($site == 'vinylbox'){
        $parseResults = parserVinylBoxRu($searchText);
    }
    
    // $parseResults = parserVinylBoxRu($searchText);
    $messageHeader = null;

    if($showMessageHeader === true){

        switch($site){
            case 'plastinka':
                $messageHeader = "plastinka.com";
                break;
            case 'vinylbox':
                $messageHeader = "vinylbox.ru";
                break;
        }
    }

    // если парсер ничего не нашёл
    if($parseResults == false){
        // $response = "По запросу не найдено ни одной пластинки. Используйте команду /find чтобы найти что-то другое 🔎";
        $response = false;
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

    $item = normalizeString($item);

    $check = writeNotificationToDatabase($chatId, $item);

    if($check === true){
        $response = "Исполнитель\альбом сохранён в список 💾\nТеперь я буду искать пластинки по этому запросу через команду /checklps";
        return $response;
    } else {
        $response = "⚠ Не удалось сохранить уведомление ⚠";
    }

}

// deleteitem - удалить аритиста или пластинку из БД
function deleteNotification($chatId, $item){

        $check = checkItemBeforeDelete($chatId, $item);

        if($check === false) {
            return "⚠ Нет такого исполнителя\альбома в списке ⚠";
        } 
        else 
        {
            $check = deleteNotificationFromDatabase($chatId, $item);

            if($check === true){
                $response = "Запрос <b>{$item}</b> удален из списка 🗑️\nБольше эта пластинка\исполнитель не будет проверяться по команде /checklps";
                return $response;
            } else {
                $response = "⚠ Не удалось удалить уведомление ⚠";
            }
        }
    }

    return $check;

?>