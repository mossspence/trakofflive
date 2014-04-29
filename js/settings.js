/*
 {
    baseDir: "/home/www/lib/musictest"
    coverDir: "imglib/covers"
    thumbSize: "40"
    maxFileSize: "41943040"
    maxSeconds: "30"
}
// eventually I will have to go full viewmodel from JSON object (with mapping)
var SettingsViewModel = ko.mapping.fromJS(data);

 * 
 */
"use strict";
function SettingsViewModel() {
    // Data
    var url = '../api/loadersettings/';
    var self = this;
    self.baseDir = ko.observable();
    self.coverDir = ko.observable();
    self.thumbSize = ko.observable();
    self.maxFileSize = ko.observable();
    self.maxSeconds = ko.observable();
    self.textBatchFile = ko.observable();
    self.updateMessage = ko.observable(false);
    self.refresh = ko.observable(false);

    // Operations
    this.updateSettings = function() {
        // POST params to DB
        var plainJs = ko.toJS(this); //serializes this
        //console.log(plainJs);
        
        $.ajax(url, {
            type: 'POST',
            data: plainJs,
            success: function (plainJs, response) {
                self.updateMessage('Settings have been updated.');
                $('#updateMessage').fadeOut(5000);
                //alert('Your data was posted, bitchassmotherfucker!!!! Now get out of my sight, asshole!!!');
                //response = jQuery.parseJSON(response);
                console.log('response: ' + response);
                self.update();
            }
        });        
    };
    this.update = function() {
		var url = '../api/loadersettings/';
        $.ajax(url, {
			type: 'GET',
            success: function (datajs) {
                //var mapped = ko.mapping.fromJS(data);
                //this.update(mapped());
                var data = jQuery.parseJSON(datajs);
                self.baseDir(data.baseDir);
                self.coverDir(data.coverDir);
                self.thumbSize(data.thumbSize);
                self.maxFileSize(data.maxFileSize);
                self.maxSeconds(data.maxSeconds);
                self.textBatchFile(data.textBatchFile);
            }
        });
    };
}
$(document).ready(function() {
    var showSettings = new SettingsViewModel();
    ko.applyBindings(showSettings, document.getElementById('settingsInfo')); // ko.applyBindings(new SettingsViewModel());
    showSettings.update();          // SettingsViewModel().update();
    /*
        var data = getDataUsingAjax();
        console.log(data);
        SettingsViewModel.baseDir(data.baseDir);
        SettingsViewModel.coverDir(data.coverDir);
        SettingsViewModel.thumbSize(data.thumbSize);
        SettingsViewModel.maxFileSize(data.maxFileSize);
        SettingsViewModel.maxSeconds(data.maxSeconds);    */
});
