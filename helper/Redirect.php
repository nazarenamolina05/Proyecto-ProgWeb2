<?php

class Redirect
{
    public static function to($url)
    {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        header("Location: " . $base . $url);
        exit();
    }

    public static function toIndex()
    {
        self::to('/');
    }
}