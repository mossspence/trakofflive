"use strict";
function saveValue(key, value)
{
    if(null !== key)
    {
        store.set(key, value);
    }
}

function getValue(key)
{
    if(key && window.localStorage.hasOwnProperty(key)){
        return store.get(key);
    }
    return null;
}

function getSongList()
{
    var playlist = storageToArray();
    var songIDList = [];

    // I only need the songIDs
    for (var i = 0; i < playlist.length; i++) {
        songIDList.push(parseInt(playlist[i].songID, 10));
    }
    return songIDList;
}

function getM3U8()
{
    var songIDList = getSongList();
    var url = '../api/export/';

    $.ajax({
        type: 'POST',
        url: url,
        /* processData: false, */
        /* dataType: "JSON",   */
        data: {playlist: songIDList}
    })
    .done(function(msg){
        //alert( "Data Saved: " + msg );
        //console.log('response: on ' + JSON.stringify(songIDList) + ' is: ' + msg);
        $('#playlistSongs').empty();    // unnecessary
        $('#playlistSongs').append(msg);
        // unhide $('#playListScroll').unhide();
    })
    .fail(function(jqXHR, textStatus) {
        //alert("Request failed: " + textStatus);
    });
}

function ExportViewModel() {
    // Data
    var self = this;
    self.localTitleKey = 'playlistTitle';
    self.localCommentsKey = 'playlistComment';
    self.localEmailKey = 'playlistEmail';
    self.playlistTitle = ko.observable();
    self.playlistComments = ko.observable();
    self.songList = ko.observableArray();
    self.playlistEmail = ko.observable();
    self.updateMessage = ko.observable(false);

    // Operations
    this.postlist = function() {
        // POST params to DB

        var songListBasic = getSongList();
        self.songList(getSongList());        
        var plTitle = self.playlistTitle;
        var plComment = self.playlistComments;
        var plEmail = self.playlistEmail;
        
        var data = {
            'playlistTitle': plTitle,
            'playlistComments': plComment,
            'playlist': songListBasic,
            'email': plEmail
        };
        // */
        
        self.url = '../api/savelist/';
        
        $.ajax(self.url, {
            type: 'POST',
            data: data,
            success: function (response) {
                self.updateMessage('Playlist has been posted to your favourite DJ. Expect your mix in the mail.');
                $('#updateMessage').fadeOut(5000);
                self.clearExportData();
                // I should also delete the current playlist from local storage
                clearSongs();

                self.playlistTitle(null);
                self.playlistComments(null);
                self.playlistEmail(null);
                self.songList(null);// an example on the knockout website showed 'undefined' but I'm pretty sure null is the way to go

                // maybe even disable the submit button
                countTime();
                countSongs();
                getM3U8(); 
            }
        });        
    };
    this.update = function() {
        self.songList(getSongList());
        
        self.playlistTitle(getValue(self.localTitleKey));
        if(null !== getValue(self.localTitleKey))
        {
            self.playlistTitle(getValue(self.localTitleKey));
        }
        if(null !== getValue(self.localCommentsKey))
        {
            self.playlistComments(getValue(self.localCommentsKey));
        }
        if(null !== getValue(self.localEmailKey))
        {
            self.playlistEmail(getValue(self.localEmailKey));
        }        
        //self.updateMessage();

        self.playlistTitle.subscribe(function (text) {
            //console.log('Title: ' + text);
            saveValue(self.localTitleKey, text);
        });
        self.playlistComments.subscribe(function (text) {
            //console.log('Comment: ' + text);
            saveValue(self.localCommentsKey, text);
        });
        self.playlistEmail.subscribe(function (text) {
            //console.log('Comment: ' + text);
            saveValue(self.localEmailKey, text);
        });        
    };
    this.clearExportData = function (){
        store.remove(self.localTitleKey);
        store.remove(self.localEmailKey);
        store.remove(self.localCommentsKey);
    };
}
$(document).ready(function() {

    countTime();
    countSongs();
    getM3U8();  // get the playlist

    var exportList = new ExportViewModel();
    ko.applyBindings(exportList, document.getElementById('mainContent'));
    exportList.update();    
});