<?php
namespace bitExpert\Adrenaline\Helper;

class TestBodyStub {
    public $context;
    public static $position = 0;
    public static $body = '';

    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }

    public function stream_read($bytes) {
        $chunk = substr(static::$body, static::$position, $bytes);
        static::$position += strlen($chunk);
        return $chunk;
    }

    public function stream_write($data) {
        return strlen($data);
    }

    public function stream_eof() {
        return static::$position >= strlen(static::$body);
    }

    public function stream_tell() {
        return static::$position;
    }

    public function stream_close() {
        return null;
    }

    public function stream_stat()
    {
        
    }
}