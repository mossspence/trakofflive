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

function userSettingsViewModel() {
    // Data
    var self = this;
    self.localHashTagKey = 'playlistHashTag';
    self.localBPMKey = 'playlistBPM';
    self.localTimeKey = 'playlistTime';
    self.localKeyKey = 'playlistKey';
    self.playlistHashTag = ko.observable();
    self.playlistBPM = ko.observable();
    self.playlistKey = ko.observable();
    self.playlistTime = ko.observable();
    self.updateMessage = ko.observable(false);

    // Operations
    this.saveUserSettings = function() {
        // POST params to DB

        var plHashTag = self.playlistHashTag;
        
        var data = {
            'hashtagTitle': plHashTag
        };
        
        self.url = '../api/savehashtag/';
        
        $.ajax(self.url, {
            type: 'POST',
            data: data,
            success: function (response) {
                
                self.playlistHashTag(null);

                self.updateMessage('Hashtag has been posted to your favourite DJ.');
                $('#updateMessage').show();
                $('#updateMessage').fadeOut(5000);
            }
        });        
    };
    this.update = function() {

        if(null !== getValue(self.localHashTagKey))
        {
            self.playlistHashTag(getValue(self.localHashTagKey));
        }

        if(null !== getValue(self.localBPMKey))
        {
            self.playlistBPM(getValue(self.localBPMKey));
        }

        if(null !== getValue(self.localKeyKey))
        {
            self.playlistKey(getValue(self.localKeyKey));
        }

        if(null !== getValue(self.localTimeKey))
        {
            self.playlistTime(getValue(self.localTimeKey));
        }

        self.playlistHashTag.subscribe(function (text) {
            //console.log('hashtagTitle: ' + text);
            saveValue(self.localHashTagKey, text);
        });
        self.playlistBPM.subscribe(function (text) {
            //console.log('hashtagTitle: ' + text);
            saveValue(self.localBPMKey, text);
        });
        self.playlistKey.subscribe(function (text) {
            //console.log('hashtagTitle: ' + text);
            saveValue(self.localKeyKey, text);
        });
        self.playlistTime.subscribe(function (text) {
            //console.log('hashtagTitle: ' + text);
            saveValue(self.localTimeKey, text);
        });
    };
    this.clearExportData = function (){
        //store.remove(self.playlistHashTag);
    };
}
$(document).ready(function() {

    var settingsList = new userSettingsViewModel();
    ko.applyBindings(settingsList, document.getElementById('mainContent'));
    settingsList.update();
});