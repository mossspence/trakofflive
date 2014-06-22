<?php

namespace moss\musicapp\exporter;

use Swift_Mailer;
//use Silex\Provider\SwiftmailerServiceProvider; //\Provider  SwiftmailerServiceProvider
// use Swift
// require_once '/path/to/swift_required.php'

/**
 * Description of sendMailToAdmin
 *
 * @author mosspence
 * get senders and recipients and then send a message
 */
class sendMessageTo implements \SplObserver
{
    private $smtp_server;
    private $smtp_port;
    private $parameters;
    private $message;
    
    public function __construct($templateDir, $appname = 'trakofflive')
    {
        //$config = new moss\musicapp\logger\SQLLiteMemoryHelper();
        //$this->settings = $config->fetchSettings();
        $this->parameters = array();
        $this->parameters['appname'] = $appname;
        $this->parameters['templateDir'] = $templateDir;
        $this->smtp_server = ini_get('SMTP');
        $this->smtp_port = ini_get('smtp_port');
    }
    public function update(\SplSubject $subject)
    {
        if($subject->getfield('title'))
        {
            // $_SERVER["SERVER_NAME"] should be $request->getHost() or getHttpHost();
            // that means the list was saved
            $this->parameters['playlist_title'] = $subject->getfield('title');
            $this->parameters['playlist_url'] = '//' . $_SERVER["SERVER_NAME"]
                . '/' . $this->parameters['appname'] . '/myplaylist/' 
                . $subject->getfield('urlTitle');
        }
        if($subject->getfield('email'))
        {
            // that means I can email someone
            $this->parameters['email'] = $subject->getfield('email');
            $this->parameters['username'] = ucfirst(substr($this->parameters['email']
                , 0, strpos($this->parameters['email'], '@')));
        }
        //
        if($subject->getfield('hashTag'))
        {
            // that means a song or playlist should be sent immediately
            // so I should email to SMS
            $this->parameters['hashTag'] = $subject->getfield('hashTag');
        }
        
        if($subject->getfield('upload'))
        {
            // that means a song or playlist was added to the DB
            // so I should email to SMS
            $this->parameters['upload'] = $subject->getfield('upload');
        }
        $paramaters = $this->parameters;
        $this->message = messageFactory::makeMessage($paramaters);
        $this->sendEmail();
    }
    
    /* testing */
    function showObject($object)
    {
        return (!empty($this->$object))?$this->$object:"NULL";
    }
    public function sendEmail()
    {
        $numSent = 0;
    
        $transport = (!empty($this->smtp_server))
                ? \Swift_SmtpTransport::newInstance($this->smtp_server, $this->smtp_port)
                :NULL;
        if(!empty($transport))
        {
            $mailer = Swift_Mailer::newInstance($transport);

            $numSent = $mailer->send($this->message);
            
            // now send an SMS to the Admin/Resident DJ
        }
        return($numSent);
    }
}
