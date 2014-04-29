/* 
 * {
 *  "totalSongs":42, 
 *  "songsDone":0, 
 *  "currentAnalyzedFile":"song1", 
 *  "memoryUsage:"390736,
 *  "baseDir":"\/home\/music"
 * }
 */
"use strict";

$(document).ready(function() {
    $('#startButton').click(function() {
        startDirectoryRead();
        (this).disabled = true;
    });
});

function prettyTimeOutput(time)
{
    var returnTime = new Array();
    // http://stackoverflow.com/questions/3733227/javascript-seconds-to-minutes-and-seconds

    // Now if you also want to get the full hours too, divide the number of total seconds by 3600 (60 minutes/hour · 60 seconds/minute) first, then calculate the remaining seconds:
    returnTime['hours'] = Math.floor(time / 3600);
    time = time - returnTime['hours'] * 3600;
    
    returnTime['minutes'] = Math.floor(time / 60);
    // And to get the remaining seconds, multiply the full minutes with 60 and subtract from the total seconds:

    returnTime['seconds'] = time - returnTime['minutes'] * 60;
    
    // Output like "1:01" or "4:03:59" or "123:03:59"
    var ret = "";

    if (returnTime['hours'] > 0)
        ret += "" + returnTime['hours'] + ":" + (returnTime['minutes'] < 10 ? "0" : "");

    ret += "" + returnTime['minutes'] + ":" + (returnTime['seconds'] < 10 ? "0" : "");
    ret += "" + returnTime['seconds'];
    return ret;    
    
    //return returnTime; 
}

var startTime; // this global is referred to many times, perhaps too many times?

function startDirectoryRead()
{
    var url = 'songFileListing.php';  // '/api/reader'
    startTime = new Date() / 1000;

    // spin that sprite
    spinner.show();

    $.get(url, function(data) {
        var obj = jQuery.parseJSON(data);
        console.log('status message: ' + obj.message);
        console.log('memory usage: ' + obj.memoryUsage);
        //console.log('files read: ' + obj.totalSongs);
        console.log('base directory of files to be analyzed: ' + obj.baseDir);
        $('#directoryReadMessage').empty();
        $('#directoryReadMessage').append(obj.message);        
        $('#progressPanel').show();

        $('#startTime').empty();
        $('#startTime').append(new Date().toLocaleString());
    })
    .done(function() {
        // check status
        doPoll();       
    })
    .fail(function() {
        alert("Error on directory read. Check log.");
        //var obj = jQuery.parseJSON(data);
        console.log('status message: could not even read the motherfunkin page');
     });
}

var url = 'getstatus.php';   // '/api/loadstatus'
var pollTime = 200;

// estimate time remaining
var SMOOTHING_FACTOR = .05;
var averageSpeed = 0;
var lastSpeed = 0; // (startTime - currentTime) * (progress * .01)
//var songsRemaining = parseInt(obj.totalSongs - obj.songsDone)
/*
var MYprogress = 1;
var MYSongs = 1;
*/

function doPoll() {

    var progress = 0;
    $.get(url, function(data) {
        // var obj = jQuery.parseJSON(data);
        // console.log('Total Songs :' + obj.totalSongs);
    })
    .done(function(data) {
        // process results here
        var obj = jQuery.parseJSON(data);
        progress = calcProgress(obj.totalSongs, obj.songsDone);
        //var progress = parseInt(MYSongs / obj.totalSongs * 100, 10);

        console.log(progress + '% Complete');

        var currentTime = new Date() / 1000;
        var timeElapsed = Math.round((currentTime - startTime), 4);

        // for shits and giggles
        // MYSongs++;

        lastSpeed = Math.round((currentTime - startTime), 4) * (progress * .01);
        averageSpeed = SMOOTHING_FACTOR * lastSpeed + (1 - SMOOTHING_FACTOR) * averageSpeed;

        // this is only useful after approx 30% completion
        var projectedTimeToCompletion = (obj.totalSongs + obj.songsDone)/ averageSpeed;

        // (TimeTaken / linesProcessed) * linesLeft=timeLeft
        // var songsRemaining = obj.totalSongs - obj.songsDone;     // OLD WAY
        var songsRemaining = obj.totalSongs;

        if ((progress > 30 && progress < 45) || (progress > 95 && progress < 98) || 100 === progress)
        {
            var day = 24 * 60 * 60;
            console.log('Projected Time to Completion: ' + projectedTimeToCompletion + ' seconds');
            //var bigGuess = new Date(new Date().getTime() + (((timeElapsed / obj.songsDone) * songsRemaining) * 1000));
            //var estimatedEndTime = bigGuess.toLocaleString();
            var estimatedEndTime = getEstimateEndTime(startTime, obj.totalSongs + obj.songsDone, obj.songsDone);

            $('#ProjectedTime').empty();
            $('#ProjectedTime').append(estimatedEndTime);
        }
        console.log('Time Elapsed: ' + timeElapsed + ' seconds');
        $('#elapsedTime').empty();
        $('#elapsedTime').append(timeElapsed + ' seconds');
        var timeRemaining = prettyTimeOutput(((timeElapsed / (obj.totalSongs + obj.songsDone)) * songsRemaining).toFixed(2));
        console.log('Estimated Time Remaining : ' + timeRemaining);
        $('#estimatedTime').empty();
        $('#estimatedTime').append(timeRemaining);

        //var progress = parseInt(MYSongs / obj.totalSongs * 100, 10);
        //document.getElementById('progressbar').style.width = progress + '%';
        $('#progressbar').css('width', progress + '%');
        document.getElementById('bartext').innerHTML = progress + '% Complete';
        //$('#bartext').text.innerHTML = progress + '%';
    })
    .fail(function() {
        alert("error. what the DOOR just happend.");
    })
    .always(function(data) {
        // var obj = jQuery.parseJSON(data);
        // check if analysis is finished

        if (!isNaN(progress) && progress < 100)
        {
            // i should check a status message to verify that there is either
            // - 0 files and just started (waiting)
            // - x files and stalled
            setTimeout(doPoll, pollTime);
        }else{
            var timeTook = prettyTimeOutput(((new Date() / 1000) - startTime).toFixed(2));
            var checkLogMessage = 'Check the <a href="../logs/">log</a> for errors.';
            var finishMessage = "Finished and process took " + timeTook + " hh:mm:ss.us" + ' ' + checkLogMessage;
            //alert(finishMessage);
            // stop that sprite
            spinner.hide();
            $('#directoryAnalyzeMessage').empty();
            $('#directoryAnalyzeMessage').append(finishMessage);        
            $('#analyzeFileprogress').show();            
            console.log(finishMessage);
         }
    });
}

// http://stackoverflow.com/questions/18082/validate-numbers-in-javascript-isnumeric
function isNumber(n) {
    console.log('isNumber Test: ' + n);
  return !isNaN(parseFloat(n)) && isFinite(n);
}

/*
 *  this should work as :total decreases
 *   
 */
function calcProgress(totalRemaining, completed)
{
    var progress = 0;
    if(!isNaN(completed) && !isNaN(totalRemaining) && (completed > 0) && (totalRemaining >= 0))
    {
        progress = parseInt((completed / (totalRemaining + completed)) * 100, 10);
    }
    return progress;
}
/*
 *  this function is currently broken. 
 *  The script crashed when there were still songs left to be analyzed.
 *   
 *  Returning to the page after the crash gives whack numbers.
 *  total = 682 (songs to be analyzed)
 *  completed = 22000 (songs analyzed)
 *  
 *  this worked when I wasn't decreasing :total
 */
function oldcalcProgress(total, completed)
{
    var progress = 0;
    if(!isNaN(completed) && !isNaN(total) && (completed > 0) && (total > 0))
    {
        progress = parseInt(completed / total * 100, 10);
    }
    return progress;
}
function getEstimateEndTime(startTime, totalSongs, songsDone)
{
    var nowTime = new Date();
    var bigGuess = new Date(nowTime.getTime() + (((((nowTime / 1000) - startTime) / songsDone) * (totalSongs - songsDone)) * 1000));
    return bigGuess.toLocaleString();
}
function StatusViewModel()
{
    var self = this;
    self.statusInfo = ko.observableArray();
    self.directoryReadMessage = ko.observable();
    self.progress = ko.computed(calcProgress(statusInfo.totalSongs, statusInfo.songsDone)); 
    self.directoryAnalyzeMessage = ko.observable();
    self.analyzeFileprogress = ko.observable();
    self.bartext = ko.observable();
    
    self.ProjectedTime = ko.computed(getEstimateEndTime(startTime, statusInfo.totalSongs, statusInfo.songsDone)); //  observable();
    self.elapsedTime = ko.computed(prettyTimeOutput(((new Date() / 1000) - startTime).toFixed(2))) // observable();
    self.estimatedTime = ko.computed(prettyTimeOutput(((((new Date() / 1000) - startTime) / statusInfo.songsDone) * (statusInfo.totalSongs - statusInfo.songsDone))).toFixed(2)); // observable();
    
    $.ajax(url, {
        success: function (data) {
            self.statusInfo(jQuery.parseJSON(data));
        }
    });
}

function timeViewModel()
{
    //self.startTime = ko.observable();
}