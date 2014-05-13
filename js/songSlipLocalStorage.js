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
 *  I was about to rewrite this to save a list (array) of songIDs in one storage spot. That
 *  storage spot would hold the songIDs (keys) in arranged order. However I realize (good thing)
 *  before re-writing everything that doing so would not allow for duplicate songIDs. 
 *  I want the option of duplicate songIDs.
 *  
 *  Au contraire!
 *  
 *  I can save duplicate songIDs in one array and note the position in the array.
 *  I also have to save the position in the songs storage slot (as I currently do)
 *  then if I delete a songID, I have to check the position before I delete it.
 *  
 *  However, everyone (and I mean everyone) claims that saving an object in 
 *  one key is the absolutely fastest way to do everything. 
 *  So I think I will do that.
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
var playListIndexKey = 'playListOrder';
var dragClass = "draglist";             // this class can be dragged in Sortable

var myListID = "songList";  // the DOM id the SLIP script utilizes

$(document).ready(function() {

    $(window).bind('storage', function(event) {
        //alert(event + ': means storage changed');
        countTime();
        countSongs();
        //renderSongs();
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
            var storageSongID = e.target.getAttribute('data-songKeyID');
            e.target.parentNode.removeChild(e.target);
            removeSong(storageSongID);
            //     }
        }); //, false);

        list.addEventListener('slip:reorder', function(e) {
            e.target.parentNode.insertBefore(e.target, e.detail.insertBefore);
            // updateOrder() also changes playlistTiming 
            // so I don't have to renderSongs()
            savePlaylistOrder(updateOrder());
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
        var DOMElement = '#' + myListID;
        $(DOMElement).empty();
        //renderSongs();
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
     
    newKey = prefix + count + Math.floor((Math.random() * 1000) + 1);
    //store.set(newKey, newSong);

    if (fromStorage)
    {
        store.set(newKey,
                {
                    songID: song.songID,
                    //songDesc: song.songDesc,
                    songTitle: song.songTitle,
                    songArtist: song.songArtist,
                    songKey: song.songKey,
                    songBPM: song.songBPM,
                    songPlaytime: song.songPlaytime //,
                    //songPosition: count
                });
    } else
    {
        store.set(newKey,
                {
                    songID: song.ID,
                    //songDesc: song.title + ', ' + song.artist,
                    songTitle: song.title,
                    songArtist: song.artist,
                    songKey: song.initial_key,
                    songBPM: song.bpm,
                    songPlaytime: song.playtime //,
                    //songPosition: count
                });
    }

    //countTime();      // taken care of by renderSongs()
    //countSongs();     // taken care of by renderSongs()
    // assert good add
    if (count < countSongs())
    {
        // console.log('Song added successfully.');
        retVal = true;
        var newOrder = getPlaylistOrder();
        newOrder.push(newKey);
        savePlaylistOrder(newOrder);
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
        //var newOrder = updateOrder();
        var newOrder = removeArrayElement(getPlaylistOrder(), localStorageKey);
        savePlaylistOrder(newOrder);
        //updateLocalStorageOrder(newOrder);

        // update song positions
        renderSongs();
    }
    return retVal;
}

function clearSongs()
{
    var re = new RegExp('^(' + prefix + ')');
    Object.keys(window.localStorage).forEach(function(key)
    {
        if (re.test(key))
        {
            //window.localStorage.removeItem(key);
            store.remove(key);
        }
    });
    // remove index
    store.remove('playListOrder');
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
    
    return (isNaN(seconds)) ? 0 : seconds;
}

function convertSecondsToMinutes(songSeconds)
{
    var minutes = 0;
    // convert seconds to minutes and seconds
    minutes = Math.floor(songSeconds / 60);
    //minutes = pad(minutes, 2);
    return (isNaN(minutes)) ? 0 : minutes;
}

function convertMinutesToHours(songMinutes)
{
    var hours = 0;
    // convert seconds to minutes and seconds
    hours = Math.floor(songMinutes / 60);
    return (isNaN(hours)) ? 0 : hours;
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
            seconds = seconds + convertToSeconds(songData.songPlaytime);
            count++;
        }
    });

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
    
    // this is going to change but it is the list order in a single storage space
    //makeOrderIndex();
}

function loadLocalStorageData(jsonData)
{
    clearSongs();
    for (var i = 0; i < jsonData.length; i++) {
        addSong(jsonData[i]);
    }
}

function updateOrder()
{
    var nums = document.getElementById(myListID);
    var listItem = nums.getElementsByTagName("li");
    var newOrder = [];
    var timeElem, indexElem;
    var hours, minutes, seconds, playTimeLength = 0;
    var playtimeInfoText = '0:00:00';

    var listToLoop = listItem;

    for (var i = 0; i < listToLoop.length; i++)
    {
        newOrder.push(listToLoop[i].getAttribute('data-songKeyID'));
        // update li index number
        // li div div h4
        indexElem = $(listToLoop[i]).find('h4');
        indexElem.text(i + 1);
        // update playlist song time start
        // .playTimeStart
        timeElem = $(listToLoop[i]).find('.playTimeStart');
        timeElem.text(playtimeInfoText);
        
        // update playtime
        // calc playlist time
        playTimeLength = playtimeInfoText;
        seconds = addSongTimes(playTimeLength, listToLoop[i].getAttribute('data-songPlaytime'));
        minutes = convertSecondsToMinutes(seconds);
        hours = convertMinutesToHours(minutes);
        playtimeInfoText = hours + ':' + pad(minutes - hours * 60, 2) + ':' + pad((seconds - minutes * 60), 2);

    }

    return newOrder;
}
/*
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
// */
/*
// returns array of JSON objects in playlist order
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
// */
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
    var divRow, indexDiv, indexNum, textDiv, songBPMInfo, songKeyInfo,
            songTimeInfo, songPlayTimeInfo, songTitleInfo, songArtistInfo,
            draggable, listItem, badge, buttClick, minusSpan, buttonDiv, songData;
    //var listIndex = 0;
    var hours, minutes, seconds, playTimeLength, listIndex = 0;
    var playtimeInfoText = '0:00:00';

    var fragment = document.createDocumentFragment();

    var songs = getPlaylistOrder();
    
    //output the correct order
    $.each(songs, function(key, index)
    {
        songData = store.get(index);

        // bootstrap OVERFLOW rules override index output
        // so "EFF you," Jobu, I do it myself.
        listIndex = listIndex + 1;

        divRow = document.createElement('div');
        $(divRow).addClass('row');

        indexDiv = document.createElement('div');
        $(indexDiv).addClass('col-xs-1 bg-warning');

        indexNum = document.createElement('h4');
        indexNum.innerHTML = listIndex;
        indexDiv.appendChild(indexNum);

        textDiv = document.createElement('div');
        //textDiv.classList.add('col-xs-10');
        $(textDiv).addClass('col-xs-10');

        songBPMInfo = document.createElement('span');
        $(songBPMInfo).addClass('small text-muted');
        songBPMInfo.innerHTML = ' [' + songData.songBPM + ' bpm] ';

        songKeyInfo = document.createElement('span');
        $(songKeyInfo).addClass('small text-muted');
        songKeyInfo.innerHTML = songData.songKey;

        songTimeInfo = document.createElement('span');
        $(songTimeInfo).addClass('small text-muted');
        songTimeInfo.innerHTML = ' (' + songData.songPlaytime + ') ';

        // calculated at end of loop
        songPlayTimeInfo = document.createElement('div');
        $(songPlayTimeInfo).addClass('playTimeStart small text-muted pull-right');
        songPlayTimeInfo.innerHTML = playtimeInfoText;

        songTitleInfo = document.createElement('span');
        songTitleInfo.setAttribute('style', 'text-transform:uppercase');
        //songTitleInfo.classList.add('text-info');
        $(songTitleInfo).addClass('text-info');
        songTitleInfo.innerHTML = songData.songTitle;

        textDiv.appendChild(songTitleInfo);

        songArtistInfo = document.createElement('span');
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
        draggable = document.createElement('span');
        //draggable.classList.add('draglist');
        $(draggable).addClass('draglist');
        //draggable.setAttribute("data-songPosition", songData.songPosition);
        draggable.setAttribute("data-songKeyID", index);
        // draggable.appendChild(listText);
        //draggable.innerHTML = listText;

        listItem = document.createElement('li');
        //listItem.classList.add('list-group-item');
        $(listItem).addClass('list-group-item');
        // required for re-sort
        //listItem.setAttribute("data-songPosition", songData.songPosition);
        listItem.setAttribute("data-songKeyID", index);
        listItem.setAttribute("data-songPlaytime", songData.songPlaytime);

        //listItem.appendChild(draggable);

        divRow.appendChild(indexDiv);

        divRow.appendChild(textDiv);
        if (!areWeMobile())
        {
            badge = document.createElement('span');
            badge.style.cssFloat = "right";

            // required for deleting item
            buttClick = document.createElement('button');
            $(buttClick).addClass('btn btn-warning removebutton btn-xs');
            buttClick.setAttribute("type", 'button');
            buttClick.setAttribute("data-songKeyID", index);

            minusSpan = document.createElement('span');
            $(minusSpan).addClass('glyphicon glyphicon-minus-sign');

            buttClick.appendChild(minusSpan);
            badge.appendChild(buttClick);

            buttonDiv = document.createElement('div');
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

/*
function getLastSongAdded()
{
    // get BPM and camelot of last song in list

    var songNumber = ($('#numSongs').html()).substring(('Songs: ').length);
    var index = parseInt(songNumber, 10) - 1;   // since storage is ZERO-indexed
    if(index >= 0)
    {
        var songData = store.get(prefix + index);
    }
    return index;
}
// */
/*
 * function makeOrderIndex
 * 
 *  this is an in-between function to move my stupid algorithm to a better one
 *  i save the storage keys in an array which is stringified 
 *  
 */
/*
function makeOrderIndex()
{
    var key = 'playListOrder';
    var storeOrder = [];
    //var listOrderArray = store.get(key);
    if(countSongs())
    {
        store.forEach(function(keyIndex, val)
        {
            if (keyIndex.indexOf(prefix) !== 0) {
                // do nothing
            } else
            {
                storeOrder.push(keyIndex);          
            }
        });
        //store.set(key, JSON.stringify(storeOrder));
        store.set(key, storeOrder.join(','));
    }
    return true;
} 
// */
function removeArrayElement(array, element)
{
    array = jQuery.grep(array, function(value)
    {
        return value !== element;
    });
    return array;
}
//returns an array
function getPlaylistOrder()
{
    //return JSON.parse(store.get('playListOrder'));
    var playlist = store.get('playListOrder');
    return (typeof playlist !== 'undefined') 
                            ? playlist.split(',') : [];

    //return (store.get('playListOrder')).split(',');
}
// accepts array of values
//boy I should use some assertions
function savePlaylistOrder(storeOrder)
{
    store.set('playListOrder', storeOrder.join(','));
}

function getLastSong()
{
    var storeOrder = getPlaylistOrder();
    var storageKey = storeOrder.pop();
    storageKey = getPlaylistOrder().pop();
    console.log('Last Song: ' + storageKey);
    return store.get(storageKey);
}
