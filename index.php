<?php

error_reporting(0);

// Connect to pg database
$db = pg_connect("host=127.0.0.1 dbname=HELIOSS user=postgres password=postgres");
if(!$db){
    header("HTTP/1.0 400 BAD REQUEST");
    die(json_encode(['connected' => false, 'error' => 'db not connected'])); // Die if conncetion fail
}


$query = pg_query($db, "SELECT ST_AsGeoJSON(lineproj, 5) AS geojson,* FROM projet");
$result = pg_fetch_all($query);

pg_close($db); // Kill the connection

// Return result as json
die(
    json_encode([
        'connected' =>  true,
        'result'    =>  $result
    ])
);