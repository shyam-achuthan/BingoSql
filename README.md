BingoSql
========

A lightweight PHP/Mysql ActiveRecord for beginners and light weight applications and cms websites. Yep its just 200 lines of code.

##Configuration
Include BingoSql.php in your php application( vendor/autoload.php if you are using composer autoloading)<br>
Initialize BingoSql Instance
<pre>
new BingoSql\Instance(array(
    'DATABASE_HOST'=>'localhost',
    'DATABASE_USER'=>'root',
    'DATABASE_PASSWORD'=>'root123',
    'DATABASE_NAME'=>'test_db',
    'MODELS_PATH'=>'examples/models/'    
));

</pre><br>
And you're done!<br>

##Creating Models
<ul>
<li>All models should extend BingoSql\Model Class
<pre>
class User_details extends BingoSql\Model
{
          
}
</pre>
</li>
<li>Default primary key will be chosen as 'id' otherwise you should specify the primary key field 
<pre>
class User_details extends BingoSql\Model
{
   
    protected $key='Id';   
    
}
</pre>
</li>
<li>Model name should be same as the table name otherwise you should add protected $table="tablename" to the class
<pre>
class User extends BingoSql\Model
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
class User extends BingoSql\Model
{
    protected $table='user_details';
    protected $key='Id';
    protected $belongs_to = array('groups'=>'group_id|Id'); 
    
}
// protected $belongs_to =array('table_name'=>'foreign_field_name|native_primary_key_for_table_which_it_belong_to'); 
class Groups extends BingoSql\Model
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
##Creating Models on runtime
You can create simple activerecord models on runtime also.
<pre>
\BingoSql\Model::CreateModel('test_table',array('table'=>'Test_Table','primary_key'=>'Id'));
$obj=new test_table();
$obj->fieldname='newvalue';
$obj->save();
</pre>

##Code Samples
<pre>

include('../vendor/autoload.php');
/* if you are not using composer autoloading instead of above line of code.
    include('../lib/BingoSql.php');
*/
new BingoSql\Instance(array(
    'DATABASE_HOST'=>'localhost',
    'DATABASE_USER'=>'root',
    'DATABASE_PASSWORD'=>'root123',
    'DATABASE_NAME'=>'test_db',
    'MODELS_PATH'=>'examples/models/'    
));

// Creating a new row in a table
$newuser = new User();
$newuser->email = rand(0,999).'new@gmail.com';
$newuser->password = md5('password');
$newuser->fullname = 'My Fullname'.rand(0,999);
$newuser->group_id = 1;
$newuser->save();

//Finding a user by id
$existinguser = new User();
$existinguser->find(5);
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
    echo "Fullname: " . $user->fullname . '<br>';
    echo "Email: " . $user->email . '<br>';
    
}


</pre>
