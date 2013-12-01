# pwrentch.ShellScript

A PHP class for creating shell scripts.

This class is part of the pwrentch library.

## Compatibility

The library is compatible with PHP 5.x. Currently it does not utilize
namespaces because I still have code that runs on servers with PHP versions
less than 5.3. When that changes, namespaces will be added.


## Installation



## Usage

The ShellScript class exists to provide functionality for creating your own
shell script and as such your shell script class will extend ShellScript. In
doing so, you will need to replicate the tasks performed in the constructor
or call it using the parent::__construct() syntax.

### Adding command line and/or configuration file options

By default there are command line arguments for displaying the syntax of the
shell script and for controlling the verbosity of output to the debugging file.

To add additional command line and/or configuration file options, add
additional entries to the $this->validConfigOptions array. Use the definition
of the -h and -v options in the __construct() method as examples of how
to add additional configuration options.

### Configuration option validation methods

The following methods are provided to validate configuration options. You can
also define additional validation functions or override and redefine any of
these.

    * ValidateString
    * ValidateAlphaString
    * ValidateAlphaNumericString
    * ValidateEmailAddress
    * ValidateDate
    * ValidateWebUrl
    * ValidateIPAddress
    * ValidateFilename
    * ValidatePath
    * ValidateLocalPath
    * ValidateMySQLServer
    * ValidateMySQLDatabase

### Debug log

By specifying the -v command line option a debugging log file will be created.
By default the file is created in the current directory and called 'debug.log'.
You can change the location and name of the log file by updating the
self::debugLogFilename property.

### Verbosity levels

Each debug statement can specify a verbosity level that must be specified on
the command line in order for the statement to be output. This allows the
person running the script to control how much detail is put into the debugging
log file.

By default, the following verbosity levels are used for indicating certain
events.

    * level 0 = outputting the errorMsg property when it's set
    * level 1 = indicating when methods start and stop and calling parameters
    * level 2 = conditionals indicated (if, for, while, switch, etc)
    * level 3 = additional details like variable values


## What's with the name?

The name is a play on my name. My last name is commonly misspelled with a "W",
thus Wrentschler rather than Rentschler because it's pronounced "wrench-ler".
Since this is a library of code much like a toolbox and a wrench is typically
found in a toolbox, "wrentch" seemed appropriate. The "P" can stand for "Paul"
or "PHP" which ever you prefer.
