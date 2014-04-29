
/* Thanks to chemicaloliver, Eric Bidelman,  et al.
 * 
 * http://stackoverflow.com/questions/4006520/using-html5-file-uploads-with-ajax-and-jquery 
 * https://github.com/chemicaloliver/PHP-5.4-Upload-Progress-Example
 * downloaded 19 April 2014
 */
"use strict";

//Holds the id from set interval
var interval_id = 0;

$(document).ready(function()
{
    document.getElementById('theFile').addEventListener('change', doPoll, false);
    document.getElementById('theFile').addEventListener('change', handleFileSelect, false);
    
    //jquery form options
    var options = {
        success: stopProgress(), //Once the upload completes stop polling the server
        error: stopProgress()
    };

    //Add the submit handler to the form
    function doPoll()
    {
        //Poll the server for progress
        interval_id = setInterval(function() {
        $.getJSON('../api/uploadprogress/', function(data)
        {
            //if there is some progress then update the status
            if (data)
            {
                $('#progress').val(data.bytes_processed / data.content_length);
                
                console.log('progress is at: ' + data.bytes_processed / data.content_length);
                
                $('#progress-txt').html('Uploading ' + Math.round((data.bytes_processed / data.content_length) * 100) + '%');
            }else
             {
                //When there is no data the upload is complete
                $('#progress').val('1');
                $('#progress-txt').html('Complete');
                stopProgress();
             }
        });
        }, 200);
    }
    
    function stopProgress()
    {
        clearInterval(interval_id);
    }

    function handleFileSelect(evt)
    {
        //var files = evt.target.files; // FileList object
        var file = document.getElementById('theFile').files[0]; //Files[0] = 1st file
        
        console.log('File info is: ' + file);
        
        var reader = new FileReader();

        reader.readAsText(file, 'UTF-8');
        reader.onload = sendTheFile(evt);
    }

    function sendTheFile(event)
    {
        var result = event.target.result;
        var fileName = document.getElementById('theFile').files[0].name;
        
        console.log('Filename is: ' + fileName);
        console.log('file object is: ' + result);
        
        $.post('../api/upload/songs/', { data: result, name: fileName });
    }
});


//more stuff from 
// http://www.html5rocks.com/en/tutorials/file/dndfiles/

/*

  function abortRead() {
    reader.abort();
  }

  function errorHandler(evt) {
    switch(evt.target.error.code) {
      case evt.target.error.NOT_FOUND_ERR:
        alert('File Not Found!');
        break;
      case evt.target.error.NOT_READABLE_ERR:
        alert('File is not readable');
        break;
      case evt.target.error.ABORT_ERR:
        break; // noop
      default:
        alert('An error occurred reading this file.');
    };
  }

  function updateProgress(evt) {
    // evt is an ProgressEvent.
    if (evt.lengthComputable) {
      var percentLoaded = Math.round((evt.loaded / evt.total) * 100);
      // Increase the progress bar length.
      if (percentLoaded < 100) {
        progress.style.width = percentLoaded + '%';
        progress.textContent = percentLoaded + '%';
      }
    }
  }

  function handleFileSelect(evt) {
    // Reset progress indicator on new file selection.
    progress.style.width = '0%';
    progress.textContent = '0%';

    reader = new FileReader();
    reader.onerror = errorHandler;
    reader.onprogress = updateProgress;
    reader.onabort = function(e) {
      alert('File read cancelled');
    };
    reader.onloadstart = function(e) {
      document.getElementById('progress_bar').className = 'loading';
    };
    reader.onload = function(e) {
      // Ensure that the progress bar displays 100% at the end.
      progress.style.width = '100%';
      progress.textContent = '100%';
      setTimeout("document.getElementById('progress_bar').className='';", 2000);
    }

    // Read in the image file as a binary string.
    reader.readAsBinaryString(evt.target.files[0]);
  }


 */