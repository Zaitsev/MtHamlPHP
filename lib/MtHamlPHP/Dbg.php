<?
namespace MtHamlPHP;
class Dbg {
    static function emsgd($f_message = null, $f_file = '', $f_line = '')
    {
        if (isset($_GET['hidemsgd'])) return;
        $dbg = debug_backtrace();
        $type = gettype ($f_message);
        if ($f_message === null) {
            $f_message = 'null';
        } elseif (($f_message === false) || ($f_message === true)) {
            $f_message = $f_message === false ? 'false' : 'true';
        }
        $f_file = $dbg[0]['file'];
        $f_line = $dbg[0]['line'];
        $f_message = "\n$type:" . print_r($f_message, TRUE) . "\n";
        echo "\n=====\n" . $f_file . " " . $f_line . " " . $f_message . "\n=====\n";
    }
    static function emsgd_bt($f_message = null, $f_file = '', $f_line = '')
    {
        if (isset($_GET['hidemsgd'])) return;
        $dbg = debug_backtrace();
        $type = gettype ($f_message);
        if ($f_message === null) {
            $f_message = 'null';
        } elseif (($f_message === false) || ($f_message === true)) {
            $f_message = $f_message === false ? 'false' : 'true';
        }
        $f_file = $dbg[0]['file'];
        $f_line = $dbg[0]['line'];
        $dbg=array_map(function($e) {return $e['file'].':'.$e['line'].'=>'.$e['function'];},array_slice($dbg,1));
        $bt = print_r($dbg, TRUE);
        $f_message .= "\n$type:" . print_r($f_message, TRUE) . "\n";
        echo "\n=====\n" . $f_file . " " . $f_line . " " . $f_message . "\n---backtrace:\n$bt\n=====\n";
    }

    static function emsgds($f_message, $f_file = '', $f_line = '')
    {
        $dbg = debug_backtrace();
        $f_file = $dbg[0]['file'];
        $f_line = $dbg[0]['line'];
        $f_message = "<pre>" . print_r($f_message, TRUE) . '</pre>';
        return '<div style="text-align:left;background-color:yellow;color:black;">' . $f_file . " " . $f_line . " " . $f_message . '</div>';
    }
 }