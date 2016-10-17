<?php

// TODO: extends/implements
// TODO: class modifiers: final, abstract
// TODO: return by ref

function extract_class_signatures($files, $extensions) {
    $class_signatures = array();
    $interface_signatures = array();

    foreach ($files as $file) {
        $doc = new DOMDocument;
        $doc->loadHTMLFile($file);
        $xpath = new DOMXpath($doc);

        list($classname, $is_interface, $extends, $implements) = extract_class_name($xpath, $file);
        if (empty($classname)) {
            // no usual class synopsis found inside the file, just skip this class
            continue;
        }
        $fields    = extract_class_fields($xpath, $classname, $file);
        $methods   = extract_class_methods($xpath, $classname, $file);

        $extension_name = get_extension_name($file, $extensions);

        if (!isset($class_signatures[$extension_name][$classname]) &&
                !isset($interface_signatures[$extension_name][$classname])) {
            if ($is_interface) {
                if (!isset($interface_signatures[$extension_name])) {
                    $interface_signatures[$extension_name] = array();
                }
                $interface_signatures[$extension_name][$classname] = array(
                    'name'              => $classname,
                    'extends'           => $extends,
                    'constants'         => $fields['constants'],
                    'properties'        => $fields['properties'],
                    'methods'           => $methods['methods'],
                );
            } else {
                if (!isset($class_signatures[$extension_name])) {
                    $class_signatures[$extension_name] = array();
                }
                $class_signatures[$extension_name][$classname] = array(
                    'name'              => $classname,
                    'extends'           => $extends,
                    'implements'        => $implements,
                    'modifiers'         => [],
                    'constants'         => $fields['constants'],
                    'properties'        => $fields['properties'],
                    'methods'           => $methods['methods'],
                );
            }
        } else {
            // there are some duplicate class names in extensions, use only the first one
        }
    }

    if (!isset($class_signatures['Predefined Interfaces and Classes']['stdClass'])) {
        $class_signatures['Predefined Interfaces and Classes']['stdClass'] = array(
            'name'              => 'stdClass',
            'extends'           => null,
            'implements'        => [],
            'modifiers'         => [],
            'constants'         => [],
            'properties'        => [],
            'methods'           => [],
        );
    }

    return array($class_signatures, $interface_signatures);
}

function extract_class_fields($xpath, $classname, $file) {
    $re = array(
        'constants' => array(),
        'properties' => array(),
    );
    $field_nodes = $xpath->query('//div[@class="classsynopsis"]//div[contains(@class, "fieldsynopsis")]');
    foreach ($field_nodes as $field_node) {
        // fields look like: <var class="varname"><a href="">$<var class="varname">y</var></a></var>
        $property_node = $xpath->query('var[@class="varname"]//var[@class="varname"]/..', $field_node);
        if ($property_node->length) {
            $property_info = handle_class_property($xpath, $field_node, $file);
            $re['properties'][$property_info['name']] = $property_info;
            continue;
        }

        // constants look like: <var class="fieldsynopsis_varname"><a href="#"><var class="varname">W3C</var></a></var>
        $constant_node = $xpath->query('*[@class="modifier" and text() = "const"]/..', $field_node);
        if ($constant_node->length) {
            $constant_info = handle_class_const($xpath, $field_node, $file);
            $re['constants'][$constant_info['name']] = $constant_info;
            continue;
        }
    }
    array_map('ksort', $re);
    return $re;
}

function extract_class_methods($xpath, $classname, $file) {
    $re = array(
        'methods' => array(),
    );
    $method_nodes = $xpath->query('//div[@class="classsynopsis"]//div[contains(@class, "constructorsynopsis") or contains(@class, "methodsynopsis")]');
    foreach ($method_nodes as $method_node) {
        $method_info = handle_method_def($xpath, $classname, $method_node, $file);
        $re['methods'][$method_info['name']] = $method_info;
    }
    array_map('ksort', $re);
    return $re;
}

function handle_method_def($xpath, $classname, $node, $file) {
    $re = array(
        'name'          => '',
        'return_type'   => '',
        'return_by_ref' => false,
        'modifiers'     => array(),
        'params'        => array(),
    );

    $type = $xpath->query('*[@class="type"]', $node);
    $methodparams = $xpath->query('*[@class="methodparam"]', $node);
    $name = $xpath->query('*[@class="methodname"]/*[@class="methodname"]', $node);

    if ($name->length === 0) {
        // methods that don't have manual pages will look like <span class="methodname"><strong> ... </strong></span>
        $name = $xpath->query('*[@class="methodname"]/strong', $node);

        // if even that failed, just give up
        if ($name->length === 0) {
            var_dump($node->textContent);
            fwrite(STDERR, "\nextraction error, cant find method name in $file\n");
            exit;
        }
    }
    // chop of class name from the inherited method names
    $name = preg_replace('/^[\w\\\\]+::/', '', trim($name->item(0)->textContent));
    $re['name'] = $name;

    // constructors and destructors dont have return types
    if ($type->length === 0 && !($name == '__construct' || $name == '__destruct' || $name == '__wakeup' || $name == $classname)) {
        var_dump($name);
        var_dump($xpath->document->saveHTML($node));
        fwrite(STDERR, "\nextraction error, cant find return type in $file\n");
        exit;
    }
    $re['return_type'] = $type->length ? trim($type->item(0)->textContent) : null;

    $modifiers = $xpath->query('*[@class="modifier"]', $node);
    foreach ($modifiers as $modifier) {
        $re['modifiers'][] = trim($modifier->textContent);
    }

    $params = array();
    $optional = false;
    foreach ($methodparams as $param_node) {
        if (!$optional
            && $param_node->previousSibling->nodeType == XML_TEXT_NODE
            && strpos($param_node->previousSibling->textContent, '[') !== false) {

            $optional = true;
        }
        $paramtype = $xpath->query('*[@class="type"]', $param_node);
        $paramname = $xpath->query('*[contains(@class, "parameter")]', $param_node);
        $paramdefault = $xpath->query('*[@class="initializer"]', $param_node);
        if ($paramname->length) {
            // regular parameter
            $param = array(
                'type' => trim($paramtype->item(0)->textContent),
                'name' => trim($paramname->item(0)->textContent),
                'optional' => $optional,
                'by_ref' => false,
                'variadic' => false,
            );
            if ($paramdefault->length) {
                $param['default'] = trim($paramdefault->item(0)->textContent, "=\n\r ");
                $param['optional'] = $optional;
            }
            if ($param['name'][0] === '&') {
                $param['name'] = substr($param['name'], 1);
                $param['by_ref'] = true;
            }
            if (0 === substr_compare($param['name'], '...', -3, 3)) {
                $param['name'] = substr_replace($param['name'], "", -3);
                $param['variadic'] = true;
            }
            if ($param['name'] === '$') {
                $param['name'] = '$?';
            }
            $params[] = $param;
        }
    }

    $re['params'] = $params;
    return $re;
}

function extract_class_name($xpath) {
    $is_interface = false;
    $class = $xpath->query('//div[@class="classsynopsis"]/div[@class="classsynopsisinfo"]/*[@class="ooclass"]/*[@class="classname"]')->item(0);
    if (!$class) {
        return array(false, $is_interface, null, null);
    }
    $classname = trim($class->textContent);
    $title = $xpath->query('//div[@class="classsynopsis"]/preceding-sibling::h2[@class="title"]')->item(0);
    if ($title && stripos(trim($title->textContent), 'interface') === 0) {
        $is_interface = true;
    }
    $title2 = $xpath->query('//div[@class="reference"]/h1[@class="title"]')->item(0);
    if ($title2 && preg_match('/interface$/i', trim($title2->textContent))) {
        $is_interface = true;
    }

    $extends = [];
    $extends_nodes = $xpath->query('//div[@class="classsynopsis"]/div[@class="classsynopsisinfo"]/*[@class="ooclass"]/*[@class="classname"]');
    for ($i = 1; $i < $extends_nodes->length; $i++) {
        $extends[] = trim($extends_nodes->item($i)->textContent);
    }
    $implements = [];
    $implements_nodes = $xpath->query('//div[@class="classsynopsis"]/div[@class="classsynopsisinfo"]/*[@class="oointerface"]/*[@class="interfacename"]');
    for ($i = 0; $i < $implements_nodes->length; $i++) {
        $implements[] = trim($implements_nodes->item($i)->textContent);
    }

    if ($is_interface) {
        $implements = null;
    } else {
        $extends = empty($extends) ? null : $extends[0];
    }

    return array($classname, $is_interface, $extends, $implements);
}

function handle_class_property($xpath, $node, $file) {
    $re = array(
        'name' => '',
        'modifiers' => array(),
        'initializer' => '',
        'type' => '',
    );
    $type = $xpath->query('*[@class="type"]', $node)->item(0);
    if ($type) {
        $re['type'] = trim($type->textContent);
    }

    $initializer = $xpath->query('*[@class="initializer"]', $node)->item(0);
    if ($initializer) {
        $re['initializer'] = trim($initializer->textContent, "= ");
    }

    $modifiers = $xpath->query('*[@class="modifier"]', $node);
    foreach ($modifiers as $modifier) {
        $re['modifiers'][] = trim($modifier->textContent);
    }

    $name = $xpath->query('var[@class="varname"]', $node)->item(0);
    if (!$name) {
        print $xpath->document->saveHTML($node);
        fwrite(STDERR, "\nextraction error, cant find field name in $file\n");
        exit;
    }

    $re['name'] = '$' . trim($name->textContent, '$ ');

    return $re;
}

function handle_class_const($xpath, $node, $file) {
    $re = array(
        'name' => '',
        'initializer' => '',
    );
    $name = $xpath->query('var//var[@class="varname"]', $node)->item(0);
    if (!$name) {
        print $xpath->document->saveHTML($node);
        fwrite(STDERR, "\nextraction error, cant find const name in $file\n");
        exit;
    }
    $re['name'] = trim($name->textContent);

    $initializer = $xpath->query('*[@class="initializer"]', $node)->item(0);
    if ($initializer) {
        $re['initializer'] = trim($initializer->textContent, "= ");
    }

    return $re;
}

