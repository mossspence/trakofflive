<?php

namespace moss\musicapp\exporter;
use moss\standard;
//use Silex\Provider\SwiftmailerServiceProvider; //\Provider  SwiftmailerServiceProvider
// use Swift
// require_once '/path/to/swift_required.php'

/**
 * Description of sendMailToAdmin
 *
 * @author mosspence
 * get senders and recipients and then send a message
 */
class sendMailToAdmin implements \SplObserver
{
    private $senders;
    private $recipients;
    private $blindcopies;
    private $messageBody;
    private $htmlBody;
    private $title;
    private $url;
    private $subjectBody;
    private $transport;
    private $message;
    private $mailer;
    private $appName = 'trakofflive';
    
    public function __construct($mailer, $message,
            $recipients, $senders, $title, $blindcopies = NULL)
    {
        $this->recipients = self::setAndCheckEmails($recipients);
        $this->senders = self::setAndCheckEmails($senders);
        $this->blindcopies = self::setAndCheckEmails($blindcopies);
        
        $this->title = standard\sanitizeValues::sanitizeString($title);
        
        $this->message = $message; // Swift_Message::newInstance();
        $this->mailer = $mailer;  // Swift_Mailer::newInstance($transport);
    }
    public function update(\SplSubject $playList)
    {
        if($playList->getfield('title'))
        {
            // that means the list was saved and I can email someone
        }
        
        //
        if($playList->getfield('hashTag'))
        {
            // that means a song or playlist should be sent immediately
            // so I should email to SMS
        }
    }
    public function setAndCheckEmails($input)
    {
        $output = NULL;
        if(is_array($input) && !empty($input))
        {
            foreach($input as $address => $name)
            {
                if(is_int($address))
                {
                    $key = standard\sanitizeValues::sanitizeEmail($name);
                    $output[$key] = standard\sanitizeValues::sanitizeString(ucfirst(substr($name, 0, strpos($name, '@'))));
                }else
                 {
                    $key = standard\sanitizeValues::sanitizeEmail($address);
                    $output[$key] = standard\sanitizeValues::sanitizeString($name);
                 }
            }
        }
        return $output;
    }
    public function makeSubject()
    {
        $this->subjectBody = '[' . $this->appName  . '] Playlist - ' . $this->title . ' - Saved';
    }
    public function makeMessage($playlistTitle, $username = '')
    {
        // make the message
        $this->url = 'http://' . $_SERVER["SERVER_NAME"] . '/' . $this->appName
                . '/myplaylist/' . $playlistTitle;
        
        $message = "Please pick up your playlist at " . $this->url;
        
        $messageHTML = 'Please pick up your playlist at <a href="' . $this->url
                . '">' .  $this->title . '</a>';
        
        $messageBody = (!empty($username)) 
                ? 'Hi there ' . $username  . ', ' . PHP_EOL . PHP_EOL
                : 'Hi there, ' . $this->appName  . ' User,' . PHP_EOL . PHP_EOL;
        $messageBody .= 'Your playlist is saved and will be mixed ASAP.' . PHP_EOL;
        $messageBody .=  PHP_EOL . $message . PHP_EOL . PHP_EOL;
        $messageBody .= 'Thank you.' . PHP_EOL . PHP_EOL;
        $messageBody .= 'Oneil Stuart Studios copyright ' . date('Y') . PHP_EOL;
        $messageBody .= 'http://oneilstuart.com/' . $this->appName . PHP_EOL;
        $this->messageBody = $messageBody;
        
        $htmlBody = (!empty($username)) 
                ? '<p>Hi there ' . $username  . ', </p>' . PHP_EOL . PHP_EOL
                : '<p>Hi there, ' . $this->appName  . ' User,</p>' . PHP_EOL . PHP_EOL;
 
        $htmlBody .= '<p>Your playlist is saved and will be mixed ASAP.</p>' . PHP_EOL;
        $htmlBody .= '<div style="font-size: 9px;">' . $messageHTML . '</div>' . PHP_EOL . PHP_EOL;
        $htmlBody .= '<p>Thank you.</p>' . PHP_EOL . PHP_EOL;
        $htmlBody .= '<p style="font-size: 5px; margin-top:40px;">';
        $htmlBody .= '<a href="http://oneilstuart.com/'. $this->appName .'">Oneil Stuart Studios</a> &copy; ' . date('Y') . PHP_EOL;
        $htmlBody .= '</p>'. PHP_EOL;
        

        $htmlBody = '<html><body><basefont face="arial, verdana" />
            <div style="font-size: 16px; float: left; height: 50px; width:100%; margin-bottom: 10px;">
            <img src="http://oneilstuart.com/images/websitelogo.jpg" 
            style="float: left; border: solid 1px #444; padding: 1px; margin-right:10px;" />
            Oneil Stuart Studios</div>
            <hr style="border: solid 1px #444; padding: 1px; width: 80%; clear: both;">
            <div>' . $htmlBody . '</div></html>';
        
        $this->htmlBody = $htmlBody;
    }
    /* testing */
    public function showSenders()
    {
        $count = 0;
        foreach ($this->senders as $address => $name)
        {
            echo '<br />Name: ' . $name . ' address: ' . $address; $count++;
        }return $count;
    }
    /* testing */
    public function showRecipients()
    {
        $count = 0;
        foreach ($this->recipients as $address => $name)
        {
            echo '<br />Name: ' . $name . ' address: ' . $address; $count++;
        }return $count;
    }
    /* testing */
    function showObject($object)
    {
        return (!empty($this->$object))?$this->$object:"NULL";
    }
    public function sendEmail()
    {
        self::makeSubject();    // this seems kinda silly

        $this->message->setSubject($this->subjectBody);
        $this->message->setFrom($this->senders);
        $this->message->setTo($this->recipients);
        if(!empty($this->blindcopies))  {   $this->message->setBcc($this->blindcopies);     }
        $this->message->setBody($this->messageBody, 'text/plain');
        $this->message->addPart($this->htmlBody, 'text/html');

        $numSent = $this->mailer->send($this->message);
        return($numSent);
    }
}
