<?php
include(__dir__."/../Clibelt.php");

$cli = new Clibelt();



/**
 * Clibelt kitchensink
 *
 * This file is a reference to all methods in the Clibelt class. It shows usage examples and
 * some ways to architecture cli scripts using the Clibelt tools.
 *
 * Table of contents
 *
 *   Output methods
 *   
 *    1.  Simple outputs
 *          a. Level outputs
 *          b. Colorized outputs  
 *          c. Stylized outputs  
 *          d. Aligned output
 *    2.  Box output
 *          a. Colorized box output
 *          b. Aligned box output
 *    3.  List outputs
 *          a. With different bullets
 *          b. Lists with sub lists
 *          c. List indentations 
 *    4.  Erase
 *    5.  Clear Line
 *    6.  Clear Screen
 *
 *   User input methods
 *    7.  Any key
 *    8.  Prompt user choice
 *          a. Setting a default choice
 *          b. Yn convenience method
 *    9.  Read user input
 *    10. Read user input password
 *    11. Menu
 *          a. Inner-align menu text
 *          b. Outer-align menu box
 *          c. Colorize menu
 *    12. Horizontal menu
 *          a. Pre-select an option
 *          b. Align horizontal menu
 *          c. Colorize horizontal menu
 *    13. File selector
 *          a. Align file selector
 *          b. Colorize file selector
 *          c. Set for returning a directory
 *    14. Date selector
 *    15. Get last input
 *
 *    Stream input methods
 *    16. read STDIN to string
 *    17. read STDIN to stream
 *    18. test STDIN
 *
 *    Backgrounding
 *    19. Run a process in the background with a spinner
 *          a. Background an anonymous function with a single argument
 *          b. Background an anonymous function with multiple args
 *          c. Background an anonymous function with different animation style
 *          d. Background an anonymous function with custom animation speed
 *          e. Background an evalable string
 *
 *    Copy and download methods
 *    20. Copy a file somewhat safely
 *
 */
$returnedValue = $cli->background($longFunction, $args, PROGRESS);


/**
 * 19d. Background an anonymous function with custom animation speed
 *
 */


/**
 * Output methods
 */

/**
 * 1. Simple outputs 
 * Command line applications can output to one of two streams: standard out (STDOUT) or standard error (STDERR)
 * @see http://tldp.org/HOWTO/Bash-Prog-Intro-HOWTO-3.html
 */

// print to STDOUT
$cli->printout("this is printed to STDOUT");

// print to STDERR
$cli->printerr("this is printed to STDERR");

/**
 * 1a.Level output
 * Output to either STDOUT or STDERR can be decorated with a colorized reference to one of 
 * eight RFC 5424 levels plus 'OK'.
 */

$cli->printout("ok sent to standard out", OK);
$cli->printerr("ok sent to standard error", OK);

$cli->printout("debug sent to standard out", DEBUG); 
$cli->printerr("debug sent to standard error", DEBUG); 

$cli->printout("info sent to standard out", INFO);
$cli->printerr("info sent to standard error", INFO);

$cli->printout("notice sent to standard out", NOTICE);
$cli->printerr("notice sent to standard error", NOTICE);

$cli->printout("warning sent to standard out", WARNING);
$cli->printerr("warning sent to standard error", WARNING);

$cli->printout("error sent to standard out", ERROR);
$cli->printerr("error sent to standard error", ERROR);

$cli->printout("critical sent to standard out", CRITICAL);
$cli->printerr("critical sent to standard error", CRITICAL);

$cli->printout("alert sent to standard out", ALERT);
$cli->printerr("alert sent to standard error", ALERT);

$cli->printout("emergency sent to standard out", EMERGENCY);
$cli->printerr("emergency sent to standard error", EMERGENCY);

/**
 * 1b. Colorized output
 * Output to either STDOUT or STDERR can be colorized using standard ANSI colors.
 * foreground and background colors can be set like so
 * printout("the text", output level, foreground color, background color)
 *
 * The available colors are:
 * RED
 * BLACK
 * GREEN
 * YELLOW
 * BLUE
 * MAGENTA
 * CYAN
 * WHITE
 *
 * @note Colorization requires an ANSI terminal
 */

$cli->printout("red text", null, RED);

$cli->printout("red text on a yellow background", null, RED, YELLOW);

$cli->printout("white text on a yellow background", null, WHITE, BLUE);

/**
 * 1c. Stylized output
 * A style can be applied to output instead of a background color
 *
 * The style options are
 * NORMAL
 * BOLD
 * ITALIC
 * UNDERLINE
 * STRIKETHROUGH
 * REVERSE
 * @note ITALIC is not widely supported by terminals
 */

$cli->printout("normal colored text underlined", null, null, UNDERLINE);

$cli->printout("cyan colored text with strikethrough", null, CYAN, STRIKETHROUGH);

$cli->printout("green foreground text plus reverse is regular text on green background", null, GREEN, REVERSE);

$cli->printout("italic works on a very limited set of terminals", null, null, ITALIC);

/**
 * 1d. Aligned output
 * Output can be aligned in the terminal
 *
 * printout("the text", output level, foreground color, background color, alignment)
 *
 * Alignment options can be one of
 * LEFT
 * RIGHT
 * CENTER
 * @note LEFT is the default
 */

$cli->printout("this text is aligned right", null, null, null, RIGHT);

$cli->printout("this text is aligned center", null, null, null, CENTER);

$cli->printout("this text is aligned left", null, null, null, LEFT);

$cli->printout("this text is also aligned left");

/**
 * all of these output arguments can be combined 
 */
$cli->printout("centered ok text, green text on white background", OK, GREEN, WHITE, CENTER);

/**
 * 2. Box outputs
 * Output can be put in a bordered box, optionally colorized
 */

$cli->box("this text is in a box");

$someText =<<<TXT
this is multiline
text put in a box
TXT;
$cli->box($someText);

/**
 * 2a. Colorized box output
 * box(text, foreground color, background color)
 */
$cli->box("yellow text on red background", YELLOW, RED);

/**
 * 2b. Aligned box output
 * By default a box output is aligned CENTER
 *
 * box(text, foreground color, background color, alignment)
 *
 * Alignment options can be one of
 * LEFT
 * RIGHT
 * CENTER
 * @note CENTER is the default
 */

$cli->box("white text on green background\naligned left", WHITE, GREEN, LEFT);

$cli->box("white text on green background\naligned left", WHITE, GREEN, RIGHT);


/**
 * 3. List outputs
 * Arrays can be converted to lists, similar to the ul and ol tags
 */

// an unordered list from an array
$listArray = ["first item", "second item", "third item"];
$cli->printlist($listArray);


/**
 * 3a. List outputs with different bullets
 * Lists can be output as ordered or unordered with different bullet types
 *
 * printlist(array, [bullet type as array element]);
 *
 * Bullet type options can be
 * BULLET_UNORDERED
 * BULLET_NUMBER
 * BULLET_LETTER_UPPERCASE
 * BULLET_LETTER_LOWERCASE
 * BULLET_ROMAN
 *
 * default bullet is BULLET_UNORDERED
 */

$listArray = ["first item", "second item", "third item"];

// ordered by arabic numerals
$cli->printlist($listArray, [BULLET_NUMBER]);

// ordered by letters, uppercase
$cli->printlist($listArray, [BULLET_LETTER_UPPERCASE]);

// ordered by letters, lowercase
$cli->printlist($listArray, [BULLET_LETTER_LOWERCASE]);

// ordered by roman numerals
$cli->printlist($listArray, [BULLET_ROMAN]);


/**
 * 3b. List outputs with sub-lists
 * Lists can have an arbitrary number of sub-lists made from sub arrays.
 *
 * The bullet type for sublists are set by the addition of bullet types to the bullet type array
 *
 * printlist(array, [bullet type top level, bullet type next level, ...]);
 *
 * the example below outputs:
 * A. top level first item
 *    B. top level second item
 *        i.  second level first item
 *        ii. second level second item
 *    C. top levelthird item
 */

// a multi-level array
$listArray = ["top level first item",
    "top level second item",
        ["second level first item",
        "second level second item"],
    "top levelthird item"];

// first level is displayed with BULLET_LETTER_UPPERCASE, second level is displayed with BULLET_ROMAN
$cli->printlist($listArray, [BULLET_LETTER_UPPERCASE, BULLET_ROMAN]);

/**
 * 3c. List indentations
 * The list can be indented a user-defined number of spaces from the left of the terminal. Sub-lists 
 * indentation from the top-level list can also be set.
 * 
 * printlist(array, [bullet types], number of space to indent whole list, number of spaces to indent sub-lists);
 */

// indent whole list 12 spaces. default 4.
$cli->printlist($listArray, [BULLET_LETTER_UPPERCASE, BULLET_ROMAN], 12);

// indent whole list 12 spaces and indent sub-list 9 spaces from top level list. default 4.
$cli->printlist($listArray, [BULLET_LETTER_UPPERCASE, BULLET_ROMAN], 12, 9);


/**
 * 4. Erase
 * Any previous output can be erased with erase()
 * 
 * erase() will only work on the last output. multiple calls do nothing. 
 */

// output three lines of text
$someText =<<<TXT
multiline
text
output
TXT;
$cli->printout($someText);

// delete the three lines output by the last call to printout() or any other output method.
$cli->erase();


/**
 * 5. Clear Line
 * Clears the last line of output. Only clears one line
 */

// output three lines of text
$someText =<<<TXT
multiline
text
output
TXT;
$cli->printout($someText);

// delete just the last line of the output
$cli->clearLine();


/**
 * 6. Clear Screen
 * Clears the entire terminal window and re-homes the cursor 
 */

$cli->clear();


/**
 * User input methods
 */

/**
 * 7. Any key
 * Pauses execution of the script, awaiting any user input key
 *
 * anykey(optional prompt string)
 */

// default prompt "Hit any key to continue:"
$cli->anykey();

// a user-supplied prompt
$cli->anykey("A custom any key prompt");

/**
 * 8. Prompt user choice
 * Prompts user to input choice from supplied array of options 
 * Prompt continues until user makes valid selection
 * 
 * promptChoice(optional prompt, optional array of char options, optional default choice)
 */

// default prompt choice "Choose one [y,n]:"
$userChoice = $cli->promptChoice();
$cli->printout("user chose ".$userChoice);

// custom prompt, list of choosable options. "Pick a vowel [a, e, i, o, u]:"
$userChoice = $cli->promptChoice("Pick a vowel", ['a','e','i','o','u']);
$cli->printout("user chose ".$userChoice);

/**
 * 8a. Setting a default choice
 * A default option can be set so that hitting RETURN selects it
 */

// default option. "Pick a vowel [a, e, i, o, u](default i):"
$userChoice = $cli->promptChoice("Pick a vowel", ['a','e','i','o','u'], 'i');
$cli->printout("user chose ".$userChoice);

/**
 * 8b. Yn convenience method
 * A convenience method for Yes/No prompts
 */

// a simple y/n prompt. "Choose 'yes' or 'no' [y, n]:"
$userChoice = $cli->promptChoiceYn();

// y/n prompt with default set to 'y'. "Choose 'yes' or 'no' [y, n](default y):"
$userChoice = $cli->promptChoiceYn("Choose yes or no", 'y');

// y/n with custom prompt and default set to 'y'. "A custom y/n prompt [y,n](default y):"
$userChoice = $cli->promptChoiceYn("A custom y/n prompt", 'y');
$cli->printout("user entered $userChoice");


/**
 * 9. Read user input
 * Reads one line of user input, submitted by the RETURN key, after the prompt.
 *
 * read(supplied prompt)
 */

$userInput = $cli->read("enter some text");
$cli->printout("user entered $userInput");


// validating input on read()

// assign user input to variable and test it's an integer
while($userInput = filter_var($cli->read("please enter an integer"), FILTER_VALIDATE_INT) === false) {
    $userInput = $cli->clearLine(); // clears user's input so they can start again
    $cli->printerr("must be an integer...",ERROR); // display error message
}
$cli->printout("user entered ".$userInput); // here's the good input

// you can stack more than one validation in a do/while loop like so
do {
    $userInput = $cli->read("enter between 5 and 10 chars");

    // error on first validation fail
    if(strlen($userInput) < 5) {
        $cli->clearLine();
        $cli->printout("too short", ERROR);
    }

    // error on second validation fail
    if(strlen($userInput) > 10) {
        $cli->clearLine();
        $cli->printout("too long", ERROR);
    }
}
// any validation fail starts the loop again
while(strlen($userInput) < 5 || strlen($userInput) > 10);


/**
 * 10. Read user input password
 * Reads one line of user input with a star echo, submitted by the RETURN key, after the prompt.
 */

$userInput = $cli->readPassword("enter a secret");


/**
 * 11. Menu
 * Display an interactive menu of options
 *
 * menu(prompt, options array, inner alignment, outer alignment, foreground color, background color)
 */

// show a menu that returns one of the keys 'a', 'b' or 'c'
$options = array(
    "a" => "New York Dolls",
    "b" => "Rocket from the Tombs",
    "c" => "Death"
);
$selectedKey = $cli->menu("Which band is the first true punk rock band?", $options);

/**
 * 11a. Inner-align menu text
 * Align the menu text inside the box. one of LEFT, RIGHT, CENTER. LEFT is default.
 */
// center the text in the box
$selectedKey = $cli->menu("Which band is the first true punk rock band?", $options, CENTER);

/**
 * 11b. Outer-align menu box
 * Align the menu box in the terminal. one of LEFT, RIGHT, CENTER. LEFT is default.
 */
// center box in terminal, used default inner text alignment
$selectedKey = $cli->menu("Which band is the first true punk rock band?", $options, null, CENTER);

// align box right in terminal, center text in box
$selectedKey = $cli->menu("Which band is the first true punk rock band?", $options, CENTER, RIGHT);

/**
 * 11c. Colorize menu
 * the foreground and background colors of the menu can be set
 * The available colors are:
 * RED
 * BLACK
 * GREEN
 * YELLOW
 * BLUE
 * MAGENTA
 * CYAN
 * WHITE
 */

// white text on a blue background
$selectedKey = $cli->menu("Which band is the first true punk rock band?", $options, null, null, WHITE, BLUE);

/**
 * 12. Horizontal menu
 * A horiontal menu is a single line menu that runs horizontally across the terminal
 *
 * menuhorizontal(description, options array, pre-selected option, alignment, foreground color, background color)
 */
$options = array(
    "a" => "Previous",
    "b" => "Home",
    "c" => "Back"
);
$selectedKey = $cli->menuhorizontal("where to next?", $options);

/**
 * 12a. Pre-select an option
 * You can set an option to be pre-selected
 */
// pre-select the option 'b', "Home"
$selectedKey = $cli->menuhorizontal("where to next?", $options, 'b');

/**
 * 12b. Align horizontal menu
 * The menu can be aligned LEFT, RIGHT or CENTER. LEFT is default.
 */
// center horizontal menu, no option pre-selected
$selectedKey = $cli->menuhorizontal("where to next?", $options, null, CENTER);

// center horizontal menu, option 'b' pre-selected
$selectedKey = $cli->menuhorizontal("where to next?", $options, 'b', CENTER);

/**
 * 12c. Colorize horizontal menu
 * The foreground and background colors of the menu can be set
 * The available colors are:
 * RED
 * BLACK
 * GREEN
 * YELLOW
 * BLUE
 * MAGENTA
 * CYAN
 * WHITE
 */
// foreground white, background blue. default 'b', aligned center
$selectedKey = $cli->menuhorizontal("where to next?", $options, 'b', CENTER, WHITE, BLUE);

// foreground white, background blue. everything else default
$selectedKey = $cli->menuhorizontal("where to next?", $options, null, null, WHITE, BLUE);

/**
 * 13. File selector
 * A file selection interface that returns a string to the full path of the selected file.
 * The interface is displayed in a box with configurable alignment and colors.
 *
 * fileselect(initial directory to open, the prompt displayed)
 */
$selectedFile = $cli->fileselect("/tmp", "Select a file");

/**
 * 13a. Align file selector
 *
 * The fileselect box can be aligned in the terminal. LEFT, RIGHT or CENTER. LEFT is default.
 */
$selectedFile = $cli->fileselect("/tmp", "Select a file", CENTER);

/**
 * 13b. Colorize file selector
 * The foreground and background colors of the menu can be set
 * The available colors are:
 * RED
 * BLACK
 * GREEN
 * YELLOW
 * BLUE
 * MAGENTA
 * CYAN
 * WHITE
 */
// white text on blue aligned center in the terminal
$selectedFile = $cli->fileselect("/tmp", "Select a file", CENTER, WHITE, BLUE);

// yellow on red aligned default
$selectedFile = $cli->fileselect("/tmp", "Select a file", null, YELLOW, RED);

// green text on default background aligned default
$selectedFile = $cli->fileselect("/tmp", "Select a file", null, GREEN);

/**
 * 13c. Set for returning a directory
 * The fileselect has the option of allowing the user to select a directory by hitting 'o' for open.
 * Directory select can be enabled by passing true
 */

// default fileselect that allows directory select
$selectedFile = $cli->fileselect("/tmp", "Select a file", null, null, true);


/**
 * 14. Date selector
 * A date selector interface that returns a Date object of the seected date
 */
$dateObject = $cli->datepicker("pick a date");

/**
 * Get last input
 * Get the last user input
 */
$lastThingUserEntered = $cli->getLastInput();


/**
 * Stream input methods
 * For reading from STDIN piped into the script, eg
 * echo "some input" | myscript.php
 * or
 * cat /some/file | myscript.php
 */

/**
 * 16. read STDIN to string
 * Read all of the STDIN into a string. 
 * Returns a null string if no input.
 */
$pipedInput = $cli->readStdin();

/**
 * 17. read STDIN to stream
 * Read STDIN input into a stream. This is the preferred way as piped-in input
 * can be of an arbitrary length. Returns an empty iterable if no input.
 */
foreach($cli->readStdinStream() as $pipedInputLine){
    $cli->printout($pipedInputLine);
}

/**
 * 18. test STDIN
 * Test to see if there is any piped input
 */
$stdinBoolean = $cli->testStdin();


/**
 * Backgrounding a function or executable code
 * Runs code in the background with a spinner graphic in the foreground
 */

/**
 * 19. Background an anonymous function
 * Run an anonymous function with the default spinner progress animation and
 * get the return value
 */
$longFunction = function() {
    sleep(2);
    return "returned from longFunction";
};
$returnedValue = $cli->background($longFunction);

/**
 * 19a. Background an anonymous function with a single argument
 */
$longFunction = function($duration) {
    sleep($duration);
    return "returned from longFunction";
};
$arg = 4;
$returnedValue = $cli->background($longFunction, $arg);

/**
 * 19b. Background an anonymous function with multiple args
 */
$longFunction = function($duration1, $duration2) {
    sleep($duration1);
    sleep($duration2);
    return "returned from longFunction";
};
$args = [2,3];
$returnedValue = $cli->background($longFunction, $args);

/**
 * 19c. Background an anonymous function with different animation style
 * animation speed options are
 * SPIN
 * PROGRESS
 * default is SPIN
 */
$returnedValue = $cli->background($longFunction, $args, PROGRESS);


/**
 * 19d. Background an anonymous function with custom animation speed
 * animation speed options are
 * DELAY_SLOW  
 * DELAY_MED  
 * DELAY_FAST  
 * DELAY_VERY_FAST  
 * default is DELAY_MED
 */
$returnedValue = $cli->background($longFunction, $args, PROGRESS, DELAY_VERY_FAST);


/**
 * 19e. Background an evalable string
 * valid PHP code in a string can be run in the background
 */
$cli->background("sleep(2);print 'slept for 2 seconds';");


/**
 * Copy and download methods
 */

/**
 * 20. Copy a file somewhat safely
 * Tests readability of source, writeability of desination, disk space 
 */
$copyCheck = $cli->safeCopy("/path/to/sourcefile", "/path/to/destinationfile");

/**
 * 20a. Get error from failed copy
 * Tests readability of source, writeability of desination, disk space 
 */


/**
 * Errors and error handling
 */