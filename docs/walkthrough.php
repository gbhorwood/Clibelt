#!/usr/bin/env php
<?php
include __dir__."/../Clibelt.php";
$cli = new Clibelt();


// get the section and subsection from cli args
$sectionsArray = getSections($argv);
$section = $sectionsArray[0];
$subSection = $sectionsArray[1];


// if section is not set, we start from the intro(). otherwise
// jump straight to the selected section
if($section == null) {
    intro(0, $subSection, $cli);
}

// run the section function
$call = "function".$section;
$call($section, $subSection, $cli);


/**
 * Intro text
 *
 * @param $section
 * @param $subSection
 * @param $cli
 * @return void
 */
function intro($section, $subSection, $cli) {
    $cli->clear();
    $cli->figlet("Clibelt.php", "http://www.figlet.org/fonts/shadow.flf", GREEN, null, CENTER);
    $cli->printout("");
    $intro =<<<TXT
This is the interactive walkthrough for Clibelt.

Clibelt is a library to facilitate making rich, interactive interfaces for command line utilities in pure PHP 7.
Clibelt does not rely on any composer packages or external libraries for maximum portibility. 

For more documentation on Clibelt, refer to:
TXT;
    $cli->printout($intro);
    $documentsArray = [
        "The README file",
        "The kitchensink.php file",
        "Doxygen API documentation"
        ];
    $cli->printlist($documentsArray, [BULLET_UNORDERED]);
    $cli->printout("");
    $cli->printout("This walkthrough was written using Clibelt.");
    $cli->printout("");
    $cli->anykey();
    function0(0, $subSection, $cli);
} // intro



/**
 * Index page
 *
 * Displays the index menu.
 * @param $section
 * @param $subSection
 * @param $cli
 * @return void
 */
function function0($section, $subSection, $cli) {
    $cli->clear();
    $cli->box("Clibelt menu",null, null, CENTER, CENTER);

    // get a sorted list of all the functions we want to make accessible in the menu
    // there are two types of functions: 
    // lettered: keyed by letters, metadata about clibelt, ie intro and license and such
    // numbered: keyed by numbers, explicit stuff about the api
    $menuFunctions = array_merge(getWalkthroughFunctions()["lettered"], getWalkthroughFunctions()["numbered"]);

    // build the array of functions for menu()
    $options = [];
    while(list(,$menuFunction) = each($menuFunctions)) {
        if($menuFunction != "function0") {
            $options[$menuFunction(null, null, null, true)[0]] = $menuFunction(null, null, null, true)[1];
        }
    }

    // menu select runs the appropriate function
    $chosenSection = $cli->menu("Select a Clibelt feature to learn about.", $options, LEFT, LEFT);
    $chosenFunction = "function".$chosenSection;
    $chosenFunction($chosenSection, null, $cli);
} // function0

/**
 * @brief Why write command line scripts in PHP?
 *
 * @param $section
 * @param $subSection
 * @param $cli
 * @param $menudata
 * @return void
 */
function functiona($section, $subSection, $cli, $menudata=null) {
    if($menudata) {
        return [substr(__FUNCTION__,strlen("function")), "Why write command line scripts in PHP?"];
    }
    $cli->clear();
    $cli->box("Section $section\n\nWhy write command line scripts in PHP?",null, null, CENTER, CENTER);
    $cli->printout("");

    $text =<<<TXT
    PHP is not the best language for writing command line applications. However there are some potentially good reasons why you would choose PHP:

TXT;
    $cli->printout($cli->wrapToTerminalWidth(ltrim($text), 20));

    $reasons = [
        BOLD_ANSI."You have reusable libraries or classes: ".CLOSE_ANSI.
        PHP_EOL.
        "If you already have PHP classes or function libs that you wish to deploy in a command line application, it makes sense to write your cli script in PHP rather than re-write your library or class in a different language.".
        PHP_EOL,

        BOLD_ANSI."You are more comfortable in PHP: ".CLOSE_ANSI.
        PHP_EOL.
        "If PHP is your strongest language, you may find it preferable to write your cli script in it rather than work in a language your are less familiar with.".
        PHP_EOL,

        BOLD_ANSI."You want to use certain PHP commands or workflows: ".CLOSE_ANSI.
        PHP_EOL.
        "PHP has some commands that are handy and not necissarily well-implemented in other languages. Maybe you require decoding WDDX data without relying on cpan, right?".
        PHP_EOL];
    $cli->printlist($reasons, [BULLET_NUMBER]);
    nextPrevious($section, $cli);
} // functiona


/**
 * @brief Download and install
 *
 * @param $section
 * @param $subSection
 * @param $cli
 * @param $menudata
 * @return void
 */
function functionb($section, $subSection, $cli, $menudata=null) {
    if($menudata) {
        return [substr(__FUNCTION__,strlen("function")), "Download and install"];
    }

    $bold_ansi = BOLD_ANSI;
    $close_ansi = CLOSE_ANSI;

    $composerText =<<<TXT
${bold_ansi}Composer is not the recommended way to install Clibelt.${close_ansi}
We love composer. However, since Clibelt is designed for writing standalone PHP cli scripts, the recommended installation method is to either download and include or to inline your script into a copy of Clibelt.

${bold_ansi}Preferred install methods.${close_ansi}
There are two preferred ways to install and use Clibelt

TXT;

    $downloadText =<<<TXT
${bold_ansi}Downloading${close_ansi}
Clibelt can be downloaded as a .zip or cloned from the repository at:

\thttps://github.com/gbhorwood/Clibelt

The class file is:

\tClibelt.php

it has no dependencies other that PHP 7

TXT;

    $includeText =<<<TXT
${bold_ansi}Including as a file${close_ansi}
You can include Clibelt.php as an external dependency using include() or require().

To do this:
TXT;

    $inlineText =<<<TXT
${bold_ansi}Writing your script inside Clibelt${close_ansi}
If you want your script to be a single file with no external dependencies other than PHP, you can simply write your custom cli script code at the bottom of the Clibelt.php file.

To do this:
TXT;

    $preferredMethods = [
        BOLD_ANSI."Include Clibelt as a file: ".CLOSE_ANSI.
        "Using include() or require() to include Clibelt.php in your script file. The advantage of this method is that it is easy to upgrade".
        "the library file. However it necessitates Clibelt.php being installed on all machines that run your script.".
        PHP_EOL,

        BOLD_ANSI."Write your script in the Clibelt file: ".CLOSE_ANSI.
        "Writing your user script inside the the Clibelt.php file. This keeps everything in one file.".
        PHP_EOL,
    ];

    $includeSteps = [
        BOLD_ANSI."Download the code: ".CLOSE_ANSI.
        "Get a copy of the latest Clibelt from https://github.com/gbhorwood/Clibelt".
        PHP_EOL,

        BOLD_ANSI."Include Clibelt in your user script : ".CLOSE_ANSI.
        "Using require() or include() as normal".
        PHP_EOL,

        BOLD_ANSI."Set the PHP path at the top of the script file: ".CLOSE_ANSI.
        "Add the following line at the very top of the Clibelt.php script file, above the <?php opening tag:".
        PHP_EOL. 
        PHP_EOL.
        "\t#!/usr/bin/env php".
        PHP_EOL,

        BOLD_ANSI."Set your script file to be executable: ".CLOSE_ANSI.
        "Make sure the permissions on your script file are executable:".
        PHP_EOL.
        PHP_EOL.
        "\tchmod 755 myfancyscript.php".
        PHP_EOL
    ];
    
    $inlineSteps = [
        BOLD_ANSI."Download the code: ".CLOSE_ANSI.
        "Get a copy of the latest Clibelt from https://github.com/gbhorwood/Clibelt".
        PHP_EOL,

        BOLD_ANSI."Add your custom script: ".CLOSE_ANSI.
        "Write your user script code at the bottom of the Clibelt.php file".
        PHP_EOL,

        BOLD_ANSI."Set the PHP path at the top of the script file: ".CLOSE_ANSI.
        "Add the following line at the very top of the Clibelt.php script file, above the <?php opening tag:".
        PHP_EOL. 
        PHP_EOL.
        "\t#!/usr/bin/env php".
        PHP_EOL,

        BOLD_ANSI."Rename Clibelt.php to whatever you want: ".CLOSE_ANSI.
        "Name the file whatever you want your script name to be:".
        PHP_EOL.
        PHP_EOL.
        "\tmv Clibelt.php myfancyscript.php".
        PHP_EOL,

        BOLD_ANSI."Set your script file to be executable: ".CLOSE_ANSI.
        "Make sure the permissions on your script file are executable:".
        PHP_EOL.
        PHP_EOL.
        "\tchmod 755 myfancyscript.php".
        PHP_EOL
    ];


    switch($subSection) {
        case null:
            $cli->clear();
            $cli->box("Section $section\n\nDownload and install Clibelt.",null, null, CENTER, CENTER);
            $cli->printout("");
            $cli->printout($cli->wrapToTerminalWidth(ltrim($composerText), 20));
            $cli->printlist($preferredMethods, [BULLET_NUMBER]);
            $cli->printout("");
            $cli->printout("This page can be referenced with `walkthrough.php --section=$section`".PHP_EOL);
            $cli->anykey(REVERSE_ANSI."Next:".CLOSE_ANSI." Ba. 'Downloading' (Hit any key)");

        case 'a':
            $cli->clear();
            $cli->box("Section $section\nSubsection a\n\nDownloading Clibelt.",null, null, CENTER, CENTER);
            $cli->printout("");
            $cli->printout($cli->wrapToTerminalWidth(ltrim($downloadText), 20));
            $cli->printout("");
            $cli->printout("This page can be referenced with `walkthrough.php --section=$section --subsection=a`".PHP_EOL);
            $cli->anykey(REVERSE_ANSI."Next:".CLOSE_ANSI." Bb. 'Writing your script in the Clibelt file' (Hit any key)");

        case 'b':
            $cli->clear();
            $cli->box("Section $section\nSubsection b\n\nWriting your script in the Clibelt file.",null, null, CENTER, CENTER);
            $cli->printout("");
            $cli->printout($cli->wrapToTerminalWidth(ltrim($inlineText), 20));
            $cli->printout("");
            $cli->printlist($inlineSteps, [BULLET_NUMBER]);
            $cli->printout("This page can be referenced with `walkthrough.php --section=$section --subsection=b`".PHP_EOL);
            $cli->anykey(REVERSE_ANSI."Next:".CLOSE_ANSI." Bc. 'Including Clibelt in your script file' (Hit any key)");

        case 'c':
            $cli->clear();
            $cli->box("Section $section\nSubsection c\n\nIncluding Clibelt in your script file.",null, null, CENTER, CENTER);
            $cli->printout("");
            $cli->printout($cli->wrapToTerminalWidth(ltrim($includeText), 20));
            $cli->printout("");
            $cli->printlist($includeSteps, [BULLET_NUMBER]);
            $cli->printout("This page can be referenced with `walkthrough.php --section=$section --subsection=c`".PHP_EOL);
    }

    nextPrevious($section, $cli);
}


/**
 * @brief License
 *
 * @param $section
 * @param $subSection
 * @param $cli
 * @param $menudata
 * @return void
 */
function functionc($section, $subSection, $cli, $menudata=null) {
    if($menudata) {
        return [substr(__FUNCTION__,strlen("function")), "License"];
    }

    $cli->clear();
    $cli->box("Section $section\n\nThe BSD-3 License.",null, null, CENTER, CENTER);
    $cli->printout("");

    $bold_ansi = BOLD_ANSI;
    $close_ansi = CLOSE_ANSI;

    $year = date("Y");
    $license =<<<TXT
{$bold_ansi}BSD 3-Clause License{$close_ansi}

Copyright  $year grant horwood

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

More information on the BSD 3 Clause license:
https://opensource.org/licenses/BSD-3-Clause


TXT;

    $cli->printout($cli->wrapToTerminalWidth($license, 20));
    $cli->printout("This page can be referenced with `walkthrough.php --section=$section`".PHP_EOL);
    nextPrevious($section, $cli);
}

/**
 * @brief Further reading
 *
 * @param $section
 * @param $subSection
 * @param $cli
 * @param $menudata
 * @return void
 */
function functiond($section, $subSection, $cli, $menudata=null) {
    if($menudata) {
        return [substr(__FUNCTION__,strlen("function")), "Further reading"];
    }

    $cli->clear();
    $cli->box("Section $section\n\nThe BSD-3 License.",null, null, CENTER, CENTER);
    $cli->printout("");

    $bold_ansi = BOLD_ANSI;
    $close_ansi = CLOSE_ANSI;

    $furtherReadings = [
        BOLD_ANSI."The README file ".CLOSE_ANSI.
        PHP_EOL.
        "A short overview and a quickstart.".
        PHP_EOL.
        "https://github.com/gbhorwood/Clibelt".
        PHP_EOL,

        BOLD_ANSI."The kitchensink file ".CLOSE_ANSI.
        PHP_EOL.
        "Examples of all the Clibelt features in one file.".
        PHP_EOL.
        "https://github.com/gbhorwood/Clibelt/blob/master/docs/kitchensink.php".
        PHP_EOL,

        BOLD_ANSI."The API reference ".CLOSE_ANSI.
        PHP_EOL.
        "The class documentation.".
        PHP_EOL.
        "<tba>".
        PHP_EOL,

    ];

    $cli->printout($cli->wrapToTerminalWidth(BOLD_ANSI."Futher reading".CLOSE_ANSI.PHP_EOL, 20));
    $cli->printlist($furtherReadings, [BULLET_UNORDERED]);

    $cli->printout("This page can be referenced with `walkthrough.php --section=$section`".PHP_EOL);
    nextPrevious($section, $cli);
} // functiond


function function1($section, $subSection, $cli, $menudata=null) {
    if($menudata) {
        return [substr(__FUNCTION__,strlen("function")), "this is function 1"];
    }

    $cli->clear();
    $cli->box("section $section sb 1",null, null, CENTER, CENTER);

    switch($subSection) {

        case null:
        print "this is null".PHP_EOL;
        $cli->anykey("next section 'a' (hit any key)");
        
        case 'a':
        print "this is a".PHP_EOL;
        $cli->anykey("next section 'b' (hit any key)");

        case 'b':
        print "this is b".PHP_EOL;

    }

    nextPrevious($section, $cli);
}

function function2($section, $subSection, $cli, $menudata=null) {
    if($menudata) {
        return [substr(__FUNCTION__,strlen("function")), "this is function 2"];
    }
    $cli->clear();
    $cli->box("section $section",null, null, CENTER, CENTER);

    nextPrevious($section, $cli);
}



function function3($section, $subSection, $cli, $menudata=null) {
    if($menudata) {
        return [substr(__FUNCTION__,strlen("function")), "this is function 3"];
    }
    $cli->clear();
    $cli->box("section $section",null, null, CENTER, CENTER);

    nextPrevious($section, $cli);
}

function getWalkthroughFunctions() {

    $numberedFunctions = array_values(array_filter(array_map(function($userfunction) {
            if(substr($userfunction, 0, strlen("function")) == "function") {
                if(is_numeric(substr($userfunction,strlen("function")))) {
                    return $userfunction;
                }
            }
        },
        get_defined_functions()["user"]),
        function($element){
            return strlen($element);
        }));
    sort($numberedFunctions);

    $letteredFunctions = array_values(array_filter(array_map(function($userfunction) {
            if(substr($userfunction, 0, strlen("function")) == "function") {
                if(!is_numeric(substr($userfunction,strlen("function")))) {
                    return $userfunction;
                }
            }
        },
        get_defined_functions()["user"]),
        function($element){
            return strlen($element);
        }));
    sort($letteredFunctions);

    return ["lettered" => $letteredFunctions, "numbered" => $numberedFunctions];
} // getWalkthroughFunctions

/**
 * Displays the previous/index/next horizontal menu and runs the selected function
 *
 * @param $section Int. The function number of the function calling this.
 * @param $cli Clibelt object.
 * @return void
 */
function nextPrevious($section, $cli) {

    $numberedFunctions = getWalkthroughFunctions()["numbered"];
    $letteredFunctions = getWalkthroughFunctions()["lettered"];

    if(is_string($section)) {

        $chosenSection = $cli->menuhorizontal("",
                array(
                    ord($section)-1 => "previous",
                    0 => "index",
                    ord($section)+1 => "next"),
                ord($section)+1,
                CENTER);
        $chosenFunction = "function".chr($chosenSection);
        print "got $chosenFunction\n";

        if(!function_exists($chosenFunction)) {
            if($chosenSection < ord($section)) {
                function0(0, null, $cli);
            }
            else {
                function1(1, null, $cli);
            }
        }
        $chosenFunction(chr($chosenSection), null, $cli);
    }
    else {
        $nextSection = $section + 1;
        $previousSection = $section - 1;
        if($previousSection == 0) {
            $previousSection = substr($letteredFunctions[count($letteredFunctions)-1],strlen("function"));
        }
        $chosenSection = $cli->menuhorizontal("",
                array(
                    $previousSection => "previous",
                    0 => "index",
                    $nextSection => "next"),
                $section+1,
                CENTER);

        $chosenFunction = "function".$chosenSection;

        if(!function_exists($chosenFunction)) {
            $letteredFunctions[0]( substr($letteredFunctions[0],strlen("function")), null, $cli);
        }

        $chosenFunction($chosenSection, null, $cli);
    }
} // nextPrevious

function getSections()
{
    $opts = getopt("", ["section::", "subsection::"]);

    $section = @$opts['section'];
    $subsection = @$opts['subsection'];
    return [$section, $subsection];
}