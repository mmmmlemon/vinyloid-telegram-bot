<?php

// formatSpaces
// замена пробелов на '%20'
function formatSpaces($string){
    $newString = $string = str_replace(' ', '%20', $string);

    return $newString;
}


?>