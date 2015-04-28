<?php
/**
 * Retrieve all HQ images from Europeana
 */
ob_start();
//set output as plain text, for easy copying.
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors','On');

set_time_limit(0);
ini_set("allow_url_fopen", true);

$base   = 'http://busy-api.de.a9sapp.eu/v2/'; // host name of the API
$key    = 'xxx'; // api key
//+provider_aggregation_edm_isShownBy:*+OR+provider_aggregation_edm_hasView:*'
$query  = '*:*'; // search query
$sizes  = array('medium','large','extra_large'); // images sizes you want to return (small, medium, large, extra_large)
$licenses = array("*mark*", "*zero*", "*by\/*", "*by-sa\/*", "*by-nc-sa\/*", "*by-nc\/*");
$total  = 1000000; // has to be dividable by 50, this number determines how much objects you want to retrieve (media items may be more!)

// Image sizes
$imageSizes = '';
foreach ($sizes as $size) {
    $imageSizes .= '&qf=IMAGE_SIZE:' . $size;
}

// Rights
$rightsQuery = '';
foreach ($licenses as $license) {
    $rightsQuery .= '&RIGHTS:"' . $license . '"';
}
$rightsQuery = "(RIGHTS:*mark*+OR+RIGHTS:*zero*+OR+RIGHTS:*by\/*+OR+RIGHTS:*by-sa\/*+OR+RIGHTS:*by-nc-sa\/*+OR+RIGHTS:*by-nc\/*)";

echo $base . 'search.json?start=0&rows=50&query='.$query.$imageSizes.$rightsQuery.'&wskey=' . $key;
exit;
// Construct search query, we're sending out new requests per 50 objects
for ($x=0; $x<($total+1); $x+=50) {
    $result = file_get_contents($base . 'search.json?start='.$x.'&rows=50&query='.$query.$imageSizes.$rightsQuery.'&wskey=' . $key);
    // Iterate through results
    $results = json_decode($result, true);
    foreach ($results['items'] as $item) {

        // For each object, we have to do a record call to retrieve the direct link to the media
        $record = json_decode(file_get_contents($base . 'record/'.$item['id'].'.json?wskey='.$key), true);
        // First, find each webResource that has a mimeType, which means its either the edmIsShownBy or hasView, and resolves to a media object
        if (isset($record['object']['aggregations'][0]['webResources'])) {
            foreach ($record['object']['aggregations'][0]['webResources'] as $webResource) {
                if (isset($webResource['ebucoreHasMimeType'])) {

                    // This webresource is a media from the object..
					
                    // ID [europeanaAggregation][about]
                    echo $record['object']['about']."\t";
                    // license
                    echo $record['object']['aggregations'][0]['edmRights']['def'][0]."\t";
					// Echo the URL to it
                    echo $webResource['about']."\n";
                }
            }
            ob_flush();
        }
    }
}
ob_end_flush(); 
