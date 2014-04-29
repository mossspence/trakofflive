/*
{
    id: "4"
    filename: "/home/www/lib/musictest/Steely Dan - Aja (album).mp3"
    message: "(size: 57718394) could/would not analyze"
    timedate: "2014-02-27 16:25:41"
}
// eventually I will have to go full viewmodel from JSON object (with mapping)
var SettingsViewModel = ko.mapping.fromJS(data);

 * 
 */
"use strict";

function LogViewModel() {
    var url = '../api/logs/';
    var self = this;
    self.errorEntry = ko.observableArray();

    $.ajax(url, {
        type: 'GET', 
        success: function (data) {
            self.errorEntry(jQuery.parseJSON(data));
        }
    });
}

$(document).ready(function() {    
     ko.applyBindings(new LogViewModel(), document.getElementById('logView'));
});