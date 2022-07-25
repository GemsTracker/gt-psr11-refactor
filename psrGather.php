<?php

require_once(__DIR__ . '/psrLib.php');

$count  = 0;
$dirs   = [
    'application/classes/',
    'src/',
    'vendor/gemstracker/',
    'vendor/jvangestel/',
    'vendor/magnafacta/',
    ];
$output = "<?php\n\nreturn [\n";

foreach($dirs as $dir) {
    if (file_exists($dir)) {
        echo "Starting on $dir\n";
        foreach (getRecursiveDirIterator($dir) as $key => $value) {
            $classCode = file_get_contents($key);
            $className = getObjectName($classCode);
            echo $key . "\n";
            if (isOldStyleClass($className)) {
                $slashClass = addslashes(getNewClassName($className));
                $output .= "    '$className' => '$slashClass',\n";
                echo $className . "\n";
                $count++;
            }
        }
    }
}

// echo $classCode;
$output .= "  ];\n";
if ($count) {
    file_put_contents('psrList.php', $output);
    echo "Class files: $count\n";
} else {
    echo "No changeable class files found.\n";
}
    
