<?php

function logData($type, $title, $details = NULL, $error = NULL){
    $logInfo = "\r\n//////////////////////////////////////////////////////////////////////////////////////////\r\n\r\n";
    $logInfo .= date("[d/m/o - H:i:s]")." - ";

    switch ($type) {
        case 'error':

            $logInfo    .=  "Error Alert!\r\n\r\n\r\n" . $title ."\r\n\r\n\r\n";
            
            if ( $details != NULL){
                $logInfo    .=  "• Details:\r\n". $details.".\r\n\r\n"; 
            }

            if ( $error != NULL){
                $logInfo    .=  "• Error Thrown Info:\r\n".$error.".\r\n\r\n";
            }

            $logInfo    .=  "\r\n"."//////////////////////////////////////////////////////////////////////////////////////////";
        break;

        case 'success': 
            $logInfo    .=  "Procedure Success! \r\n\r\n". $title ."\r\n\r\n";
            if ( $details != NULL ){
                $logInfo    .=  "• Details: ".$details.".\r\n";
            }
            $logInfo    .=  "\r\n"."//////////////////////////////////////////////////////////////////////////////////////////";
        break;
    }

    file_put_contents('Log.txt',$logInfo,FILE_APPEND | LOCK_EX);
}  