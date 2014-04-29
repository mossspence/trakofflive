/*
 * 
 * Author: Mark Spence (mosspence@gmail.com)
 * 
 * Script saves playlist song data (JSON) to local storage 
 * with the assistance of "store.js" (proven so far)
 * and "slip.js" (proven for re-ordering and on touch devices too)
 * 
 * purpose is to make a playlist so the playlist needs
 *  songID
 *  songTitle
 *  songArtist
 *  songBPM
 *  songKey
 *  songPosition
 *  
 *  {
 *      song.ID,
 songDesc: song.title + ', ' + song.artist,
 songKey: song.initial_key,
 songBPM: song.bpm,
 songPosition: `calculated`
 }
 
 *
 *  Since playlists can be modified, and local storage can be edited (painfully), 
 *  the script constantly clears and rewrites local storage onchange events.
 *  
 *  What is the Big O Notation for this?
 *  
 *  delete the last item in the list (only have to edit 1 item)
 *  delete the first item in the list (have to edit all items)
 *  change a position, edit everything between A and B
 *  
 *  I'm also using two (2) types of pattern matching techniques
 *  I should rectify this.
 *  
 */
"use strict";   // there has to be a better way of doing this.

if (!window.Modernizr.localstorage) {
    alert("This browser does not support local storage.");
    //return;
}

if (!window.JSON) {
    alert("This browser does not support JSON.");
    //return;
}

if (!store.enabled) {
    alert('Local storage is not supported by your browser. \n\
     Please disable "Private Mode", or upgrade to a modern browser.');
}

var prefix = "MyPlayListSongs__";       // this global var will be dealt with
var dragClass = "draglist";             // this class can be dragged in Sortable

var myListID = "songList";  // the DOM id the SLIP script utilizes

$(document).ready(function() {

    $(window).bind('storage', function(event) {
        //alert(event + ': means storage changed');
        //countTime();
        //countSongs();
        renderSongs();
    });


    if (document.getElementById(myListID))
    {
        renderSongs();

        var list = document.getElementById(myListID);

        list.addEventListener('slip:beforeswipe', function(e) {
            //alert('swipe started ...');
            //console.log(e.target);
        }, false);

        list.addEventListener('slip:swipe', function(e) {
            //if (thatWasSwipeToRemove) {
            //alert('removing: ' + e.target.getAttribute('data-songKeyID'));
            //console.log(e);
            //console.log(this.getAttribute('data-songKeyID'));
            //removeSong(this.getAttribute('data-songKeyID'));
            //console.log(e.getAttribute('data-songKeyID'));
            //console.log(e.target);
            var storageSongID = e.target.getAttribute('data-songKeyID');
            e.target.parentNode.removeChild(e.target);
            removeSong(storageSongID);
            //     }
        }); //, false);
        /*    
         list.addEventListener('slip:afterswipe', function(e){
         removeSong(this.getAttribute('data-songKeyID'));
         }, false);
         */
        list.addEventListener('slip:reorder', function(e) {
            e.target.parentNode.insertBefore(e.target, e.detail.insertBefore);
            //console.log(e.target.getAttribute('data-songKeyID'));
            updateLocalStorageOrder(updateOrder(true));
            return false;
        }); //, false);


        new Slip(list);

        $(document).on("click", '.removebutton', function() {
            removeSong(this.getAttribute('data-songKeyID'));
        });
    }
    /*
     $(document).on("click", '#exportbutton', function() {
     exportSongs();
     });
     */
    $(document).on("click", '#clearbutton', function() {
        clearSongs();
        // these should  be in a callback list or BOUND to storage as below
        // and they are but they don't listen to a remove event so ...
        //countTime();
        //countSongs();

        renderSongs();
    });

}); /* end ready */

// this works ONLY on JSON data from the database

function addSong(JSONObject, fromStorage)
{
    // optional function paramater, defaults to true
    //  true because this function will be used more often with localStorage data

    fromStorage = typeof fromStorage !== 'undefined' ? true : false;

    var newKey;
    var retVal = false;
    // assert is JSON object
    // easier to do if I used the same notation locally and on server
    // if((isset(song.songID) && !empty(song.songID)) || (isset(song.ID) && !empty(song.ID)))
    //var song = JSON.parse(JSONObject);
    var song = JSONObject;
    //console.log('Adding: ' + song);

    // get playlist position
    var count = countSongs();

    // add song
    // Store an object literal - store.js uses JSON.stringify under the hood
    newKey = prefix + count;
    //store.set(newKey, newSong);

    if (fromStorage)
    {
        store.set(newKey,
                {
                    songID: song.songID,
                    songDesc: song.songDesc,
                    songTitle: song.songTitle,
                    songArtist: song.songArtist,
                    songKey: song.songKey,
                    songBPM: song.songBPM,
                    songPlaytime: song.songPlaytime,
                    songPosition: count
                });
    } else
    {
        store.set(newKey,
                {
                    songID: song.ID,
                    songDesc: song.title + ', ' + song.artist,
                    songTitle: song.title,
                    songArtist: song.artist,
                    songKey: song.initial_key,
                    songBPM: song.bpm,
                    songPlaytime: song.playtime,
                    songPosition: count
                });
    }

    //countTime();      // taken care of by renderSongs()
    //countSongs();     // taken care of by renderSongs()
    // assert good add
    if (count < countSongs())
    {
        // console.log('Song added successfully.');
        retVal = true;
    }
    // re-render playlist
    renderSongs();
    return newKey;
}

// this works INDIVIDUALLY ONLY
function removeSong(localStorageKey)
{
    var retVal = false;
    // assert
    // console.log('Removing: ' + localStorageKey);
    var count = countSongs();

    // delete
    store.remove(localStorageKey);

    // assert good delete
    if (count > countSongs())
    {
        // console.log('Song removed successfully.');
        retVal = true;

        // re-render playlist
        var newOrder = updateOrder();
        updateLocalStorageOrder(newOrder);

        // update song positions
        renderSongs();
    }
    return retVal;
}

function clearSongs()
{
    deletemyPrefix();
    /*
     store.forEach(function(key, val)
     {
     if(key.indexOf(prefix) === 0)   // only remove keys with prefix
     {
     // perhaps this is overkill but I noticed that 
     // this function is flaky since it only sometimes
     // removes half the keys
     while((typeof(store.get(key)) !== 'undefined'))
     {
     window.setTimeout(removeSong(key),timeout);
     }
     }
     });
     */
}
// this works
function countSongs()
{
    var count = 0;
    store.forEach(function(key, val)
    {
        count += (key.indexOf(prefix) === 0) ? 1 : 0;   // only count keys with MY prefix
    });

    $('#numSongs').empty();
    $('#numSongs').append('Songs: ' + count);
    
    if(count > 0) { $('#abovefooter').show(); }else{ $('#abovefooter').hide();}
    
    return count;
}
function pad(num, size) {
    var s = num + "";
    while (s.length < size)
        s = "0" + s;
    return s;
}
// this function cannot calculate a time input with days
function convertToSeconds(songTime)
{
    var seconds = 0;
    var expVal = 1;
    var multiplier = 60;
    var time = ((songTime).split(':')).reverse();   // songTime is a string
    for(var i=0; i<time.length; i++)
    {
        seconds += (parseInt(time[i], 10)) * expVal;
        expVal *= multiplier;
    }
    
    return (!isNaN(seconds)) ? seconds : 0;
}

function convertSecondsToMinutes(songSeconds)
{
    var minutes = 0;
    // convert seconds to minutes and seconds
    minutes = Math.floor(songSeconds / 60);
    //minutes = pad(minutes, 2);
    return (!isNaN(minutes)) ? minutes : 0;
}

function convertMinutesToHours(songMinutes)
{
    var hours = 0;
    // convert seconds to minutes and seconds
    hours = Math.floor(songMinutes / 60);
    return (!isNaN(hours)) ? hours : 0;
}

function addSongTimes(song1, song2)
{
    return convertToSeconds(song1) + convertToSeconds(song2);
}

function countTime()
{
    var songData;
    var time;
    var seconds = 0;
    var minutes = 0;
    var hours = 0;
    var secondsOutput = 0;
    var count = 0;  // sanity check 

    store.forEach(function(key, val)
    {
        if (key.indexOf(prefix) !== 0) {
            // do nothing
        } else
        {
            songData = store.get(key);
            // time = (songData.songPlaytime).split(':');

            // minutes are worth 60 seconds.
            // .9 is completely random  percentage to prepare for mixout
            // but this is more trouble than it is worth
            //var songSeconds = (+time[0]) * 60 + (+time[1]);
            //seconds = seconds + (songSeconds * .9).toFixed(2);
            // seconds = seconds + (+time[0]) * 60 + (+time[1]);
            seconds = seconds + convertToSeconds(songData.songPlaytime);
            count++;
        }
    });
/*
    // convert seconds to minutes and seconds
    minutes = Math.floor(seconds / 60);
    hours = Math.floor(minutes / 60);
    secondsOutput = pad((seconds - minutes * 60), 2);
    minutes = pad(minutes, 2);
*/
    minutes = convertSecondsToMinutes(seconds);
    secondsOutput = pad((seconds - minutes * 60), 2);
    hours = convertMinutesToHours(minutes);

    $('#sumTimeSongs').empty();
    var tilde = (seconds > 0) ? '~' : '';

    if (hours > 0)
    {
        minutes = pad((minutes - (hours * 60)), 2);
        $('#sumTimeSongs').append('Playlist Time: ' + hours + ':' + minutes + ':' + secondsOutput);
    } else
    {
        $('#sumTimeSongs').append('Playlist Time: ' + minutes + ':' + secondsOutput);
    }
    return (count === countSongs()) ? seconds : -1;
}

// just for testing
function showEverything()
{
    var count = 0;
    store.forEach(function(key, val) {
        console.log(key, '==', val);
        count++;
    });
    console.log('Items stored: ' + count);
}

// this works properly and with regularity
// but I wish I knew why the other individually didn't
// I have to assume that this regular expression test is better than
// what I was using before
// (key.indexOf(prefix) !== 0)
// (key.indexOf(prefix) === 0) -> I think this is really bad

function deletemyPrefix()
{
    var re = new RegExp('^(' + prefix + ')');
    Object.keys(window.localStorage)
            .forEach(function(key) {
                if (re.test(key)) {
                    //window.localStorage.removeItem(key);
                    store.remove(key);
                }
            });
}

function renderSongs()
{
    spinner.show();

    // this is the last one used
    //renderLocalStorage();
       
    renderLocalStorageToDiv();
    countTime();    // this should be done on storage event
    countSongs();    // this should be done on storage event but doesn't always for some reason 
    // this is for testing and is to be used by the search
    
    spinner.hide();
}

function loadLocalStorageData(jsonData)
{
    clearSongs();
    for (var i = 0; i < jsonData.length; i++) {
        addSong(jsonData[i]);
    }
}

function updateOrder(HTMLList)
{
    // optional function paramater, defaults to true
    //  true because this function will be used more often with HTMLList data

    HTMLList = typeof HTMLList !== 'undefined' ? true : false;

    var nums = document.getElementById(myListID);
    var listItem = nums.getElementsByTagName("li");
    var newOrder = [];
    var songData;

    var listToLoop = (HTMLList) ? listItem : window.localStorage;

    for (var i = 0; i < listToLoop.length; i++)
    {
        if (HTMLList)
        {
            newOrder.push(parseInt(listToLoop[i].getAttribute('data-songPosition'), 10));
        } else
        {
            var key = listToLoop.key(i);
            if (key.indexOf(prefix) !== 0) {
                continue;
            }
            songData = JSON.parse(listToLoop[key]);
            newOrder.push(parseInt(songData.songPosition, 10));
        }
    }
    newOrder = (HTMLList) ? newOrder : newOrder.sort(function(a, b) {
        return a - b;
    });
    return newOrder;
}
// expects array of stringified JSON objects
function updateLocalStorageOrder(newOrder)
{
    var lastPlaylist = storageToArray();

    clearSongs();

    $.each(newOrder, function(index, k) {
        addSong(lastPlaylist[k], true);
    });
    renderSongs();
}

// returns array of JSON objects
// not currently used, but I should
function storageToArray()
{
    var jsonDataArray = [];
    var songData;

    store.forEach(function(key, val)
    {
        if (key.indexOf(prefix) !== 0) {
            // do nothing
        } else
        {
            // val = store.get(key);    // should be done already
            songData = store.get(key);
            jsonDataArray[parseInt(songData.songPosition, 10)] = songData;
        }
    });
    return jsonDataArray;
}

/*
 * Place the local storage data
 * into the DOM
 * uses '#songList' UL/OL
 * adds 
 *  data-songPosition
 *  data-songKey
 *      to allow for easy re-arangement and deletion
 * 
 */
function renderLocalStorage()
{
    var songs = []; // this array will hold the correct order of songs
    var songData;
    var index;
    var listIndex;
    var fragment = document.createDocumentFragment();

    store.forEach(function(key, val)
    {
        if (key.indexOf(prefix) !== 0) {
            // do nothing
        } else
        {
            // val = store.get(key);    // should be done already
            songData = store.get(key);
            index = parseInt(songData.songPosition, 10);
            songs[index] = songData;            
        }
    });


    //output the correct order
    $.each(songs, function(index, songData) {
        
            // bootstrap OVERFLOW rules override index output
            // so "EFF you," Jobu, I do it myself.
            listIndex = index + 1;
            var listText = listIndex + '. '
                    + songData.songDesc + ' <br /><small> ['
                    + songData.songBPM + ' bpm] '
                    + songData.songKey + ' ('
                    + songData.songPlaytime + ')</small>';

            var seconds = addSongTimes(playTimeLength, songData.songPlaytime);
            var minutes = convertSecondsToMinutes(seconds);
            var hours = convertMinutesToHours(minutes);
            var playtimeInfoText = hours + ':' + pad(minutes - hours * 60, 2) + ':' + pad((seconds - minutes * 60), 2);
            playTimeLength = playtimeInfoText;
                    
            var songPlayTimeInfo = document.createElement('div');
            /*
            songPlayTimeInfo.classList.add('small');
            songPlayTimeInfo.classList.add('text-muted');
            songPlayTimeInfo.classList.add('pull-right');
            // */
            $(songsongPlayTimeInfo).addClass('small text-muted pull-right');
            songPlayTimeInfo.innerHTML = playtimeInfoText;

            // reduce draggable space to allow for button interaction
            var draggable = document.createElement('span');
            //draggable.classList.add('draglist');
            $(draggable).addClass('draglist');
            draggable.setAttribute("data-songPosition", songData.songPosition);
            draggable.setAttribute("data-songKeyID", prefix + index);
            // draggable.appendChild(listText);
            draggable.innerHTML = listText + ' ' + playtimeIntoText;

            var listItem = document.createElement('li');
            //listItem.classList.add('list-group-item');
            $(listItem).addClass('list-group-item');
            // required for re-sort
            listItem.setAttribute("data-songPosition", songData.songPosition);
            listItem.setAttribute("data-songKeyID", prefix + index);

            var badge = document.createElement('span');
            //badge.classList.add('badgel');
            //badge.style.backgroundColor="yellow";
            badge.style.cssFloat = "right";

            // required for deleting item
            var buttClick = document.createElement('button');
            /*
            buttClick.classList.add('btn');
            buttClick.classList.add('btn-warning');
            buttClick.classList.add('removebutton');
            buttClick.classList.add('btn-xs');
            // */
            $(buttClick).addClass('btn btn-warning removebutton btn-xs');
            buttClick.setAttribute("type", 'button');
            buttClick.setAttribute("data-songKeyID", prefix + index);
            //buttClick.setAttribute("data-songPosition", songData.songPosition);

            var minusSpan = document.createElement('span');
            /*
            minusSpan.classList.add('glyphicon');
            minusSpan.classList.add('glyphicon-minus-sign');
            // */
            $(minusSpan).addClass('glyphicon glyphicon-minus-sign');

            buttClick.appendChild(minusSpan);
            badge.appendChild(buttClick);

            listItem.appendChild(draggable);
            if (!areWeMobile())
                listItem.appendChild(badge);
    
        fragment.appendChild(listItem);
    });

    //empty the current HTML OL/UL
    var DOMElement = '#' + myListID;
    $(DOMElement).empty();

    $(DOMElement).append(fragment);
}

/*
 * Place the local storage data
 * into the DOM
 * uses '#songList' UL/OL
 * adds 
 *  data-songPosition
 *  data-songKey
 *      to allow for easy re-arangement and deletion
 * 
 */
function renderLocalStorageToDiv()
{
    var songs = []; // this array will hold the correct order of songs
    var songData;
    var index;
    var listIndex;
    var hours, minutes, seconds, playTimeLength = 0;
    var playtimeInfoText = '0:00:00';

    var fragment = document.createDocumentFragment();

    store.forEach(function(key, val)
    {
        if (key.indexOf(prefix) !== 0) {
            // do nothing
        } else
        {
            // val = store.get(key);    // should be done already
            songData = store.get(key);
            index = parseInt(songData.songPosition, 10);
            songs[index] = songData;            
        }
    });
    //output the correct order
    $.each(songs, function(index, songData) {

            // bootstrap OVERFLOW rules override index output
            // so "EFF you," Jobu, I do it myself.
            listIndex = index + 1;
            var listText = listIndex + '. '
                    + songData.songDesc + ' <br /><small> ['
                    + songData.songBPM + ' bpm] '
                    + songData.songKey + ' ('
                    + songData.songPlaytime + ')</small>';

            var divRow = document.createElement('div');
            //divRow.classList.add('row');
            $(divRow).addClass('row');

            var indexDiv = document.createElement('div');
            //indexDiv.classList.add('col-xs-1');
            //indexDiv.classList.add('bg-warning');
            // */
            $(indexDiv).addClass('col-xs-1 bg-warning');
            
            var indexNum = document.createElement('h4');
            indexNum.innerHTML = listIndex;
            indexDiv.appendChild(indexNum);

            var textDiv = document.createElement('div');
            //textDiv.classList.add('col-xs-10');
            $(textDiv).addClass('col-xs-10');

            var songText = document.createElement('span');
            songText.setAttribute('style', 'text-transform:uppercase');
            songText.innerHTML = songData.songDesc + '<br />';

            var songBPMInfo = document.createElement('span');
            //songBPMInfo.classList.add('small');
            //songBPMInfo.classList.add('text-muted');
            $(songBPMInfo).addClass('small text-muted');
            songBPMInfo.innerHTML = ' [' + songData.songBPM + ' bpm] ';

            var songKeyInfo = document.createElement('span');
            //songKeyInfo.classList.add('small');
            //songKeyInfo.classList.add('text-muted');
            $(songKeyInfo).addClass('small text-muted');
            songKeyInfo.innerHTML = songData.songKey;

            var songTimeInfo = document.createElement('span');
            //songTimeInfo.classList.add('small');
            //songTimeInfo.classList.add('text-muted');
            $(songTimeInfo).addClass('small text-muted');
            songTimeInfo.innerHTML = ' (' + songData.songPlaytime + ') ';

            // calculated at end of loop
            
            var songPlayTimeInfo = document.createElement('div');
            //songPlayTimeInfo.classList.add('small');
            //songPlayTimeInfo.classList.add('text-muted');
            //songPlayTimeInfo.classList.add('pull-right');
            $(songPlayTimeInfo).addClass('small text-muted pull-right');
            songPlayTimeInfo.innerHTML = playtimeInfoText;
            /*
             textDiv.appendChild(songText);
             // */

            var songTitleInfo = document.createElement('span');
            songTitleInfo.setAttribute('style', 'text-transform:uppercase');
            //songTitleInfo.classList.add('text-info');
            $(songTitleInfo).addClass('text-info');
            songTitleInfo.innerHTML = songData.songTitle;

            textDiv.appendChild(songTitleInfo);

            var songArtistInfo = document.createElement('span');
            //songTitleInfo.classList.add('text-primary');
            $(songArtistInfo).addClass('text-primary');
            songArtistInfo.innerHTML = ' ' + songData.songArtist + '<br /> ';

            textDiv.appendChild(songArtistInfo);
// */
            textDiv.appendChild(songBPMInfo);
            textDiv.appendChild(songKeyInfo);
            textDiv.appendChild(songTimeInfo);
            textDiv.appendChild(songPlayTimeInfo);

            // reduce draggable space to allow for button interaction
            var draggable = document.createElement('span');
            //draggable.classList.add('draglist');
            $(draggable).addClass('draglist');
            draggable.setAttribute("data-songPosition", songData.songPosition);
            draggable.setAttribute("data-songKeyID", prefix + index);
            // draggable.appendChild(listText);
            draggable.innerHTML = listText;

            var listItem = document.createElement('li');
            //listItem.classList.add('list-group-item');
            $(listItem).addClass('list-group-item');
            // required for re-sort
            listItem.setAttribute("data-songPosition", songData.songPosition);
            listItem.setAttribute("data-songKeyID", prefix + index);

            //listItem.appendChild(draggable);

            divRow.appendChild(indexDiv);

            divRow.appendChild(textDiv);
            if (!areWeMobile())
            {
                var badge = document.createElement('span');
                //badge.classList.add('badgel');
                //badge.style.backgroundColor="yellow";
                badge.style.cssFloat = "right";

                // required for deleting item
                var buttClick = document.createElement('button');
                /*
                buttClick.classList.add('btn');
                buttClick.classList.add('btn-warning');
                buttClick.classList.add('removebutton');
                buttClick.classList.add('btn-sm');
                // */
                $(buttClick).addClass('btn btn-warning removebutton btn-xs');
                buttClick.setAttribute("type", 'button');
                buttClick.setAttribute("data-songKeyID", prefix + index);
                //buttClick.setAttribute("data-songPosition", songData.songPosition);

                var minusSpan = document.createElement('span');
                //minusSpan.classList.add('glyphicon');
                //minusSpan.classList.add('glyphicon-minus-sign');
                $(minusSpan).addClass('glyphicon glyphicon-minus-sign');

                buttClick.appendChild(minusSpan);
                badge.appendChild(buttClick);

                var buttonDiv = document.createElement('div');
                //buttonDiv.classList.add('col-xs-1');
                $(buttonDiv).addClass('col-xs-1');
                
                //listItem.appendChild(badge);
                buttonDiv.appendChild(badge);
                divRow.appendChild(buttonDiv);
            }

            listItem.appendChild(divRow);
            
        fragment.appendChild(listItem);

        // calc playlist time
        playTimeLength = playtimeInfoText;
        seconds = addSongTimes(playTimeLength, songData.songPlaytime);
        minutes = convertSecondsToMinutes(seconds);
        hours = convertMinutesToHours(minutes);
        playtimeInfoText = hours + ':' + pad(minutes - hours * 60, 2) + ':' + pad((seconds - minutes * 60), 2);
        
    });

    //empty the current HTML OL/UL
    var DOMElement = '#' + myListID;
    $(DOMElement).empty();

    $(DOMElement).append(fragment);
}

function areWeMobile()
{
    var myMobileThreshold = 480;
    return ($(window).width() < myMobileThreshold);
}

function getLastSongAdded()
{
    // get BPM and camelot of last song in list

    var songNumber = ($('#numSongs').html()).substring(('Songs: ').length);
    var index = parseInt(songNumber, 10) - 1;   // since storage is ZERO-indexed
    if(index >= 0)
    {
        var songData = store.get(prefix + index);
        //alert ('BPM: ' + songData.songBPM + ' Key: ' + songData.songKey);
    }
    return index;
}