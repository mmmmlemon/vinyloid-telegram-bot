<?php

function eraseSpaces($string){
    $newString = $string = str_replace(' ', '%20', $string);

    return $newString;
}

function normalizeString($string){

    $string = eraseSpaces($string);

    $string = strtolower($string);

    return $string;

}

?>