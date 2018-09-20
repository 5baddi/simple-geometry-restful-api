<?php

error_reporting(0);

// Return result as json
function  response($query)
{
    $result = !is_null($query) ? pg_fetch_all($query) : null;
    pg_close($db); // Kill the connection

    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json");

    die(
        json_encode([
            'connected' =>  $result ? true : false,
            'result'    =>  $result
        ])
    );
}

// Connect to pg database
$db = pg_connect("host=127.0.0.1 dbname=helioss_gis user=postgres password=postgres");
if(!$db){
    header("HTTP/1.0 400 BAD REQUEST");
    die(json_encode(['connected' => false, 'error' => 'db not connected'])); // Die if conncetion fail
}

// Params
$project = isset($_GET['projet']) ? $_GET['projet'] : false;
$year = isset($_GET['annee']) ? $_GET['annee'] : false;
$region = isset($_GET['region']) ? $_GET['region'] : false;
$province = isset($_GET['province']) ? $_GET['province'] : false;
$commune = isset($_GET['commune']) ? $_GET['commune'] : false;
$program = isset($_GET['programme']) ? $_GET['programme'] : false;
$moa = isset($_GET['moa']) ? $_GET['moa'] : false;
$sector = isset($_GET['secteur']) ? $_GET['secteur'] : false;

$query = null;

if(sizeof($_GET) === 0){
    $query = pg_query($db, "SELECT ST_AsGeoJSON(lineproj, 5) AS geojson, * FROM projet");
}else{
    if($project !== false){
        if(is_numeric($project)) $query = pg_query($db, "SELECT ST_AsGeoJSON(lineproj, 5), ST_AsGeoJSON(polyproj, 5), ST_AsGeoJSON(poinproj, 5), ST_AsGeoJSON(mulpoipr, 5), ST_AsGeoJSON(centproj, 5), * FROM projet WHERE idenproj = $project");
        else if(is_numeric($year)) $query = pg_query($db, "SELECT ST_AsGeoJSON(lineproj, 5), ST_AsGeoJSON(polyproj, 5), ST_AsGeoJSON(poinproj, 5), ST_AsGeoJSON(mulpoipr, 5), ST_AsGeoJSON(centproj, 5), * FROM projet WHERE anneproj = $year");
        else if(!is_numeric($project) && !is_numeric($year)) $query = pg_query($db, "SELECT DISTINCT anneproj FROM projet WHERE anneproj IS NOT NULL ORDER BY anneproj DESC");
    }

    if($program !== false){
        if(is_numeric($program)) $query = pg_query($db, "SELECT * FROM projet_programme_r WHERE idenprog = $program");
        else $query = pg_query($db, "SELECT nom_prog, idenprog FROM projet_programme_r ORDER BY nom_prog DESC");
    }

    if($moa !== false){
        if(is_numeric($moa)) $query = pg_query($db, "SELECT * FROM projet_moa_r WHERE iden_moa = $moa");
        else $query = pg_query($db, "SELECT nom_moa, iden_moa FROM projet_moa_r ORDER BY nom_moa DESC");
    }
    
    if($sector !== false){
        if(is_numeric($sector)) $query = pg_query($db, "SELECT * FROM projet_secteur_r WHERE idensect = $sector");
        else $query = pg_query($db, "SELECT nom_sect, idensect FROM projet_secteur_r ORDER BY nom_sect DESC");
    }

    if($project !== false){
        if(is_numeric($project)) $query = pg_query($db, "SELECT ST_AsGeoJSON(lineproj, 5), ST_AsGeoJSON(polyproj, 5), ST_AsGeoJSON(poinproj, 5), ST_AsGeoJSON(mulpoipr, 5), ST_AsGeoJSON(centproj, 5), * FROM projet WHERE idenproj = $project");
        else if(is_numeric($year)) $query = pg_query($db, "SELECT ST_AsGeoJSON(lineproj, 5), ST_AsGeoJSON(polyproj, 5), ST_AsGeoJSON(poinproj, 5), ST_AsGeoJSON(mulpoipr, 5), ST_AsGeoJSON(centproj, 5), * FROM projet WHERE anneproj = $year");
        else if(!is_numeric($project) && !is_numeric($year)) $query = pg_query($db, "SELECT DISTINCT anneproj FROM projet WHERE anneproj IS NOT NULL ORDER BY anneproj DESC");
    }

    if($region !== false){
        if(is_numeric($region) && $province === false) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n1 WHERE idedecn1 = $region");
        else if($province !== false && !is_numeric($province)) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n2 WHERE idedecn1 = $region");
        else $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n1");
    }

    if($province !== false){
        if(is_numeric($province) && $commune === false) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n2 WHERE idedecn2 = $province");
        else if($commune !== false && !is_numeric($commune)) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n3 WHERE idedecn2 = $province");
        else if($region === false) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n2");
    }

    if($commune !== false){
        if(is_numeric($commune) && $province === false) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n3 WHERE idedecn3 = $commune");
        else if($province !== false) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5), * FROM decoupage_n3 WHERE idedecn2 = $province");
    } 
}

return response($query);