<?php
    require "vendor/autoload.php";
    use voku\helper\HtmlDomParser;
    
    function parserPlastinkaCom($search){

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
            $lpParams = str_replace('<', ', <', $lpParams);
            $lpParams = strip_tags($lpParams);
            $lpParams = preg_replace('/\s+/', ' ', $lpParams);
            $lpParamsExplode = explode(", ", $lpParams);
            $lpCountryAndLabel = $lpParamsExplode[0];
            $lpType = $lpParamsExplode[count($lpParamsExplode) - 2];
            $lpCondition = $lpParamsExplode[count($lpParamsExplode) - 1];
            // убираем переносы строк и пробелы
            // $lpParams = str_replace(array("\r", "\n"), ' ', $lpParams);
            // $lpParams = preg_replace('/\s+/', ' ', $lpParams);

            $explodeStr = "";

            foreach($lpParamsExplode as $explode){
                $explodeStr .= $explode . " ";
            }

            // получить цену на пластинку
            $lpPrice = null;

            // проверяем указана ли у пластинки старая цена
            $lpPriceOld = $productItem->find('span.prev-price');

            // если у пластинки указана старая цена, значит действует скидка, указываем обе цены
            if(count($lpPriceOld) > 0){
                $lpPrice = strip_tags($productItem->find('div.products-grid-item__price')[0]->innerText);
                $lpPriceOld = "<s>".$lpPriceOld[0]->innerText."</s>";
                $dotPos = strpos($lpPrice, ".")+1;
                $lpPriceNew = substr($lpPrice, $dotPos, strlen($lpPrice) - $dotPos);

                $lpPrice = $lpPriceNew . " " . $lpPriceOld;
       
            } else {
                // если нет, то просто указываем цену
                $lpPrice = $productItem->find('div.products-grid-item__price')[0]->innerText;
            }
    
            // если url не равен null, добавляем в сообщение
            if($lpUrl !== null){

                $append = "{$artistName} - {$lpName} \n<i>{$lpCountryAndLabel}</i>\n<b><i>{$lpPrice}</i></b> <i>({$lpType} {$lpCondition})</i>\n<a href='https://plastinka.com{$lpUrl}'><b>Перейти на сайт</b></a>\n\n";
                // $append = $lpPrice;

                if(2500 - strlen($messageText) >= strlen($append)){
                    $messageText .= $append;
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
            return $arrayOfMessages;
        }
    }

    function parserVinylBoxRu($search){
        $searchText = strtolower($search);
        $searchText = str_replace("the ", "", $searchText);
        $searchText = eraseSpaces($searchText);

        $html = HtmlDomParser::file_get_html("http://www.vinylbox.ru/search/result?setsearchdata=1&category_id=0&add_desc_in_search=1&search={$searchText}");

        $messageText = "";

        $arrayOfMessages = [];

        // найти все пластинки и добавить в сообщение
        $table = $html->find('table.product');

        foreach($table as $productItem){
            // получаем url пластинки
            $productURL = $productItem->find('a')[0]->href;
            $productURL = "www.vinylbox.ru".$productURL;

            // получаем имя исполнителя
            $artistName = $productItem->find('div.name')->find('a')[0]->innerText;
            $artistName = preg_replace('/\s+/', ' ', $artistName);
            // получаем название альбома
            $albumName = $productItem->find('div.description')[0]->innerText;
            $albumName = preg_replace('/\s+/', ' ',  $albumName);
            // получаем цену на пластинку
            $lpPrice = $productItem->find('div.jshop_price')[0]->innerText;
            $lpPrice = preg_replace('/\s+/', '',  $lpPrice);
            $lpPrice = str_replace(".00", " ", $lpPrice);

            // if(strlen($albumCondition) == 2 || strlen($albumCondition) == 3){
            //     $albumCondition = "{$albumCondition}/{$albumCondition}";
            // }

            $append = "";

            if($productURL != null && $artistName != "Книга"){

                // для того чтобы получить лейбл, состояние пластинки и другую информацию нужно парсить страницу товара
                $htmlProductPage = HtmlDomParser::file_get_html("http://".$productURL);

                $extraFieldsDiv = $htmlProductPage->find('div.extra_fields');
                $extraFields = $extraFieldsDiv->find('div');

                $albumYear = substr($extraFields[4]->find('a')[0]->innerText,2,2);
                $albumLabel = $extraFields[3]->find('a')[0]->innerText;
                $albumCountry = $extraFields[5]->find('a')[0]->innerText;
                $albumType = $extraFields[6]->find('a')[0]->innerText;
                $albumCondition = $extraFields[8]->find('a')[0]->innerText;

                $append = "{$artistName} - {$albumName} '{$albumYear} \n<i>{$albumCountry} / {$albumLabel}</i>\n<b><i>{$lpPrice}</i></b> <i>({$albumType}, {$albumCondition})</i>\n<a href='{$productURL}'><b>Перейти на сайт</b></a>\n\n";
                
            }

            if(2500 - strlen($messageText) >= strlen($append)){
                $messageText .= $append;
            } else {
                $messageText .= $append;
                array_push($arrayOfMessages, $messageText);
                $messageText = "";
            }  
        }

        if($messageText !== ""){
            array_push($arrayOfMessages, $messageText);
        }

        if(count($arrayOfMessages) === 0){
            return false;
        } else {
            return $arrayOfMessages;
        }

    }
    
?>