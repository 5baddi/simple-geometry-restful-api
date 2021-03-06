/**
 * Ajax GET method adjusted to our API
 */
function get(url, callback)
{
    // load data from the url by GET method
    $.ajax({
        url: url,
        method: "GET",
        success: function(data){
            // call the callback function and pass the result
            if(data.connected === true) callback(data.result);
        }
    });
}

/*
* Get provinces data
*/
function getProvinces(region)
{
    // load provinces
    get("http://localhost/pg_api/API/?province&region=" + region, function(provinces){
        if(provinces !== null && provinces !== "undefined"){
            // init province
            $province = $("select[name=province]");
            $province.html($('<option>').text("sélectionner une province"));
            // charge data
            $.each(provinces, function(i, value){
                $province.append($('<option>').text(value.nomdecn2).attr('value', value.idedecn2));
            });
            // enable
            $province.attr("disabled", false);
        }
    });
}

/*
* Get towns data
*/
function getTowns(province)
{
    // load towns
    get("http://localhost/pg_api/API/?commune&province=" + province, function(towns){
        if(towns !== null && towns !== "undefined"){
            // init province
            $town = $("select[name=town]");
            $town.html($('<option>').text("sélectionner une commune"));
            // charge data
            $.each(towns, function(i, value){
                $town.append($('<option>').text(value.nomdecn3).attr('value', value.idedecn3));
            });
            // enable
            $town.attr("disabled", false);
        }
    });
}

/*
* Clean the Map layers
*/
function cleanMap(map)
{
    if(map === null || map === "undefined") return;
    map.eachLayer(function(layer){
        map.removeLayer(layer);
    });

    map.addLayer(googleLayer);
}

/*
* Generate Random HEX color
*/
function randomColor()
{
    return '#' + (Math.random() * 0xFFFFFF<<0).toString(16);
}

/*
* Get Geometry style
*/
function getGeoStyle(type)
{
    style = defaultStyle;
    switch(type){
        case "MultiLineString":
            style.color = "red";
            style.weight = 3;
            style.opacity = 1;
        break;
        case "MultiPolygon":
            //style.fillColor = randomColor();
            style.fillOpacity = 0.5;
        break;
    }

    return style;
}

/*
* Parse the project geometry by type
*/
function getGeoJSON(value)
{
    var geom = {
        line: value.line || null,
        multiPoints: value.multiPoints || null,
        point: value.point || null,
        poly: value.poly || null,
        zoom: value.zoomproj || null,
        center: value.center || null,
    };
    
    geoJSON = {type: null, zoom: geom.zoom, center: geom.center, geometry: null, style: {}};
    $.each(geom, function(key, value){
        if(key === "zoom" || key === "center") return;

        value = JSON.parse(value);
        if(value !== null){
            geoJSON.geometry = value;
            geoJSON.style = getGeoStyle(value.type);
        }
    });

    return geoJSON;
}

/*
* Locate geometry of one projects 
*/
function locate(id)
{
    var geoInput = $("input[data-id=" + id + "]");
    var geometry = geoInput[0].value;
    if(geometry === null || geometry === "undefined") return alert("No geometry data!");

    cleanMap(L); // Clean the map 
}

// Global vars
var googleLayer = L.tileLayer('http://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}', {attribution: '© I2S Ingenierie'});
var defaultStyle = {
    radius: 8,
    weight: 3,
    color: "#3FCFE5",
    opacity: 1,
    fillColor: "#3FCFE5",
    fillOpacity: 0.3
};

// Page loaded
$(document).ready(function(){
    // vars
    var printTitile = "Helioss";

    // init the Map
    var map = L.map("map", {
        layers: [googleLayer],
        center: [33.589886, -7.603869],
        zoom: 8
    });

    // Add Controls
    L.control.browserPrint({documentTitle: printTitile}).addTo(map); // Browser print

    // load regions
    get("http://localhost/pg_api/API/?region", function(regions){
        // charge data
        $region = $("select[name=region]");
        $.each(regions, function(i, value){
            $region.append($('<option>').text(value.nomdecn1).attr('value', value.idedecn1));
        });
    });

    // load projects years
    get("http://localhost/pg_api/API/?projet&annee", function(years){
        $years = $("select[name=year]");
        $.each(years, function(i, value){
            $years.append($('<option>').text(value.anneproj).attr('value', value.anneproj));
        });
    });
    
    // load projects programs
    get("http://localhost/pg_api/API/?programme", function(programs){
        $programs = $("select[name=program]");
        $.each(programs, function(i, value){
            $programs.append($('<option>').text(value.nom_prog).attr('value', value.idenprog));
        });
    });
    
    // load projects moas
    get("http://localhost/pg_api/API/?moa", function(moas){
        $moas = $("select[name=moa]");
        $.each(moas, function(i, value){
            $moas.append($('<option>').text(value.nom_moa).attr('value', value.iden_moa));
        });
    });
    
    // load projects secteurs
    get("http://localhost/pg_api/API/?secteur", function(sectors){
        $sectors = $("select[name=sector]");
        $.each(sectors, function(i, value){
            $sectors.append($('<option>').text(value.nom_sect).attr('value', value.idensect));
        });
    });

    // region changed
    $("select[name=region]").on("change", function(){
        cleanMap(map); // remove exists layers
        // Zoom to the selected region
        get("http://localhost/pg_api/API/?region=" + this.value, function(data){
            var geometry = JSON.parse(data[0].geometry);
            if(geometry === null || geometry === "undefined") return;
            var geo = new L.geoJSON(geometry);
            geo.setStyle(defaultStyle);
            geo.addTo(map);
            map.fitBounds(geo.getBounds());
        }); 
    });
    
    // province changed
    $("select[name=province]").on("change", function(){
        cleanMap(map); // remove exists layers
        // Zoom map to a province
        get("http://localhost/pg_api/API/?province=" + this.value, function(data){
            provinceInfo = data[0];
            var geometry = JSON.parse(provinceInfo.st_asgeojson);
            if(geometry === null || geometry === "undefined") return;
            var poly = new L.geoJSON(geometry);
            poly.setStyle(defaultStyle);
            poly.addTo(map);
            map.fitBounds(poly.getBounds());
        });

        // Get towns
        getTowns(this.value);
    });

    // Town changed
    $("select[name=town]").on("change", function(){
        cleanMap(map); // remove exists layers
        // Zoom map to a province
        get("http://localhost/pg_api/API/?commune=" + this.value, function(data){
            provinceInfo = data[0];
            var geometry = JSON.parse(provinceInfo.st_asgeojson);
            if(geometry === null || geometry === "undefined") return;
            var poly = new L.geoJSON(geometry);
            poly.setStyle(defaultStyle);
            poly.addTo(map);
            map.fitBounds(poly.getBounds());
        });
    });

    // Fetch data
    $("button[name=fetch]").on("click", function(){
        // All options
        $.ajax({
            url: "http://localhost/pg_api/API/?geo=project",
            method: "POST",
            data: $.param({
                region: $("select[name=region]")[0].value,
                province: $("select[name=province]")[0].value,
                town: $("select[name=town]")[0].value,
                sector: $("select[name=sector]")[0].value,
                moa: $("select[name=moa]")[0].value,
                program: $("select[name=program]")[0].value,
                year: $("select[name=year]")[0].value,
            }),
            success: function(data){
                var result = data.result;
                if(result !== null && result !== "undefined"){
                    if(result === false) return alert("No data!");
                    $("#result").html("");
                    cleanMap(map);
                    var layer = L.geoJSON().addTo(map);
                    $.each(result, function(i, value){
                        var geo = getGeoJSON(value);
                        var tr = $("<tr>");
                        tr.append($("<td>").text(value.descr));
                        tr.append($("<td>").text(value.province));
                        tr.append($("<td>").text(value.annee));
                        tr.append($("<td>").text(value.moa));
                        tr.append($("<td>").text(value.programme));
                        tr.append($("<td>").text(value.secteur));

                        var actionTd = $("<td>");
                        actionTd.append($("<input>").attr("type", "hidden").attr("data-id", value.id).attr("value", JSON.stringify(geo)));
                        actionTd.append($("<button onclick=\"locate(" + value.id + ")\">").text("Localisé"));
                        tr.append(actionTd);

                        layer.addData(geo.geometry); 
                        layer.bindPopup("<img src=\"http://i2singenierie.com/accueil/wp-content/themes/I2sIngenierie/assets/img/logo.png\"/><h3>Titre de test</h3><hr/><p>info: bla bla<br/>info2: test</p>");
                        layer.setStyle(geo.style);   
                        if(geo.geometry.type === "MultiLineString"){
                            var l2 = L.geoJSON(geo.geometry, {
                                style: {
                                color: "yellow",
                                weight: 2,
                                opacity: 1
                            }
                            }).addTo(map);
                        }

                        $("#result").append(tr);
                    });

                    map.fitBounds(layer.getBounds());
                }
            }
        })
    });
});