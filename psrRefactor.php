<?php

require_once(__DIR__ . '/psrLib.php');

$count    = 0;
$dirs     = [
    'application/',
    'htdocs/',
    'src/',
    'test/',
    'vendor/gemstracker/',
    'vendor/magnafacta/',
];
$listFile = 'psrList.php';
$maxFiles = -1;
$preview  = in_array('-p', $argv);
$rename   = in_array('-n', $argv);

if (in_array('-h', $argv)) {
    echo "Refactor a GemsTracker project.\nOptions are:\n\t-h  Show this help.\n\t-n Add .new to file names.\n\t-p Preview but do not change.\n";
    exit(0);
}

if (! file_exists($listFile)) {
    echo "Refactor file $listFile does not exist. Run psrGather.php first.\n";
    exit(1);
}

$renameCore  = require 'psrList.php';
$renames     = [];
$pregRenames = [];

foreach ($renameCore as $old => $new) {
    $newSlashed    = str_replace('\\', '\\\\', $new);
    $stringSlashed = str_replace('\\', '\\\\\\\\', $new);
    $renames[$old] = $new;

    $pregRenames["|([\'\"])([\\\\]?$old)([\'\":])|"] = "\\1\\\\\\\\$stringSlashed\\3";
    $pregRenames["|([\\s\\(])([\\\\]?$old)([\\s\\-.:,\\)])|"] = "\\1\\\\$newSlashed\\3";
    
    $oldExploded = explode('_', $old);
    $newExploded = explode('\\', $new);

    while (count($newExploded) > 2) {
        array_shift($oldExploded);
        array_shift($newExploded);

        $oldRest = implode('_', $oldExploded);
        $newRest = implode('\\\\', $newExploded);
        if ($oldRest) {
            $renames["'$oldRest'"] = "'$newRest'";
            $renames["\"$oldRest\""] = "\"$newRest\"";
        }
    }    
}
foreach (['ArrayIterator', 'ArrayObject', 'Iterator', 'Traversable'] as $class) {
    $pregRenames["|([\'\"])([\\\\]?$class)([\'\":])|"] = "\\1\\\\\\\\$class\\3";
    $pregRenames["|([\\s\\(])([\\\\]?$class)([\\s\\-.:,\\(\\)])|"] = "\\1\\\\$class\\3";
}

$pregRenames["!(\\s+)(Zend_|ZendX_)!"] = '\\1\\\\\\2';
$pregRenames["!(\\s+)(MUtil|Gems|Zalt|Mezzio|Laminas|Symfony)(\\W)!"] = '\\1\\\\\\2\\3';
$pregRenames['!(namespace|use|class|interface|trait|@package)(\\s+)\\\\!'] = '\\1\\2';

// Make sure the correct View Helpers can be found
foreach (['MUtil_View_Helper', 'MUtil_Less_View_Helper', 'Gems_View_Helper'] as $helper) {
    $newHelper = strtr($helper, '_', '\\\\');
    $renames["'$helper'"] = "'$newHelper'";
}

// Do not remove this slash in Gems\Roles everytime
$renames['(get_class(self::$_instanceOfSelf)!==\'\\\\Gems\\\\Roles\')'] = '(get_class(self::$_instanceOfSelf)!==\'Gems\\\\Roles\')'; 

// Optional debug checks
if (false) {
    print_r($pregRenames);
    return;
}

foreach($dirs as $dir) {
    echo "Starting on " . rtrim($dir, '/') . "\n";
    if (file_exists($dir)) {
        foreach (getRecursiveDirIterator($dir) as $key => $value) {
            $classCode = file_get_contents($key);
            $className = getObjectName($classCode);
            $newClass  = $className;
            $newCode   = $classCode;
            $newName   = false;
    
            if (isset($renames[$className])) {
                $newCode  = implementNameSpace($newCode, $className, $renames[$className]);
                $newClass = $renames[$className];
                
                $oldName = strtr($className, '_', '\\');
                if ($oldName != $renames[$className]) {
                    $newName = str_replace($oldName, $renames[$className], $key);
                    $dir = dirname($newName);
                    if (! file_exists($dir)) {
                        mkdir($dir, 0777, true);
                    }
                }
            }
            $newCode = renameOldClasses($newCode, $renames, $pregRenames);
            
            if ($newName || ($newCode != $classCode)) {
                if (! $newName) {
                    $newName = $key;
                }
                if ($rename) {
                    $newName .= '.new';
                }
                if (! $preview) {
                    file_put_contents($newName, $newCode);
                }
                echo $key . ' -> ' . $newName . "\n";
                $count++;

                if ($count == $maxFiles) {
                    exit(0);
                }
            }
        }
    }
}

echo "Files changed: $count\n";

