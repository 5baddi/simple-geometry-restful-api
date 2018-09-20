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
$geo = isset($_GET['geo']) ? $_GET['geo'] : false;
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
        if(is_numeric($region) && $province === false) $query = pg_query($db, "SELECT ST_AsGeoJSON(geom, 5) geometry, * FROM decoupage_n1 WHERE idedecn1 = $region");
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

    if($geo !== false && sizeof($_POST) > 0){
        $town = is_numeric($_POST['town']) ? (int)$_POST['town'] : "-1 or -1=-1";
        $province = is_numeric($_POST['province']) ? (int)$_POST['province'] : "-1 or -1=-1";
        $region = is_numeric($_POST['region']) ? (int)$_POST['region'] : "-1 or -1=-1";
        $sector = is_numeric($_POST['sector']) ? (int)$_POST['sector'] : "-1 or -1=-1";
        $moa = is_numeric($_POST['moa']) ? (int)$_POST['moa'] : "-1 or -1=-1";
        $program = is_numeric($_POST['program']) ? (int)$_POST['program'] : "-1 or -1=-1";
        $year = is_numeric($_POST['year']) ? (int)$_POST['year'] : "-1 or -1=-1";

        $sql = "select ST_AsGeoJSON(prj.centproj, 5) center, prj.zoomproj, ST_AsGeoJSON(prj.lineproj, 5) line, ST_AsGeoJSON(prj.polyproj, 5) poly, ST_AsGeoJSON(prj.mulpoipr, 5) multipoints, ST_AsGeoJSON(prj.poinproj, 5) point, prj.idenproj ID, prj.descproj DESCR, n2.nomdecn2 PROVINCE,  prj.anneproj ANNEE,  
        moar.nom_moa MOA, progr.nom_prog PROGRAMME, sect.nom_sect  SECTEUR
        from 
        projet prj, 
        projet_programme prog,  -- table intermédiare entre projet et projet_programme-r
        projet_programme_r progr,  --référentiel des programmes
        projet_province_v prj_prv, -- une vue pour identifier la ou les provinces d'un projet
        decoupage_n2 n2,    -- référentiel des provinces
        projet_moa_r moar,    --référentiel des MOA
        projet_secteur_r sect    -- référentiel des secteurs
        where 
        prj.idenproj=prog.idenproj
        and prog.idenprog = progr.idenprog   
        and prj.idenproj = prj_prv.idenproj
        and prj_prv.idenprov = n2.idedecn2
        and prj.iden_moa = moar.iden_moa
        and prj.idensect = sect.idensect
        and (prj.iden_moa = $moa)     --- pour ignorer la condition MO ==> prj.iden_mo=-1
        and (prj.idensect = $sector)     --- pour ignorer la condition secteur ==> prj.idensect=-1
        and (prog.idenprog = $program)    --- pour ignorer la condition prog ==> prj.idenprog=-1
        and (prj_prv.idenprov = $province)  --- pour ignorer la condition province ==> prj.idenprov=-1
        and (prj.idenproj in (select comm.idenproj from projet_decoupage_n3 comm 
        where comm.idedecn3 = $town))   --- pour ignorer la condition commune ==> prj.idedecn3 = $town 
        ";

        $query = pg_query($db, $sql);
    }
}

return response($query);