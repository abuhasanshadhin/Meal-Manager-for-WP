<?php

require './header.php';

$request = raw_input_stream();

switch ($request['action'] ?? '') {

    case 'get':
        echo get_members($request);
        break;

    case 'store':
        echo add_member($request);
        break;

    case 'update':
        echo update_member($request);
        break;

    case 'delete':
        echo delete_member($request);
        break;

    default:
        echo response(['message' => 'Not found'], 404);
        break;
}

function get_members($req)
{
    global $mm_db;

    $query = "SELECT m.*, r.room_number FROM mm_members AS m
        JOIN mm_rooms AS r ON r.id = m.room_id
        WHERE m.deleted_at IS NULL
        ORDER BY m.id DESC
    ";

    $result = $mm_db->query($query);

    $members = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            unset($row['deleted_at']);
            $members[] = $row;
        }
    }

    echo response(['members' => $members]);
}

function add_member($req)
{
    global $mm_db;

    $room_id = $req['room_id'];
    $name = $req['name'];
    $phone = $req['phone'];
    $address = $req['address'];

    try {

        $mm_db->query("
            INSERT INTO mm_members (room_id, name, phone, address)
            VALUES ('$room_id', '$name', '$phone', '$address')
        ");

        echo response(['message' => 'Member added successfully']);
        
    } catch (mysqli_sql_exception $e) {
        
        echo response(['message' => $e->getMessage()], 500);
    }
}

function update_member($req)
{
    global $mm_db;

    $room_id = $req['room_id'];
    $name = $req['name'];
    $phone = $req['phone'];
    $address = $req['address'];
    $member_id = $req['member_id'];

    $result = $mm_db->query("
        UPDATE mm_members
        SET room_id = '$room_id',
        name = '$name',
        phone = '$phone',
        address = '$address'
        WHERE id = '$member_id'
    ");

    if ($result) {
        echo response(['message' => 'Member updated successfully']);
    } else {
        echo response(['message' => '500! Error Occured'], 500);
    }
}

function delete_member($req)
{
    global $mm_db;
    
    $member_id = $req['member_id'];
    $dateTime = mm_date('Y-m-d H:i:s');

    $result = $mm_db->query("
        UPDATE mm_members
        SET deleted_at = '$dateTime'
        WHERE id = '$member_id'
    ");

    if ($result) {
        echo response(['message' => 'Member deleted successfully']);
    } else {
        echo response(['message' => '500! Error Occured'], 500);
    }
}
