<?php
class Groups extends BingoSql\Model
{
    protected $table='groups';
    protected $key='Id';
    protected $has_many = array('user_details'=>'Id|group_id'); 
    
}
