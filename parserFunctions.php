<?php
    require "vendor/autoload.php";
    use PHPHtmlParser\Dom;

    function parserTest(){

        $page = file_get_contents('https://mistermisteroo.ru/');

        $dom = new Dom;
        $dom->loadStr($page);
        $a = $dom->find('h1[class=title post_title]')[0];
        echo $a->text; 
    }
    
?>