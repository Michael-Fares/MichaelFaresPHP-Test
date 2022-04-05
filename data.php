<?php
require 'vendor/autoload.php';

use LearnositySdk\Request\DataApi;

/**
* Data API documentation:
* https://reference.learnosity.com/data-api/endpoints
**/



function processSessions() 
{
    $sessions = downloadSessions();

    // initilize the table as a two dimensional array where first nested array is table head
    $table = array (
        array ("Session ID", "Question Reference", "Question Type", "Score", "Max Score")
      );
      
      // loop over the sessions data and drill down to the 3nd level (session => list of questions => each question data)
      // 3 loops, 2 nested
      foreach($sessions as $questionsList) {
          //access the session id for each list fo questions and save to a variable called $session_id 
            $session_id = $questionsList["session_id"]; 
        foreach($questionsList as $question) {
            // drill down by ignoring items in the outer array that are not themselves arrays
           if(is_array($question)) {
                // drill down by ignoring items in the outer array that are not themselves arrays
                foreach($question as $data) {
                    if(is_array($data)) {
                        // push the requested data items to be persisted into the table as a "row" in the form of an array
                            array_push($table,
                            array(
                            // get session id from first level
                            $session_id,
                            // get data info for each question underneath that session id from
                            // the question data array (3rd level)
                            $data["question_reference"],
                            $data["question_type"],
                            $data["score"],
                            $data["max_score"] 
                            )
                        );
                    }
                }
           }
        }
      }
  
      // filter the table so that only complete rows are shown
      // not the session reapeating where the rest of the row is empty
      $table = array_filter(
        $table,
        function ($row) {
          // if the entry after session id is empty, it is a "redundant" row, so exclude it from the filter
          return !empty($row[1]);
        }
      );

      // re-index the filterd table
      $table = array_values($table);
      // open a wrtiable file called results.csv
      $fp = fopen('results.csv', 'w');
      // write the table to the file as a csv table
      foreach ($table as $fields) {
          fputcsv($fp, $fields);
      }
      // close the file
      fclose($fp);

}

function downloadSessions():array
{
    // decode string JSON provided as the reuqest and convert to php object before making the request
    // session ids for which data to be retrieved are provided as an array of string as per the docs
    // terminal error message verified that an array is being passed in
    // but I keep getting the error message enclosed here in "errormessage.txt"
    $request = json_decode(<<<EOS
    {
        "session_id": [
            "a9c92dc1-725c-456a-acc9-cdd32fd9f81b",
            "ecc0bdb2-2fc9-4489-b2d6-b818a8b0ebc0",
            "ee5ed0c3-f590-40d0-8480-8037914738ba",
            "93af036e-2aad-425e-a997-fa3827cb4c30"
         ],
         "status": [
            "completed"
         ],
         "sort": "asc",
         "mintime": "2022-03-03",
         "limit": 5
    }
    EOS
    , true);
    return makeDataAPICall($request, '/sessions/responses');
}

function makeDataAPICall(array $request, string $endpoint, string $consumer_key = 'yis0TYCu7U9V4o7M', $consumer_secret = '74c5fd430cf1242a527f6223aebd42d30464be22', $version = 'v2022.1.LTS', $action = 'get'):array
{

    $url = "https://data.learnosity.com/{$version}{$endpoint}";
    
    $security =[
        'consumer_key'=>$consumer_key,
        'domain' => 'localhost'
    ];
    // initliazes expected return value to an array
    $ret = [];
    $dataApi = new DataApi();
    $dataApi->requestRecursive(
        $url,
        $security,
        $consumer_secret,
        $request,
        $action,
        function ($return) use (&$ret, $endpoint) {
            // if the actual return is a string, then
            // reassign $ret to an empty string
            // and concat the actual returned string to it
            if (is_string($return)) {
                $ret = "";
                $ret .= $return;
            // merge the data returned into the $ret array
            } else {
                $ret = array_merge($ret, $return['data']);
            }
            echo PHP_EOL.$endpoint.PHP_EOL;
            // print the metadata for returned data as encoded JSON to the console
            // showing the request was successful
            print_r(json_encode($return['meta']));
        }
    );
    // return the data for processing
    return $ret;
}

processSessions();
