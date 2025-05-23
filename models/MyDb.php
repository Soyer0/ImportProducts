<?php
require_once(__DIR__ . '/../lib/db.php');
class MyDb extends Db {
    public float $time_start;
    public int $mem_start;
}
