<?php

// formatSpaces
// замена пробелов на '%20'
function formatSpaces($string, $insert){
    $newString = $string = str_replace(' ', $insert, $string);

    return $newString;
}


?>