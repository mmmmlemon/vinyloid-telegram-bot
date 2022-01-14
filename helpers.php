<?php

function eraseSpaces($string){
    $newString = $string = str_replace(' ', '%20', $string);

    return $newString;
}

?>