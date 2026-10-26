<?php

class db {
    public static function connect(){
        return new mysqli(
            getenv('DB_HOST'),
            getenv('DB_USER'),
            getenv('DB_PASS'),
            getenv('DB_NAME')
        );
    }
}
?>