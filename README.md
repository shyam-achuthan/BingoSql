BingoSql
========

A lightweight PHP/Mysql ActiveRecord for beginners and light weight applications and cms websites.

##Configuration
Include boostrap.php in your php application<br>
change the database settings in lib/config.php<br>
And you're done!<br>

##Creating Models
<ul>
<li>All models should extend BingoSql Class
<pre>
class User_details extends BingoSqlModel
{
          
}
</pre>
</li>
<li>Default primary key will be chosen as 'id' otherwise you should specify the primary key field 
<pre>
class User_details extends BingoSqlModel
{
   
    protected $key='Id';   
    
}
</pre>
</li>
<li>Model name should be same as the table name otherwise you should add protected $table="tablename" to the class
<pre>
class User extends BingoSqlModel
{
    protected $table='user_details';
    protected $key='Id';
}
</pre>
</li>
<li>Defining relations - As of now BingoSql support two kind of relations, belongs_to and has_many<br>
While defining the relation your should represent the foreign filed and native primary key field.
Here in the example a user->belongs_to->groups and a groups->has_many->users
<pre>
class User extends BingoSqlModel
{
    protected $table='user_details';
    protected $key='Id';
    protected $belongs_to = array('groups'=>'group_id|Id'); 
    
}
// protected $belongs_to =array('table_name'=>'foreign_field_name|native_primary_key_for_table_which_it_belong_to'); 
class Groups extends BingoSqlModel
{
    protected $table='groups';
    protected $key='Id';
    protected $has_many = array('user_details'=>'Id|group_id'); 
    
}
// protected $has_many =array('table_name'=>'native_primary_key|foreign_key_field_at_related_table'); 
</pre>
</li>
<li>Models can be in separate php class files in the models directory or any other single directory.</li>
</ul>

##Code Samples
<pre>


include('../bootstrap.php');

// Creating a new row in a table
$newuser = new User();
$newuser->email = 'new@gmail.com';
$newuser->password = md5('password');
$newuser->fullname = 'My Fullname';
$newuser->groups = 1;
$newuser->save();

//Finding a user by id
$existinguser = new User();
$existinguser->find(204);
//accessing fields of that specific user
echo "Fullname: " . $existinguser->fullname . '<br>';
echo "Email: " . $existinguser->email . '<br>';


//Updating the found and existing record
$existinguser->email="updatedemail@gmail.com";
$existinguser->save();


//Accessing a relation to groups table assuming groups table have a field group_name
echo "User belongs to Group: " . $existinguser->groups->group_name;

//To find all relations to the group
$grp = new Groups();
$grp->find(1);
echo "There are " . count($grp->user_details) . " users in this group<br>";

foreach ($grp->user_details as $user) {
    echo "Fullname: " . $existinguser->fullname . '<br>';
    echo "Email: " . $existinguser->email . '<br>';
    echo '<hr>';
}



//Advanced find method where you can use the options like limit, where, order_by etc
$users=$s->find('all',array(
                    'limit'=>'0,2'
                    ));
// returned will be an array of objects of the respective model class

//using where conditions
$users=$s->find('all',array(
                    'where'=>array('id>1',"email like '%gmail.com'")
                    ));

//using order_by
$users=$s->find('all',array(
                    'order_by'=>array('id desc',"email asc")
                    ));
                    
//using multiple advanced finds
$users=$s->find('all',array(
                    'limit'=>'0,2',
                    'where'=>array('id>1',"email like '%gmail.com'"),
                    'order_by'=>array('id desc',"email asc")
                    ));

                    

</pre>
