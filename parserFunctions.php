ou<?php
    require "vendor/autoload.php";
    use voku\helper\HtmlDomParser;

    function parserTest($search){

        $searchText = eraseSpaces($search);

        $html = HtmlDomParser::file_get_html("https://plastinka.com/lp/search?str={$searchText}&params=%7B%22styles%22:%5B%5D,%22labels%22:%5B%5D,%22countries%22:%5B%5D,%22price_from%22:0,%22price_to%22:0,%22record_year_from%22:0,%22record_year_to%22:%222022%22,%22release_year_from%22:0,%22release_year_to%22:2022,%22types%22:%5B%5D,%22sort%22:%22artist%22,%22view_type%22:%22grid%22,%22page%22:1,%22per_page%22:10%7D");
    
        $messageText = "";

        $arrayOfMessages = [];

        // найти все пластинки и добавить в сообщение
        foreach ($html->find('div.products-grid-item') as $productItem) {
            // ищем <a> ссылку на пластинку
            $aTagWithURL = $productItem->find('div.products-grid-item__artist')->find('a')[0];
            
            // получаем ссылку на пластинку
            $lpUrl = $aTagWithURL->href;
            // получаем название артиста
            $artistName = $aTagWithURL->innerText;
            // получаем название пластинки
            $lpName = $productItem->find('div.products-grid-item__title')->find('a')[0]->innerText;
            // получаем характеристики пластинки
            $lpParams = $productItem->find('div.products-grid-item__params')->find('a')[0]->innerText;
            // заменяем HTML-теги на пробелы
            $lpParams = str_replace('<', ' <', $lpParams);
            $lpParams = strip_tags($lpParams);
            $lpParams = preg_replace('/\s+/', ' ', $lpParams);
            // убираем переносы строк и пробелы
            // $lpParams = str_replace(array("\r", "\n"), ' ', $lpParams);
            // $lpParams = preg_replace('/\s+/', ' ', $lpParams);


            // получить цену на пластинку
            $lpPrice = strip_tags($productItem->find('div.products-grid-item__price')[0]->innerText);
    
            // если url не равен null, добавляем в сообщение
            if($lpUrl !== null){

                $append = "{$artistName} - {$lpName} \n<b><i>{$lpPrice}</i></b> \n<i>({$lpParams})</i> \n<a href='https://plastinka.com{$lpUrl}'><b>Перейти на сайт</b></a>\n\n";

                if(2500 - strlen($messageText) >= strlen($append)){
                    $messageText .= $append;
                    // Добавляем нового человека в файл
                    $current .= "{$lpName} - {$lpPrice}\n";
                    // Пишем содержимое обратно в файл
                    file_put_contents($file, $current);
                } else {
                    $messageText .= $append;
                    array_push($arrayOfMessages, $messageText);
                    $messageText = "";
                }
                
            }
        }

        if($messageText !== ""){
            array_push($arrayOfMessages, $messageText);
        }

        if(count($arrayOfMessages) === 0){
            return false;
        } else {
            return  $arrayOfMessages;
        }
    }
    
?>