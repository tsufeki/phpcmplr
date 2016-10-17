#!/usr/bin/env php
<?php

/**
 * Create dictionaries for phpcomlete.vim to use in autocompletion
 *
 * Uses the offical PHP documentation html files to extract function
 * and method names with parameter signatures. The extracted info
 * dumped into vim dictionaries that phpcomlete.vim plugin loads in
 * for omnicomplete.
 */

require_once __DIR__.'/'.'extract-stdlib/tools.php';
require_once __DIR__.'/'.'extract-stdlib/constants.php';
require_once __DIR__.'/'.'extract-stdlib/functions.php';
require_once __DIR__.'/'.'extract-stdlib/classes.php';

$enabled_function_extensions  = array(
    'math', 'strings', 'apache', 'arrays', 'php_options_info', 'classes_objects',
    'urls', 'filesystem', 'variable_handling', 'calendar',
    'function_handling', 'directories', 'date_time', 'network', 'spl',
    'misc', 'curl', 'error_handling', 'dom', 'program_execution',
    'mail', 'fastcgi_process_manager', 'filter', 'fileinfo', 'output_control',
    'gd', 'iconv', 'json', 'libxml', 'multibyte_string', 'mssql',
    'mysql', 'mysqli', 'password_hashing', 'postgresql',
    'pcre', 'sessions', 'streams', 'simplexml', 'xmlwriter', 'zip',
);
$enabled_class_extensions = array(
    'spl', 'predefined_interfaces_and_classes', 'curl', 'date_time', 'directories',
    'dom', 'predefined_exceptions', 'libxml', 'mysqli', 'pdo', 'phar', 'streams',
    'sessions', 'simplexml', 'spl_types', 'xmlreader', 'zip',
);
$enabled_interface_extensions = array(
    'predefined_interfaces_and_classes', 'spl', 'date_time', 'json',
);
$enabled_constant_extensions  = array(
    'common', 'arrays', 'calendar', 'curl', 'date_time', 'libxml', 'mysqli', 'spl',
    'unknow', 'directories', 'dom', 'command_line_usage', 'handling_file_uploads',
    'fileinfo', 'filesystem', 'filter', 'php_options_info', 'strings',
    'error_handling', 'math', 'network', 'urls', 'gd', 'json', 'multibyte_string',
    'mssql', 'mysql', 'output_control', 'password_hashing', 'postgresql',
    'pcre', 'program_execution', 'sessions', 'variable_handling', 'misc',
    'streams','iconv', 'phpini_directives', 'types', 'pdo',
    'list_of_reserved_words', 'php_type_comparison_tables',
);

function main($argv){

    if (count($argv) < 2) {
        usage($argv);
        return 1;
    }

    if (!is_dir($argv[1])) {
        fprintf(STDERR, "Error: Invalid php_doc_path. {$argv[1]} is not a directory\n\n");
        usage($argv);
        return 1;
    }
    if (!is_readable($argv[1])) {
        fprintf(STDERR, "Error: Invalid php_doc_path. {$argv[1]} is not readalbe\n\n");
        usage($argv);
        return 1;
    }

    $extensions = get_extension_names($argv[1]);

    libxml_use_internal_errors(true);

    $function_files = glob("{$argv[1]}/function.*.html");
    $functions = extract_function_signatures($function_files, $extensions);

    $extra_function_files = list_procedural_style_files("{$argv[1]}");
    $functions = extract_function_signatures($extra_function_files, $extensions, $functions);

    $class_files = glob("{$argv[1]}/class.*.html", GLOB_BRACE);
    list($classes, $interfaces) = extract_class_signatures($class_files, $extensions);

    // unfortunately constants are really everywhere, the *constants.html almost there ok but leaves out
    // pages like filter.filters.sanitize.html
    $constant_files = glob("{$argv[1]}/*.html");
    list($constants, $class_constants) = extract_constant_names($constant_files, $extensions);

    // some class constants like PDO::* are not defined in the class synopsis
    // but they show up with the other constatns so we add them to the extracted classes
    inject_class_constants($classes, $class_constants, false);

    global $enabled_function_extensions;
    global $enabled_class_extensions;
    global $enabled_interface_extensions;
    global $enabled_constant_extensions;

    filter_enabled_extensions($enabled_function_extensions, $functions);
    filter_enabled_extensions($enabled_class_extensions, $classes);
    filter_enabled_extensions($enabled_interface_extensions, $interfaces);
    filter_enabled_extensions($enabled_constant_extensions, $constants);

    $functions = array_values(array_merge(...array_values($functions)));
    $classes = array_values(array_merge(...array_values($classes)));
    $interfaces = array_values(array_merge(...array_values($interfaces)));
    $constants = array_values(array_merge(...array_values($constants)));

    foreach ($classes as &$class) {
        foreach (['constants', 'properties', 'methods'] as $key) {
            $class[$key] = array_values($class[$key]);
        }
        unset($class);
    }

    foreach ($interfaces as &$interface) {
        foreach (['constants', 'properties', 'methods'] as $key) {
            $interface[$key] = array_values($interface[$key]);
        }
        unset($interface);
    }

    $outfile = __DIR__ . '/../data/stdlib.json';
    file_put_contents($outfile, json_encode(array(
        'functions' => $functions,
        'classes' => $classes,
        'interfaces' => $interfaces,
        'traits' => array(),
        'constants' => $constants,
    )));

    print "\nextracted ".count($functions)." built-in functions";
    print "\nextracted ".count($classes)." built-in classes";
    print "\nextracted ".count($interfaces)." built-in interfaces";
    print "\nextracted ".count($constants)." built-in constants";
    print "\n";

    return 0;
}

function usage($argv) {
    fprintf(STDERR,
        "USAGE:\n".
        "\tphp {$argv[0]} <php_doc_path>\n".
        "\n".
        "php_doc_path:\n".
        "\tPath to a directory containing the\n".
        "\textracted Many HTML files version of the documentation.\n".
        "\tDownload from here: http://www.php.net/download-docs.php\n"
    );
}

return main($argv);
