<?php

/********************************************************************
 * Filename: shellscript.class.php
 *
 * Description: Provides a generic class for creating shell scripts.
 *              Provides commonly needed features for most shell
 *                scripts.
 *              Intended to be extended for individual script needs.
 *
 * PHP Version: 4.x or 5.x
 *
 * Written by: Paul Rentschler
 * Created on: 30 December 2009
 ********************************************************************/


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


  class ShellScript {
    
    var $scriptTimer = array();              // track the default starting and stopping times for the timer methods
    var $configurationRead = false;          // indicates when the command line arguments have been read
    var $configOptions = array();            // holds configuration options from the command line and config file
    var $validConfigOptions = array();       // options that are valid on the command line and in the config file
    var $errorMsg = '';                      // error message generated from any of the internal methods
    var $errorType = '';                     // type of error message generated from any of the internal methods
    var $debugLogFilename = 'debug.log';     // debug log file name to use
    var $debugMode = false;                  // debug mode on or off. If on, it outputs to $debugLogFilename
    var $debugVerboseKey = 'verbose';        // what key in $configOptions indicates the level of verbosity for debug messages
    
   
    function ShellScript () {
      /** PUBLIC Constructor:
        *   Initializes the class properties
        */
      
      $this->debug('ShellScript() called', 1);
      
      // initialize the configuration options
      $this->debug('   initialize the configuration options', 2);
      $this->configOptions = array();
      $this->configurationRead = false;
      
      // define the valid configuration options
      $this->debug('   define the two default valid configuration options', 2);
      $this->validConfigOptions = array( array( 'shorttag' => 'h',
                                                'longtag' => 'help',
                                                'filetag' => '',
                                                'configkey' => 'help',
                                                'type' => 'switch',
                                                'validate' => '',
                                                'combine' => false ),
                                         array( 'shorttag' => 'v', 
                                                'longtag' => '', 
                                                'filetag' => '', 
                                                'configkey' => 'verbose', 
                                                'type' => 'switch', 
                                                'validate' => '', 
                                                'combine' => true ),
                                       );
                                         
      $this->debug('ShellScript() ended',1);
      
    }  // end of function ShellScript
    
    
    
    function OutputSyntaxInstructions () {
      /** PRIVATE
        *   Displays the syntax of how this script should
        *     be called from the command line including
        *     what options are available.
        *   This function should be overridden and the
        *     message provided states that.
        */
      
      $this->debug('OutputSyntaxInstructions() called', 1);
      
      echo "-------------------------------------------------------------------------------\n";
      echo "ShellScript PHP script syntax\n";
      echo "This software comes with ABSOLUTELY NO WARRANTY. Use at your own risk!\n";
      echo "\n";
      echo "This text has been generated by the ShellScript:OutputSyntaxInstructions()\n";
      echo "  method which should be overridden in your shell script to provide the proper\n";
      echo "  syntax and any command line options for calling your shell script.\n";
      echo "\n";
      echo "Written by Paul Rentschler <paul@surfsupwebdesign.com>\n";
      echo "Copyright 2009-".date('Y')." Surf's Up Web Design.\n";
      echo "-------------------------------------------------------------------------------\n\n";
      
      $this->debug('OutputSyntaxInstructions() ended', 1);
      
    }  // end of function OutputSyntaxInstructions
    
    
    
    function ProcessCommandLine () {
      /** PRIVATE
        *   Reads any parameters that were passed to the script
        *     when it was called from the command line. It validates
        *     them against validConfigOptions array and stores the
        *     valid ones in configOptions array.
        *   Sets configurationRead to true when finished.
        */
      
      $this->debug('ProcessCommandLine() called', 1);
      
      // make sure some parameters were passed
      if ($_SERVER['argc'] > 1) {
        $this->debug('   parameters passed on the command line: '.$_SERVER['argc'], 2);
        
        // create arrays indexed on short and long tags
        $this->debug('   create arrays indexed on short and long tags', 2);
        $validShortTags = array();
        $validLongTags = array();
        foreach ($this->validConfigOptions as $key => $data) {
          if (isset($data['shorttag']) && $data['shorttag'] <> '') {
            $validShortTags[$data['shorttag']] = $data;
          }
          if (isset($data['longtag']) && $data['longtag'] <> '') {
            $validLongTags[$data['longtag']] = $data;
          }
        }
        
        // process the command line arguments
        $this->debug('   for loop to process all the command line arguments', 2);
        for ($i = 1; $i < $_SERVER['argc']; $i++) {
          $arg = $_SERVER['argv'][$i];
          $tag = '';
          $tagData = array();
          $value = '';
          if (substr($arg, 0, 2) == '--') {
            $this->debug('   argument ('.$arg.') is a long tag argument', 2);
            // we got a long tag
            if (strpos($arg, '=') !== false) {
              list($tag, $value) = explode('=', substr($arg, 2));
            } elseif (strpos($arg, ':') !== false) {
              list($tag, $value) = explode(':', substr($arg, 2));
            } else {
              $tag = substr($arg, 2);
            }
            
            if (array_key_exists($tag, $validLongTags)) {
              $this->debug('   argument('.$arg.') is a valid option', 2);
              // grab the data
              $tagData = $validLongTags[$tag];
            }
          
          } elseif (substr($arg, 0, 1) == '-') {
            $this->debug('   argument ('.$arg.') is a short tag argument', 2);
            // we got a short tag
            if (strpos($arg, '=') !== false) {
              list($tag, $value) = explode('=', substr($arg, 1));
            } elseif (strpos($arg, ':') !== false) {
              list($tag, $value) = explode(':', substr($arg, 1));
            } else {
              $tag = substr($arg, 1);
            }
            
            if (array_key_exists($tag, $validShortTags)) {
              $this->debug('   argument('.$arg.') is a valid option', 2);
              // grab the data
              $tagData = $validShortTags[$tag];
            }
          }
          
          // process the tag if it exists
          if ($tag <> '' && is_array($tagData) && count($tagData) > 0) {
            $this->debug('   processing tag ('.$tag.')', 2);
            
            switch (strtolower($tagData['type'])) {
              case 'switch':
                $this->debug('   tag ('.$tag.') is a switch type', 2);
                // see if the switch has already been stored
                if (array_key_exists($tagData['configkey'], $this->configOptions)) {
                  $this->debug('   tag ('.$tag.') already exists in configOptions', 2);
                  // it does, so can multiple tags be combined
                  if ($tagData['combine']) {
                    $this->debug('   tag ('.$tag.') is combinable, incrementing the value', 2);
                    // yes, so increment the value
                    $this->configOptions[$tagData['configkey']]++;
                  }
                } else {
                  $this->debug('   tag ('.$tag.') does not exist in configOptions, adding it', 2);
                  // store the config option
                  $this->configOptions[$tagData['configkey']] = 1;
                }
                break;
                
              case 'value':
                $this->debug('   tag ('.$tag.') is a value type', 2);
                $validValue = '';
                
                // if we don't already have a value, we need to see if the next argument is the value
                if ($value == '') {
                  $this->debug('   we do not have a value already, checking the next argument', 2);
                  if (substr($_SERVER['argv'][$i + 1], 0, 1) <> '-') {
                    $this->debug('   the next argument was a value', 2);
                    // the next argument is the value
                    $value = $_SERVER['argv'][$i + 1];
                    $i++;
                  }
                }
                
                // validate the value if a validator is provided
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
                  $this->StoreConfigOption($tagData['configkey'], $validValue);
                }
                break;
            }
          }
        }
      }
      
      // indicate that we have read/processed the command line
      $this->debug('   indicate the command line arguments have been processed', 2);
      $this->configurationRead = true;
      
      $this->debug('ProcessCommandLine() ended', 1);
      
    }  // end of function ProcessCommandLine
      
    
    
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
    
    
    
    function StoreConfigOption ($configKey, $value, $optionsProperty = 'configOptions') {
      /** PRIVATE
        *   Stores the $configKey = $value combination in the
        *     $this->{$optionsProperty} array.
        *   If a value exists and it matches $value, nothing is changed
        *   If a value exists and does not match $value, then the
        *     value for the $configKey entry is converted to an
        *     array that holds the old value and $value
        */
            
      $this->debug('StoreConfigOption('.$configKey.', '.$value.', '.$optionsProperty.') called', 1);
      
      if (array_key_exists($configKey, $this->{$optionsProperty})) {
        $this->debug('   configKey ('.$configKey.') exists in configOptions', 2);
        // it exists, do the current and new values match?
        if ($this->{$optionsProperty}[$configKey] <> $value) {
          $this->debug('   there is an existing value that does not match this one', 2);
          // no. if it's not an array, make it one
          if (!is_array($this->{$optionsProperty}[$configKey])) {
            $this->debug('   this option is not an array, making it one', 2);
            $tmpValue = $this->{$optionsProperty}[$configKey];
            $this->{$optionsProperty}[$configKey] = array();
            $this->{$optionsProperty}[$configKey][] = $tmpValue;
            unset($tmpValue);
          }
          
          // store the value in the array
          $this->debug('   storing the value in configOptions['.$configKey.'][]', 2);
          $this->{$optionsProperty}[$configKey][] = $value;
        }
      } else {
        // store the value
        $this->debug('   storing the value in configOptions['.$configKey.']', 2);
        $this->{$optionsProperty}[$configKey] = $value;
      }
      
      $this->debug('StoreConfigOption() ended', 1);
      
    }  // end of function StoreConfigOption
          
    

    function cecho ($message, $forecolor = 'default', $bright = false, $backcolor = 'default', $reset = true) {
      /** PRIVATE
        *   Outputs the provided $message using the echo command
        *     but allows for ANSI color codes to be output as well
        *     to control the color of the text and background.
        *   The text and background color is always reset to the 
        *     default color after output unless $reset = false
        */
      
      $this->debug('cecho('.$message.', '.$forecolor.', '.(($bright) ? 'true' : 'false').', '.$backcolor.', '.(($reset) ? 'true' : 'false').') called', 1);
      
      // define the default value
      $default = "\033[0m";
      
      // define the color codes
      $colorCodes = array( 'black' => 0,
                           'red' => 1,
                           'green' => 2,
                           'yellow' => 3,
                           'blue' => 4,
                           'magenta' => 5,
                           'cyan' => 6,
                           'white' => 7 );
      
      
      // if the default foreground and background are used, just display the text
      if ($forecolor == 'default' && $backcolor == 'default') {
        $this->debug('   foreground and background colors are default', 2);
        echo $default;
        if ($message <> '') {
          $this->debug('   outputing the message', 2);
          echo $message;
        }
        
      } else {
        /* DETERMINE THE COLOR CODES */
        
        // default codes
        $foreCode = '';
        $backCode = '';
      
        // look up the provided colors to get the codes
        $forecolor = strtolower($forecolor);
        $backcolor = strtolower($backcolor);
        if (isset($forecolor) && array_key_exists($forecolor, $colorCodes)) {
          $foreCode = 30 + ((int) $colorCodes[$forecolor]);
          $this->debug('   setting the foreground code to: '.$foreCode, 2);
        }
        if (isset($backcolor) && array_key_exists($backcolor, $colorCodes)) {
          $backCode = 40 + ((int) $colorCodes[$backcolor]);
          $this->debug('   setting the background code to: '.$backCode, 2);
        }
        
        
        /* OUTPUT THE COLORED MESSAGE */
        // the color code
        $this->debug('   building the escape sequence', 2);
        $escapeSeq = "\033[";
        $escapeSeq .= (($bright) ? '1' : '0');
        $escapeSeq .= (($foreCode <> '') ? ';'.$foreCode : '');
        $escapeSeq .= (($backCode <> '') ? ';'.$backCode : '');
        $escapeSeq .= 'm';
        echo $escapeSeq;
        
        // the message
        $msgLength = strlen($message);
        if ($msgLength > 0 && strrpos($message, "\n") == $msgLength - 1 && $backCode <> '' && $reset) {
          $this->debug('   the message ended with a carriage return and the background color is not the default', 2);
          if ($msgLength >= 2) {
            $this->debug('   outputting the message, resetting the color, then outputing the carriage return', 2);
            echo substr($message, 0, $msgLength - 2);
            echo $default;
            echo "\n";
          }
        } else {
          $this->debug('   the message has no carriage return or the background color is the default', 2);
          if ($message <> '') {
            $this->debug('   outputting the message', 2);
            echo $message;
          }
          
          // reset the colors
          if ($reset) {
            $this->debug('   resetting the colors to the default', 2);
            echo $default;
          }
        }
      }
      
      $this->debug('cecho() ended', 1);
      
    }  // end of function cecho
    
    
    
    function debug ($message = '', $verbosityLevel = 0) {
      /** PRIVATE
        *   Outputs a message to the debug log defined by
        *     the $this->debugLogFilename property if debug
        *     mode is turned on ($this->debugMode == true).
        *   If $message == '' then the $this->errorMsg and
        *     $this->errorType properties are used to generate
        *     the debug entry.
        *   $verbosityLevel works with the $this->debugVerboseKey
        *     property to specify how many messages should be
        *     added to the debug log. $this->debugVerboseKey
        *     references a value in $this->configOptions to
        *     determine how much the user wants to see in the
        *     debug log. If $verbosityLevel is less than or
        *     equal to the level the user specified, then the
        *     message is added to the debug log.
        */
      
      // see if debug mode is enabled
      if ($this->debugMode) {
        // indicate if we are outputting the message or not, yes by default
        $outputMsg = true;
        
        // has a verbosity level been specified
        if ($verbosityLevel > 0) {
          // assume the message will not be output unless the settings prove otherwise
          $outputMsg = false;
          
          // see if a verbosity level has been specified
          if ($this->debugVerboseKey <> '' && isset($this->configOptions[$this->debugVerboseKey]) && 
              is_numeric($this->configOptions[$this->debugVerboseKey])) {
            // is the verbosity level for this message above the threshold
            if ($verbosityLevel <= $this->configOptions[$this->debugVerboseKey]) {
              $outputMsg = true;
            }
          }
        }
        
        // are we outputting a message
        if ($outputMsg) {
          // are we outputting a message passed to the method or the
          //   one stored in the $this->errorMsg property?
          if (!isset($message) || $message == '') {
            $message = $this->errorMsg;
          }
          
          // remove any carriage returns from the message
          $message = str_replace("\n", '', $message);
          $message = str_replace("\r", '', $message);
          
          // add a date/time stamp to the beginning of the message
          //   and a carriage return at the end
          $stamp = '['.date('Y-m-d H:i:s').'] ';
          $message = $stamp.$message."\n";
          
          // output the debug message to the file
          if (function_exists('file_put_contents')) {
            // the easy way suppoted in PHP5
            file_put_contents($this->debugLogFilename, $message, FILE_APPEND);
            
          } else {
            // the hard / PHP4 way
            $FH = fopen($this->debugLogFilename, 'a');
            if ($FH) {
              fwrite($FH, $message);
              fclose($FH);
            }
            unset($FH);
          }
        }
      }
      
    }  // end of function debug
    
    
    
    function OutputError ($fatal = false, $message = '') {
      /** PRIVATE
        *   Outputs the message stored in $this->errorMsg
        *     to the user.
        *   $fatal indicates whether or not to stop the
        *     script from executing. If $fatal is true,
        *     the message is displayed in bright red.
        */
      
      $this->debug('OutputError('.(($fatal) ? 'true' : 'false').') called', 1);
      
      // output the word ERROR: in red or bright red if fatal
      $this->cecho('ERROR: ', 'red', $fatal);

      // see if we are using a provided message
      if (!(isset($message) && $message <> '')) {
        $this->debug('   no message was provided, using $this->errorMsg', 2);
        $message = $this->errorMsg;
      }
      
      // see if this message is fatal and thus stops the script
      if ($fatal) {
        $this->debug('   the message is fatal', 2);
        die($message."\n");
        
      } else {
        $this->debug('   the message is not fatal', 2);
        // output the message to the user
        echo $message."\n";
      }
      
      $this->debug('OutputError() ended', 1);
    
    }  // end of function OutputError
    
    
    
    function GetUserInput ($prompt = '', $length = 255) {
      /** PRIVATE
         *   Prompts the user to enter some type of input and 
         *     then returns the entered value to the calling
         *     function
         *   $msgQuery parameter allows the calling function
         *     to specify the question to be asked
         *   $length parameter allows the calling function to
         *     specify a maximum length that the user can enter
         */
      
      $this->debug('GetUserInput('.$prompt.', '.$length.') called', 1);
      
      // prompt the user with the provided question
      if (isset($prompt) && $prompt <> '') {
        $this->debug('   outputting the provided user prompt', 2);
        echo $prompt;
      }
      
      // collect the user's response
      $this->debug('   collecting the user\'s response', 2);
      $STREAM = fopen("php://stdin", "r");
      $input = fgets($STREAM, $length);
      $input = trim($input);
      fclose($STREAM);
      
      // return the response
      $this->debug('GetUserInput() = '.$input.' ended', 1);
      return $input;
      
    }  // end of function GetUserInput
    
    
    
    function GetMicrotime () {
      /** PRIVATE
        *   Gets the time in microseconds and returns it to
        *     the calling function so that the time this
        *     script takes to run can be calculated.
        */
      
      $this->debug('GetMicrotime() called', 1);

      list($usec, $sec) = explode(" ",microtime());
      $result = ((float)$usec + (float)$sec);
      
      $this->debug('GetMicrotime() = '.$result.' ended', 1);
      return $result;

    }  // end of function GetMicrotime
    
    
    
    function StartTimer ($timerProperty = 'scriptTimer') {
      /** PRIVATE
        *   Stores the current timestamp with microseconds as 
        *     a starting time.
        *   $timerProperty allows for the class property
        *     that will hold the starting and ending times
        *     to be provided. By default it uses 
        *     $this->scriptTimer to track the total time the
        *     script has been run.
        */
      
      $this->debug('StartTimer('.$timerProperty.') called', 1);
      
      $this->{$timerProperty} = array( 'start' => 0, 'end' => 0 );
      $this->{$timerProperty}['start'] = $this->GetMicrotime();
      
      $this->debug('StartTimer() ended', 1);
    
    }  // end of function StartTimer
    
    
    
    function StopTimer ($timerProperty = 'scriptTimer') {
      /** PRIVATE
        *   Stores the current timestamp with microseconds as 
        *     a ending time.
        *   $timerProperty allows for the class property
        *     that will hold the starting and ending times
        *     to be provided. By default it uses 
        *     $this->scriptTimer to track the total time the
        *     script has been run.
        */

      $this->debug('StopTimer('.$timerProperty.') called', 1);
      
      if (isset($this->{$timerProperty}) && is_array($this->{$timerProperty})) {
        $this->{$timerProperty}['end'] = $this->GetMicrotime();
      }
      
      $this->debug('StopTimer() ended', 1);
      
    }  // end of function StopTimer
    
    
    
    function GetTimerSeconds ($timerProperty = 'scriptTimer') {
      /** PRIVATE
        *   Gets the elapsed number of microseconds based on
        *     starting and ending timestamps
        *   $timerProperty allows for the class property
        *     that will hold the starting and ending times
        *     to be provided. By default it uses 
        *     $this->scriptTimer to track the total time the
        *     script has been run.
        */

      $this->debug('GetTimerSeconds('.$timerProperty.') called', 1);
      $elapsedSeconds = 0;
      
      if (isset($this->{$timerProperty}) && is_array($this->{$timerProperty})) {
        $this->debug('   timerProperty ('.$timerProperty.') exists and is an array', 2);
        if (isset($this->{$timerProperty}['start']) && is_numeric($this->{$timerProperty}['start']) &&
            isset($this->{$timerProperty}['end']) && is_numeric($this->{$timerProperty}['end']) &&
            $this->{$timerProperty}['start'] > 0 && $this->{$timerProperty}['end'] > 0) {
          $elapsedSeconds = $this->{$timerProperty}['end'] - $this->{$timerProperty}['start'];
          $this->debug('   starting and ending times are present and non-zero, elapsed seconds computed', 2);
        }
      }
      
      $this->debug('GetTimerSeconds() = '.$elapsedSeconds.' ended', 1);
      return $elapsedSeconds;
      
    }  // end of function GetTimerSeconds
      
    
    
    function FormatRuntime ($runSeconds) {
      /** PRIVATE
         *   Formats the runtime specified ($runSeconds)
         *     in seconds into a string that includes
         *     hours, minutes, seconds, and hundredths
         *     of a second and returns it to the calling
         *     function.
         */
      
      $this->debug('FormatRuntime('.$runSeconds.') called', 1);
      
      // break down the runSeconds into the number of whole hours
      if ($runSeconds > 3600) {
        $this->debug('   runSeconds is over an hour', 2);
        $hours = floor($runSeconds / 3600);
        $runSeconds -= ($hours * 3600);
      }
      
      // take the remaining runSeconds and break it down into the
      //  number of whole minutes
      if ($runSeconds > 60) {
        $this->debug('   runSeconds is over a minute', 2);
        $minutes = floor($runSeconds / 60);
        $runSeconds -= ($minutes * 60);
      }

      // piece the hours, minutes, and seconds together
      $runtime = '';
      if (isset($hours)) {
        $this->debug('   hours ('.$hours.') is defined', 2);
        $runtime .= $hours.' hours ';
      }
      if (isset($minutes)) {
        $this->debug('   minutes ('.$minutes.') is defined', 2);
        $runtime .= $minutes.' minutes ';
      }
      // include the seconds as a 2-place decimal value
      $runtime .= number_format($runSeconds, 2).' seconds';

      // return the formatted run time in hours, minutes, and seconds
      $this->debug('FormatRuntime() = '.$runtime.' ended', 1);
      return $runtime;

    }  // end of FormatRuntime

    
    
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
