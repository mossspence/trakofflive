"use strict";
var browserInfo = (function(){
    function browserInfo()
    {    
        this.areWeMobile = function()
        {
            var myMobileThreshold = 600;
            return ($(window).width() < myMobileThreshold);
        };
    }
    return new browserInfo();
}());


var songsShowcase = (function(){
    function songsShowcase()
    {    
        this.areWeMobile = function()
        {
            var myMobileThreshold = 600;
            return ($(window).width() < myMobileThreshold);
        };
    }
    return new songsShowcase();
}());

function makeHeaderFooter()
{
    // first header item is for images
    // last header item is for Add button
    if(window.browserInfo.areWeMobile())
    {
        var headerArray = ['', 'Title', 'Time', 'Key', 'BPM', ''];
    }else
     {
         var headerArray = ['', 'Title', 'Artist', 'Album', 'Time', 'Key', 'BPM', ''];
     }
    
    var row = document.createElement('tr');
    var td;
    var text;
    for(var i=0; i < headerArray.length; i++)
    {
        td = document.createElement('th');
        text = document.createTextNode(headerArray[i]);
        td.appendChild(text);
        row.appendChild(td);
    }
    return row;
}
/*
 * we've got dinner guests arriving shortly
 * @returns table element
 */
function setTheTable()
{
    var table = document.createElement('table');
    //table.classList.add("table", "table-hover");
    $(table).addClass("table", "table-hover");
    
    if(!window.browserInfo.areWeMobile())
    {
         //table.classList.add("table-condensed");
         $(table).addClass("table-condensed");
    }
    return table;
}
/*
 * expects json object of json objects
 */
function outputTable(jsonData)
{
    // clear local storage of my prefix'ed data
    // for testing 
    // clearSongs();
    
    //class list is problematic for gingerbread browser on android
    // I might have to do
    // .addClass
    // and therefore use more jQuery
    
    jsonData = jsonData.songs;
    var songData;
    var imagesize = 35;
    var fragment = document.createDocumentFragment();
    var searchArray = [];
    var searchAlbumArray = [];
    var albumImagesArray = [];
    
    fragment.appendChild(makeHeaderFooter());
    
    for (var i = 0; i < jsonData.length; i++)
    {
        //songData = JSON.parse(jsonData[i]);
        songData = jsonData[i];
        
        // add to local storage
        // this is just for testing
        // addSong(JSON.stringify(songData));
        
        var tr = document.createElement('tr');   
        
        var imagebox = document.createElement('img');
        
        //var image = songData.coverImage;
        imagebox.setAttribute("id", songData.ID);
        
        // setup cover album class
        $(imagebox).addClass('album-' + songData.album);
        
        //if ( albumImagesArray.indexOf(songData.album) > -1 )
        //if ( jQuery.inArray( songData.album, albumImagesArray ) > -1 )
        {
            // if not in array
            albumImagesArray.push(songData.album);
            searchAlbumArray[songData.album] = songData.artist + ' ' + songData.title;
            //console.log('Added search term for: ' + songData.album);
        }

        imagebox.setAttribute('src', songData.coverImage);
        
        // make array to check iTunes cover images
        // if album is the same don't search again
        if('/imglib/covers/default.png' === songData.coverImage)
        {
            searchArray[songData.ID] = songData.artist + ' ' + songData.title;
            //console.log('adding iTunes search for: ' + songData.ID);
        }
        
        
        imagebox.setAttribute('alt', songData.title);

        imagebox.setAttribute('width', imagesize + 'px');
        imagebox.setAttribute('height', imagesize + 'px');
        //imagebox.classList.add('img-circle');
        //imagebox.classList.add('img-rounded'); // also 'thumbnail'
        
        //imagebox.classList.add('songImage');
        
        var divimage = document.createElement('div');

        divimage.appendChild(imagebox);
        
        var tdimage = document.createElement('td'); // image

        var td1 = document.createElement('td'); //title
        if(!window.browserInfo.areWeMobile())
            var td2 = document.createElement('td'); // artist
        
        if(!window.browserInfo.areWeMobile())
            var td3 = document.createElement('td');  //album
        
        var tdtime = document.createElement('td');  // time
        var td4 = document.createElement('td');  // key
        var td5 = document.createElement('td');  //bpmcircle
        var td6 = document.createElement('td');  //add


        var buttClick = document.createElement('button');
        // IE doesn't like this multiple paramaters ...
        //buttClick.classList.add('btn', 'btn-primary');
        //buttClick.classList.add('btn');
        //buttClick.classList.add('btn-primary');
        $(buttClick).addClass('btn btn-primary');
        
        var buttonSize = (window.browserInfo.areWeMobile()) ? 'btn-sm' : 'btn-xs';
        //buttClick.classList.add(buttonSize);
        $(buttClick).addClass(buttonSize);
        
        buttClick.setAttribute("type", 'button');
        
        // fields required for local storage playlist instead of going back to server
        buttClick.setAttribute("data-songid", songData.ID);
        buttClick.setAttribute("data-songtitle", songData.title);
        buttClick.setAttribute("data-songartist", songData.artist);
        buttClick.setAttribute("data-songkey", songData.initial_key);
        buttClick.setAttribute("data-songbpm", songData.bpm);
        buttClick.setAttribute("data-songplaytime", songData.playtime);
        
        var plusSpan = document.createElement('span');
        // IE doesn't like attaching multiple classes at the same time
        // buttClick.classList.add('glyphicon', 'glyphicon-plus-sign');
        //plusSpan.classList.add('glyphicon');
        //plusSpan.classList.add('glyphicon-plus-sign');
        $(plusSpan).addClass('glyphicon glyphicon-plus-sign');
        buttClick.appendChild(plusSpan);

        var text1 = document.createTextNode(songData.title);
        
        if(!window.browserInfo.areWeMobile())
            var text2 = document.createTextNode(songData.artist);
        if(!window.browserInfo.areWeMobile())
            var text3 = document.createTextNode(songData.album);
        var texttime = document.createTextNode(songData.playtime);
        var text4 = document.createTextNode(songData.initial_key);
        var text5 = document.createTextNode(songData.bpm);
        //buttClick.appendChild(document.createTextNode('&#xe081;'));
        //buttClick.appendChild(document.createTextNode('+'));
        var text6 = buttClick;

        // make the domains

        //tdimage.appendChild(imagebox);
        tdimage.appendChild(divimage);
        td1.innerHTML = songData.title;         // td1.appendChild(text1);
        if(!window.browserInfo.areWeMobile())
            td2.innerHTML = songData.artist;    // td2.appendChild(text2);
        if(!window.browserInfo.areWeMobile())
            td3.innerHTML = songData.album;     // td3.appendChild(text3);
        tdtime.appendChild(texttime);
        td4.appendChild(text4);
        td5.appendChild(text5);
        td6.appendChild(text6);

        // add the domains to the row
        
        tr.appendChild(tdimage);
        tr.appendChild(td1);
        if(!window.browserInfo.areWeMobile())
            tr.appendChild(td2);
        if(!window.browserInfo.areWeMobile())
            tr.appendChild(td3);
        tr.appendChild(tdtime);
        tr.appendChild(td4);
        tr.appendChild(td5);
        tr.appendChild(td6);
        
        //tr.classList.add(getbitRateColour(songData.bitrate/1000));
        $(tr).addClass(getbitRateColour(songData.bitrate/1000));
        
        // add the row to the body
        fragment.appendChild(tr);
       
    }
    
    // actually make the table
    
    var searchTable = setTheTable();
    
    //var tblHead = document.createElement("thead");
    var tblBody = document.createElement("tbody");
    var tblFoot = document.createElement("tfoot");
    
    
    // what is going on here with the thead
    var headerFooter = makeHeaderFooter();
    //tblHead.appendChild(headerFooter);
    
    //searchTable.appendChild(tblHead);
    
    tblBody.appendChild(fragment);
    searchTable.appendChild(tblBody);
    
    tblFoot.appendChild(headerFooter);
    searchTable.appendChild(tblFoot);

    // remove whatever is in the spot
    $('.contentInfoContainer').empty();
    $('.contentInfoContainer').append(searchTable);
    if(!window.browserInfo.areWeMobile())
    {   
        //searchTable.parentNode.classList.add('table-responsive');
        $(searchTable.parentNode).addClass("table-responsive");
    }
    
    //return searchAlbumArray;
    return searchArray;
}
/*
 * -- calcNumSongs --
 *      calculon how many songs and show it on the html page
 *      
 * @param {int} offset
 * @param {int} numObjects
 * @param {int} numRows
 * @returns {}
 */
function calcNumSongs(offset, numObjects, numRows)
{
    $('#songsCount').empty();

    var start = (numObjects) ? (offset + 1) : offset;
    var finish = offset + numObjects;
    var range = start + '-' + finish;

    $('#songsCount').append(range + ' of ' + numRows);
}

function calcPageNav(offset, numRows, limit, q)
{
    $("#pageNav").empty();
    var pages = Math.ceil(numRows / limit);
    if (pages > 1)
    {
        var currentPage = (offset) ? (Math.ceil(offset / limit) + 1) : 1;

        var options = {
            currentPage: currentPage,
            totalPages: pages,
            size: 'medium',
            numberOfPages: 3,
            bootstrapMajorVersion: 3,
            onPageClicked: function(e,originalEvent,type,page){

                e.stopImmediatePropagation();

                var currentTarget = $(e.currentTarget);

                var pages = currentTarget.bootstrapPaginator("getPages");

                //alert("Page item clicked, current page: "+page);
                
                runSearch(q, page);
                
            }
        };

        $("#pageNav").bootstrapPaginator(options);
    }
}

/* This script creates a new CanvasLoader instance and places it in the wrapper div */
function ShowWaitSpinner()
{
    var cl = new CanvasLoader('canvasloader-container');
    cl.setShape('roundRect'); // default is 'oval'
    cl.setDiameter(97); // default is 40
    cl.setDensity(13); // default is 40
    cl.setRange(0.8); // default is 1.3
    cl.setSpeed(1); // default is 2
    cl.setFPS(18); // default is 24
    //cl.show(); // Hidden by default

    // This bit is only for positioning - not necessary
    var loaderObj = document.getElementById("canvasLoader");
    loaderObj.style.position = "absolute";
    loaderObj.style["top"] = cl.getDiameter() * -0.5 + "px";
    loaderObj.style["left"] = cl.getDiameter() * -0.5 + "px";
    return cl;
}
/*
 * -- performKeyBoardHide --
 *  for mobile devices - close the keyboard after a search
 */
function performKeyBoardHide()
{
    var field = document.createElement('input');
    var appendableID = document.getElementById("content");
    field.setAttribute('type', 'radio');
    field.setAttribute('id', 'completelyHidden');
    //document.body.appendChild(field);
    
    $(appendableID).append(field);

    setTimeout(function() {
        field.focus();
        setTimeout(function() {
            field.setAttribute('style', 'display:none;');
        }, 1);
    }, 1);
    //field.parentNode.removeChild(field);
}

function runSearch(q, page)
{
    var url = '../api/search/' + q + '/';
    if((parseInt(page, 10) > 0))
    {
        url = url + page + '/';
    }
    
    if(window.browserInfo.areWeMobile()) { performKeyBoardHide(); }

    $(document).ajaxStart(function() { spinner.show(); });
    $(document).ajaxComplete(function() { spinner.hide();});

        /*
        accept: { json: 'application/json', xml: 'application/xml' },
        url: url /*,
        data: {q: q, offset: offset} // */

    $.ajax({
        type: 'GET',
        url: url
    })
    .done(function(result){
        try{
            var obj = jQuery.parseJSON(result);
        } catch (error){
            console.log('Got bad data from the server: ' + error);
        }
        if(typeof obj !== 'object')
        {
            alert('Sorry, got bad data from the server');
            if(obj === false)
            {
                alert('Sorry, False returned. That\'s bad, yo!');
            }else
             {

             }
            sorryBrah();
            $('#songsCount').empty();
            $('#pageNav').empty();
        }else{
            /*
            window.history.pushState({page: './'}, 
                q + ': DJ Playlist Tools: Search ' + obj.page, q + '/');
                // */
            if((parseInt(obj.numResults, 10) > 0))
            {
                $('.hideaftersearch').fadeOut(500);
                var objData = outputTable(obj);
                checkiTunesCovers(objData);                
                calcNumSongs(obj.offset, obj.numRows, obj.numResults);
                calcPageNav(obj.offset, obj.numResults, obj.limit, q);
            }else
             {
                sorryBrah();
                $('#songsCount').empty();
                $('#pageNav').empty();
             }   
        }
         
    })
    .fail(function(jqXHR, failStatus) {
        alert("Search failed: " + failStatus);
    });
    // */
    // return false;
}

/*
 *             songID: song.songID,
            songDesc: song.songDesc,
            songKey: song.songKey,
            songBPM: song.songBPM,
            songPosition: count
        }
 */

// while this is very cool, I think, I should probably just cross out the song
// from the table once I've added the song to local storage. I should probably 
// only allow the user to remove songs from the playlist page.

// because of how I index songs in the playlist, I can only delete by key, but that
// key is not UNIQUE. I would have to test against songID. And that becomes
// problematic.

// I have to look into the glyphicon fiasco I'm enabling with my
// incompetence.
// Look into :before and :after to input the actual content or HTML Entity
// + &#xe081;
// - &#xe082;
// x &#xe083;
// play circle -- &#xe029;

function sorryBrah()
{
    $('.contentInfoContainer').empty();
    var output = document.createElement('div');
    output.appendChild(document.createTextNode("Sorry, brah. No results"));
    $('.contentInfoContainer').append(output);
}

function getbitRateColour(bitrateQuality)
{
    var bitrateQualityColour;
    if (bitrateQuality > 256)
    {
        bitrateQualityColour = 'info';

    } else if (bitrateQuality <= 256 && bitrateQuality > 192)
    {
        bitrateQualityColour = 'success';
    } else if (bitrateQuality <= 192 && bitrateQuality > 160)
    {
        bitrateQualityColour = 'warning';
    } else
    {
        bitrateQualityColour = 'danger';    // , Will Robinson! Danger!
    }
    return bitrateQualityColour;
}

function checkiTunesCovers(dataArray)
{
    var coverImage;
    
    console.log('searching iTunes');
    dataArray.forEach(function(val, key, array)
    {    
        //console.log('searching for: ' + key);
         coverImage = checkForCover(val, key);
    });
}

function checkForCover(searchTerm, key)
{
    var retVal = false;
    
    $.ajax({
        type: 'GET',
        url: 'https://itunes.apple.com/search',
        data : {
            term: searchTerm,
            country : 'CA',
            media : 'music',
            entity : 'song',//,musicArtist,album',
            limit : '5'
        },
        dataType: 'jsonp',
        success: function(data){
            
            if((parseInt(data.resultCount, 10) > 0))
            {
                for (var i = 0; i < data.results.length; i++)
                {
                    //console.log('On key: ' + key + ' going through: ' + data.results[i]);
                    if(data.results[i].hasOwnProperty('artworkUrl60'))
                    {   
                        //console.log(data.results[i].artworkUrl60); 
                        $('.album-' + key).src = data.results[i].artworkUrl60;
                        document.getElementById(key).src = data.results[i].artworkUrl60;
                        return data.results[i].artworkUrl60;
                    }

                    if(data.results[i].hasOwnProperty('artworkUrl30'))
                    {   
                        //console.log(data.results[i].artworkUrl30);
                        $('.album-' + key).src = data.results[i].artworkUrl30;
                        document.getElementById(key).src = data.results[i].artworkUrl30;
                        return data.results[i].artworkUrl30;
                    }                    
                }
            }            
        }
    });
    return retVal;
}

$(document).ready(function()
{
    // if jsonData is set then I should output the data
    // I must delete it right after
    if (typeof window.jsonData !== 'undefined')
    {
        if((parseInt(window.jsonData.numResults, 10) > 0))
        {
            var objData = outputTable(window.jsonData);
            checkiTunesCovers(objData);
        }else
         {
            sorryBrah();
         }  
    }
    
    $('#formsearch').submit(function(event) {
        event.preventDefault(); // prevent normal submit
        runSearch($("#q").val(), 0);
        return false;  // just in case ?
    });
    
    // has to be done everytime 
    // for the playlist which might be on a different page
    window.playlist.countTime();
    window.playlist.countSongs();


    $(document).on("click", 'button', function() {
        if(!isNaN(parseInt(this.getAttribute('data-songid'),10)))
        {
            var JSONSong = {
                songID: this.getAttribute('data-songid'),
                songDesc: this.getAttribute('data-songtitle') + ', ' 
                            + this.getAttribute('data-songartist'),
                songTitle: this.getAttribute('data-songtitle'),
                songArtist: this.getAttribute('data-songartist'),
                songKey: this.getAttribute('data-songkey'),
                songBPM: this.getAttribute('data-songbpm'),
                songPlaytime: this.getAttribute('data-songplaytime')
            };

            var localStorageKey = window.playlist.addSong(JSONSong, true);
            this.setAttribute("data-songkeyid", localStorageKey);
            this.removeAttribute('data-songid');

            // change sign
            this.setAttribute("disabled", 'disabled');                
            $(this).find('span').toggleClass('glyphicon-ok-circle glyphicon-plus-sign');            
        }
    });
});