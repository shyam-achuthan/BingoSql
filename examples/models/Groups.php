<?php
class Groups extends BingoSqlModel
{
    protected $table='groups';
    protected $key='Id';
    protected $has_many = array('user_details'=>'Id|group_id'); 
    
}
