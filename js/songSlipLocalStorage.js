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

var playlist = (function(){
    
    var prefixer = "MyPlayListSongs__"; 
    function myPlaylist()
    {
        //var self = this;
        this.prefix = "MyPlayListSongs__";      // this global var will be dealt with
        this.playListIndexKey = 'playListOrder';
        var dragClass = "draglist";             // this class can be dragged in Sortable

        this.myListID = "songList";  // the DOM id the SLIP script utilizes

        this.areWeMobile = function()
        {
            var myMobileThreshold = 600;
            return ($(window).width() < myMobileThreshold);
        };

        this.updateOrder = function()
        {
            var nums = document.getElementById(this.myListID);
            
            //console.log('mylist: ' + this.myListID);
            
            var listItem = nums.getElementsByTagName("li");
            var newOrder = [];
            var timeElem, indexElem;
            var hours, minutes, seconds, playTimeLength = 0;
            var playtimeInfoText = '0:00:00';

            var listToLoop = listItem;

            for (var i = 0; i < listToLoop.length; i++)
            {
                //console.log('storage Key ID: ' + listToLoop[i].getAttribute('data-songKeyID'));
                
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
        };

        this.removeArrayElement = function(array, element)
        {
            array = jQuery.grep(array, function(value)
            {
                return value !== element;
            });
            return array;
        };

        //returns an array
        this.getPlaylistOrder = function()
        {
            //return JSON.parse(store.get('playListOrder'));
            var playlistSongs = store.get(this.playListIndexKey);
            return (typeof playlistSongs !== 'undefined') ? playlistSongs.split(',') : [];
        };
        // accepts array of values
        //boy I should use some assertions
        this.savePlaylistOrder = function(storeOrder)
        {
            store.set(this.playListIndexKey, storeOrder.join(','));
        };

        // just for testing ...
        // or when crap happens
        this.makePlaylistIndex = function()
        {
            var prefix = this.prefix; // help out with scope
            var order = [];

            store.forEach(function(key, val)
            {
                if (key.indexOf(prefix) !== -1) {
                    order.push(key);
                }
            });
            this.savePlaylistOrder(order);
        };

        this.getLastSong = function()
        {
            var storageKey = this.getPlaylistOrder().pop();
            console.log('Last Song: ' + storageKey);
            return store.get(storageKey);
        };
        // this works
        this.countSongs = function()
        {
            var count = this.getPlaylistOrder().length;
            if (!isNaN(count))
            {
                $('#numSongs').empty();
                $('#numSongs').append('Songs: ' + count);
                if(count > 0) { $('#abovefooter').show(); }else{ $('#abovefooter').hide();}
            }
            return count;
        };

        function pad(num, size)
        {
            var s = num + "";
            while (s.length < size)
                s = "0" + s;
            return s;
        };
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
        };

        function convertSecondsToMinutes(songSeconds)
        {
            var minutes = 0;
            // convert seconds to minutes and seconds
            minutes = Math.floor(songSeconds / 60);
            //minutes = pad(minutes, 2);
            return (isNaN(minutes)) ? 0 : minutes;
        };

        function convertMinutesToHours(songMinutes)
        {
            var hours = 0;
            // convert seconds to minutes and seconds
            hours = Math.floor(songMinutes / 60);
            return (isNaN(hours)) ? 0 : hours;
        };

        function addSongTimes(song1, song2)
        {
            return (convertToSeconds(song1) + convertToSeconds(song2));
        };

        this.clearSongs = function()
        {
            var re = new RegExp('^(' + this.prefix + ')');
            Object.keys(window.localStorage).forEach(function(key)
            {
                if (re.test(key))
                {
                    //window.localStorage.removeItem(key);
                    store.remove(key);
                }
            });
            // remove index
            store.remove(this.playListIndexKey);
            return true;
        };

        this.countTime = function()
        {
            var songData;
            var prefix = this.prefix;
            var seconds = 0;
            var minutes = 0;
            var hours = 0;
            var secondsOutput = 0;
            var count = 0;  // sanity check 

            store.forEach(function(key, val)
            {
                if (key.indexOf(prefix) !== -1)
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
            return (count === this.countSongs()) ? seconds : -1;
        };

        this.addSong = function(JSONObject, fromStorage)
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
            var count = this.countSongs();

            // add song
            // Store an object literal - store.js uses JSON.stringify under the hood

            newKey = this.prefix + count + Math.floor((Math.random() * 1000) + 1);
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

            var newOrder = this.getPlaylistOrder();
            //console.log('lastOrder is: ' + newOrder);
            newOrder.push(newKey);
            //console.log('newOrder is: ' + newOrder);
                
            if (count < newOrder.length)
            {
                // console.log('Song added successfully.');
                retVal = true;
                this.savePlaylistOrder(newOrder);
            }
            // re-render playlist
            this.renderSongs();
            return newKey;
        };

        // this works INDIVIDUALLY ONLY
        this.removeSong = function(localStorageKey)
        {
            var retVal = false;
            // assert
            // console.log('Removing: ' + localStorageKey);
            var count = this.countSongs();

            // delete
            store.remove(localStorageKey);
            var newOrder = this.removeArrayElement(this.getPlaylistOrder(), localStorageKey);
            this.savePlaylistOrder(newOrder);
                
            // assert good delete
            if (count > newOrder.length)
            {
                // console.log('Song removed successfully.');
                retVal = true;

                // update song positions
                this.renderSongs();
            }
            return retVal;
        };

        function renderLocalStorageToDiv(songs)
        {
            var divRow, indexDiv, indexNum, textDiv, songBPMInfo, 
                    songKeyInfo, songTimeInfo, songPlayTimeInfo, 
                    songTitleInfo, songArtistInfo, draggable, listItem, 
                    badge, buttClick, minusSpan, buttonDiv, songData;

            //var listIndex = 0;
            var hours, minutes, seconds, playTimeLength, listIndex = 0;
            var playtimeInfoText = '0:00:00';

            var fragment = document.createDocumentFragment();

            //var songs = this.getPlaylistOrder();

            //output the correct order
            //$.each(songs, function(key, index)
            for(var index = 0; index < songs.length; index++ )
            {
                //songData = store.get(index);
                songData = store.get(songs[index]);

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
                draggable.setAttribute("data-songKeyID", songs[index]);
                // draggable.appendChild(listText);
                //draggable.innerHTML = listText;

                listItem = document.createElement('li');
                //listItem.classList.add('list-group-item');
                $(listItem).addClass('list-group-item');
                // required for re-sort
                //listItem.setAttribute("data-songPosition", songData.songPosition);
                listItem.setAttribute("data-songKeyID", songs[index]);
                listItem.setAttribute("data-songPlaytime", songData.songPlaytime);

                //listItem.appendChild(draggable);

                divRow.appendChild(indexDiv);

                divRow.appendChild(textDiv);
                //if (!window.browserInfo.areWeMobile())
                {
                    badge = document.createElement('span');
                    $(badge).addClass('hidden-xs hidden-sm');
                    badge.style.cssFloat = "right";

                    // required for deleting item
                    
                    buttClick = document.createElement('button');
                    $(buttClick).addClass('btn btn-warning removebutton btn-xs');
                    buttClick.setAttribute("type", 'button');
                    buttClick.setAttribute("data-songKeyID", songs[index]);

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
               //*
                playTimeLength = playtimeInfoText;
                seconds = addSongTimes.call(undefined, playTimeLength, songData.songPlaytime);
                minutes = convertSecondsToMinutes(seconds);
                hours = convertMinutesToHours(minutes);
                playtimeInfoText = hours + ':' + pad(minutes - hours * 60, 2) + ':' + pad((seconds - minutes * 60), 2);
                // */
            }

            return fragment;
        };

        this.renderSongs = function()
        {
            spinner.show();

            // this is the last one used
            //renderLocalStorage();
            
            var songs = this.getPlaylistOrder();
            var fragment = renderLocalStorageToDiv(songs);
            //console.log('mylistID is available in renderSongs scope? : ' + this.myListID);
            
            var DOMElement = '#' + this.myListID;
            $(DOMElement).empty();
            $(DOMElement).append(fragment);
            
            this.countTime();    // this should be done on storage event
            this.countSongs();    // this should be done on storage event but doesn't always for some reason 
            // this is for testing and is to be used by the search

            spinner.hide();

            // this is going to change but it is the list order in a single storage space
            //makeOrderIndex();
        };
    }
    return new myPlaylist();
    /*
    {
        myListID: function() {return myListID; },
        prefix: function() { return prefix; },
        countTime: countTime,
        countSongs: countSongs,
        renderSongs: renderSongs
    } */
}());

$(document).ready(function() {

    //var playlisting = window.playlist; //playlistFun();

    $(window).bind('storage', function(event) {
        //alert(event + ': means storage changed');
        window.playlist.countTime();
        window.playlist.countSongs();
        //renderSongs();
    });

    if (document.getElementById(playlist.myListID))
    {
        window.playlist.renderSongs();

        var list = document.getElementById(playlist.myListID);

        list.addEventListener('slip:beforeswipe', function(e) {
            //alert('swipe started ...');
            //console.log(e.target);
        }, false);

        list.addEventListener('slip:swipe', function(e) {
            var storageSongID = e.target.getAttribute('data-songKeyID');
            e.target.parentNode.removeChild(e.target);
            window.playlist.removeSong(storageSongID);
            //     }
        }); //, false);

        list.addEventListener('slip:reorder', function(e) {
            e.target.parentNode.insertBefore(e.target, e.detail.insertBefore);
            // updateOrder() also changes playlistTiming 
            // so I don't have to renderSongs()
            window.playlist.savePlaylistOrder(window.playlist.updateOrder());
            return false;
        }); //, false);

        new Slip(list);

        $(document).on("click", '.removebutton', function() {
            window.playlist.removeSong(this.getAttribute('data-songKeyID'));
        });
    }
    /*
     $(document).on("click", '#exportbutton', function() {
     exportSongs();
     });
     */
    $(document).on("click", '#clearbutton', function() {
        window.playlist.clearSongs();
        var DOMElement = '#' + window.playlist.myListID;
        $(DOMElement).empty();
        //renderSongs();
    });

}); /* end ready */

// this works ONLY on JSON data from the database


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

function loadLocalStorageData(jsonData)
{
    clearSongs();
    for (var i = 0; i < jsonData.length; i++) {
        addSong(jsonData[i]);
    }
}