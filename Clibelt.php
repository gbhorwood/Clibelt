<?php

/**
 * Rate of speed of spinners and progress bars.
 * values given in miliseconds for usleep() between frame updates of animation
 */
define('DELAY_SLOW', 7500000);
define('DELAY_MED', 300000);
define('DELAY_FAST', 150000);
define('DELAY_VERY_FAST', 50000);

/**
 * Constants to define animation to use when running code with background()
 */
define('SPIN', 1); // ASCII rotating spinner
define('PROGRESS', 2); // Progress bar of #'s left to right


/**
 * Convenience defines of meta characters
 */
define('BACKSPACE', chr(8));
define('ESC', "\033"); // for use with ANSI codes

/**
 * ANSI color codes for output styling. Background colors are calculated from these foreground codes.
 */
define('BLACK', '30');
define('RED', '31');
define('GREEN', '32');
define('YELLOW', '33');
define('BLUE', '34');
define('MAGENTA', '35');
define('CYAN', '36');
define('WHITE', '37');

/**
 * ANSI styling codes.
 */
define('NORMAL', '0');
define('BOLD', '1');
define('ITALIC', '3'); // limite terminal support. ymmv.
define('UNDERLINE', '4');
define('STRIKETHROUGH', '9');
define('REVERSE', '7');

/**
 * Convenience ANSI codes
 */
define('CLOSE_ANSI', ESC."[0m"); // termination code to revert to default styling
define('BOLD_ANSI', ESC."[1m"); //
define('GREEN_ANSI', ESC."[32m"); //
define('RED_ANSI', ESC."[31m"); //

/**
 * Colorized output tags for PSR-2/RFC-5424 levels.
 */
define('OK', "[".ESC."[".GREEN."mOK".CLOSE_ANSI."] "); // non-standard
define('DEBUG', "[".ESC."[".YELLOW."mDEBUG".CLOSE_ANSI."] ");
define('INFO', "[".ESC."[".YELLOW."mINFO".CLOSE_ANSI."] ");
define('NOTICE', "[".ESC."[".YELLOW."mNOTICE".CLOSE_ANSI."] ");
define('WARNING', "[".ESC."[".YELLOW."mWARNING".CLOSE_ANSI."] ");
define('ERROR', "[".ESC."[".RED."mERROR".CLOSE_ANSI."] ");
define('CRITICAL', "[".ESC."[".RED."mCRITICAL".CLOSE_ANSI."] ");
define('ALERT', "[".ESC."[".RED."mALERT".CLOSE_ANSI."] ");
define('EMERGENCY', "[".ESC."[".RED."mEMERGENCY".CLOSE_ANSI."] ");

/**
 * serialized array of levels used to validate user-supplied level constants in write()
 */
define('VALID_LEVELS', serialize(array(
    OK,
    DEBUG,
    INFO,
    NOTICE,
    WARNING,
    ERROR,
    CRITICAL,
    ALERT,
    EMERGENCY, )));

/**
 * used for alignment of text, both in the terminal and inside of boxes.
 */
define('LEFT', 0);
define('RIGHT', 1);
define('CENTER', 2);

/**
 * constants for printlist() to determine classes of bullets
 */
define('BULLET_UNORDERED', 0);
define('BULLET_NUMBER', 1);
define('BULLET_LETTER_UPPERCASE', 2);
define('BULLET_LETTER_LOWERCASE', 3);
define('BULLET_ROMAN', 4);

define('KEY_RETURN', 10);
define('KEY_UP_ARROW', 65);
define('KEY_DOWN_ARROW', 66);
define('KEY_RIGHT_ARROW', 67);
define('KEY_LEFT_ARROW', 68);
define('KEY_TAB', 9);
define('KEY_BACKSPACE', 127);
define('KEY_DELETE', 126);
define('KEY_O', 111);

/**
 * Clibelt
 *
 * @author gbh
 *
 * needs: php-curl
 *
 * needs: stty
 *
 * may need: ANSI
 *
 * @todo banner() // printout text as a banner
 */
class Clibelt
{
    /**
     * Count of lines of last output. Used by erase()
     */
    private $lastPrintLineCount = null;

    /**
     * The text of the last user input
     */
    private $lastInput = null;

    /**
     * The array of cached menu output so it does not need to be rebuilt every time it's rendered for display
     */
    private $cachedMenuArray = null;

    /**
     * The cached length of the longest line in a menu so it does not need to be recalculated
     */
    private $cachedMaxContentLineLength = null;

    /**
     * @brief Gets the last input provided by the script user
     *
     * The key hit for calls to anyKey() are never stored here.
     *
     * @return String
     */
    public function getLastInput()
    {
        return $this->lastInput;
    } // getLastInput

    ##
    # Methods to read input piped in

    /**
     * @brief Tests whether there is piped input incoming on STDIN, returns boolean
     *
     * @return Boolean
     */
    public function testStdin()
    {
        $streamArray = [STDIN];
        $writeArray = [];
        $exceptArray = [];
        $streamCount = @stream_select($streamArray, $writeArray, $exceptArray, 0); // zero seconds on timeout since this is just for testing
        return (boolean) $streamCount;
    } // testStdin

    /**
     * @brief Returns the content of data piped in on STDIN as a string
     *
     * @return String
     */
    public function readStdin()
    {
        $returnStdin = null;

        // prevents infinite loop if no stream.
        if (!$this->testStdin()) {
            return;
        }

        while ($line = fgets(STDIN)) {
            $returnStdin .= $line;
        }

        return (string) $returnStdin;
    } // readStdin


    /**
     * @brief Returns an iteratable stream of data piped in on STDIN
     *
     * The stream returned from this method can be iterated over using foreach()
     * @code
     * foreach($cli->readStdinStream() as $list){
     *     ... do something with $list ...
     * }
     * @endcode
     *
     * @return Stream
     */
    public function readStdinStream()
    {
        // prevents infinite loop if no stream.
        if (!$this->testStdin()) {
            return [];
        }
        while ($line = fgets(STDIN)) {
            yield $line;
        }
    } // readStdinStream

    ##
    # Methods to read user supplied input

    /**
     * @brief Pauses script execution until the user hits any key.
     *
     * An optional custom prompt can by supplied.
     *
     * @param $prompt Optional display prompt. If none provided, default is "Hit any key to continue: "
     * @return void
     */
    public function anyKey($prompt = null)
    {
        if (!$prompt) {
            $prompt = "Hit any key to continue: ";
        }
        fwrite(STDOUT, $prompt);
        $this->getKeyDown();
        fwrite(STDOUT, PHP_EOL);
    } // anyKey


    /**
     * @brief Convenience method wrapping promptChoice() to offer yes/no choice as [y,n]
     *
     * @param $prompt String. Optional. The optional string to display as a prompt to the user. Default "Choose 'yes' or 'no'"
     * @param $default Char. Optional. Either 'y' or 'n'.
     *  The value to return if the user selects an invalid option or hits RETURN
     * @return String. Either 'y' or 'n'. Lowercase.
     */
    public function promptChoiceYn($prompt = "Choose 'yes' or 'no'", $default = null)
    {
        $default = strtolower($default);
        if ($default && !in_array($default, ["y", "n"])) {
            $default = null;
        }

        return $this->promptChoice($prompt, ["y", "n"], $default);
    } // promptChoiceYn


    /**
     * @brief Prompts the user to choose from a list of char values and returns the selection.
     *
     * This method provides a simple way for the script user to choose from a list of provided, single char, options.
     * The most common example would be yes/no choice presented as [y,n], but any list of characters and any prompt text
     * can be used. [y,n] is the default behaviour.
     *
     * A default option value can be set. If the script user makes a selection that is not in the list of options
     * or simply hits RETURN, and a default value is set, the default value is returned.
     *
     * If a default value is set, it is indicated both by being bolded in the displayed options list and by displayed in the
     * (Default:) part of the prompt.
     *
     * Options are case sensitive, ie an option list of [F,f] is valid.
     *
     * If no default value is set and the script user chooses and invalid option, the prompt is displayed again and will be
     * continued to be dispalyed until a valid option is selected.
     *
     * @param $prompt String. Optional. The string to display as a prompt to the user. Default "Choose one"
     * @param $options Array. Optional. An array of chars representing the options. Deafault ["y","n"]
     * @param $default Char. Optional. A char representing the default option if the user selects something
     *  not in the $options array. Default null.
     * @return String The value selected by the user as a single char
     * @note Only single chars are used as options and selections
     */
    public function promptChoice($prompt = "Choose one", $options = array("y", "n"), $default = null)
    {
        // The options[] argument is forced to an array of chars. If an array of strings is passed only the
        // the first letter is used. Since this method takes keystroke input without requiring a RETURN, it by
        // necessity can only work with single chars.
        $testOptions = array_map(function ($option) {
                return $option[0];
            },
            $options);

        // A string of the options is appended to the prompt, with the default option in bold
        // (if the terminal supports it).
        $defaults = array_fill(0, count($options), $default);
        $displayOptions = implode(",", array_map(function ($option, $default) {
                if ($option[0] == @$default[0]) {
                    return BOLD_ANSI.$option[0].CLOSE_ANSI;
                }

                return $option[0];
            },
            $options,
            $defaults));

        // The prompt displays the list of options as well as the default option, if it is set, in brackets afterwards
        $displayPrompt =  $prompt." [".$displayOptions."]";
        if ($default) {
            $displayPrompt .= "(Default $default)";
        }
        $displayPrompt .= ": ";

        // if the prompt is too long for the terminal, wrap at the word break. it looks nicer.
        $displayPrompt = $this->wrapToTerminalWidth($displayPrompt);

        fwrite(STDOUT, $displayPrompt); // direct output used for prompt to preserve ANSI encoding for bold

        // read user keydown until we get a valid response
        while (true) {
            // read the key down
            $userChoice = $this->getKeyDown();

            // selection not in list, use the default if one has been set
            if ($default && !in_array($userChoice, $testOptions)) {
                $userChoice = $default;
                break; // usable input, break from loop
            }

            // selection is in list of options
            elseif (in_array($userChoice, $testOptions)) {
                break; // usable input, break from loop
            }

            // bad selection, reprint prompt and continue with the loop
            else {
                if ($userChoice != PHP_EOL) {
                    fwrite(STDOUT, $userChoice); // echo back user's keystroke so they can see their invalid choice
                }
                fwrite(STDOUT, PHP_EOL.$displayPrompt);
            }
        }

        fwrite(STDOUT, PHP_EOL);

        // log the user choice in lastInput so it can be retrieved after the return event
        $this->lastInput = $userChoice;

        // The user input is returned as a char
        return $userChoice;
    } // promptChoice


    /**
     * @brief Reads one line of user input and returns it
     *
     * This method reads one line only. User must hit RETURN to submit.
     *
     * The text entered by the user is returned by this method. It can also be accessed by calling
     * getLastInput(). The getLastInput method only returns the most recent user-input.
     *
     * Validation of the user-supplied text is the responsibility of the script author.
     * This can be done with a test in while() loop, eg
     *
     * @code
     *  // assign user input to variable and test it's an integer
     *  while($userInput = filter_var($cli->read("please enter an integer"), FILTER_VALIDATE_INT) === false) {
     *      $cli->clearLine(); // clears user's input so they can start again
     *      $cli->printout("must be an integer...",ERROR); // display error message
     *  }
     *  $cli->printout("user entered ".$userInput); // here's the good input
     *
     *  // you can stack more than one validation in a do/while loop like so
     *  do {
     *      $userInput = $cli->read("enter between 5 and 10 chars");
     *
     *      // error on first validation fail
     *      if(strlen($userInput) < 5) {
     *          $cli->clearLine();
     *          $cli->printout("too short", ERROR);
     *      }
     *
     *      // error on second validation fail
     *      if(strlen($userInput) > 10) {
     *          $cli->clearLine();
     *          $cli->printout("too long", ERROR);
     *      }
     *  }
     *  // any validation fail starts the loop again
     *  while(strlen($userInput) < 5 || strlen($userInput) > 10);
     * @endcode
     *
     * @param $prompt String. The prompt to display to the user.
     * @return Mixed
     */
    public function read($prompt)
    {
        // force echo on
        system("stty echo");

        $this->printout($prompt.":");
        $userInput = trim(fgets(STDIN));

        // log the user choice in lastInput so it can be retrieved after the return event
        $this->lastInput = $userInput;

        return $userInput;
    } // read


    /**
     * @brief Reads user input with star-echo for privacy
     *
     * @param $prompt String. The prompt to display before user input
     * @return String
     */
    public function readPassword($prompt)
    {
        // output user-supplied prompt
        $this->printout($prompt.":");

        // the array of chars of user input
        $enteredCharsArray = [];

        // loop while polling user input on STDIN
        while (true) {
            // read user key down event into enteredChar for adding to array and testing
            // for special keys
            $enteredChar = $this->getKeyDown();

            // RETURN key. break loop to return entered text
            if (ord($enteredChar) == KEY_RETURN) {
                break;
            }

            // backspace key. delete previous char and backspace previous output star
            elseif (ord($enteredChar) == KEY_BACKSPACE || ord($enteredChar) == KEY_DELETE) {
                array_pop($enteredCharsArray);
                fwrite(STDOUT, BACKSPACE);
                fwrite(STDOUT,  "\033[0K"); // erase line
            }

            // any other key. add to array of entered chars
            else {
                $enteredCharsArray[] = $enteredChar;
                fwrite(STDOUT, "*");
            }
        } // while(true)

        fwrite(STDOUT, PHP_EOL);

        // log the user choice in lastInput so it can be retrieved after the return event
        $this->lastInput = implode("", $enteredCharsArray);

        // user got here by hitting RETURN. build string from array and return.
        return implode("", $enteredCharsArray);
    } // readPassword


    /**
     * @brief Outputs a menu, navigable with arrow or tab keys, with optional alignment and styling
     *
     * This method builds and displays a menu of the provided $options. The user can navigate the menu by either
     *   * Using the up and down arrow keys to highlight the selection and hitting RETURN to select
     *   * Using the tab key to scroll down the menu and hitting RETURN to select
     *	 * Pressing the key for the option, if the $options array is keyed by a single char
     *
     * The value returned is the key of the $options element that was selected.
     *
     * The menu continues to display until the user makes a correct selection.
     *
     * The foreground and backgroud colours of the menu can be set using the $foregroundColour and $backgroundColour
     * arguments. Colours must be one of the pre-set ANSI constants: BLACK, RED, GREEN, YELLOW, BLUE, MAGENTA, CYAN or WHITE.
     *
     * The menu option currently hightlighted by the user is shown by having the colour options reversed using ANSI REVERSE.
     *
     * The menu is displayed inside of a box. The text in the box can be aligned with the $innerAlign argument by passing
     * one of the pre-determined alignment constants: LEFT, RIGHT or CENTER.
     *
     * The menu box can be aligned in the terminal using the $outerAlign argument. Valid options are LEFT, RIGHT or CENTER.
     *
     * The menu box may wrap the contents of the description or options in order to fit the whole box nicely in the width of
     * of the terminal. Line breaks provided in the content of options and the description are preserved.
     *
     * sample usage:
     * @code
     * $description = "Which band can be considered the first true punk band?"
     * $options = array(
     *     "a" => "New York Dolls",
     *     "b" => "Rocket from the Tombs",
     *     "c" => "Death"
     * );
     * $result = $cli->menu($description, $options);
     * // the key, either 'a', 'b' or 'c' is in $result.
     * @endcode
     *
     * @param $description String. The description that goes above the list of selectable options.
     * @param $options Array.  The associative array of options to choose from.
     * @param $innerAlign Pre-defined constant. Optional. The alignment of the text inside the box. One of LEFT, RIGHT or CENTER
     * @param $outerAlign Pre-defined constant. Optional. The alignment of the box in the terminal. One of LEFT, RIGHT or CENTER
     * @param $foregroundColour Pre-defined constant. Optional. The colour of the foreground text. One of the pre-set ANSI colours.
     * @param $backgroundColour Pre-defined constant. Optional. The colour of the background of the text. One of the pre-set ANSI colours.
     * @return String
     */
    public function menu($description, $options, $innerAlign = LEFT, $outerAlign = LEFT, $foregroundColour = null, $backgroundColour = null)
    {
        // hightlight the first option in the menu to start
        $selectedIndex = 0;

        // retrieve the key by the index
        $selectedKey = array_keys($options)[$selectedIndex];

        // draw the menu
        $this->printoutMenuBox($description, $options, $selectedKey, $innerAlign, $outerAlign, $foregroundColour, $backgroundColour);

        // loop awaiting user input
        while (true) {
            // get key down event from user
            $userChoice = $this->getKeyDown();

            // scroll down
            // 66 down arrow
            // 9 tab
            if (ord($userChoice) == KEY_DOWN_ARROW || ord($userChoice) == KEY_TAB) {
                // delete ouput of menu
                $this->erase();

                // update the menu to highlight the next option
                if ($selectedIndex < count($options)-1) {
                    $selectedIndex++;
                }
                // wrap back to top
                else {
                    $selectedIndex = 0;
                }

                // draw the new menu
                $this->printoutMenuBox(
                    $description,
                    $options,
                    array_keys($options)[$selectedIndex],
                    $innerAlign,
                    $outerAlign,
                    $foregroundColour,
                    $backgroundColour);
            }

            // scroll up
            // 65 up arrow
            if (ord($userChoice) == KEY_UP_ARROW) {
                // delete output of menu
                $this->erase();

                // update the menu to highlight previous option
                // wrap back to bottom
                if ($selectedIndex == 0) {
                    $selectedIndex = count($options)-1;
                } else {
                    $selectedIndex--;
                }

                // draw the new menu
                $this->printoutMenuBox(
                    $description,
                    $options,
                    array_keys($options)[$selectedIndex],
                    $innerAlign,
                    $outerAlign,
                    $foregroundColour,
                    $backgroundColour);
            }

            // select and return current key
            // 10 return
            if (ord($userChoice) == KEY_RETURN) {
                $returnVal = array_keys($options)[$selectedIndex];
                break;
            }

            // key selection of option
            // if pressed key is the char of an option key select and return
            if (in_array($userChoice, array_keys($options))) {
                $returnVal = $userChoice;
                break;
            }
        } // while true

        $this->lastInput = $returnVal;

        // cached values for this menu are now cleared so future menus are forced to build and draw
        $this->cachedMaxContentLineLength = null;
        $this->cachedMenuArray = null;

        // return selected key
        return $returnVal;
    } // menu


    /**
     * @brief A single-line menu, navigable by left/right arrows or tab key
     *
     * This method builds and displays a horizontal menu of the provided $options. The user can navigate the menu by either
     *   * Using the left and right arrow keys to highlight the selection and hitting RETURN to select
     *   * Using the tab key to scroll rightward through the menu and hitting RETURN to select
     *
     * The value returned is the key of the $options element that was selected.
     *
     * The menu continues to display until the user makes a correct selection.
     *
     * The foreground and backgroud colours of the menu can be set using the $foregroundColour and $backgroundColour
     * arguments. Colours must be one of the pre-set ANSI constants: BLACK, RED, GREEN, YELLOW, BLUE, MAGENTA, CYAN or WHITE.
     *
     * The menu option currently hightlighted by the user is shown by having the colour options reversed using ANSI REVERSE.
     *
     * @param $description String
     * @param $options Array. List of options for this menu. The key of the selected item is returned.
     * @param $selectedKey String. Optional. The key to set as the initial selected key.
     * @param $alignment pre-defined constant. Optional. The alignment of the menu in the terminal.
     *  One of LEFT, RIGHT or CENTER.
     * @param $foregroundColour pre-defined constant. Optional. The foreground colour.
     * @param $backgroundColour pre-defined constant. Optional. The background colour.
     * @return String
     */
    public function menuhorizontal($description, $options, $selectedKey = null, $alignment = LEFT, $foregroundColour = null, $backgroundColour = null)
    {
        // set the selected key and index

        // if no selectedKey set or if it is invalid, default to the first key
        if (!$selectedKey || !in_array($selectedKey, array_keys($options))) {
            $selectedIndex = 0;
            // retrieve the key by the index
            $selectedKey = array_keys($options)[$selectedIndex];
        } else {
            $selectedIndex = array_search($selectedKey, array_keys($options));
        }

        // print the menu for the first time
        $this->printoutMenuhorizontal($description, $options, $selectedKey, $alignment, $foregroundColour, $backgroundColour);

        // loop awaiting user input
        while (true) {
            // get key down event from user
            $userChoice = $this->getKeyDown();

            // right arrow
            if (ord($userChoice) == KEY_RIGHT_ARROW || ord($userChoice) == KEY_TAB) {
                // delete ouput of menu
                $this->erase();

                // update the menu to highlight the next option
                if ($selectedIndex < count($options)-1) {
                    $selectedIndex++;
                }
                // wrap back to top
                else {
                    $selectedIndex = 0;
                }

                // redraw menu to update highlighting of selected key
                $this->printoutMenuhorizontal(
                    $description,
                    $options,
                    array_keys($options)[$selectedIndex],
                    $alignment,
                    $foregroundColour,
                    $backgroundColour);
            }

            // left arrow
            if (ord($userChoice) == KEY_LEFT_ARROW) {
                // delete ouput of menu
                $this->erase();

                // update the menu to highlight previous option
                // wrap back to bottom
                if ($selectedIndex == 0) {
                    $selectedIndex = count($options)-1;
                } else {
                    $selectedIndex--;
                }

                // redraw menu to update highlighting of selected key
                $this->printoutMenuhorizontal(
                    $description,
                    $options,
                    array_keys($options)[$selectedIndex],
                    $alignment,
                    $foregroundColour,
                    $backgroundColour);
            }

            // select and return current key
            // 10 return
            if (ord($userChoice) == KEY_RETURN) {
                $returnVal = array_keys($options)[$selectedIndex];
                break;
            }
        }

        $this->lastInput = $returnVal;

        return $returnVal;
    } // menuhorizontal


    /**
     * @brief Outputs a file selection interface, navigable with arrow or tab keys, with optional alignment and styling
     *
     * This method builds and displays a file selection menu with the initial direcotry defined by arg $directory
     * The user can navigate the menu by either
     * 		* Using the up arrow to scroll up the file list
     * 		* Using the down arrow or tab key to scroll down the file list
     * 		* Using the RETURN or 'o' key to open a directory
     * 		* Using the RETURN or 'o' key to select a file
     *
     * The fileselect interface continues to display until the user makes a valid selection.
     *
     * The foreground and backgroud colours of the menu can be set using the $foregroundColour and $backgroundColour
     * arguments. Colours must be one of the pre-set ANSI constants: BLACK, RED, GREEN, YELLOW, BLUE, MAGENTA, CYAN or WHITE.
     *
     * The current highlighted file is shown by having the colour options reversed using ANSI REVERSE.
     *
     * The fileselect interface box can be aligned in the terminal using the align argument; one of LEFT, RIGHT or CENTER.
     * Default is LEFT.
     *
     * The string passed as $description may be word wrapped to fit the fileselect interface width.
     *
     * Example usage:
     * @code
     *	$cli = new Clibelt();
     *	$selectedFile = $cli->fileselect(
     *	    "/tmp/", // directory to open
     *	    "select any file",  // description to put in fileselect box
     *	    LEFT, // align in terminal
     *	    WHITE, // foreground colour
     *	    BLUE); // background colour
     *
     *	print "absolute path to select file is ".$selectedFile;
     * @endcode
     *
     * Throws a ClibeltException on errors:
     *   * Starting directory does not exist
     *   * Starting directory is not readable
     *
     * @param $directory String. The initial directory opened in the fileselect interface.
     * @param $description String. Optional. The text description to put in the fileselect interface.
     * @param $alignment pre-defined Constant. Optional. Alignment of the fileselect interface in the terminal.
     * @param $foregroundColour pre-defined Constant. Optional. Foreground colour of the text
     * @param $backgroundColour pre-defined Constant. Optional. Background colour of the text
     * @param $returnDirectory Boolean. Optional.
     * @throws ClibeltException
     * @return String
     */
    public function fileselect($directory,
        $description = null,
        $alignment = LEFT,
        $foregroundColour = null,
        $backgroundColour = null,
        $returnDirectory = false)
    {
        /**
         * If starting directory is not a valid directory
         * ClibeltException error code 9 on failure and returns false.
         */
        if (!is_dir($directory)) {
            throw new ClibeltException("starting directory $directory is not a directory", 9, __FUNCTION__);
        }

        /**
         * If starting directory is not readable
         * ClibeltException error code 10 on failure and returns false.
         */
        if (!is_readable($directory)) {
            throw new ClibeltException("starting directory $directory is not readable", 10, __FUNCTION__);
        }

        // remove whitespace and enforce trailing "/" on directory
        $directory = trim($directory);
        if (substr($directory, -1) != "/") {
            $directory .= "/";
        }

        // get index of the first file, for setting default select highlight
        $selectedIndex = $this->getIndexOfFirstFile($directory);

        // initial output of the file select interface
        $this->printoutFileselect($directory, $description, $selectedIndex, $alignment, $backgroundColour, $foregroundColour);

        // read user keydown until we get a valid response
        while (true) {
            // get key down event from user
            $userChoice = $this->getKeyDown();

            // scroll down
            if (ord($userChoice) == KEY_DOWN_ARROW || ord($userChoice) == KEY_TAB) {
                // delete ouput of menu
                $this->erase();

                // set next file down as selected
                $selectedIndex++;

                // stop overrun of bottom of directory list
                if ($selectedIndex >= count(scandir($directory))) {
                    $selectedIndex = count(scandir($directory))-1;
                }

                // printout fileselect interface
                $this->printoutFileselect($directory,
                    $description,
                    $selectedIndex,
                    $alignment,
                    $backgroundColour,
                    $foregroundColour);
            }

            // scroll up
            elseif (ord($userChoice) == KEY_UP_ARROW) {
                // delete ouput of menu
                $this->erase();

                // set next file up as selected
                $selectedIndex--;

                // stop overrun off the top of directory list
                if ($selectedIndex < 0) {
                    $selectedIndex = 0;
                }

                // printout  fileselect interface
                $this->printoutFileselect($directory,
                    $description,
                    $selectedIndex,
                    $alignment,
                    $backgroundColour,
                    $foregroundColour);
            }

            // select event
            elseif (ord($userChoice) == KEY_RETURN || ord($userChoice) == KEY_O) {
                // the full path of the file or directory selected
                $selectedItem = realpath($directory.scandir($directory)[$selectedIndex]);

                // element is a file, always return element
                if (is_file($selectedItem)) {
                    $this->lastInput = $selectedItem;

                    return $selectedItem;
                }

                // element is a directory, select event is 'o', return element
                elseif ($returnDirectory && ord($userChoice) == KEY_O) {
                    $this->lastInput = $selectedItem;

                    return $selectedItem;
                }

                // element is directory, select event is RETURN, open directory and continue with fileselect interface
                else {
                    $directory = $selectedItem."/"; // ensure trailing slash
                    $selectedIndex = $this->getIndexOfFirstFile($directory); // set index to first real file after '.' and '..'
                    $this->erase();
                    $this->printoutFileselect($directory,
                        $description,
                        $selectedIndex,
                        $alignment,
                        $backgroundColour,
                        $foregroundColour);
                }
            }
        } // while(true)
    } // fileselect


    /**
     * @brief Outputs a datepicker interface and returns selected date as DateTime object
     *
     * Returned DateTime object is set to the timezone of the system
     *
     * @param $prompt String. Optional. The prompt to display in the datepicker interface. Defalt "Select a date".
     * @return DateTime
     * @todo Add alignment and styling
     */
    public function datepicker($prompt = "Select a date")
    {
        // date array set to initial state of today
        $dateArray = [(int) date("Y"),
            (int) date("m"),
            (int) date("j"), ];

        // pre-select year
        $currentIndex = 0;

        // output the datepicker interface
        $this->printDatepicker($prompt, $dateArray, $currentIndex);

        $userInputArray = [];

        // loop while taking user input
        while (true) {
            // get user key event
            $userInput = $this->getKeyDown();

            // break to return statement
            if (ord($userInput) == KEY_RETURN) {
                break;
            }

            // move to the next date segment on tab or right arrow
            elseif (ord($userInput) == KEY_TAB || ord($userInput) == KEY_RIGHT_ARROW) {
                $userInputArray = [];
                $currentIndex++;
                if ($currentIndex > 2) {
                    $currentIndex = 0;
                }
                $this->erase();
                $this->printDatepicker($prompt, $dateArray, $currentIndex);
            }

            // move to the previous date segment on left arrow
            elseif (ord($userInput) == KEY_LEFT_ARROW) {
                $userInputArray = [];
                $currentIndex--;
                if ($currentIndex < 0) {
                    $currentIndex = 2;
                }
                $this->erase();
                $this->printDatepicker($prompt, $dateArray, $currentIndex);
            }

            // decrement the date by one step
            elseif (ord($userInput) == KEY_DOWN_ARROW) {
                $userInputArray = [];
                $dateArray = $this->decrementDate($dateArray, $currentIndex);
                $this->erase();
                $this->printDatepicker($prompt, $dateArray, $currentIndex);
            }

            // increment the date by one step
            elseif (ord($userInput) == KEY_UP_ARROW) {
                $userInputArray = [];
                $dateArray = $this->incrementDate($dateArray, $currentIndex);
                $this->erase();
                $this->printDatepicker($prompt, $dateArray, $currentIndex);
            } elseif (ctype_alnum($userInput)) {
                $userInputArray[] = $userInput;
                $dateArray = $this->updateDate($dateArray, $currentIndex, $userInputArray);
                $this->erase();
                $this->printDatepicker($prompt, $dateArray, $currentIndex);
            } elseif (ord($userInput) == KEY_BACKSPACE) {
                array_pop($userInputArray);
                $dateArray = $this->updateDate($dateArray, $currentIndex, $userInputArray);
                $this->erase();
                $this->printDatepicker($prompt, $dateArray, $currentIndex);
            }
        }

        return new DateTime(implode("-", $dateArray));
    } // datepicker


    ##
    # Methods to do formatted output

    /**
     * @brief Writes string to STDOUT with optional ANSI formatting
     *
     * This is used instead of the print() or echo() commands as cli applications differentiate
     * output and error by stream.
     *
     * Exmples:
     *
     * @code
     *
     *  $cli->printout("this is ok", OK); // [OK] this is ok
     *  $cli->printout("warning here", WARNING); // [WARNING] this is ok
     *  $cli->printout("I am white text on a red background", null, WHITE, RED);
     *  $cli->printout("I am green text underlined", null, GREEN, UNDERLINE);
     *
     * @endcode
     *
     * @param $text String. The string to write to the stream
     * @param $level Pre-defined constant. Optional PSR-2 level to tag the output with. Valid constants to use for $level are:
     *      * OK
     *      * DEBUG
     *      * INFO
     *      * NOTICE
     *      * WARNING
     *      * ERROR
     *      * CRITICAL
     *      * ALERT
     *      * EMERGENCY
     * @param $foreground Pre-defined constant. Optional foreground color of text. Valid constants for $foreground are:
     *      * BLACK
     *      * RED
     *      * GREEN
     *      * YELLOW
     *      * BLUE
     *      * MAGENTA
     *      * CYAN
     *      * WHITE
     * @param $background Pre-defined constant. Optional background color or font style. Valid constants for $background are:
     *      * NORMAL
     *      * BOLD
     *      * ITALIC
     *      * UNDERLINE
     *      * STRIKETHROUGH
     *      * BLACK
     *      * RED
     *      * GREEN
     *      * YELLOW
     *      * BLUE
     *      * MAGENTA
     *      * CYAN
     *      * WHITE
     * @param $alignment Pre-defined constant. Optional alignment for text. Default is left. Valid constant is CENTER
     * @return void
     */
    public function printout($text, $level = null, $foreground = null, $background = null, $alignment = null)
    {
        $this->write($text, STDOUT, $level, $foreground, $background, $alignment);
    } // printout


    /**
     * @brief Writes string to STDERR with option ANSI formatting
     *
     * This is used instead of the print() or echo() commands as cli applications differentiate
     * output and error by stream.
     *
     * @param $text String. The string to write to the stream
     * @param $level Pre-defined constant. Optional PSR-2 level to tag the output with. Valid constants to use for $level are:
     *      * OK
     *      * DEBUG
     *      * INFO
     *      * NOTICE
     *      * WARNING
     *      * ERROR
     *      * CRITICAL
     *      * ALERT
     *      * EMERGENCY
     * @param $foreground Pre-defined constant. Optional foreground color of text. Valid constants for $foreground are:
     *      * BLACK
     *      * RED
     *      * GREEN
     *      * YELLOW
     *      * BLUE
     *      * MAGENTA
     *      * CYAN
     *      * WHITE
     * @param $background Pre-defined constant. Optional background color or font style. Valid constants for $background are:
     *      * NORMAL
     *      * BOLD
     *      * ITALIC
     *      * UNDERLINE
     *      * STRIKETHROUGH
     *      * BLACK
     *      * RED
     *      * GREEN
     *      * YELLOW
     *      * BLUE
     *      * MAGENTA
     *      * CYAN
     *      * WHITE
     * @param $alignment Pre-defined constant. Optional alignment for text. Default is left. Valid constant is CENTER
     * @return void
     */
    public function printerr($text, $level = null, $foreground = null, $background = null, $alignment = null)
    {
        $this->write($text, STDERR, $level, $foreground, $background, $alignment);
    } // printerr


    /**
     * @brief Outputs the provided text in a box bordered by hashes
     *
     * The box is bordered by # chars, with an inner left and right margin of 4 spaces.
     *
     * The box can be styled using the color and alignment constants as accepted by printout()
     * and printerr().
     *
     * Box is aligned centre by default.
     * @param $text String.
     * @param $foreground Pre-defined constant. Optional. A color constant as used in printout()
     * @param $background Pre-defined constant. Optional. A color constant as used in printout()
     * @param $alignment Pre-defined constant. Optional. An alignment constant as used in printout()
     * @return void
     * @todo make boxMargin settable by arg
     */
    public function box($text, $foreground = null, $background = null, $alignment = CENTER)
    {
        // inner left and right margin, in spaces
        $boxMargin = 4;

        // wrap text to fit inside box
        $text = $this->wrapToTerminalWidth($text, ($boxMargin*2) +2);

        // get the length of the longest line if there are more than one.
        // this is used to determine the overall width of the box and pad lines for centreing
        $lengthOfLongestLine = $this->getLengthOfLongestLine($text);

        // construct an array of all the lines of the box, excluding the top and bottom bars
        $box = array_map(
            function ($longext, $margin, $line) {
                $boxLine = "#";

                for ($i = 0;$i<$margin;$i++) {
                    $boxLine .= " ";
                }

                for ($i = 0;$i<ceil(($longext-$this->strlenAnsiSafe($line))/2);$i++) {
                    $boxLine .= " ";
                }
                $boxLine .= $line;

                for ($i = 0;$i<floor(($longext-$this->strlenAnsiSafe($line))/2);$i++) {
                    $boxLine .= " ";
                }

                for ($i = 0;$i<$margin;$i++) {
                    $boxLine .= " ";
                }

                $boxLine .= "#";

                return $boxLine;
            },
            array_fill(0, count(explode(PHP_EOL, $text)), $lengthOfLongestLine),
            array_fill(0, count(explode(PHP_EOL, $text)), $boxMargin),
            explode(PHP_EOL, $text));

        // print top bar of box
        $this->write(implode("", array(str_pad("", $lengthOfLongestLine+($boxMargin *2)+2, "#"))),
            STDOUT, null, $foreground, $background, $alignment);

        // print contents of box
        while (list(, $line) = each($box)) {
            $this->write($line, STDOUT, null, $foreground, $background, $alignment);
        }

        // print bottom bar of box
        $this->write(implode("", array(str_pad("", $lengthOfLongestLine+($boxMargin *2)+2, "#"))),
         STDOUT, null, $foreground, $background, $alignment);

        // override lastPrintLineCount for erase()
        $this->lastPrintLineCount = count($box)+2;
    } // box


    /**
     * @brief Takes an array and outputs it as a list
     *
     * The array can be of an arbitrary depth.
     *
     * The argument bulletsArray determines the type of bullet applied to the list level. It's values can be
     * one of:
     *   * BULLET_UNORDERED
     *   * BULLET_NUMBER
     *   * BULLET_LETTER_LOWERCASE
     *   * BULLET_LETTER_UPPERCASE
     *   * BULLET_ROMAN
     *
     * Example usage:
     * @code
     * $cli = new Clibelt();
     *
     * // the array to print as a list. three deep
     * $listArray = array (
     *     "first level list item 1",
     *     "first level list item 2",
     *     "first level list item 3\nsecond line of item 3.",
     *     array (
     *         "second level list item 1",
     *         array (
     *             "third level list item 1",
     *             "third level list item 2"
     *         ),
     *     ),
     *     "first level list last item"
     * );
     *
     * // bullets, one for each level of the array
     * $bulletsArray = array (
     *     BULLET_LETTER_UPPERCASE,
     *     BULLET_NUMBER,
     *     BULLET_LETTER_LOWERCASE
     * );
     *
     * // call printlist()
     * $cli->printlist($listArray, $bulletsArray, 4, 4);
     *
     * // the output looks like this
     * //     A. first level list item 1
     * //     B. first level list item 2
     * //     C. first level list item 3
     * //        second line of item 3.
     * //         1. second level list item 1
     * //             a) third level list item 1
     * //             b) third level list item 2
     * //     D. first level list last item
     * @endcode
     *
     * @param $listArray Array. The array of values to render as a list.
     * @param $bulletsArray Array. Optional array of bullet types.
     * @param $listIndentSize Int. Optional. Indentation of the entire list, number of spaces.
     * @param $subListIndentSize Int. Optional. Amount to indent sublists from the top level list, number of spaces
     * @return void
     */
    public function printlist($listArray, $bulletsArray = [], $listIndentSize = 4, $subListIndentSize = 4)
    {
        // modify bulletsArray to include both the bullet type and the count value, which is used
        // to increment the bullet with each new item, ie 'a' to 'b' or '1' to '2' and so on.
        $countableBulletsArray = [];
        while (list($key, $val) = each($bulletsArray)) {
            $countableBulletsArray[$key] = ["bullet" => $val, "count" => 0];
        }

        // get the list as an array
        $list = $this->getPrintlist($listArray,
            $countableBulletsArray,
            $listIndentSize,
            $subListIndentSize);

        // wrap the value to fit terminal width
        // bust value into array of lines so right vertical flushing can be done
        while (list($key, $val) = each($list)) {
            $list[$key]["value"] = explode(PHP_EOL, $this->wrapToTerminalWidth($val["value"], $this->strlenAnsiSafe($val["bullet"])));
        }
        reset($list);

        // build a printable string of the list from the list array.
        $listString = null;
        while (list($key, $val) = each($list)) {
            // add the bullet and the first line of the value
            $listString .= $val["bullet"].$val["value"][0].PHP_EOL;
            // if there are other lines to the value, append them with padding for right vertical flush
            for ($i = 1;$i<count($val["value"]);$i++) {
                $listString .= str_pad("", strlen($val["bullet"]), " ").$val["value"][$i].PHP_EOL;
            }
        }

        // output the list to STDOUT
        $this->printout($listString);
    } // printlist


    /**
     * @brief Erases the output of the last call to either printout() or printerr()
     *
     * Only erases the last output, to either STDOUT or STDERR. If this method is called more than once successively
     * the subsequent calls will do nothing.
     * @return void
     * @note This method relies on cursor control. If either stream's output is being directed elsewhere, behaviour may be affected.
     */
    public function erase()
    {
        for ($i = 0;$i<$this->lastPrintLineCount;$i++) {
            fwrite(STDOUT,  "\033[F");  // move up one line
            fwrite(STDOUT,  "\033[2K"); // erase line
        }
        $this->lastPrintLineCount = null;
    } // erase


    /**
     * @brief Clears the last line of output
     *
     * @return void
     * @note This method relies on cursor control. If either stream's output is being directed elsewhere, behaviour may be affected.
     */
    public function clearLine()
    {
        fwrite(STDOUT,  "\033[F");  // move up one line
        fwrite(STDOUT,  "\033[2K"); // erase line
        $this->lastPrintLineCount = null;
    }

    /**
     * @brief Clears screen and homes cursor
     *
     * @return void
     */
    public function clear()
    {
        fwrite(STDOUT,  "\033[2J");     // ANSI to clear screen and home cursor
        fwrite(STDOUT,  "\033[0;0f");   // ANSI to move cursor to top left position
        $this->lastPrintLineCount = null;
    }

    ##
    # Methods to run code in background processes

    /**
     * @brief Runs executable PHP code in the background with an optional progress animation
     *
     * Throws a ClibeltException on errors
     *   * Invalid code for background operation
     *
     * @param $function Either a callable or string containing valid PHP
     * @param $args Optional array of arguments for callable
     * @param $progressType The optional type of animation to display. One of SPIN or PROGRESS
     * @param $delay The speed at which the optional animation runs. One of DELAY_SLOW, DELAY_MED, DELAY_FAST or DELAY_VERY_FAST
     * @throws ClibeltException
     * @return mixed
     */
    public function background($function, $args = [], $progressType = SPIN, $delay = DELAY_MED)
    {
        // args may be sent as either an array of args, ie for call_user_func_array(), a string containg the value of
        // one arg, or null. Prepare args into an array for usage.
        $args = $this->prepArgs($args);

        /**
         * If code passed to background is not executable
         * ClibeltException error code 6 on failure and returns false.
         */
        if (!is_callable($function) && !is_string($function)) {
            throw new ClibeltException("Invalid code for background operation", 6, __FUNCTION__);
        }

        /**
         * Process fork is attempted.
         */
        $pid = pcntl_fork();

        /**
         * If a child process could not be spawned, die.
         */
        if ($pid == -1) {
            die("Could not spawn child process");
        }

        /**
         * Code execution is done in the child process
         */
        if ($pid) {
            $returnedVal = null;
            $parseError = false;
            // a callable function is passed
            if (is_callable($function)) {
                $returnedVal = @call_user_func_array($function, $args);
            }

            // a string of PHP code is passed
            //elseif (is_string($this->function)) {
            elseif (is_string($function)) {
                try {
                    $returnedVal = @eval($function);
                }
                catch(ParseError $pe) {
                    $parseError = true;
                }
            }
        }

        /**
         * Progress display is done in the parent process
         */
        else {
            if ($progressType == PROGRESS) {
                $this->progress($delay);
            } elseif ($progressType == SPIN) {
                $this->spin($delay);
            }
        }

        /**
         * at end of execution, SIGKILL the progress animation
         */
        posix_kill($pid, SIGKILL);

        if($parseError) {
            throw new ClibeltException("Invalid code for background operation", 6, __FUNCTION__);
        }

        fwrite(STDOUT, PHP_EOL);

        return $returnedVal;
    } // background


    /**
     * @brief A somewhat safe file copy method with optional update animation.
     *
     * Animation is based on file size of destination file and is moderatley accurate.
     *
     * Usage example:
     * @code
     *
     * $cli = new Clibelt();
     * try {
     *     $copyCheck = $cli->safeCopy("/path/to/souce/file", "/path/to/destination/file");
     * }
     * catch(ClibeltException $cbe) {
     * ...
     * }
     * @endcode
     *
     * Throws a ClibeltException on errors:
     *   * Souce file does not exist
     *   * Souce file is not readable
     *   * Destination directory does not exist
     *   * Destination directory is not writeable
     *   * Insufficient diskspace for operation
     *
     * @param $sourceFile Path of file to copy
     * @param $destFile String. Path to the file or directory for the destination of the copied file.
     * If a directory is provided, the destination file is given the same name as it has in the source file path.
     * @return String.          The path of the newly-copied file
     * @throws ClibeltException
     * @note spawns child process
     */
    public function safeCopy($sourceFile, $destFile)
    {
        // the destination file may be a directory. if that is the case the desired behaviour is to use the filename
        // from the source file path.
        if (is_dir($destFile)) {
            $destFile .= "/".basename($sourceFile);

            // the directory path passed may or may not have been terminated with a /. if it was, we will have
            // a double / in the path. remove it.
            $destFile = preg_replace("/\/\//", "/", $destFile);
        }

        // tests the source file exists.
        // ClibeltException error code 1 on failure and returns false.
        if (!@file_exists($sourceFile)) {
            throw new ClibeltException("Source file $sourceFile does not exist", 1, __FUNCTION__);
        }

        // tests the source file is readable.
        // ClibeltException error code 2 on failure and returns false.
        if (!@is_readable($sourceFile)) {
            throw new ClibeltException("Source file $sourceFile is not readable", 2, __FUNCTION__);
        }

        // tests the destination directory exists.
        // ClibeltException error code 3 on failure and returns false.
        if (!@file_exists(dirname($destFile))) {
            throw new ClibeltException("Destination directory ".dirname($destFile)." does not exist", 3, __FUNCTION__);
        }

        // tests the destination directory is writeable.
        // ClibeltException error code 4 on failure and returns false.
        if (!@is_writeable(dirname($destFile))) {
            throw new ClibeltException("Destination directory ".dirname($destFile)." is not writeable", 4, __FUNCTION__);
        }

        // tests is sufficient disk space for the operation.
        // ClibeltException error code 5 on failure and returns false.
        if (disk_free_space(dirname($destFile)) <= filesize($sourceFile)) {
            throw new ClibeltException("Insufficient disk space for operation", 5, __FUNCTION__);
        }

        // Process fork is attempted.
        $pid = pcntl_fork();

        // If a child process could not be spawned, die.
        if ($pid == -1) {
            die("Could not spawn child process");
        }

        // child process: Progress animation
        if ($pid) {
            copy($sourceFile, $destFile);
        }

        // parent processes: file copy
        else {
            $this->progressFilesizeTrack($destFile, filesize($sourceFile));
        }

        // at end of copy(), SIGKILL the progress animation
        posix_kill($pid, SIGKILL);

        // convenience message for script user
        fwrite(STDOUT, " done".PHP_EOL);

        return $destFile;
    } // safeCopy


    /**
     * @brief A somewhat safe file download method with a progress animation.
     *
     * Animation is based on file size of destination file and is moderatley accurate.
     *
     * Usage example:
     * @code
     * $cli = new Clibelt();
     * try {
     *     $cli->download("http://example.com/file.tar", "/path/to/destination/file.tar");
     * }
     * catch(ClibeltException $cbe) {
     * ...
     * }
     * @endcode
     *
     * Throws a ClibeltException on errors
     *   * Destination directory does not exist
     *   * Destination directory is not writeable
     *   * Insufficient disk space
     *   * Download error
     *
     * @param $url String. The url of the file to download
     * @param $destFile String. Path to the file or directory for the destination of the downloaded file.
     * If a directory is provided, the destination file is given the same name as it has in the url.
     * @throws ClibeltException
     * @return String.          The path of the newly-downloaded file
     * @note spawns child process
     * @note test with http://ipv4.download.thinkbroadband.com/512MB.zip
     */
    public function download($url, $destFile)
    {
        // the destination file may be a directory. if that is the case the desired behaviour is to use the filename
        // from the url. here we strip out the filename from the url (removing the query string if necessary), and
        // append it to the directory path.
        if (is_dir($destFile)) {
            $destFile .= "/".array_values(
                array_slice(explode("/", parse_url($url, PHP_URL_PATH)), -1)
            )[0];

            // the directory path passed may or may not have been terminated with a /. if it was, we will have
            // a double / in the path. remove it.
            $destFile = preg_replace("/\/\//", "/", $destFile);
        }

        // ClibeltException error code 3 on destination directory does not exist
        if (!@file_exists(dirname($destFile))) {
            throw new ClibeltException("Destination directory ".dirname($destFile)." does not exist", 3, __FUNCTION__);
        }

        // ClibeltException error code 4 on destination directory not writeable
        if (!@is_writeable(dirname($destFile))) {
            throw new ClibeltException("Destination directory ".dirname($destFile)." is not writeable", 4, __FUNCTION__);
        }

        // for progressFilesizeTrack(), we need to know the size of the file being
        // downloaded. we do this by making a CURLOPT_NOBODY call to url to get just
        // the headers. we harvest the Content-Length from there for file size.

        // curl call with no body being downloaded
        $timeout = 5;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // in seconds
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);

        // ClibeltException error code 7 on any curl error
        if ($res === false) {
            throw new ClibeltException(curl_error($ch), 7, __FUNCTION__);
        }

        // get size of file from Content-Length header as kb int
        $fileSizeKb = (int) implode(null, array_map(function ($header) {
                switch (strtolower((substr(trim($header), 0, strlen("Content-Length"))))) {
                    case "content-length" :
                        return floor(explode(" ", $header)[1]/1024);
                        break;
                    default :
                        return;
                }
            },
            explode("\n", $res)));

        curl_close($ch);

        // ClibeltException error code 5 on not sufficient disk space for the operation
        if (disk_free_space(dirname($destFile)) <= $fileSizeKb) {
            throw new ClibeltException("Insufficient disk space for operation", 5, __FUNCTION__);
        }

        // Process fork is attempted.
        $pid = pcntl_fork();

        // If a child process could not be spawned, die.
        if ($pid == -1) {
            die("Could not spawn child process");
        }

        // child process: show progress animation
        if ($pid) {
            // do the download.
            if (file_put_contents($destFile, fopen($url, 'r')) === false) {
                throw new ClibeltException("error downloading file", 8, __FUNCTION__);
            }
        }

        // parent process: do download
        else {
            $this->progressFilesizeTrack($destFile, $fileSizeKb);
        }

        // at end of copy(), SIGKILL the progress animation
        @posix_kill($pid, SIGKILL);

        // convenience message for script user
        fwrite(STDOUT, " done".PHP_EOL);

        return $destFile;
    } // download

    ##
    # Private methods

    /**
     * @brief Returns the strlen of the longest line in either the string or array passed
     *
     * Uses ANSI safe strlen() to strip out printable but unseen ANSI control chars.
     * @param $lines Mixed. Either a string of lines separated by PHP_EOL or an array of lines
     * @return Int
     */
    private function getLengthOfLongestLine($lines)
    {
        // if string, make an array
        if (is_string($lines)) {
            $lines = explode(PHP_EOL, $lines);
        }

        $lengthOfLongestLine = array_reduce(
            $lines,
            function ($max, $line) {
                if ($this->strlenAnsiSafe($line) > $max) {
                    return $this->strlenAnsiSafe($line);
                }

                return $max;
            }
        );

        return $lengthOfLongestLine;
    } // getLengthOfLongestLine


    /**
     * @brief Returns the longest line in either the string or array passed
     *
     * Uses ANSI safe strlen() to strip out printable but unseen ANSI control chars.
     * @param $lines Mixed. Either a string of lines separated by PHP_EOL or an array of lines
     * @return String
     */
    private function getLongestLine($lines)
    {
        if (is_string($lines)) {
            $lines = explode(PHP_EOL, $lines);
        }

        $longestLine = $lines[0];
        $max = 0;
        while (list(, $line) = each($lines)) {
            if ($this->strlenAnsiSafe($line) > $max) {
                $max = $this->strlenAnsiSafe($line);
                $longestLine = $line;
            }
        }

        return $longestLine;
    } // getLongestLine


    /**
     * @brief Writes to a stream, either STDOUT or STDERR, user-supplied text with optional color and formatting
     *
     * @param $text String. The string to write to the stream.
     * @param $stream Pre-defined constant. Optional. The stream to write to; either STDOUT or STDERR. Default STDOUT
     * @param $level Pre-defined constant. Optional. The PSR-2 logging level. Default NULL. Refer to the defined constants for printout or printerr.
     * @param $foreground Pre-defined constant. Optional foreground color of text. Refer to the defined constants for printout or printerr.
     * @param $background Pre-defined constant. Optional background color or font style. Refer to the defined constants for printout or printerr.
     * @param $alignment Pre-defined constant. Optional. One of LEFT, RIGHT or CENTER. default LEFT. Determines the alignment of the text.
     * @return void
     * @see Defined constants for output formatting
     */
    private function write($text, $stream = STDOUT, $level = null, $foreground = null, $background = null, $alignment = null)
    {
        // enforce that values that are not STDOUT or STDERR can never be used here.
        if ($stream != STDOUT && $stream != STDERR) {
            $stream = STDOUT;
        }

        // enforce that the level is a valid level constant. failing that, set to null
        $validLevels = unserialize(VALID_LEVELS);
        if ($level != null and !in_array($level, $validLevels)) {
            $level = null;
        }

        $textLines = explode(PHP_EOL, $text);

        $printableTextLines = [];

        // if output styling options are passed, build the required ANSI codes
        if ($foreground || $background) {
            // background colors are defined as foreground color codes plus ten. in order to allow the use of
            // the same color constant (ie RED) for both fore and background colors, we just add 10 if in the
            // background position. we only do this if the code is higher than 30 as formatting, such as BOLD,
            // is less than 30 and we don't want to increment that.
            if ($background >= BLACK) {
                $background += 10;
            }

            // since either one or both of the foreground and background can be used and as the codes must be
            // delimited with a ; and terminated with an 'm', it's easiest just to build an array of non-null
            // codes and use join() for formatting
            if ($foreground) {
                $ansiArray[] = $foreground;
            }

            if ($background) {
                $ansiArray[] = $background;
            }

            // build the output string with ANSI codes
            while (list(, $line) = each($textLines)) {
                $printableTextLines[] = $level.ESC."[".implode(";", $ansiArray)."m".$line.CLOSE_ANSI;
            }
        }

        // no output styling, build plain ouput string
        else {
            while (list(, $line) = each($textLines)) {
                $printableTextLines[] = $level.$line;
            }
        }

        // do the output to the stream with alignment padding if necessary
        while (list(, $printableTextLine) = each($printableTextLines)) {
            fwrite($stream, $this->pad($printableTextLine, $alignment).PHP_EOL);
        }

        $this->lastPrintLineCount = count($textLines);
    } // write


    /**
     * @brief Display 'waiting' animation as an ASCII art spinner
     *
     * This method contains an infinite loop and is designed to terminated with posix_kill() by a child process.
     * @param $delay Pre-defined constant. Optional. The speed at which the animation runs. One of DELAY_SLOW, DELAY_MED, DELAY_FAST or DELAY_VERY_FAST.
     * Default DELAY_MED.
     * @note only call this method from a method that spawns a child process
     */
    private function spin($delay = DELAY_MED)
    {
        // frames of clockwise-spinning animation as chars
        $chars = array("|", "/", "-", "\\", "|", "/", "-");

        // output first char so that the initial BACKSPACE has something to erase
        fwrite(STDOUT, $chars[0]);

        // infinite loop to be killed by child process
        while (true) {
            for ($i = 0;$i<5;$i++) {
                while (list(, $char) = each($chars)) {
                    fwrite(STDOUT, BACKSPACE);  // erase previous frame
                    usleep($delay);             // wait $delay for animation speed
                    fwrite(STDOUT, $char);      // nex frame
                }
                reset($chars); // move array pointer back to start
            }
        }
        exit();
    } // spinner


    /**
     * @brief Display 'waiting' animation as a progress bar
     *
     * Progress bar is a run of # chars.
     *
     * This method contains an infinite loop and is designed to terminated with posix_kill() by a child process.
     * @param $delay Pre-defined constant. Optional. The speed at which the animation runs. One of DELAY_SLOW, DELAY_MED, DELAY_FAST or DELAY_VERY_FAST.
     * Default DELAY_MED.
     * @note only call this method from a method that spawns a child process
     */
    private function progress($delay = DELAY_MED)
    {
        fwrite(STDOUT, "#");

        // infinite loop to be killed by child process
        while (true) {
            fwrite(STDOUT, BACKSPACE);
            usleep($delay);
            fwrite(STDOUT, "#>");
        }
        exit();
    } // progress


    /**
     * @brief Display progress update for copying files, shows percentage complete by filesize comparison
     *
     * Displayed output is in the format:
     *
     * `copying... XX.XX%`
     *
     * @param $destFile Path to the file being copied to
     * @param $targetSize Size of file being copied
     * @param $delay The speed at which the animation runs. One of DELAY_SLOW, DELAY_MED, DELAY_FAST or DELAY_VERY_FAST.
     * default is DELAY_VERY_FAST.
     * @return void
     * @note only call this method from a method that spawns a child process to do the copy work
     */
    private function progressFilesizeTrack($destFile, $targetSize, $delay = DELAY_VERY_FAST)
    {
        // loop until this child process is killed by the calling method
        while (true) {
            // PHP caches data on file sizes. In order to get realtime update on file size of $destFile, clear cache
            clearstatcache();

            // sleep for the delay. adjustable to speed up or slow down animation
            usleep($delay);

            // calculate the percentage done and format into a displayable string
            $percentDone =  number_format((@filesize($destFile)/$targetSize) * 100, 2).'%';
            $displayUpdate = "copying... $percentDone";

            // clear the previous display line
            for ($i = 0;$i<strlen($displayUpdate);$i++) {
                fwrite(STDOUT, BACKSPACE);
            }

            // we have not reached 100% done, print the new display percentage
            if ((@filesize($destFile)/$targetSize) <1) {
                fwrite(STDOUT, $displayUpdate);
            }

            // file done copying. exit loop. child process still active until killed by calling method
            else {
                fwrite(STDOUT, PHP_EOL."finishing...  ");
                fwrite(STDOUT, BACKSPACE);

                return 1;
            }
        }
    } // progressFilesizeTrack


    /**
     * @brief Prints out the box of options, description and prompt that is a 'menu'.
     *
     * This method builds all the lines that comprise a menu box, including padding and alignment, composes and array of those
     * lines and calls printoutMenuBoxCache() to do the actual output to STDOUT. If an array of build menu lines
     * is already set in cachedMenuArray, then this method immediately calls printoutMenuBoxCache() with that array. This is
     * caching mechanism to improve performance as the menu is redrawn on every valid user keydown event.
     *
     * @param $description String. The description that goes above the list of selectable options.
     * @param $options Array.  The associative array of options to choose from.
     * @param $selectedKey String. The key of the options array that is currently highlighted as selected
     * @param $innerAlign Pre-defined constant. Optional. The alignment of the text inside the box. One of LEFT, RIGHT or CENTER
     * @param $outerAlign Pre-defined constant. Optional. The alignment of the box in the terminal. One of LEFT, RIGHT or CENTER
     * @param $foreground Pre-defined constant. Optional. The colour of the foreground text. One of the pre-set ANSI colours.
     * @param $background Pre-defined constant. Optional. The colour of the background of the text. One of the pre-set ANSI colours.
     * @return void
     * @see menu
     */
    private function printoutMenuBox($description, $options, $selectedKey, $innerAlign, $outerAlign, $foreground, $background)
    {
        // the character that makes the box border
        // @todo make arg
        $boxBorderChar = "#";

        // margin inside the box before text
        // @todo make arg
        $boxMargin = 2;

        // the minimum padding between an option's key and it's value. padding may be greater in menus with LEFT-justified
        // content so that values are vertically flush
        $keyPad = "  ";

        // options are indented from the $description and the $prompt to improve readability. this is the indentation string.
        $indent = "  ";

        // build ANSI colour coding tags

        // default foreground, background as supplied
        $defaultAnsi = ESC."[".implode(";", [$foreground, $background+10])."m";

        // the hightlight tag
        $selectedAnsi = BOLD_ANSI.ESC."[".implode(";", [REVERSE])."m";

        // if this menu's printableMenuArray has already been built and cached, we should use that and call
        // printoutMenuBoxCache(). This saves having to rebuild the menu on every update, which improves
        // performance marginally.
        if (is_array($this->cachedMenuArray)) {
            $this->printoutMenuBoxCache($this->cachedMenuArray,
                array_keys($options),
                $innerAlign,
                $outerAlign,
                $selectedKey,
                $defaultAnsi,
                $selectedAnsi,
                $boxBorderChar,
                $boxMargin,
                $this->cachedMaxContentLineLength);

            return true;
        }

        // default prompt
        // @todo make arg
        $prompt = "(Use up and down arrow keys, hit RETURN to select)";

        // since option keys are padded so that option vals are verticlly aligned, we need to know the length
        // of the longest key. used for padding and figuring out word wrap for option vals
        $lengthOfLongestKey = $this->getLengthOfLongestLine(array_keys($options));

        // all the lines in the menu are put in one array so we can treat them as a single block
        $fullMenuArray = array_merge(
            ["description" => $description],
            ["blank1" => ""], // empty space for readability
            $options,
            ["blank2" => ""], // empty space for readability
            ["prompt" => $prompt]);

        // word wrap
        // option vals, the description or the prompt may be wider than current terminal. this creates ugly default wrapping.
        // here we word wrap all the elements so the box will fit nicely in the terminal

        // to calculate word wrap we need to know the extra width, in addition to the length of the string, for each line.
        // there are two types of lines:
        // text lines, ie the description and the prompt, their format for width is:
        // boxBorderChar + boxMargin +  value + boxMargin + boxBorderChar
        $textPad = strlen($boxBorderChar)+$boxMargin+$boxMargin+strlen($boxBorderChar);
        // option lines, ie the choosable options their format for width is:
        // boxBorderChar + boxMargin + longest key + one space pad + value + boxMargin + boxBorderChar
        if ($innerAlign == LEFT) {
            $optionPad = strlen($boxBorderChar)+$boxMargin+strlen(")")+strlen($keyPad)+$lengthOfLongestKey+$boxMargin+strlen($boxBorderChar);
        } else {
            $optionPad = strlen($boxBorderChar)+$boxMargin+strlen(")")+$lengthOfLongestKey+$boxMargin+strlen($boxBorderChar);
        }

        // array that holds the menu lines after wrapping
        $fullMenuArrayWrapped = [];

        // do word wrap on each line, push into fullMenuArrayWrapped
        while (list($key, $val) = each($fullMenuArray)) {
            if (in_array($key, array_keys($options))) {
                $fullMenuArrayWrapped[$key] = $this->wrapToTerminalWidth($val, $optionPad);
            } else {
                $fullMenuArrayWrapped[$key] = $this->wrapToTerminalWidth($val, $textPad);
            }
        }

        // once the lines have been wrapped to the screen width, we build an array of all the lines to
        // display complete with keys for options, padding on keys and creating sub-arrays for lines.
        $fullMenuArrayWrappedAndPadded = []; // the array that will hold all the formatted lines
        $longestKey = $this->getLongestLine(array_keys($options)); // the longest option key, used for padding
        $maxContentLineLength = 0; // the length of the longest line, used for building top and bottom borders of the box

        while (list($key, $val) = each($fullMenuArrayWrapped)) {
            $thisElementArray = explode(PHP_EOL, $val);

            for ($i = 0;$i<count($thisElementArray);$i++) {
                if (in_array($key, array_keys($options))) {
                    // if the innerAlign is LEFT then we want the values to be vertically flush
                    // this is done by padding the space between the end of the key and the beginning of the value
                    // $keyAlignPad is this padding
                    // if the value of the option is across several lines then, to maintain the vertical flush we
                    // need to pad out the length of the key for all lines of the value other than the first one.
                    // this achieves the effect of:
                    //  key1)  first line of value for key1
                    //         second line of value for key1
                    //  k2)    value for k2
                    $emptyKey = ""; // default null, only pad for innerAlign LEFT
                    $keyAlignPad = ""; // default null, only pad for innerAlign LEFT
                    // innerAlign, we pad the keys
                    if ($innerAlign == LEFT) {
                        $emptyKey = str_pad("", strlen($key.")"), " "); // the length of key and bracket
                        $keyAlignPad = str_pad("", $lengthOfLongestKey-strlen($key), " "); // padding defined by longest key in options[]
                    }

                    // if the option has multiple lines in the value, we only print the key on the first line
                    if ($i == 0) {
                        $thisElementArray[$i] = $indent.
                                                $key.")".
                                                $keyAlignPad.
                                                $keyPad.
                                                $thisElementArray[$i];
                    }
                    // all subsequent value lines have spaces where the key would normally be to maintain vertical flush
                    else {
                        $thisElementArray[$i] = $indent.
                                                $emptyKey.
                                                $keyAlignPad.
                                                $keyPad.
                                                $thisElementArray[$i];
                    }
                }

                // set the length of the last line. this is used to calculate padding inside the box
                if ($this->strlenAnsiSafe($thisElementArray[$i]) > $maxContentLineLength) {
                    $maxContentLineLength = $this->strlenAnsiSafe($thisElementArray[$i]);
                }
            }

            $fullMenuArrayWrappedAndPadded[$key] = $thisElementArray;
        }

        // push the top and bottom borders of the box onto the array of lines
        $borderString = str_pad("", strlen($boxBorderChar)+$boxMargin+$maxContentLineLength+$boxMargin+strlen($boxBorderChar), $boxBorderChar);
        $printableMenuArray = array_merge(
            array("bordertop" => [$borderString]),
            $fullMenuArrayWrappedAndPadded,
            array("borderbottom" => [$borderString]));

        // cache the printableMenuArray and the maxContentLineLength (used for building the top and bottom borders of the box)
        // so that on future calls to this method for menu refresh we don't need to do all this calculation again.
        // performance gains are marginal, but nice to have.
        $this->cachedMenuArray = $printableMenuArray;
        $this->cachedMaxContentLineLength = $maxContentLineLength;

        // call the method to printout
        $this->printoutMenuBoxCache($printableMenuArray,
            array_keys($options),
            $innerAlign,
            $outerAlign,
            $selectedKey,
            $defaultAnsi,
            $selectedAnsi,
            $boxBorderChar,
            $boxMargin,
            $maxContentLineLength);
    } // printoutMenuBox


    /**
     * @brief Prints out the menu from $printableMenu. Should be called only by printoutMenuBox()
     *
     * @param $printableMenu Array. An array of all the elements with sub-array for all the lines in the printable menu box
     * @param $optionKeys Array.  An array of all the keys for the options array that makes up the menu
     * @param $innerAlign Pre-defined constant. Optional. The alignment of the text inside the box. One of LEFT, RIGHT or CENTER
     * @param $outerAlign Pre-defined constant. Optional. The alignment of the box in the terminal. One of LEFT, RIGHT or CENTER
     * @param $selectedKey String. The key of the options array that is currently highlighted as selected
     * @param $defaultAnsi String. The ANSI style supplied by the user from foregroundColour and backgroundColour
     * @param $selectedAnsi String. The reverse of defaultAnsi, used to highlight the selected menu item
     * @param $boxBorderChar String. The character used to make the box. Default '#'
     * @param $boxMargin Int. The number of spaces between the left and right edges of the box and the content
     * @param $max Int.  The length of the longest line of content, used for calcuating padding.
     * @return boolean
     * @see printoutMenuBox
     */
    private function printoutMenuBoxCache($printableMenu,
        $optionKeys,
        $innerAlign,
        $outerAlign,
        $selectedKey,
        $defaultAnsi,
        $selectedAnsi,
        $boxBorderChar,
        $boxMargin,
        $max)
    {
        $menu = null;
        while (list($key, $printableMenuLineArray) = each($printableMenu)) {
            while (list($subkey, $line) = each($printableMenuLineArray)) {
                if ($innerAlign == CENTER) {
                    $padLeft = ceil(($max-$this->strlenAnsiSafe($line))/2); // alternate ceil/floor to accommodate odd total padding
                    $padRight = floor(($max-$this->strlenAnsiSafe($line))/2);
                } elseif ($innerAlign == RIGHT) {
                    $padLeft = $max-$this->strlenAnsiSafe($line);
                    $padRight = 0;
                } else {
                    $padLeft = 0;
                    $padRight = $max-$this->strlenAnsiSafe($line);
                }

                if ($key == "bordertop" || $key == "borderbottom") {
                    $menu .= $defaultAnsi.$line.CLOSE_ANSI.PHP_EOL;
                } elseif (in_array($key, $optionKeys)) {
                    $keyAnsi = $defaultAnsi;
                    if ($key == $selectedKey) {
                        $keyAnsi = $selectedAnsi;
                    }
                    $menu .=
                        $defaultAnsi.
                        $boxBorderChar.
                        str_pad("", $boxMargin, " ").
                        str_pad("", $padLeft, " ").
                        CLOSE_ANSI.
                        $keyAnsi.
                        $line.
                        CLOSE_ANSI.
                        $defaultAnsi.
                        str_pad("", $padRight, " ").
                        str_pad("", $boxMargin, " ").
                        $boxBorderChar.
                        CLOSE_ANSI.
                        PHP_EOL;
                } else {
                    $menu .=
                        $defaultAnsi.
                        $boxBorderChar.
                        str_pad("", $boxMargin, " ").
                        str_pad("", $padLeft, " ").
                        $line.
                        str_pad("", $padRight, " ").
                        str_pad("", $boxMargin, " ").
                        $boxBorderChar.
                        CLOSE_ANSI.
                        PHP_EOL;
                }
            }
        }

        $this->printout($menu, null, null, null, $outerAlign);

        return true;
    } // printoutMenuBoxCache


    /**
     * @brief Prepares arguments for the background() method.
     *
     * Arguments for the background() method can be provided as either NULL, a string of one arg value
     * or an array of multiple args. However, call_user_func_array() requires args as an array. This
     * method converts supplied $args to useful ones.
     * @param $args An array of args or a string of one arg or null
     * @return Array An array of arguments usable by background()
     */
    private function prepArgs($args)
    {
        if ($args == null) {
            return [];
        }

        if (is_scalar($args)) {
            return [$args];
        }

        return $args;
    } // prepArgs


    /**
     * @brief Returns the width of the terminal in columns or 0 or failure
     *
     * If there is a failure because of stty() or beause exec() has been disabled in PHP, then zero will
     * be returned. _Make sure you handle this_.
     *
     * @return Int
     * @note This method relies on an exec() call to `stty`
     * @see pad
     * @see wrapToTerminalWidth
     */
    private function getTerminalWidth()
    {
        return (int) implode("", array_map(function ($row) {
                if (substr(trim($row), 0, strlen("columns")) == "columns") {
                    return filter_var(preg_split("/ /", trim($row))[1], FILTER_VALIDATE_INT);
                }

                return;
            },
            preg_split("/;/", strtolower(exec('stty -a | grep columns')))));
    } // getTerminalWidth


    /**
     * @brief Returns the provided text with left-padding to align it (left, right or centre) in the terminal
     *
     * If the calculation of the terminal width fails or if the text is wider than the terminal, text
     * is returned unpadded, ie left-justified.
     * @param $text String. The text to centre in the terminal
     * @param $alignment Pre-defined constant. The alignment the padding should produce. One of:
     *      * LEFT
     *      * RIGHT
     *      * CENTER
     * default is CENTER.
     * @return String
     * @see getTerminalWidth
     * @see strlenAnsiSafe
     */
    private function pad($text, $alignment)
    {
        // LEFT is defined as 0 (no alignment).
        // since the alignment variable is used for division, we trap here to avoid a
        // division by zero error
        if (!$alignment) {
            return $text;
        }

        // calculate padding on left of text to create alignment.
        // note that RIGHT value is 1 and CENTER is 2 so division by $alignment works
        $leftpad = floor(($this->getTerminalWidth() - $this->strlenAnsiSafe($text))/$alignment);

        // accommodate 0 return from getTerminalWidth if it fails, or if the string to
        // centre is wider than the terminal
        if ($leftpad <= 0) {
            $leftpad = 0;
        }

        // return the padded string
        return implode("", array_fill(0,
            $leftpad,
            " ")).$text;
    } // pad


    /**
     * @brief An ANSI formatting safe strlen()
     *
     * Regular strlen() will count the printable, but invisible, chars used in ANSI escape sequences.
     * This leads to undesired results when padding for alignment.
     *
     * This method strips all ANSI control data from the provided string and returns it's strlen()
     *
     * Credit due to kenneth mccall for this.
     *
     * @param $text String. The text to get the lenght of
     * @return Int
     */
    private function strlenAnsiSafe($text)
    {
        $text = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "", $text);
        $text = preg_replace('/\x1b(\[|\(|\))[;?0-9]*[0-9A-Za-z]/', "", $text);
        $text = preg_replace('/[\x03|\x1a]/', "", $text);

        return strlen($text);
    } // strlenAnsiSafe


    /**
     * @brief Poll for user keyboard input
     *
     * Reads from STDIN and returns the char pressed on keydown. Some keydown events return
     * multiple chars; this method will return them individually.
     * @return char
     */
    private function getKeyDown()
    {
        readline_callback_handler_install("", function () {});
        while (true) {
            $r = array(STDIN);
            $w = null;
            $e = null;
            $n = stream_select($r, $w, $e, null);
            if ($n && in_array(STDIN, $r)) {
                // read the user choice from STDIN
                $userChoice = stream_get_contents(STDIN, 1);

                break;
            }
        }

        return $userChoice;
    } // getKeyDown


    /**
     * @brief Wraps the supplied to the width of the terminal minus the supplied widith of padding
     *
     * If the terminal width is unavailable because of `stty` failure, the text is returned unmodified.
     *
     * @param $text String. The text to wrap
     * @param $padLength Int. The length of any padding to the text, eg for margins, box borders &c
     * @return String
     * @see getTerminalWidth
     */
    private function wrapToTerminalWidth($text, $padLength = 0)
    {
        $terminalWidth = $this->getTerminalWidth();

        // getTerminalWidth() failed, return the text unmodified
        if ($terminalWidth == 0) {
            return $text;
        }

        return wordwrap($text, $terminalWidth-$padLength, PHP_EOL, false);
    } // wrapToTerminalWidth


    /**
     * @brief Returns an array of a printlist ready for formatting and output
     *
     * This method uses recursion to navigate to arbitrary depth of sublists.
     *
     * @param $listArray Array. The array to listify as supplied by the user to printlist()
     * @param $countableBulletsArray Array. An array, keyed by list level, of arrays for each bullet with 'bullet' and 'count' elemets.
     * @param $listIndentSize Int. The number of spaces to indent the entire list.
     * @param $subListIndentSize Int. The number of spaces to indent a sublist from its parent.
     * @param $level Int. The current level of the sublist
     * @param $printableArray Array. The array which is eventually returned. listed here for recursion.
     * @return Array
     */
    private function getPrintlist($listArray, $countableBulletsArray, $listIndentSize, $subListIndentSize, $level = 0, $printableArray = [])
    {
        // to keep the values vertically flushed, we pad out to the length of the longest key
        // get length of the longest key for this level
        $longestBulletLength = $this->getPrintlistBulletMaxLength(@$countableBulletsArray[$level]["bullet"], count($listArray));

        // process this level of the list. list entries can be either scalar values for addition to the printableArray
        // or an array, indicating a sublist
        while (list(, $val) = each($listArray)) {
            // an element to add to the list in printableArray
            if (!is_array($val)) {
                // increment the count in countableBulletsArray so that eg. 'a' becomes 'b' and 'i' becomes 'ii'
                @$countableBulletsArray[$level]["count"]++;

                // get the actual bullet for this list entry, ie 'a)' or 'vii.'
                $bullet = $this->getPrintlistBullet(@$countableBulletsArray[$level]["bullet"], $countableBulletsArray[$level]["count"]);

                // pad for right vertical flush of values
                $bulletPad = str_pad("", $longestBulletLength-$this->strlenAnsiSafe($bullet), " ");

                // add this list item to printableArray in format
                // ["bullet" => the printable bullet with padding, "value" => the value of the list item
                $subArray = array(
                    "bullet" => str_pad("", $listIndentSize, " ").
                    str_pad("", $level*$subListIndentSize, " ").
                    $bullet.
                    $bulletPad, "value" => $val, );
                $printableArray[] = $subArray;
            // a sub list. recurse.
            } else {
                $printableArray = $this->getPrintlist($val, $countableBulletsArray, $listIndentSize, $subListIndentSize, $level+1, $printableArray);
            }
        }

        return $printableArray;
    } // getPrintlist()


    /**
     * @brief Converts a bullet type and count to an actual bullet
     *
     * ie, converts bullet 'i' and count '7' to 'vii' or bullt 'a' and count '5' to 'e)'
     *
     * @param $bullet pre-defined constant. The type of bullet, one of BULLET_LETTER_UPPERCASE, BULLET_LETTER_LOWERCASE, BULLET_NUMBER,
     *        BULLET_ROMAN, BULLET_UNORDERED. Default BULLET_UNORDERED.
     * @param $count Int. The number to convert to a bullet.
     */
    private function getPrintlistBullet($bullet, $count)
    {
        $printableBullet = null;

        // convert decimal number to alphabetical. ie dec 28 to 'ab' or dec 798 to 'adr'
        // upper or lower case version
        if ($bullet == BULLET_LETTER_UPPERCASE || $bullet == BULLET_LETTER_LOWERCASE) {
            // pre-calculate the number of characters this bullet will have
            // by converting to base 26 and testing the length
            $numOfBulletChars = strlen(strval(base_convert($count, 10, 26)));

            $printableBullet = null; // the bullet to return

            // for each place in the previously-calculated base 26 number, we convert to a letter
            for ($i = ($numOfBulletChars-1);$i > 0; $i--) {
                $baseVal = pow(26, $i); // the value of this place in the base 26 number

                // convert to ascii. ascii 97 is 'a', so if we want to convert 1 to 'a', we add 96
                $printableBullet .= chr(($count/$baseVal)+96);

                // subtract the value of this place so we move on to the next place in the number
                $count = $count - $baseVal;
            }
            // final place of base 26 number
            $printableBullet .= chr(($count%26)+96);

            // uppercase if bullet type is 'A', the uppercase bullet.
            if ($bullet == BULLET_LETTER_UPPERCASE) {
                $printableBullet = strtoupper($printableBullet).". ";
            } else {
                $printableBullet .= ") ";
            }

        // convert a bullet number to a number bullet
        } elseif ($bullet == BULLET_NUMBER) {
            $printableBullet = $count.". ";

        // convert a bullet number to a roman numeral
        } elseif ($bullet == BULLET_ROMAN) {
            $lookup = ['m' => 1000, 'cm' => 900, 'd' => 500,
             'cd' => 400, 'c' => 100, 'xc' => 90, 'l' => 50,
             'xl' => 40, 'x' => 10, 'ix' => 9, 'v' => 5,
             'iv' => 4, 'i' => 1, ];

            while (list($roman, $value) = each($lookup)) {
                $matches = intval($count/$value);
                $printableBullet .= str_repeat($roman, $matches);
                $count = $count % $value;
            }

            $printableBullet .= ". ";

        // default is unorder bullet '*'
        } else {
            $printableBullet = "* ";
        }

        return $printableBullet;
    } // getPrintlistBullet

    /**
     * @brief Gets the length of the longest bullet in the range defined by 0 to $count for the bullet type $bullet
     *
     * This is to ensure right vertical flushing. Return value is the int length of the longest bullet.
     * @param $bullet pre-defined constant. The type of bullet, one of BULLET_LETTER_UPPERCASE, BULLET_LETTER_LOWERCASE, BULLET_NUMBER,
     *        BULLET_ROMAN, BULLET_UNORDERED. Default BULLET_UNORDERED.
     * @param $count Int. The end of the range 0..$count to test.
     * @return Int
     */
    private function getPrintlistBulletMaxLength($bullet, $count)
    {
        if (!isset($bullet)) {
            return 1;
        }
        $maxLength = 0;

        for ($i = 0;$i<$count;$i++) {
            $length = $this->strlenAnsiSafe($this->getPrintlistBullet($bullet, $count));
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        return $maxLength;
    } // getPrintlistBulletMaxLength


    /**
     * @brief Builds and outputs the menu from menuhorizontal()
     *
     * @param $description String
     * @param $options Array. List of options for this menu. The key of the selected item is returned.
     * @param $selectedKey String. The key to set as the initial selected key.
     * @param $alignment pre-defined constant. The alignment of the menu in the terminal. One of LEFT, RIGHT or CENTER.
     * @param $foreground pre-defined constant. The foreground colour.
     * @param $background pre-defined constant. The background colour.
     * @return void
     */
    private function printoutMenuhorizontal($description, $options, $selectedKey, $alignment, $foreground, $background)
    {
        // default prompt
        // @todo make arg
        $prompt = "(Use up and down arrow cards, hit RETURN to select)";

        $optionSpacing = 2;

        // build ANSI colour coding tags

        // default foreground, background as supplied
        $defaultAnsi = ESC."[".implode(";", [$foreground, $background+10])."m";

        // the hightlight tag
        $selectedAnsi = BOLD_ANSI.ESC."[".implode(";", [REVERSE])."m";

        // default prompt
        $prompt = "(Use left and right arrow keys, hit RETURN to select)";

        $printableOptions = [];

        // highlight the slected key
        while (list($key, $val) = each($options)) {
            if ($key == $selectedKey) {
                $printableOptions[] = CLOSE_ANSI.$selectedAnsi.$val.CLOSE_ANSI.$defaultAnsi;
            } else {
                $printableOptions[] = $val;
            }
        }

        // print out the menu
        $this->printout(
            $description.PHP_EOL.// the descriptions
            implode(str_pad("", $optionSpacing, " "), $printableOptions).PHP_EOL.// the options as string
            $prompt, // the prompt
            null,  // level, always null
            $foreground, // foreground colour constant
            $background, // background constant constant
            $alignment); // alignment constant
    } // printoutMenuhorizontal


    /**
     * @brief Outputs the fileselect interface
     *
     * @param $directory String. The initial directory opened in the fileselect interface.
     * @param $description String. The text description to put in the fileselect interface.
     * @param $selectedIndex Int. The index of the array of files in the directory that is currently selected.
     * @param $alignment pre-defined Constant. Alignment of the fileselect interface in the terminal.
     * @param $foregroundColour pre-defined Constant. Foreground colour of the text
     * @param $backgroundColour pre-defined Constant. Background colour of the text
     * @return Mixed. String
     * @see fileselect
     */
    private function printoutFileselect($directory,
        $description = "Select a file",
        $selectedIndex,
        $alignment = LEFT,
        $backgroundColour = null,
        $foregroundColour = null)
    {
        // the number of files visible in the fileselect interface box
        $listWindowSize = 5;

        // minimum space between the box border on left and right and the inner content
        $boxMargin = 4;

        // prompt to display below file list
        $prompt = "(Use up and down arrow keys, 'o' to select, RETURN to open directory)";

        // build ANSI colour coding tags

        // default foreground, background using colours supplied supplied
        $defaultAnsi = ESC."[".implode(";", [$foregroundColour, $backgroundColour+10])."m";

        // ansi tag for highlighting selected file
        $selectedAnsi = BOLD_ANSI.ESC."[".implode(";", [REVERSE])."m";

        // array of all files and directories in the current directory
        $rawDirectoryScan = scandir($directory);

        // stop overrun of bottom of directory list
        if ($selectedIndex >= count($rawDirectoryScan)) {
            $selectedIndex = count($rawDirectoryScan)-1;
        }

        // stop of overrun off the top of directory list
        if ($selectedIndex < 0) {
            $selectedIndex = 0;
        }

        // create an array of the files in the directory
        // key: the full path to the file, ie /path/to/file/foo.txt
        // val: the name of the file, ie foo.txt
        // append a "/" onto directories so they are identifiable as such
        $directoryScan = array_map(
            function ($lineScan, $directoryName) {
                if (is_dir($directoryName.$lineScan)) {
                    return [$directoryName.$lineScan."/" => "  ".$lineScan."/"];
                }

                return [$directoryName.$lineScan => "  ".$lineScan];
            },
            $rawDirectoryScan,
            array_fill(0, count($rawDirectoryScan), $directory));

        // get maximum width of all the lines to print to work out padding
        // for the enclosing box
        $maxWidth = 0;
        $maxWidthTestArray = array_merge($rawDirectoryScan, [realpath($directory)]);
        while (list(, $val) = each($maxWidthTestArray)) {
            if ($this->strlenAnsiSafe($val)+2 > $maxWidth) {
                $maxWidth = $this->strlenAnsiSafe($val)+2;
            }
        }

        // the longest line may be quite short, creating a box that is very narrow and difficult to read
        // bump to a minimum of one third the terminal width.
        if (floor($this->getTerminalWidth()/3) > $maxWidth) {
            $maxWidth = floor($this->getTerminalWidth()/3);
        }

        // the string of chars at the top and bottom of the box
        $borderString = str_pad("", $maxWidth+($boxMargin*2)+2, "#");

        // the string of '-' that divides the description, options and prompt
        $dividerString = "#".str_pad("", $boxMargin, " ").str_pad("", $maxWidth, "-").str_pad("", $boxMargin, " ")."#";

        // build the window bounds. these are the upper and lower array indexes that will
        // be shown since we don't want to show all the files in the directory

        $windowUpperBound = 0;
        $windowLowerBound = $listWindowSize-1;

        // don't draw window larger than the number of files
        if (count($rawDirectoryScan) <= $windowLowerBound) {
            $windowLowerBound = count($rawDirectoryScan)-1;
        }

        // don't let a downkey event run the selectedIndex off the lower bound of the window
        if ($selectedIndex >= $listWindowSize) {
            $windowLowerBound = $selectedIndex;
            $windowUpperBound = $windowLowerBound-$listWindowSize+1;
        }

        // build array of options
        $printableFileselectArray = [];
        for ($i = $windowUpperBound;$i <= $windowLowerBound;$i++) {
            $openAnsi = null;
            $closeAnsi = null;
            if ($i == $selectedIndex) {
                $openAnsi = $selectedAnsi;
                $closeAnsi = CLOSE_ANSI.$defaultAnsi;
            }

            $printableFileselectArray[] = "#".
                str_pad("", $boxMargin, " ").
                $openAnsi.
                end($directoryScan[$i]).
                $closeAnsi.
                str_pad("", $boxMargin, " ").
                str_pad("", $maxWidth-$this->strlenAnsiSafe(end($directoryScan[$i])), " ").
                "#";
        }

        // format the description
        // wrap the description to the width of the box and split by lines to array
        $descriptionLines = explode(PHP_EOL, wordwrap($description, $maxWidth));
        // build array of printable description lines, with padding and box borders.
        $printableDescriptionArray = [];
        while (list(, $val) = each($descriptionLines)) {
            $printableDescriptionArray[] = "#".
                str_pad("", $boxMargin, " ").
                $val.
                str_pad("", $boxMargin, " ").
                str_pad("", $maxWidth-$this->strlenAnsiSafe($val), " ").
                "#";
        }
        // add the divider at the bottom of the description
        $printableDescriptionArray[] = $dividerString;

        // format the list of files with padding and box borders. one line per array element.
        $currentDirectoryArray = ["#".
            str_pad("", $boxMargin, " ").
            realpath($directory).
            str_pad("", $boxMargin, " ").
            str_pad("", $maxWidth-$this->strlenAnsiSafe(realpath($directory)), " ").
            "#", ];

        // format the prompt
        // wrap the prompt to the width of the box and split by lines to array
        $promptLines = explode(PHP_EOL, wordwrap($prompt, $maxWidth));
        // build array of printable prompt lines, with padding and box borders.
        $printablePromptArray[] = $dividerString;
        while (list(, $val) = each($promptLines)) {
            $printablePromptArray[] = "#".
                str_pad("", ceil(($maxWidth-$this->strlenAnsiSafe($val))/2), " ").
                str_pad("", $boxMargin, " ").
                $val.
                str_pad("", $boxMargin, " ").
                str_pad("", floor(($maxWidth-$this->strlenAnsiSafe($val))/2), " ").
                "#";
        }

        // put all the arrays together to create an array of all the lines of the box
        $printableFileselectArray = array_merge(
            [$borderString], // top border
            $printableDescriptionArray, // the description
            $currentDirectoryArray, // string of the current directory
            $printableFileselectArray, // all the files in current directory
            $printablePromptArray, // the prompt
            [$borderString]); // bottom border

        // output
        $this->printout(
            implode(PHP_EOL, $printableFileselectArray),
            null,
            $foregroundColour,
            $backgroundColour,
            $alignment);
    } // printoutFileselect


    /**
     * @brief Returns the index of the first file of the provided directory
     *
     * Returns the index of the first file in a directory listing after '.' and '..', or the index of '..' if the file is emtpy
     *
     * @param $directory String. The path to the directory to check
     * @return Int
     * @see fileselect
     */
    private function getIndexOfFirstFile($directory)
    {
        $firstIndex = count(scandir($directory))-1;
        if ($firstIndex > 2) {
            $firstIndex = 2;
        }

        return $firstIndex;
    } // getIndexOfFirstFile


    /**
     * @brief Decrement date from down arrow key event
     *
     * Returns a new date array that is decremented, suitable for display.
     * @param $dateArray Array. Array of integers numerically keyed to represent date as yyyy mm dd
     * @param $currentIndex Int. The index of the dateArray that is being decremented
     * @return Array
     */
    private function decrementDate($dateArray, $currentIndex)
    {
        switch ($currentIndex) {
            // year
            case 0:
                $dateArray[0]--;
                break;

            // month
            case 1:
                // month wrap
                $dateArray[1]--;
                if ($dateArray[1] < 1) {
                    $dateArray[1] = 12;
                }

                // prevent day overrunning max for the month
                $maxDay = (int) date("t", strtotime($dateArray[0]."-".$dateArray[1]."-"."1"));
                if ($dateArray[2] > $maxDay) {
                    $dateArray[2] = $maxDay;
                }
                break;

            // day
            case 2:
                $dateArray[2]--;

                // day wrap
                $maxDay = (int) date("t", strtotime($dateArray[0]."-".$dateArray[1]."-"."1"));
                if ($dateArray[2] < 1) {
                    $dateArray[2] = $maxDay;
                }

        }

        return $dateArray;
    } // decrementDate

    /**
     * @brief Increment date from up arrow key event
     *
     * Returns a new date array that is incremented, suitable for display.
     * @param $dateArray Array. Array of integers numerically keyed to represent date as yyyy mm dd
     * @param $currentIndex Int. The index of the dateArray that is being incremented
     * @return Array
     */
    private function incrementDate($dateArray, $currentIndex)
    {
        switch ($currentIndex) {
            // year
            case 0:
                $dateArray[0]++;
                break;

            // month
            case 1:
                // month wrap
                $dateArray[1]++;
                if ($dateArray[1] > 12) {
                    $dateArray[1] = 1;
                }

                // prevent day overrunning max for the month
                $maxDay = (int) date("t", strtotime($dateArray[0]."-".$dateArray[1]."-"."1"));
                if ($dateArray[2] > $maxDay) {
                    $dateArray[2] = $maxDay;
                }
                break;

            // day
            case 2:
                $dateArray[2]++;

                // day wrap
                $maxDay = (int) date("t", strtotime($dateArray[0]."-".$dateArray[1]."-"."1"));
                if ($dateArray[2] > $maxDay) {
                    $dateArray[2] = 1;
                }

        }

        return $dateArray;
    } // incrementDate


    /**
     * @brief Gets an array of ANSI tags for formatting datepicker output
     *
     * @param $currentIndex Int. The element of the dateArray to format as highlighted
     * @see printDatepicker
     */
    private function getDatePickerAnsiFormats($currentIndex)
    {
        $ansiFormats = [];
        for ($i = 0;$i<3;$i++) {
            if ($i == $currentIndex) {
                $ansiFormats[$i]["open"] = BOLD_ANSI.ESC."[".implode(";", [REVERSE])."m";
                $ansiFormats[$i]["close"] = CLOSE_ANSI;
            } else {
                $ansiFormats[$i]["open"] = null;
                $ansiFormats[$i]["close"] = null;
            }
        }

        return $ansiFormats;
    } // getDatePickerAnsiFormats


    /**
     * @brief Converts month number to equivalent month String.
     *
     * ie, 1 to 'Jan'.
     * @param $monthNumber Int
     * @return String
     */
    private function getDisplayMonth($monthNumber)
    {
        return date('M', mktime(0, 0, 0, $monthNumber, 10));
    } // getDisplayMonth


    /**
     * @brief Outputs the datepicker interface
     *
     * @param $prompt String
     * @param $dateArray Array
     * @param $currentIndex Int
     * @return void
     * @see datepicker
     */
    private function printDatepicker($prompt, $dateArray, $currentIndex)
    {
        // get array of ANSI formatting tags to apply to output
        $ansiFormats = $this->getDatePickerAnsiFormats($currentIndex);

        $printableString =
            $prompt.PHP_EOL.
            // year
            $ansiFormats[0]["open"].$dateArray[0].$ansiFormats[0]["close"]." ".
            // month
            $ansiFormats[1]["open"].$this->getDisplayMonth($dateArray[1]).$ansiFormats[1]["close"]." ".
            // day
            $ansiFormats[2]["open"].$dateArray[2].$ansiFormats[2]["close"];

        $this->printout($printableString);
    } // printDatepicker


    /**
     * @brief Updates the datepicker date from events other than increment and decrement
     *
     * Instead of just scrolling date elements up and down, users can enter direct keystrokes (ie
     * 'mar' for 'March'). This method handles those keystrokes and updates the dateArray accordingly.
     *
     * User key input is passed as the userInputArray, with one element per key pressed. ie, for 'mar'
     * ['m', 'a', 'r'], since each key press is a separate event.
     *
     * Arrow up, down, left, right and tab key events clear the userInputArray so that keys used to select
     * eg the year do not carry over to selecting the month
     *
     * @param $dateArray Array. The array showing the currently-selected date.
     * @param $currentIndex Int. The index of the dateArray currently be affected. 0 for year, 1 for month, 2 for day.
     * @param $userInputArray Array. Keys pressed by user.
     * @return Array
     * @see datepicker
     */
    private function updateDate($dateArray, $currentIndex, $userInputArray)
    {
        switch ($currentIndex) {
            // year
            case 0:
                // only 4 chars in a year, hit a fifth, we reset to current year
                if (count($userInputArray) > 4) {
                    $dateArray[0] = (int) date("Y");
                }
                // build year for the number of keystrokes. ie '19' builds year '1900'
                else {
                    $dateArray[0] = (int) str_pad(implode(null, $userInputArray), 4, "0", STR_PAD_RIGHT);
                }
                break;

            // month
            case 1:
                // user input array to a string. lowercase for matching convenience.
                $userInputString = strtolower(implode(null, $userInputArray));

                // an array of month names to match against user input. lowercase for matching convenience.
                $monthNames = array_map(function ($i) {
                        return strtolower($this->getDisplayMonth($i));
                    },
                    range(1, 13));

                // match user input to month name. partial matches first instance of months in
                // chronological order, ie 'ma' matches 'march', not 'may'.
                while (list($key, $val) = each($monthNames)) {
                    if ($userInputString == substr($val, 0, strlen($userInputString))) {
                        $dateArray[1] = $key+1;
                        break;
                    }
                }

                // prevent day overrunning max for the month. ie, if day is set to 30 and then user
                // selects 'Feb' for month, set day to 28 (or 29 for leap)
                $maxDay = (int) date("t", strtotime($dateArray[0]."-".$dateArray[1]."-"."1"));
                if ($dateArray[2] > $maxDay) {
                    $dateArray[2] = $maxDay;
                }
                break;

            // day
            case 2:
                $maxDay = (int) date("t", strtotime($dateArray[0]."-".$dateArray[1]."-"."1"));
                // max 2 chars in day, hit a third reset to current day
                if (count($userInputArray) > 2) {
                    $dateArray[2] = (int) date("j");
                } else {
                    $userEnteredDate = (int) str_pad(implode(null, $userInputArray), 2, "0", STR_PAD_RIGHT);
                    // attempts to overrun highest day for current month sets day to max day for current month
                    if ($userEnteredDate > $maxDay) {
                        $userEnteredDate = $maxDay;
                    }
                    $dateArray[2] = $userEnteredDate;
                }
                break;
        }

        return $dateArray;
    } // updateDate
} //Clibelt

/**
 * @brief
 * @author gbh
 */
class ClibeltException extends Exception
{
    private $method;

    public function __construct($message, $code = 0, $method)
    {
        parent::__construct($message, $code, null);
        $this->method = $method;
    } // __construct

    public function __toString()
    {
        return __CLASS__."::".$this->method." ".$this->code." ".$this->message;
    }
} // ClibeltException
