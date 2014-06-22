<?php

namespace moss\musicapp\exporter;
use Twig_Environment;
use Twig_Loader_Filesystem;
use moss\musicapp\logger;
use Swift_Message;
/**
 * Description of messageFactory
 *
 *  requires SwiftMailer
 * 
 * @author mosspence
 * 
 * - get some paramaters/arguments
 * - make a message
 * - return message
 */
class messageFactory {
    
    public  $message;
    private static $twig;
    private static $template;
    private static $messageTwigs;
    
    private function __construct() { }
    
    public static function init($templateDir)
    {
        //Twig_Autoloader::register();
        
        self::$twig = new Twig_Environment(
                new Twig_Loader_Filesystem($templateDir));
        
        self::$messageTwigs = array();
        self::$messageTwigs['SMS'] = 'SMSNotification.txt.twig';
        self::$messageTwigs['playlistSaved'] = 'email.html.twig';
        self::$messageTwigs['songsAdded'] = 'email.html.twig';
    }
    
    public function getMessage()
    {
        return self::$message;
    }
    // populates a Twig template and returns it
    public static function getInstance($paramaters)
    {
        self::init($paramaters['templateDir']);
        // i should make this so it can send more than one message or ...

        // if email -> send email
        if(array_key_exists('email', $paramaters))
        {
            if(array_key_exists('upload', $paramaters))
            {
                self::$template = self::$twig->loadTemplate(self::$messageTwigs['songsAdded']);
            }else
             {
                // assert !empty($paramaters['playlist_title'])
                self::$template = self::$twig->loadTemplate(self::$messageTwigs['playlistSaved']);
             }
        }else
         {
            // if hashTag && !email -> send SMS
            // assert !empty($paramaters['hashTag'])
            self::$template = self::$twig->loadTemplate(self::$messageTwigs['SMS']);
         }

        return self::$template;
    }
    
    // returns Swift Message
    public static function makeMessage($paramaters)
    {
        $template = self::getInstance($paramaters);
        
        $config = new logger\SQLLiteMemoryHelper();
        $settings = $config->fetchSettings();
        
        $message = Swift_Message::newInstance();

        $message->setSubject(self::$template->renderBlock('subject', $paramaters));

        $message->setFrom(array($settings['residentDJ'] => 'Resident DJ'));
        
        $recipient = (array_key_exists('email', $paramaters))
                    ?   $paramaters['email']
                    :   $settings['DJSMSaddress'];

        $message->setTo($recipient);

        //if(!empty($this->blindcopies))  {   $message->setBcc($blindcopies); }
        
        $message->setBody(self::$template->renderBlock('body_text', $paramaters), 'text/plain');
        if(array_key_exists('email', $paramaters))
        {
            $message->addPart(self::$template->renderBlock('body_html', $paramaters), 'text/html');
        }

        return $message;
    }
}
