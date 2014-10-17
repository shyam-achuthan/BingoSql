<?php
        
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
    echo '<hr>';
}
