<?php
namespace pongo;
require_once realpath(__DIR__) . '/../lib/pongo.php';

Pongo::initializeConnection('mongodb://localhost:27017', 'pongo_dev');

class User extends Pongo {
    protected static $FIELDS = array('_id', 'name', 'age');
    protected static $TABLE = 'users';
}
