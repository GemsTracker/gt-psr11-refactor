<?php

function getFileClass(string $filepath)
{
    return substr($filepath, strrpos($filepath, '\\') + 1); 
}

function getNameSpace(string $filepath)
{
    return substr($filepath, 0, strrpos($filepath, '\\'));
}

function getNameSpaceFromCode(string $classCode)
{
    $matches = [];
    if (preg_match("/namespace\\s+([\\w\\\\]+)/", removePHPComments($classCode), $matches)) {
        if (isset($matches[1])) {
            return $matches[1];
        }
    }

    return false;
}

function getNewClassName(string $oldName)
{
    if ('GemsEscort' == $oldName) {
        return 'Gems\\Escort';
    }
    $output = strtr($oldName, '_', '\\');
    
    $renames = [
        '\\Default\\' => '\\Actions\\',
        '\\Echo'      => '\\EchoOut\\EchoOut',
        '\\String'    => '\\StringUtil\\StringUtil',
    ]; 
    $output = str_replace(array_keys($renames), $renames, $output);

//    Disabled as moving Gems_Util to Gems_Util_Util breaks too much
//    if (1 == \substr_count($output, '\\')) {
//        list($name, $sub) = explode('\\', $output, 2);
//        $output = $name . '\\' . $sub . '\\' . $sub; 
//    }

    return $output;
}

function getObjectName(string $classCode)
{
    $matches = [];
    if (preg_match("/(class|trait|interface)\\s+(\w+)(\\s|{)/", removePHPComments($classCode), $matches)) {
        if (isset($matches[2])) {
            return $matches[2];
        }
    }

    return false;
}

function getRecursiveDirIterator(string $dir): \Iterator
{
    $iter    = new \RecursiveDirectoryIterator($dir);
    $recIter = new \RecursiveIteratorIterator($iter);
    // return $recIter;
    return new RegexIterator($recIter, '/^.+\.(phtml|php)$/i', RecursiveRegexIterator::GET_MATCH);
}

function implementNameSpace(string $fileContent, string $oldClass, string $newClass): string
{
    $namespace = getNameSpace($newClass);
    $newName   = getFileClass($newClass);
    // echo $oldClass . ' ' . $namespace . ' ' , $newName . "\n";

    $content = $fileContent;
    $content = preg_replace("/(class\\s+|trait\\s+|interface\\s+)$oldClass(\\s|{)/", "\\1$newName\\2", $content, 1);

    $content = preg_replace('!(\\<\\?php\\s+(/\*[\s\S]*?\*/\\s+))!', "\\1namespace $namespace;\n\n", $content);
    
    return $content;
}

function isOldStyleClass($className)
{
    if ($className && str_contains($className, '_')) {
        foreach (['ZFDebug_', 'Zend_'] as $start) {
            if (str_starts_with($className, $start)) {
                return false;
            }
        }
        return true;
    } else {
        return 'GemsEscort' == $className; 
    }
}

function removePHPComments(string $content): string
{
    return preg_replace('/[\r\n]+/', "\n",
                        preg_replace('/\/\/[^\n\r]*(?:[\n\r])/', "\n", 
                                     preg_replace('!/\*[\s\S]*?\*/!', "\n", $content)
                        )
    );
}
        
function renameOldClasses(string $fileContent, array $renames, array $pregRenames): string
{
    $content = $fileContent;

    // Clean up old comments
    $endSearch = '* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.';
    $start     = strpos($fileContent, '* Copyright (c)');
    $end       = strpos($fileContent, $endSearch);
    if ($start && $end) {
        $content = substr($content, 0, $start - 1) . substr($content, $end + strlen($endSearch) + 1);
    }
    $content = preg_replace("!(\\s+)\\* @version\\s.*!", '', $content);
    
    // Make sure all the namespaces are correct
    foreach ($pregRenames as $find => $replace) {
        $content = preg_replace($find, $replace, $content);    
    }

    // Rename all object names with _ in the name
    $content = str_replace(array_keys($renames), $renames, $content);

    return $content;    
}