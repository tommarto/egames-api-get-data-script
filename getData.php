<?php

require('functions.php');

if( $json    =  file_get_contents('config.json') ) { //Check if config data can be readed.

    $configs = json_decode($json, TRUE);

    if ($configs != NULL){

        foreach($configs as $key => $value){
            define($key, $value);
        }

        try{
            $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME,DB_USER,DB_PASS);
        }catch(Exception $e){
            // TODO: log pdo exception;
            logData('error','Error al instanciar PDO', 'Al instanciar el objeto PDO el mismo a arrojado una excepcion. Puede que haya un dato de configuracion erroneo.', $e);
            die();
        }

        $curlHandler       =   curl_init(API_URL);
        $curlConfigArray   =  curl_setopt_array(  $curlHandler, 
                                                array(
                                                    //CURLOPT_HEADER          => TRUE,
                                                    CURLOPT_RETURNTRANSFER  => TRUE
                                                ));

        if ($data = curl_exec($curlHandler)){

            if(curl_error($curlHandler) == ''){

                $statusCode = curl_getinfo($curlHandler, CURLINFO_HTTP_CODE);

                if ( $statusCode == 200 ){

                    // Json Data Decode: 
                    $data       =   json_decode($data, TRUE);
                    $matches    =   $data['scores']['match'];
                    $updated    =   $data['scores']['@updated'];
                    $recorded   =  date('Y-m-d H:i:s'); 
                    // Data Validation: 

                    foreach($matches as $match => $matchData){        

                        foreach($matchData as $key => $value){
                            
                            if(!is_array($value)){

                                $matchData[$key]  =   str_replace( '((' , '%(' , $value);
                                $matchData[$key]  =   str_replace( '('  , '%(' , $value); 
                                $matchData[$key]  =   str_replace( '))' , '%)' , $value);
                                $matchData[$key]  =   str_replace( ')'  , '%)' , $value);
                                $matchData[$key]  =   str_replace( '%(' , '((' , $value);
                                $matchData[$key]  =   str_replace( '%)' , '))' , $value);
                                $matchData[$key]  =   addslashes($value);   

                                if($value == '' || $value == ' '){
                                    $matches[$match][$key] =   NULL;         
                                }

                            }
                        }
                    }

                    $query = "INSERT INTO matches (id, status, league_id, league, rounds, type, timer, date, time, localteam_name, localteam_id, localteam_score, awayteam_name, awayteam_id, awayteam_score, updated, recorded_server_time) VALUES";

                    foreach ($matches as $match => $data) { 

                        $localscore =   ($data['localteam']['@score'] != '') ? $data['localteam']['@score'] : 'NULL';
                        $awayscore  =   ($data['awayteam']['@score'] != '') ? $data['awayteam']['@score'] : 'NULL';

                        if($data['@id'] == end($matches)['@id']){
                            $query .= "(".$data['@id'].",'".$data['@status']."',". $data['@league_id'].",'". $data['@league'] ."','" . $data['@round'] . "' ,'" . $data['@type'] . "','". $data['@timer']."','". $data['@date']."','". $data['@time']. "','". $data['localteam']['@name']."',". $data['localteam']['@id']."," . $localscore . ",'" . $data['awayteam']['@name'] . "'," . $data['awayteam']['@id'] . "," . $awayscore . ",'".$updated."','".$recorded."')";
                        }else{
                            $query .= "(".$data['@id'].",'".$data['@status']."',". $data['@league_id'].",'". $data['@league'] ."','" . $data['@round'] . "' ,'" . $data['@type'] . "','". $data['@timer']."','". $data['@date']."','". $data['@time']. "','". $data['localteam']['@name']."',". $data['localteam']['@id']."," . $localscore . ",'" . $data['awayteam']['@name'] . "'," . $data['awayteam']['@id'] . "," . $awayscore . ",'".$updated."','".$recorded."'),";
                        }
                    }

                    $query .= " ON DUPLICATE KEY UPDATE id = VALUES(id), status = VALUES(status), league_id = VALUES(league_id), league = VALUES(league), rounds = VALUES(rounds), type = VALUES(type), timer = VALUES(timer), date = VALUES(date), time = VALUES(time), localteam_name = VALUES(localteam_name), localteam_id = VALUES(localteam_id), localteam_score = VALUES(localteam_score), awayteam_name = VALUES(awayteam_name), awayteam_id = VALUES(awayteam_id), awayteam_score = VALUES(awayteam_score), updated = VALUES(updated), recorded_server_time = VALUES(recorded_server_time)";
                    


                    $stmt = $pdo->prepare($query);
                    $execAns = $stmt->execute();

                    if($execAns){
                        // TODO: Log Update - Update de DB realizado correctamente a las  H.
                        logData('success', 'El script ha terminado su ejecucion con exito', "\r\n-Se han descargado los datos desde la API correctamente. \r\n-Se almacenaron los datos en la BBDD correctamente. \r\n-Los registros con un id ya existente fueron actualizados. \r\n-En esta operacion se han visto afectados ".$stmt->rowCount()." registros");
                    }else{
                        
                        $errorInfo = "Error Code: ".$stmt->errorInfo()[0].". \r\nError Info: ".$stmt->errorInfo()[2];
                        
                        logData('error', 'Error al ejecutar el query sql', 'Al ejecutar el query sql mediante $stmt->execute(), ha devuelto false.', $errorInfo);
                    }

                }else{
                    logData('error', 'Rquest Status Code Error', 'El status code recibido es distinto de 200', 'Status Code Recibido: '. $statusCode );
                }
            }else{
                logData('error', 'Ha habido un error de cURL', 'Al ejecutar la peticion curl y chequear si hay errores mediante curl_error(ch) se ha detectado un error', curl_error($curlHandler));
            }   
        }else{
            logData('error', 'Error al ejecutar request cURL', 'Al ejecutar la peticion cURL del curlHandler que tiene seteada la URL de la API, curl_exec devuelve false');
        }
    }else{
        logData('error', 'Error al parsear Config.json', 'Al parsear el archivo config.json con json_decode() ha devuelto NULL');
    }
}else{
    logData('error', 'Error al abrir Config.json', 'El error se produce al intentar hacer un file_get_contents() del archivo config.json');
}

// Curl Close: 
curl_close($curlHandler);