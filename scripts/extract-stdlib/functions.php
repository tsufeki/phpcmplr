<?php

// TODO: return by ref

function extract_function_signatures($files, $extensions, $signatures = array()) {
    foreach ($files as $file) {
        $extension_name = get_extension_name($file, $extensions);
        if (!isset($signatures[$extension_name])) {
            $signatures[$extension_name] = array();
        }

        $doc = new DOMDocument;
        $doc->loadHTMLFile($file);
        $xpath = new DOMXpath($doc);
        $nodes = $xpath->query('//div[contains(@class, "methodsynopsis")]');
        if ($nodes->length == 0) {
            // no signature found, maybe its an alias?
            $nodes = $xpath->query('//div[contains(@class, "description")]/p[@class="simpara"][contains(text(), "This function is an alias of:")]');
            if ($nodes->length) {
                $signatures[$extension_name][] = handle_func_alias($xpath, $nodes, $file);
            }
        } else if ($nodes->length == 1) {
            if (!preg_match('/\w+::\w+/', $nodes->item(0)->textContent)) {
                $signatures[$extension_name][] = handle_func_def($xpath, $nodes->item(0), $file);
            } else {
                fwrite(STDERR, "WARNING: Only class-like function definition found in ".$file."\n");
                continue;
            }
        } else if ($nodes->length > 1) {
            // more than one signature for a single function name
            // maybe its a procedural style of a method like  xmlwriter_text -> XMLWriter::text
            // search for the first non object style synopsis and extract from that
            foreach ($nodes as $node) {
                if (!preg_match('/\w+::\w+/', $node->textContent)) {
                    $signatures[$extension_name][] = handle_func_def($xpath, $node, $file);
                    break;
                }
            }
        }
    }
    return $signatures;
}

function list_procedural_style_files($dir) {
    $files = array();
    $dir = rtrim($dir, '/');
    $dh  = opendir($dir);

    $doc = new DOMDocument();
    while (false !== ($filename = readdir($dh))) {
        if (preg_match('/\.html$/', $filename)) {
            $doc->loadHTMLFile($dir.'/'.$filename);
            $xpath = new DOMXPath($doc);
            $nodes = $xpath->query('//p[contains(@class, "para") and contains(translate(text(), "P", "p"), "procedural style")]');
            if ($nodes && $nodes->length !== 0) {
                $files[] = $dir.'/'.$filename;
            }
        }
    }
    return array_unique($files);
}

function handle_func_def($xpath, $nodes, $file) {
    $type = $xpath->query('span[@class="type"]', $nodes);
    $methodname = $xpath->query('*[@class="methodname"]/*', $nodes);
    $methodparams = $xpath->query('*[@class="methodparam"]', $nodes);
    if ($type->length === 0) {
        fwrite(STDERR, "WARNING: can't find return type in ".$file."\n");
        $return_type = 'void';
    } else {
        $return_type = trim($type->item(0)->textContent);
    }
    if ($methodname->length === 0) {
        fwrite(STDERR, "Extraction error, can't find method name in ".$file."\n");
        exit;
    }
    $params = array();
    $optional = false;
    foreach ($methodparams as $param) {
        if (!$optional
            && $param->previousSibling->nodeType == XML_TEXT_NODE
            && strpos($param->previousSibling->textContent, '[') !== false) {

            $optional = true;
        }
        $paramtype = $xpath->query('*[@class="type"]', $param);
        $paramname = $xpath->query('*[contains(@class, "parameter")]', $param);
        $paramdefault = $xpath->query('*[@class="initializer"]', $param);
        if ($paramname->length) {
            // regular parameter
            $p = array(
                'type' => $paramtype->item(0)->textContent,
                'name' => $paramname->item(0)->textContent,
                'optional' => $optional,
                'by_ref' => false,
                'variadic' => false,
            );
            if ($paramdefault->length) {
                $p['default'] = trim($paramdefault->item(0)->textContent, "=\r\n ");
                $p['optional'] = true;
            }
            if ($p['name'][0] === '&') {
                $p['name'] = substr($p['name'], 1);
                $p['by_ref'] = true;
            }
            if (0 === substr_compare($p['name'], '...', -3, 3)) {
                $p['name'] = substr_replace($p['name'], '', -3);
                $p['variadic'] = true;
            }
            if ($p['name'] === '$') {
                $p['name'] = '$?';
            }
            $params[] = $p;
        }
    }
    return array(
        'kind' => 'function',
        'return_type' => $return_type,
        'return_by_ref' => false,
        'name' => trim($methodname->item(0)->textContent),
        'params' => $params
    );
}

function handle_func_alias($xpath, $nodes, $file) {
    $methodname = $xpath->query('//h1[@class="refname"]');
    $refname = $xpath->query('//*[contains(@class, "description")]/p[@class="simpara"]/*[@class="methodname" or @class="function"]');
    $name = trim(str_replace("\n", '', $methodname->item(0)->textContent));
    $aliased_name = trim(str_replace("\n", '', $refname->item(0)->textContent));
    if (0 === substr_compare($aliased_name, '()', -2, 2)) {
        $aliased_name = substr_replace($aliased_name, '', -2);
    }
    return array(
        'kind' => 'alias',
        'name' => $name,
        'aliased_name' => $aliased_name,
    );
}

