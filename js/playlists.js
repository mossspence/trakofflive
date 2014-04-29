"use strict";

function ListViewModel() {
    var url = '../api/playlists/';
    var self = this;
    self.listEntry = ko.observableArray();

    $.ajax(url, {
        success: function (data) {
            var tableData = jQuery.parseJSON(data);
            tableData = addUrlToData(url, tableData);
            self.listEntry(tableData);
        }
    });
}

function addUrlToData(url, tableData)
{
    for(var i=0; i < tableData.length; i++)
    {
        tableData[i].url = url + tableData[i].id;
    }
    return tableData;
}

$(document).ready(function() {    
     ko.applyBindings(new ListViewModel(), document.getElementById('listView'));
});



