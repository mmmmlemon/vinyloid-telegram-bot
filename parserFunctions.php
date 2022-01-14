<?php
    require "vendor/autoload.php";
    use PHPHtmlParser\Dom;

    function parserTest($search){

        $searchText = eraseSpaces($search);

        $dom = new Dom;

        $page = file_get_contents("https://plastinka.com/lp/search?str={$searchText}&params=%7B%22styles%22:%5B%5D,%22labels%22:%5B%5D,%22countries%22:%5B%5D,%22price_from%22:0,%22price_to%22:0,%22record_year_from%22:0,%22record_year_to%22:2022,%22release_year_from%22:0,%22release_year_to%22:2022,%22types%22:%5B%5D,%22sort%22:%22artist%22,%22view_type%22:%22grid%22,%22page%22:1,%22per_page%22:10%7D");

        $dom = new Dom;
        $dom->loadStr($page);
    
        $a = $dom->find('div[class=products-grid-item__artist]');
    
        return $a;

      
    }
    
?>