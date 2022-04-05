<?php
require 'vendor/autoload.php';

use LearnositySdk\Request\DataApi;

/**
* Data API documentation:
* https://reference.learnosity.com/data-api/endpoints
**/

/***** Notes on an issue I ran into ****/

// After entering my request JSON inside of the $request varibale in downloadSessions (see below)
// I kept getting an error which appears to be originating in the LearnositySDK (although I might be wrong about this)
//  you can see the full error with the stack trace in "errormessage.txt" enclosed here


// I made WAS able to successfully make the request to the enpoint "/sessions/responses" 
// API request DATA API demos at https://demos.learnosity.com/analytics/data/index.php with the
// 4 session_ids provided in the test
// I have copied the response JSON that came back to a file enclosed here
// However, since I cannot see what the php array looks like that will come back from makeDataAPICall()
// called by downloadSessions, it's difficult for me to proceed and approach writing the processSessions function logic

// I have also commented extensively below inside of both of these functions to document my understaning of what they are doing

/**** *****/




function processSessions() 
{
    $sessions = downloadSessions();

    print_r($sessions);

    // this part was difficult for me to attempt without being able to really 
    // see what the php array that comes back from makeDataCall() looks like
    // (see explanation above)

    ////
    // To do: Add code to process the sessions and save to CSV,
    // feel free to add more functions if needed.
    //

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
