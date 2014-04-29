<?php
namespace moss\standard;
/**
 * Description of Messages
 * - holds messages, warnings and errors for 'whole' script
 *
 * @category   Messaging Holding Centre
 * @author     Mark Spence mosspence at gmail dot com
 * @copyright  none
 * @deprecated NOT
 * @method constructor
 * @method addError public load error into vector
 * @method addWarning public load warning into vector
 * @method addMessage public load message into vector
 * @method countErrors public count number of errors
 * @method countWarnings public count number of warnings
 * @method countMessages public count number of messages
 * @method showErrors public return vector
 * @method showWarnings public return vector
 * @method showMessages public return vector
 */
class Messages {

    public static $errors = array();
    public static $warnings = array();
    public static $messages = array();

    function Messages() {
        self::$errors = array();
        self::$warnings = array();
        self::$messages = array();
    }

    public static function addError($string) {
        self::$errors[] = $string;
    }

    public static function addMessage($string) {
        self::$messages[] = $string;
    }

    public static function addWarning($string) {
        self::$warnings[] = $string;
    }

    public static function countErrors() {
        $numOfErrors = count(self::$errors);
        return $numOfErrors;
    }

    public static function countWarnings() {
        $numOfWarnings = count(self::$warnings);
        return $numOfWarnings;
    }

    public static function countMessages() {
        $num = count(self::$messages);
        return $num;
    }

    public static function showErrors() {
        foreach (self::$errors as $error) {
            echo "$error <br />\n";
        }
    }

    public static function showWarnings() {
        foreach (self::$warnings as $warning) {
            echo "$warning <br />\n";
        }
    }

    public static function showMessages() {
        foreach (self::$messages as $message) {
            echo "$message <br />\n";
        }
    }
    
    public static function clearErrors() {
        self::$errors = NULL;
        self::$errors = array();
    }

    public static function clearWarnings() {
        unset(self::$warnings);
        self::$warnings = array();
    }

    public static function clearMessages() {
        unset(self::$messages);
        self::$messages = array();
    }

}

?>
