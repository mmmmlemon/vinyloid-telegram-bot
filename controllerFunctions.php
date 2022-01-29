<?php

require_once "vendor/autoload.php";
require __DIR__ . '/dbFunctions.php';
require __DIR__ . '/parserFunctions.php';

// start
function startCommand(){
    $response = "Бот начал работу 🤖 \nДля справки по работе с ботом выполните команду /help";
    return $response;
}
// help
function helpCommand(){
    $response = "Этот бот предназначен для поиска виниловых пластинок в Интернете. Бот находится в стадии разработки, сейчас реализован поиск по сайту plastinka.com \n\nСписок доступных команд:
    \n/start - начать работу с ботом\n/help - справка\n/find - поиск пластинки";
    return $response;
}

// find
function findCommand(){
    $response = "Введите название исполнителя/группы 👩‍🎤 или альбома 💽\n\nНапример:\n'Кино'\n'Русское поле экспериментов'\n'metallica'\n'sgt peppers lonely hearts club band'\nи тому подобное. \n\nВводить желательно без знаков препинания. Работают как строчные так и прописные буквы.";
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

?>