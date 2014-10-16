BingoSql
========

A lightweight PHP/Mysql ActiveRecord for beginners and light weight applications and cms websites.


##Code Samples
<pre>
<?php

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

</pre>