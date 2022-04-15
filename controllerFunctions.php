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
    $response = "Бот запущен и готов искать для вас пластинки 🤖 \n\nДля справки по работе с ботом выполните команду /help";
    return $response;
}
// help
function helpCommand(){
    $response = "Этот бот предназначен для поиска виниловых пластинок в Интернете. Бот находится в стадии разработки, сейчас реализован поиск по сайтам plastinka.com и vinylbox.ru. \n\nВы можете искать пластинки по имени исполнителя либо по названию альбома. Поиск можно производить вручную, либо создать список из исполнителей и альбомов которые вы бы хотели найти и проверять все сразу одной командой. \n\nСписок доступных команд:
    \n/start - начать работу с ботом\n/help - список команд и справка по работе с ботом\n\n/find - поиск пластинок (ручной)\n\n/findlist - поиск пластинок из списка для поиска\n/addlist - добавить исполнителя или альбом в список для поиска\n/deletelist - удалить исполнителя или альбом в список для поиска\n/showlist - показать список исполнителей\альбомов для поиска";
    return $response;
}

// find
function findCommand(){
    $response = "Введите имя исполнителя 👩‍🎤 или альбома 💽 и я найду пластинки.\n\nНапример, <i>The Beatles</i>, <i>Гражданская оборона</i>, <i>Sgt. Pepper's Lonely Hearts Club Band</i>, <i>Русское поле экспериментов</i> и тому подобное. \n\n❗<i>Для лучших результатов используйте <b>либо</b> имя исполнителя <b>либо</b> альбома. Не будет лишним писать как можно точнее, со всеми знаками препинания и прочим.</i>";
    return $response;
}

// additem
function addlistCommand(){
    $response = "Введите название <b><i>одного</i></b> исполнителя 👩‍🎤 или альбома 💽 чтобы добавить его в список для поиска.\n\n❗<i>Вводите <b>либо</b> название исполнителя <b>либо</b> название альбома, желательно как  можно точнее (со знаками препинания и т.п).</i>";
    return $response;
}

// delete item
function deletelistCommand($chatId){
    // получаем список пластинок\артистов для проверки и возвращаем его в виде кнопок
    $notifications = getList($chatId);

    $response = [];

    if(count($notifications) > 0){
        $response = ["Вот полный список всех исполнителей 👩‍🎤 и альбомов 💽 для поиска. \n"];
    } else {
        $response = ["В списке для проверки пока ничего нет. Добавьте исполнителей или альбомы через команду /addlist"];
    }

    foreach($notifications as $notification){
        array_push($response, $notification);
    }

    if(count($notifications) > 0){
        array_push($response, "Чтобы удалить 🗑️ введите название <b><i>одного</i></b> из них (или перешлите сообщение c названием боту)\n\n");
    }

    return $response;
}


// showitems
function showlistCommand($chatId){

    $notifications = getList($chatId);

    if(count($notifications) == 0) {
        $response = "В списке для проверки пока ничего нет. Добавьте исполнителей или альбомы через команду /addlist";
    } else {
        $response = "Список всех исполнителей 👩‍🎤 и альбомов 💽 для поиска через команду /findlist\n\n";
        foreach($notifications as $notification){
            $response .= $notification."\n";
        }
        $response .="\n\nДобавить новый пункт в список - /addlist\nУдалить пункт из списка - /deletelist";
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
                                            'callback_data' => "{$i}[{$searchText}]({$site})"]);
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
function addItemToList($chatId, $item){

    $item = normalizeString($item);

    $check = addListItemToDatabase($chatId, $item);

    if($check === true){
        $response = "Исполнитель\альбом сохранён в список 💾\nТеперь я буду искать пластинки по этому запросу через команду /findlist";
        return $response;
    } else {
        $response = "⚠ Не удалось сохранить уведомление ⚠";
    }

}

// deleteitem - удалить аритиста или пластинку из БД
function deleteItemFromList($chatId, $item){

        $check = checkListItemBeforeDelete($chatId, $item);

        if($check === false) {
            return "⚠ Нет такого исполнителя\альбома в списке ⚠";
        } 
        else 
        {
            $check = deleteListItemFromDatabase($chatId, $item);

            if($check === true){
                $response = "Запрос <b>{$item}</b> удален из списка 🗑️\nБольше этот исполнитель\альбом не будет проверяться по команде /findlist";
                return $response;
            } else {
                $response = "⚠ Не удалось удалить уведомление ⚠";
            }
        }
    }

    return $check;

?>