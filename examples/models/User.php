<?php
class User extends BingoSql\Model
{
    protected $table='user_details';
    protected $key='Id';
    protected $belongs_to = array('groups'=>'group_id|Id'); 
    
}
