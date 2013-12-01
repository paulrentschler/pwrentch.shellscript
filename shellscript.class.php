<?php


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Class for creating shell scripts
 * 
 * PHP version 5
 * 
 * @author     Paul Rentschler <paul@rentschler.ws>
 * @since      30 November 2013
 * @since      30 December 2009
 */


/********************************************************************
 * DOCUMENTATION:
 *
 * It is expected that you will use this class as a base class and
 *   extend it to make your own shell script.
 * In doing so, you must replicate the tasks performed in the
 *   constructor or call parent:constructor()
 * 
 * You must define the valid configuration options that can be
 *   specified on the command line, but redefining, or continuing
 *   the $this->validConfigOptions array.
 * 'type' can be either "switch" or "value"
 * 'validate' should be blank or one of the following functions:
 *              ValidateString
 *              ValidateAlphaString
 *              ValidateAlphaNumericString
 *              ValidateEmailAddress
 *              ValidateDate
 *              ValidateWebUrl
 *              ValidateIPAddress
 *              ValidateFilename
 *              ValidatePath
 *              ValidateLocalPath
 *              ValidateMySQLServer
 *              ValidateMySQLDatabase
 *            You can also define and use your own validation
 *              functions or redefine any of these provided.
 *
 * Verbosity levels used for debug entries
 *   All of the methods below allow for debug messages to be output
 *     using the debug method.
 *   level 0 = outputting the errorMsg property when it's set
 *   level 1 = indicating when methods start and stop along with
 *               calling parameters
 *   level 2 = conditionals indicated (if, for, while, switch, etc)
 ********************************************************************/


/**
 * The basis for a shell script
 *
 * Provides the most commonly needed features for creating shell scripts
 * and is intended to be extented to create each shell script.
 * 
 * @author     Paul Rentschler <paul@rentschler.ws>
 * @since      30 November 2013
 * @since      30 December 2009
 */
class ShellScript
{
    /**
     * Holds the parsed configuration options from the command line
     * and/or the config file
     *
     * @var        array
     */
    protected $configOptions = array();


    /**
     * Indicates if the command line arguments have been read
     *
     * @var        boolean
     */
    protected $configurationRead = false;


    /**
     * Debug log file name
     *
     * Defaults to "debug.log" in the current directory.
     *
     * @var        string
     */
    protected $debugLogFilename = 'debug.log';


    /**
     * Indicates if debug mode is on or off
     *
     * If on, it outputs to self::debugLogFilename
     *
     * @var        boolean
     */
    protected $debugMode = false;


    /**
     * What array key in self::configOptions indicates the level of
     * verbosity for debug messages
     *
     * @var        string
     */
    protected $debugVerboseKey = 'verbose';


    /**
     * Describe what the shell script does
     *
     * @var        string
     */
    protected $description = 'A basic shell script';


    /**
     * Error message generated from any of the internal methods
     *
     * @var        string
     */
    protected $errorMsg = '';


    /**
     * Type of error message generated from any of the internal methods
     *
     * @var        string
     */
    protected $errorType = '';


    /**
     * Track the starting and stopping times for the timer methods
     *
     * @var        array
     */
    protected $scriptTimer = array();


    /**
     * Suppress displaying the "this software supplied with no warranty"
     * message when outputting the syntax
     *
     * @var        boolean
     */
    protected $suppressWarrantyWarning = false;


    /**
     * Configuration options defined for use with the script
     *
     * @var        array
     */
    protected $validConfigOptions = array();
    

    
    /**
     * Initialize the class
     * 
     * @return     void
     * @access     public
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      30 November 2013
     * @since      30 December 2009
     */
    public function __construct ()
    {
        $this->debug('__construct() called', 1);

        // initialize the configuration options
        $this->debug('initialize the configuration options', 2);
        $this->configOptions = array();
        $this->configurationRead = false;

        // define the valid configuration options
        $this->debug('define the two default valid configuration options', 2);
        $this->validConfigOptions = array(
            array(
                'shorttag' => 'h',       // -o style command line param
                'longtag' => 'help',     // --option style command line param
                'filetag' => '',         // key used in config file
                'configkey' => 'help',   // index in the configOptions array
                'type' => 'switch',      // can be "switch" or "value"
                'valuePlaceholder' = '', // used for the syntax if type=value
                'validate' => '',        // validation method to call
                'combine' => false,      // used if type=switch
                'description' => 'Display this syntax help info',
            ),
            array(
                'shorttag' => 'v', 
                'longtag' => '', 
                'filetag' => '', 
                'configkey' => 'verbose', 
                'type' => 'switch', 
                'valuePlaceholder' = '',
                'validate' => '', 
                'combine' => true,
                'description' => 'Write debugging info to '
                    .$this->debugLogFilename.'. Use multiple times to increase '
                    .'log verbosity.',
            ),
        );
                                         
        $this->debug('__construct() ended', 1);
    }
    
    
    
    /**
     * Echoes text to the screen with optional ANSI color coding
     *
     * Text and background colors are always reset to defaults unless
     * $reset = false.
     * 
     * @param      string  $text       a string containing the text to output
     * @param      string  $forecolor  a string indicating the foreground color
     * @param      boolean $bright     a boolean indicating if the foreground
     *                                 color should be the bright version
     * @param      string  $backcolor  a string indicating the background color
     * @param      boolean $reset      a boolean indicating if the colors
     *                                 should be reset to their defaults
     * @return     void
     * @access     public
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    public function cecho ($text, $forecolor = 'default', $bright = false,
        $backcolor = 'default', $reset = true
    ) {
        $this->debug('cecho('.$message.', '.$forecolor.', '
            .(($bright) ? 'true' : 'false').', '.$backcolor.', '
            .(($reset) ? 'true' : 'false').') called', 1);
      
        // define the default value
        $default = "\033[0m";
      
        // define the color codes
        $colorCodes = array(
            'black' => 0,
            'red' => 1,
            'green' => 2,
            'yellow' => 3,
            'blue' => 4,
            'magenta' => 5,
            'cyan' => 6,
            'white' => 7,
        );
      
      
        // if the default foreground and background are used, just display the text
        if ($forecolor == 'default' && $backcolor == 'default') {
            $this->debug('foreground and background colors are default', 2);
            echo $default;
            if ($message <> '') {
                $this->debug('outputting the message', 2);
                echo $message;
            }

        } else {
            /* DETERMINE THE COLOR CODES */

            // default codes (white on black)
            $foreCode = 37;
            $backCode = 40;

            // look up the provided colors to get the codes
            $forecolor = strtolower($forecolor);
            $backcolor = strtolower($backcolor);
            if (isset($forecolor) && array_key_exists($forecolor, $colorCodes)) {
                $foreCode = 30 + ((int) $colorCodes[$forecolor]);
                $this->debug('setting the foreground code to: '.$foreCode, 2);
            }
            if (isset($backcolor) && array_key_exists($backcolor, $colorCodes)) {
                $backCode = 40 + ((int) $colorCodes[$backcolor]);
                $this->debug('setting the background code to: '.$backCode, 2);
            }
        
        
            /* OUTPUT THE COLORED MESSAGE */
            // the color code
            $this->debug('building the escape sequence', 2);
            $escapeSeq = "\033[";
            $escapeSeq .= (($bright) ? '1' : '0');
            $escapeSeq .= (($foreCode <> '') ? ';'.$foreCode : '');
            $escapeSeq .= (($backCode <> '') ? ';'.$backCode : '');
            $escapeSeq .= 'm';
            echo $escapeSeq;
        
            // the message
            $msgLength = strlen($message);
            if ($msgLength > 0
                && strrpos($message, "\n") == $msgLength - 1
                && $backCode <> ''
                && $reset
            ) {
                $this->debug('the message ended with a carriage return and '
                    .'the background color is not the default', 2);
                if ($msgLength >= 2) {
                    $this->debug('outputting the message, resetting the color, '
                        .'then outputing the carriage return', 2);
                    echo substr($message, 0, $msgLength - 2);
                    echo $default;
                    echo "\n";
                }
            } else {
                $this->debug('the message has no carriage return or the '
                    .'background color is the default', 2);
                if ($message <> '') {
                    $this->debug('outputting the message', 2);
                    echo $message;
                }

                // reset the colors
                if ($reset) {
                    $this->debug('resetting the colors to the default', 2);
                    echo $default;
                }
            }
        }
      
        $this->debug('cecho() ended', 1);
    }
    
    
    
    /**
     * Write a debug message to the debugging file
     * 
     * Outputs a message to the debug log defined by self::debugLogFilename
     * if debug mode is turned on (i.e., self::debugMode == true)
     *
     * If $message == '' then self::errorMsg and self::errorType are used to
     * generate the debug entry
     *
     * The $verbosityLevel parameter works with self::debugVerboseKey to
     * specify which messages should be output to the debug log.
     * self::debugVerboseKey references a value in self::configOptions to
     * determine how much detail the user wants in the debug log. If
     * $verbosityLevel is less than or equal to the user-specified level, then
     * the message is added to the debug log.
     *
     * @param      string  $message         a string containing the message
     *                                      to write into the debug log
     * @param      integer $verbosityLevel  an integer indicating the minimum
     *                                      verbosity level required to write
     *                                      the message to the debug log
     * @return     void
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      30 November 2013
     * @since      30 December 2009
     */
    protected function debug ($message = '', $verbosityLevel = 0)
    {
        // see if debug mode is enabled
        if ($this->debugMode) {
            // indicate if we are outputting the message, yes by default
            $outputMsg = true;
        
            // if a message verbosity level is specified, see if it meets
            // the threshold to be added to the debug log
            if ($verbosityLevel > 0) {
                // don't output unless the message verbosity level is equal to
                // or below the script threshold
                $outputMsg = false;

                // determine if there is a script-wide verbosity threshold
                if ($this->debugVerboseKey <> ''
                    && isset($this->configOptions[$this->debugVerboseKey])
                    && is_numeric($this->configOptions[$this->debugVerboseKey])
                ) {
                    // is the message verbosity level above the threshold
                    if ($verbosityLevel <= $this->configOptions[$this->debugVerboseKey]) {
                        $outputMsg = true;
                    }
                }
            }
        
        
            if ($outputMsg) {
                // use self::errorMsg if $message is blank
                if (!isset($message) || $message == '') {
                    $message = $this->errorMsg;
                }
              
                // remove any carriage returns from the message
                $message = str_replace("\n", '', $message);
                $message = str_replace("\r", '', $message);

                // apply an indent based on the verbosity level
                for ($i = 2; $i <= $verbosityLevel; $i++) {
                    $message = '    '.$message;
                }
              
                // add a date/time stamp and carriage return
                $stamp = '['.date('Y-m-d H:i:s').'] ';
                $message = $stamp.$message."\n";
              
                // output the debug message to the file
                file_put_contents(
                    $this->debugLogFilename,
                    $message,
                    FILE_APPEND
                );
            }
        }
    }
    
    
    
    /**
     * Formats time into hours, minutes, seconds, and hundredths of a second
     * 
     * @param      float $totalSeconds  a float containing the total number of
     *                                  seconds to format
     * @return     string  a string indicating the number of hours, minutes,
     *                     seconds and hundredths of a second
     * @access     public
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    public function formatTime ($totalSeconds)
    {
        $this->debug('formatTime('.$totalSeconds.') called', 1);

        // break down the totalSeconds into whole hours
        if ($totalSeconds > 3600) {
            $this->debug('totalSeconds is over an hour', 2);
            $hours = floor($totalSeconds / 3600);
            $totalSeconds -= ($hours * 3600);
            $this->debug('whole hours: '.$hours, 3);
        }
      
        // break down the remaining totalSeconds into whole minutes
        if ($totalSeconds > 60) {
            $this->debug('totalSeconds is over a minute', 2);
            $minutes = floor($totalSeconds / 60);
            $totalSeconds -= ($minutes * 60);
            $this->debug('whole minutes: '.$minutes, 3);
        }

        // piece the hours, minutes, and seconds together
        $formattedTime = '';
        if (isset($hours)) {
            $this->debug('including hours ('.$hours.') in the result', 2);
            $formattedTime .= $hours.' hours ';
        }
        if (isset($minutes)) {
            $this->debug('including minutes ('.$minutes.') in the result', 2);
            $formattedTime .= $minutes.' minutes ';
        }
        // include the seconds as a 2-place decimal value
        $formattedTime .= number_format($totalSeconds, 2).' seconds';

        // return the formatted run time in hours, minutes, and seconds
        $this->debug('formatTime() = '.$formattedTime.' ended', 1);
        return $formattedTime;
    }



    /**
     * Format the syntax for the config option tag (short or long)
     *
     * Used by the self::outputSyntax method for auto-generating the syntax
     * options for the shell script.
     * 
     * @param      array  $option  an associative array that represents the
     *                             entry from self::validConfigOptions being
     *                             worked with
     * @param      string $tag     a string indicating which tag is being
     *                             generated.  Valid options: shorttag, longtag.
     * @return     string  a string containing the syntax for the specified
     *                     tag in the option
     * @access     private
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     */
    private function generateSyntaxTag ($option, $tag)
    {
        $this->debug('generateSyntaxTag(array, '.$tag.') called', 1);

        if ($option[$tag] == '') {
            $this->debug('no tag exists', 2);
            return '';
        }

        // assemble the command line option syntax
        $this->debug('assembling the command line option syntax', 2);
        $result = '';
        if ($tag == 'shorttag') {
            $this->debug('short tag syntax', 3);
            $result = '-'.$option[$tag];

        } elseif ($tag == 'longtag') {
            $this->debug('long tag syntax', 3);
            $result = '--'.$option[$tag];

        } else {
            $this->debug('invalid tag type', 3);
            return '';
        }

        // if the option includes a value, add the placeholder to the syntax
        $this->debug('adding optional placeholder', 2);
        if ($option['type'] = 'value') {
            $this->debug('placeholder used', 3);
            $placeHolder = 'value';
            if ($option['valuePlaceholder'] <> '') {
                $placeHolder = $option['valuePlaceholder'];
            }
            $result .= ' <'.$placeHolder.'>';
            $this->debug('placeholder: '.$placeHolder, 3);
        }

        $this->debug('generateSyntaxTag() = '.$result.' ended', 1);
        return $result;
    }
    
    
    
    /**
     * Gets the number of elapsed seconds for the specified timer
     * 
     * Elapsed seconds are measured in microseconds for accuracy and based
     * on the starting and ending timestamps stored in the $timerProperty array.
     *
     * @param      string $timerProperty  a string indicating the class property
     *                                    that holds the starting and ending
     *                                    timestamps
     * @return     float  a float indicating the number of elapsed microseconds
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    protected function getElapsedSeconds ($timerProperty = 'scriptTimer')
    {
        $this->debug('getElapsedSeconds('.$timerProperty.') called', 1);
        $elapsedSeconds = 0;

        if (isset($this->{$timerProperty})
            && is_array($this->{$timerProperty})
        ) {
            $this->debug('timerProperty ('.$timerProperty
                .') exists and is an array', 2);
            if (isset($this->{$timerProperty}['start'])
                && is_numeric($this->{$timerProperty}['start'])
                && $this->{$timerProperty}['start'] > 0 
                && isset($this->{$timerProperty}['end'])
                && is_numeric($this->{$timerProperty}['end'])
                && $this->{$timerProperty}['end'] > 0
            ) {
                $elapsedSeconds = $this->{$timerProperty}['end'];
                $elapsedSeconds -= $this->{$timerProperty}['start'];
                $this->debug('starting and ending times are present and '
                    .'non-zero, elapsed seconds computed', 2);
            }
        }

        $this->debug('getElapsedSeconds() = '.$elapsedSeconds.' ended', 1);
        return $elapsedSeconds;
    }
      
    
    
    /**
     * Gets the current timestamp including microseconds
     * 
     * @return     float  a float indicating the current timestamp in microseconds
     * @access     private
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    private function getMicrotime ()
    {
        $this->debug('getMicrotime() called', 1);

        list($usec, $sec) = explode(' ', microtime());
        $result = ((float)$usec + (float)$sec);

        $this->debug('getMicrotime() = '.$result.' ended', 1);
        return $result;
    }
    
    
    
    /**
     * Prompt the user to enter input and return it
     * 
     * @param      string  $prompt  a string containing the prompt to display
     * @param      integer $length  an integer indicating the maximum length
     *                              of the user's input
     * @return     string  a string containing the value entered by the user
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    protected function getUserInput ($prompt = '', $length = 255)
    {
        $this->debug('getUserInput('.$prompt.', '.$length.') called', 1);

        // prompt the user, if specified
        if (isset($prompt) && $prompt <> '') {
            $this->debug('outputting the provided user prompt', 2);
            echo $prompt;
        }
      
        // collect the user's response
        $this->debug('collecting the user\'s response', 2);
        $STREAM = fopen("php://stdin", "r");
        $input = fgets($STREAM, $length);
        $input = trim($input);
        fclose($STREAM);
      
        // return the response
        $this->debug('getUserInput() = '.$input.' ended', 1);
        return $input;
    }
    
    
    
    /**
     * Outputs the self::errorMsg error message to the user
     *
     * Script execution is halted if $fatal = true along with the message
     * being displayed in bright red.
     * 
     * @param      boolean $fatal    a boolean indicating if the error should
     *                               stop script execution
     * @param      string  $message  a string containing the message to display.
     *                               If blank, self::errorMsg is used.
     * @return     void
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    protected function outputError ($fatal = false, $message = '')
    {
        $this->debug('outputError('.(($fatal) ? 'true' : 'false').') called', 1);

        // output the word ERROR: in red or bright red if fatal
        $this->cecho('ERROR: ', 'red', $fatal);

        // see if we are using a provided message
        if (!(isset($message) && $message <> '')) {
            $this->debug('no message was provided, using self::errorMsg', 2);
            $message = $this->errorMsg;
        }
      
        // see if this message is fatal and thus stops the script
        if ($fatal) {
            $this->debug('the message is fatal', 2);
            $this->debug('outputError() ended with die()', 1);
            die($message."\n");

        } else {
            $this->debug('the message is not fatal', 2);
            // output the message to the user
            echo $message."\n";
        }
      
        $this->debug('outputError() ended', 1);
    }
    
    
    
    /**
     * Displays the syntax of how the script should be called
     *
     * The output includes inumerating all of the configuration options
     * available on the command line.
     *
     * This method can be overridden if the output needs to be customized.
     * 
     * @return     void
     * @access     public
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    public function outputSyntax ()
    {
        $this->debug('outputSyntax() called', 1);
      
        $wrappedDescription = $this->wrap($this->description, 78);
        $hasOptions = false;
        $this->debug('build array of config options to display', 2);
        if (count($this->validConfigOptions) > 0) {
            $hasOptions = true;
            $indent = 0;
            $syntaxOptions = array();
            foreach ($this->validConfigOptions as $option) {
                $this->debug('generating the tag syntax', 2);
                // define the syntax for the tags
                $syntaxOptions[] = array(
                    'shorttag' => $this->generateSyntaxTag($option, 'shorttag'),
                    'longtag' => $this->generateSyntaxTag($option, 'longtag'),
                );

                // set the tag descriptions
                $this->debug('setting the tag descriptions', 2);
                if ($syntaxOptions['shorttag'] <> '') {
                    $this->debug('there is a short tag', 3);
                    $syntaxOptions['shortdesc'] = $option['description'];
                    if ($syntaxOptions['longtag'] <> '') {
                        $this->debug('with an accompanying long tag', 3);
                        $syntaxOptions['longdesc'] = 'same as -'
                            .$option['shorttag'];
                    }

                } elseif ($syntaxOptions['longtag'] <> '') {
                    $this->debug('there is a long tag', 3);
                    $syntaxOptions['shortdesc'] = '';
                    $syntaxOptions['longdesc'] = $option['description'];
                }

                // determine the max indent
                $this->debug('determining the max indent', 2);
                foreach (array('shorttag', 'longtag') as $tag) {
                    $length = strlen($option[$tag]);
                    if ($length > $indent) {
                        $indent = $length;
                    }
                    $this->debug('max indent now: '.$indent, 3);
                }
            }

            // indent 2 spaces and 2 spaces between the option and description
            $indent += 4;
            $descLength = 78 - $indent;
            $this->debug('max indent: '.$indent, 2);
            $this->debug('description max length: '.$descLength, 2);
        }


        $this->debug('outputting the syntax help', 2);
        echo '----------------------------------------------------------------'
            ."---------------\n";
        foreach ($wrappedDescription as $desc) {
            echo $desc."\n";
        }
        echo "\n";
        echo 'Usage: '.__FILE__.(($hasOptions) ? ' [options]' : '')."\n";

        if ($hasOptions) {
            $this->debug('outputting the syntax options', 2);
            echo "Options:\n";
            foreach ($syntaxOptions as $option) {
                if ($syntaxOptions['shorttag'] <> '') {
                    $this->debug('outputting the short tag', 3);

                    // output the tag
                    $post = $indent - strlen($syntaxOptions['shorttag']) - 2;
                    echo $this->pad($syntaxOptions['shorttag'], 2, $post);

                    // output the description
                    $wrappedDesc = $this->wrap(
                        $syntaxOptions['shortdesc'],
                        $descLength
                    );
                    echo array_pop($wrappedDesc)."\n";
                    foreach ($wrappedDesc as $desc) {
                        echo $this->pad($desc, $indent, 0)."\n";
                    }
                }

                if ($syntaxOptions['longtag'] <> '') {
                    $this->debug('outputting the long tag', 3);
                    
                    // output the tag
                    $post = $indent - strlen($syntaxOptions['longtag']) - 2;
                    echo $this->pad($syntaxOptions['longtag'], 2, $post);

                    // output the description
                    $wrappedDesc = $this->wrap(
                        $syntaxOptions['longdesc'],
                        $descLength
                    );
                    echo array_pop($wrappedDesc)."\n";
                    foreach ($wrappedDesc as $desc) {
                        echo $this->pad($desc, $indent, 0)."\n";
                    }
                }
            }
        }

        if (!$this->suppressWarrantyWarning) {
            echo "\n";
            echo 'This software comes with ABSOLUTELY NO WARRANTY. '
                ."Use at your own risk!\n";
        }
        echo '----------------------------------------------------------------'
            ."---------------\n";
      
        $this->debug('outputSyntax() ended', 1);
    }



    /**
     * Adds the specified character a specified number of times to the beginning
     * and/or end of the provided text.
     * 
     * @param      string  $text  a string containing the text to prepend and/or
     *                            append $char to
     * @param      integer $pre   an integer indicating how many times to
     *                            prepend $char to $text
     * @param      integer $post  an integer indicating how many times to
     *                            append $char to $text
     * @param      string  $char  a string indicating the character to prepend
     *                            and/or append to $text
     * @return     string  a string with $char prepended and/or appended to it
     * @access     private
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     */
    private function pad ($text, $pre, $post, $char = ' ')
    {
        $this->debug('pad('.$text.', '.$pre.', '.$post.', '.$char.') called', 1);

        // pad before the text
        $this->debug('prepending char to text', 2);
        for ($i = 0; $i < $pre; $i++) {
            $text = $char.$text;
        }
        $this->debug('prepended: ['.$text.']', 3);

        // pad after the text
        $this->debug('appending char to text', 2);
        for ($i = 0; $i < $post; $i++) {
            $text += $char;
        }
        $this->debug('appended: ['.$text.']', 3);
        
        $this->debug('pad() = ['.$text.'] ended', 1);
        return $text;
    }



    /**
     * Reads command line parameters, validates them, and stores them
     * 
     * Parameters are validated against self::validConfigOptions and stored
     * in self::configOptions.
     *
     * self::configurationRead is set to true when finished.
     *
     * @return     void
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    function processCommandLine ()
    {
        $this->debug('processCommandLine() called', 1);
      
        $this->debug('parameters passed on the command line: '
            .$_SERVER['argc'], 2);

        // make sure some parameters were passed
        if ($_SERVER['argc'] > 1) {
            // create arrays indexed on short and long tags
            $this->debug('create arrays indexed on short and long tags', 2);
            $validShortTags = array();
            $validLongTags = array();
            foreach ($this->validConfigOptions as $key => $data) {
                if (isset($data['shorttag']) && $data['shorttag'] <> '') {
                    $validShortTags[$data['shorttag']] = $data;
                    $this->debug('valid short tag: '.$data['shorttag'], 3);
                }
                if (isset($data['longtag']) && $data['longtag'] <> '') {
                    $validLongTags[$data['longtag']] = $data;
                    $this->debug('valid long tag: '.$data['longtag'], 3);
                }
            }
        
            // process the command line arguments
            $this->debug('for loop to process the command line arguments', 2);
            for ($i = 1; $i < $_SERVER['argc']; $i++) {
                $arg = $_SERVER['argv'][$i];
                $tag = '';
                $tagData = array();
                $value = '';
                if (substr($arg, 0, 2) == '--') {
                    $this->debug('long tag ('.$arg.') detected', 2);
                    // long tag detected
                    if (strpos($arg, '=') !== false) {
                        list($tag, $value) = explode('=', substr($arg, 2));
                    } elseif (strpos($arg, ':') !== false) {
                        list($tag, $value) = explode(':', substr($arg, 2));
                    } else {
                        $tag = substr($arg, 2);
                    }

                    if (array_key_exists($tag, $validLongTags)) {
                        $this->debug('argument ('.$arg.') is a valid option', 2);
                        // grab the option data
                        $tagData = $validLongTags[$tag];
                    }

                } elseif (substr($arg, 0, 1) == '-') {
                    $this->debug('short tag ('.$arg.') detected', 2);
                    // short tag detected
                    if (strpos($arg, '=') !== false) {
                        list($tag, $value) = explode('=', substr($arg, 1));
                    } elseif (strpos($arg, ':') !== false) {
                        list($tag, $value) = explode(':', substr($arg, 1));
                    } else {
                        $tag = substr($arg, 1);
                    }

                    if (array_key_exists($tag, $validShortTags)) {
                        $this->debug('argument ('.$arg.') is a valid option', 2);
                        // grab the option data
                        $tagData = $validShortTags[$tag];
                    }
                }
          
                // process the tag if it exists
                if ($tag <> '' && is_array($tagData) && count($tagData) > 0) {
                    $this->debug('processing tag ('.$tag.')', 2);
            
                    $configKey = $tagData['configkey'];
                    switch (strtolower($tagData['type']))
                    {
                        case 'switch':
                            $this->debug('tag ('.$tag.') is a switch', 2);
                            // see if the switch has already been stored
                            if (array_key_exists(
                                $configKey,
                                $this->configOptions
                            )) {
                                $this->debug('tag ('.$tag.') already exists '
                                    .'in configOptions', 2);
                                // combine multiple tags, if possible
                                if ($tagData['combine']) {
                                    $this->debug('tag ('.$tag.') is '
                                        .'combinable, incrementing the '
                                        .'value', 2);
                                    // increment the value
                                    $this->configOptions[$configKey]++;
                                }
                            } else {
                                $this->debug('tag ('.$tag.') does not exist '
                                    .'in configOptions, adding it', 2);
                                // store the config option
                                $this->configOptions[$configKey] = 1;
                            }
                            break;
                
                        case 'value':
                            $this->debug('tag ('.$tag.') is a value type', 2);
                            $validValue = '';

                            // if we don't already have a value, see if the
                            // next argument is the value
                            if ($value == '') {
                                $this->debug('do not have a value already, '
                                    .'checking the next argument', 2);
                                if (substr($_SERVER['argv'][$i + 1], 0, 1) <> '-') {
                                    $this->debug(
                                        'next argument is the value',
                                        2
                                    );
                                    // the next argument is the value
                                    $value = $_SERVER['argv'][$i + 1];
                                    $i++;
                                }
                            }

                            // validate the value if a validator is provided
                            if ($value <> ''
                                && isset($tagData['validate'])
                                && $tagData['validate'] <> ''
                            ) {
                                // if the validation method exists, call it
                                $validateMethod = $tagData['validate'];
                                $this->debug('validating the value. '
                                    .'validate method: '.$validateMethod, 2);
                                if (method_exists($this, $validateMethod)) {
                                    $this->debug('validate method exists', 2);
                                    if ($this->$validateMethod($value)) {
                                        $this->debug('the value was valid', 2);
                                        // the value is valid
                                        $validValue = $value;
                                    }
                                }
                            }

                            // store the value
                            // if a value exists, make it an array of values
                            if ($validValue <> '') {
                                $this->debug('storing the valid value', 2);
                                $this->storeConfigOption(
                                    $tagData['configkey'],
                                    $validValue
                                );
                            }
                            break;
                    }
                }
            }
        }
      
        // indicate that we have read/processed the command line
        $this->debug('all command line arguments processed', 2);
        $this->configurationRead = true;

        $this->debug('processCommandLine() ended', 1);
    }
      
    
    
    function ProcessConfigFile ($filename, $optionsProperty = 'configOptions') {
      /** PRIVATE
        *   Reads the configuration file specified by $filename.
        *   Configuration options are validated based on the information
        *     in the $this->validConfigOptions array and the valid
        *     values are stored in $this->configOptions array.
        *   $optionsProperty allows you to specify a different
        *     class property to store the valid options in instead
        *     of using the $this->configOptions array.
        */

      $this->debug('ProcessConfigFile('.$filename.', '.$optionsProperty.') called', 1);
      
      // assume the file is read and processed successfully
      $result = true;
      
      // make sure the configuration file exists
      if (!file_exists($filename)) {
        $this->errorMsg = 'The configuration file you specified ('.$filename.') does not exist.';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
        
      } else {
        $this->debug('   filename ('.$filename.') exists, open it for reading', 2);
        // open the config file for reading
        if (!($configFile = fopen($filename, 'r'))) {
          $this->errorMsg = 'The configuration file ('.$filename.') could not be '.
                            'opened for reading. Please check your permissions.';
          $this->errorType = 'error';
          $this->debug();
          $result = false;
          
        } else {
          $this->debug('   config file has been opened for reading', 2);
          // build an array of config options indexed by the file tag
          $validFileTags = array();
          foreach ($this->validConfigOptions as $key => $data) {
            if (isset($data['filetag']) && $data['filetag'] <> '') {
              $validFileTags[strtolower($data['filetag'])] = $data;
            }
          }
          
          // read and process the config file
          $this->debug('   while loop to read through the config file', 2);
          while (!feof($configFile)) {
            // get a line from the file
            $line = trim(fgets($configFile));
            
            // ignore blank lines and lines that start with a hash (#)
            if ($line <> '' && substr($line, 0, 1) <> '#') {
              $this->debug('   line is not blank and not a comment', 2);
              // it's a valid line, look for a equals
              if (strpos($line, '=') !== false) {
                $this->debug('   line contained an equals sign', 2);
                // split the line into a tag and a value
                list($tag, $value) = explode('=', $line, 2);
                $tag = trim(strtolower($tag));
                $tagData = array();
                $value = trim($value);
                
                
                // validate the tag
                if (array_key_exists($tag, $validFileTags)) {
                  $this->debug('   tag ('.$tag.') was valid', 2);
                  $tagData = $validFileTags[$tag];
                  $validValue = '';
                  
                  // check to see if this option is a value type
                  if ($tagData['type'] == 'value') {
                    $this->debug('   tag ('.$tag.') is a value type', 2);
                    // validate the value if a validator is specified
                    if ($value <> '' && isset($tagData['validate']) && $tagData['validate'] <> '') {
                      // if the validation method exists, call it
                      $validateMethod = $tagData['validate'];
                      $this->debug('   validating the value. validate method: '.$validateMethod, 2);
                      if (method_exists($this, $validateMethod)) {
                        $this->debug('   validate method exists', 2);
                        if ($this->$validateMethod($value)) {
                          $this->debug('   the value was valid', 2);
                          // the value is valid
                          $validValue = $value;
                        }
                      }
                    }
                    
                    // store the value, if a value exists, make it an array of values
                    if ($validValue <> '') {
                      $this->debug('   we have a valid value, store it', 2);
                      $this->StoreConfigOption($tagData['configkey'], $validValue, $optionsProperty);
                    }
                  }
                }                  
              }
            }
          }
          
          // close the config file
          $this->debug('   close the config file', 2);
          fclose($configFile);
        }
      }
      
      $this->debug('ProcessConfigFile() ended', 1);
      
    }  // end of function ProcessConfigFile
    
    
    
    /**
     * Starts the specified timer by storing the timestamp with microseconds
     * 
     * @param      string $timerProperty  a string indicating the class property
     *                                    that holds the starting and ending
     *                                    timestamps
     * @return     void
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    protected function startTimer ($timerProperty = 'scriptTimer')
    {
        $this->debug('startTimer('.$timerProperty.') called', 1);

        $this->{$timerProperty} = array(
            'start' => $this->getMicrotime(),
            'end' => 0,
        );

        $this->debug('startTimer() ended', 1);
    }
    
    
    
    /**
     * Stops the specified timer by storing the timestamp with microseconds
     * 
     * @param      string $timerProperty  a string indicating the class property
     *                                    that holds the starting and ending
     *                                    timestamps
     * @return     void
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    protected function stopTimer ($timerProperty = 'scriptTimer')
    {
        $this->debug('stopTimer('.$timerProperty.') called', 1);

        if (isset($this->{$timerProperty})
            && is_array($this->{$timerProperty})
        ) {
            $this->{$timerProperty}['end'] = $this->getMicrotime();
        }

        $this->debug('stopTimer() ended', 1);
    }
    
    
    
    /**
     * Stores a value in the config options array
     * 
     * If the options array has an existing value that matches $value, nothing
     * is changed.
     *
     * If the options array has an existing value that does not match $value,
     * the value is converted to an array and both the old and new $value are
     * stored.
     *
     * @param      string $configKey        a string indicating the array
     *                                      index to store $value in
     * @param      string $value            a string containing the value
     * @param      string $optionsProperty  a string indicating which class
     *                                      property holds the config options
     * @return     void
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     * @since      30 December 2009
     */
    protected function storeConfigOption ($configKey, $value,
        $optionsProperty = 'configOptions'
    ) {
        $this->debug('storeConfigOption('.$configKey.', '.$value.', '
            .$optionsProperty.') called', 1);
      
        if (array_key_exists($configKey, $this->{$optionsProperty})) {
            $this->debug('configKey ('.$configKey.') exists', 2);
            // it exists, do the current and new values match?
            if ($this->{$optionsProperty}[$configKey] <> $value) {
                $this->debug('there is an existing value that does not '
                    .'match this one', 2);

                // no. if it's not an array, make it one
                if (!is_array($this->{$optionsProperty}[$configKey])) {
                    $this->debug('option is not an array, making it one', 2);
                    $origValue = $this->{$optionsProperty}[$configKey];
                    $this->{$optionsProperty}[$configKey] = array();
                    $this->{$optionsProperty}[$configKey][] = $origValue;
                    unset($origValue);
                }

                // store the value in the array
                $this->debug('storing the value in '.$optionsProperty
                    .'['.$configKey.'][]', 2);
                $this->{$optionsProperty}[$configKey][] = $value;
            }
        } else {
            // store the value
            $this->debug('storing the value in '.$optionsProperty
                .'['.$configKey.']', 2);
            $this->{$optionsProperty}[$configKey] = $value;
        }
      
        $this->debug('storeConfigOption() ended', 1);
    }
          
    

    /**
     * Word wraps text to a maximum length
     * 
     * @param      string  $text       a string containing the text to word wrap
     * @param      integer $maxLength  an integer indicating the maximum length
     *                                 of the text before it must be wrapped
     * @return     array  an array containing the word wrapped lines of text
     * @access     protected
     * @author     Paul Rentschler <paul@rentschler.ws>
     * @since      1 December 2013
     */
    protected function wrap ($text, $maxLength)
    {
        $this->debug('wrap('.$text.', '.$maxLength') called', 1);

        $wordStack = explode(' ', $text);
        $wrapped = array('');
        $index = 0;
        $this->debug('processing the word stack', 2);
        do while count($wordStack) > 0 {
            $this->debug('processing: '.$wordStack[0], 3);
            if (strlen($wrapped[$index].' '.$wordStack[0]) > $maxLength) {
                $this->debug('exceeds max length, wrapping', 3);
                $index++;
            }
            $this->debug('adding ('.$wordStack[0].') to line '.$index, 3);
            $wrapped[$index] += ' '.array_pop($wordStack);
        }

        $this->debug('wrap() = array('.$index.') ended', 1);
        return $wrapped;
    }
    
    


    /********************************************************************
     *   VALIDATION METHODS
     ********************************************************************/
    
    function ValidateString ($text) {
      /** PRIVATE
        *   Validate the text provided contains valid characters
        */
      
      $this->debug('ValidateString('.$text.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the text has to contain letters, numbers, and a few select symbols
      
      $pattern = '/^[a-z0-9\x20!\[\]{}()\\\\\/@#\$%\^&\*\-_\+=:;\'\",\.\?]*$/Di';
      if (!preg_match($pattern, $text)) {
        $this->errorMsg = 'The string contains one or more invalid characters. '.
                          'The string can contain letters, numbers, spaces and the '.
                          'following symbols: !@#$()[]{}\\/%^&*-_+=:;\'",.?';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateString() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateString
    
    
    
    function ValidateAlphaString ($text) {
      /** PRIVATE
        *   Validate the text provided contains valid characters
        */
      
      $this->debug('ValidateAlphaString('.$text.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the text has to contain letters and spaces
      
      $pattern = '/^[a-z\x20]*$/Di';
      if (!preg_match($pattern, $text)) {
        $this->errorMsg = 'The string contains one or more invalid characters. '.
                          'The string can contain only letters and spaces.';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateAlphaString() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateAlphaString
    
    
    
    function ValidateAlphaNumericString ($text) {
      /** PRIVATE
        *   Validate the text provided contains valid characters
        */
      
      $this->debug('ValidateAlphaNumericString('.$text.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the text has to contain letters, numbers, and spaces
      
      $pattern = '/^[a-z0-9\x20]*$/Di';
      if (!preg_match($pattern, $text)) {
        $this->errorMsg = 'The string contains one or more invalid characters. '.
                          'The string can contain letters, numbers, and spaces.';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateAlphaNumericString() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateAlphaNumericString
    
    
    
    function ValidateEmailAddress ($address) {
      /** PRIVATE
        *   Validate the address provided is a valid e-mail address
        *     and contains only select characters
        */
      
      $this->debug('ValidateEmailAddress('.$address.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the text has to contain letters, numbers, and a few select symbols
      //   - must be in the format: user+extra@domain.tld
      
      $pattern = '/^[^@\s<&>]+@([-a-z0-9]+\.)+[a-z]{2,}$/Di';
      if (!preg_match($pattern, $address)) {
        $this->errorMsg = 'The e-mail address contains one or more invalid '.
                          'characters or is not in the correct format. '.
                          'The e-mail address should be in the format: '.
                          'username@domain.tld (example: jsmith@yahoo.com).';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateEmailAddress() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateEmailAddress
    
    
    
    function ValidateDate ($text) {
      /** PRIVATE
        *   Validate the date provided contains valid characters
        *     and is in date format
        */
      
      $this->debug('ValidateDate('.$text.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the text has to contain numbers
      //   - be in the format: #/#/#### or ##/##/####
      
      $pattern = '/^\d{1,2}\/\d{1,2}\/\d{4}$/D';    
      if (!preg_match($pattern, $text)) {
        $this->errorMsg = 'The date contains one or more invalid characters or '.
                          'is not in the correct format. The date should be '.
                          'in the format: m/d/yyyy.';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateDate() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateDate



    function ValidateWebUrl ($url) {
      /** PRIVATE
        *   Validate the url provided contains valid characters
        */

      $this->debug('ValidateWebUrl('.$url.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the text has to contain letters, numbers, and a few select symbols
      
      $pattern = '/^[A-Za-z0-9%&\/\-_\+=:\.\#?]*$/Di';
      if (!preg_match($pattern, $url)) {
        $this->errorMsg = 'The url contains one or more invalid characters. '.
                          'The url can contain letters, numbers, and the '.
                          'following symbols: #%&-_+=:/.?';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateWebUrl() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateWebUrl
    
    
    
    function ValidateIPAddress ($ip) {
      /** PRIVATE
        *   Validate the ip address provided contains valid characters
        *     and is in the IPv4 format
        */
      
      $this->debug('ValidateIPAddress('.$ip.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the text has to contain numbers
      //   - be in the format: ###.###.###.###
      //   - each number must be between 0 and 255
      
      $pattern = '/^(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))\.(((([0-1])?([0-9])?[0-9])|(2[0-4][0-9])|(2[0-5][0-5])))$/Di';
      if (!preg_match($pattern, $ip)) {
        $this->errorMsg = 'The ip address contains one or more invalid characters '.
                          'or is not in the correct format. The ip address should be '.
                          'in the format #.#.#.# where each # ranges from 0 to 255 '.
                          '(ex: 127.0.0.1).';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateIPAddress() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateIPAddress
    
    
    
    function ValidateFilename ($filename) {
      /** PRIVATE
        *   Validate the filename provided contains valid characters
        */
      
      $this->debug('ValidateFilename('.$filename.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the filename has to contain letters, numbers, and a few select symbols

      // see if only valid characters exist in the path
      $pattern = '/^[a-z0-9\x20\[\]{}()\/@#\$%\^&\-_\+=;\',\.`~]*$/Di';
      if (!preg_match($pattern, $filename)) {
        $this->errorMsg = 'The filename contains one or more invalid characters. ';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateFilename() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateFilename
    
    
    
    function ValidatePath (&$path) {
      /** PRIVATE
        *   Validate the path provided contains valid characters
        *   $path = the path to check passed by reference so
        *     an ending slash (/) can be added if it's not present
        */
      
      $this->debug('ValidatePath('.$path.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the path has to contain letters, numbers, and a few select symbols

      // see if only valid characters exist in the path
      if ($this->ValidateFilename($path)) {
        // make sure it ends with a slash
        if (substr($path, -1, 1) <> '/') {
          $this->debug('   path ('.$path.') did not end in a slash, adding it', 2);
          $path .= '/';
        }
          
      } else {
        $this->errorMsg = 'The path contains one or more invalid characters. ';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      // return the result
      $this->debug('ValidatePath() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidatePath
    
    
    
    function ValidateLocalPath (&$path) {
      /** PRIVATE
        *   Validate the path provided is valid and exists
        *     on the file system
        *   $path = the path to check passed by reference so
        *     an ending slash (/) can be added if it's not present
        */
      
      $this->debug('ValidateLocalPath('.$path.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      // To be valid:
      //   - the path has to contain letters, numbers, and a few select symbols
      //   - the directory has to exist and be a directory

      // see if only valid characters exist in the path
      if ($this->ValidatePath($path)) {
        $this->debug('   path ('.$path.') contains valid characters', 2);
        // see if the path exists and is a directory
        if (!(file_exists($path) && is_dir($path))) {
          $this->errorMsg = 'The path specified ('.$path.') does not exist.';
          $this->errorType = 'error';
          $this->debug();
          $result = false;
        }
        
      } else {
        // $this->errorMsg and $this->errorType are set from calling ValidatePath
        $result = false;
      }
      
      // return the result
      $this->debug('ValidateLocalPath() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateLocalPath
    
    
    
    function ValidateMySQLServer ($server) {
      /** PRIVATE
        *   Validate the MySQL server name
        */
      
      $this->debug('ValidateMySQLServer('.$server.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      
      // To be valid:
      //   - must contain only letters, numbers, and select symbols
      
      $pattern = '/^[a-zA-Z0-9\.-_]*$/Di';
      if (!preg_match($pattern, $server)) {
        $this->errorMsg = 'The MySQL server you specified '.
                          'is not valid. The server name can contain '.
                          'only letters, numbers, and the following symbols: .-_';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      
      // return the result
      $this->debug('ValidateMySQLServer() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateMySQLServer
    
    
    
    function ValidateMySQLDatabase ($database) {
      /** PRIVATE
        *   Validate the MySQL database
        *   $database = the MySQL database name
        */
      
      $this->debug('ValidateMySQLDatabase('.$database.') called', 1);
      
      // assume it will be valid
      $this->errorMsg = '';
      $this->errorType = '';
      $result = true;
      
      
      // To be valid:
      //   - must contain only letters, numbers, dashes, and underlines
      $pattern = '/^[a-zA-Z0-9-_]*$/Di';
      if (!preg_match($pattern, $database)) {
        $this->errorMsg = 'The MySQL database you specified '.
                          'is not valid. The database name can contain '.
                          'only letters, numbers, dashes, and underlines.';
        $this->errorType = 'error';
        $this->debug();
        $result = false;
      }
      
      
      // return the result
      $this->debug('ValidateMySQLDatabase() = '.(($result) ? 'true' : 'false').' ended', 1);
      return $result;
      
    }  // end of function ValidateMySQLDatabase
    
  }  // end of class ShellScript
   
?>
