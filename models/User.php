<?php
class User extends BingoSqlModel
{
    protected $table='user_details';
    protected $key='Id';
    protected $belongs_to = array('groups'=>'group_id|Id'); 
    
}
