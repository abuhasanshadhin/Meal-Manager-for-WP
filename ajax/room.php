<?php

require './header.php';

$request = raw_input_stream();

switch ($request['action'] ?? '') {

    case 'get':
        echo get_rooms($request);
        break;

    case 'store':
        echo add_room($request);
        break;

    case 'update':
        echo update_room($request);
        break;

    case 'delete':
        echo delete_room($request);
        break;

    default:
        echo response(['message' => 'Not found'], 404);
        break;
}

function get_rooms($req)
{
    global $mm_db;

    $query = "SELECT * FROM mm_rooms WHERE deleted_at IS NULL ORDER BY id DESC";
    $result = $mm_db->query($query);

    $rooms = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            unset($row['deleted_at']);
            $rooms[] = $row;
        }
    }

    echo response(['rooms' => $rooms]);
}

function add_room($req)
{
    global $mm_db;

    $room_number = $req['room_number'];

    try {

        $mm_db->query("INSERT INTO mm_rooms (room_number) VALUES ('$room_number')");

        echo response(['message' => 'Room added successfully']);
        
    } catch (mysqli_sql_exception $e) {
        
        echo response(['message' => $e->getMessage()], 500);
    }
}

function update_room($req)
{
    global $mm_db;

    $room_id = $req['room_id'];
    $room_number = $req['room_number'];

    $result = $mm_db->query(" UPDATE mm_rooms SET room_number = '$room_number' WHERE id = '$room_id'");

    if ($result) {
        echo response(['message' => 'Room updated successfully']);
    } else {
        echo response(['message' => '500! Error Occured'], 500);
    }
}

function delete_room($req)
{
    global $mm_db;
    
    $room_id = $req['room_id'];
    $dateTime = mm_date('Y-m-d H:i:s');

    $result = $mm_db->query("
        UPDATE mm_rooms
        SET deleted_at = '$dateTime'
        WHERE id = '$room_id'
    ");

    if ($result) {
        echo response(['message' => 'Room deleted successfully']);
    } else {
        echo response(['message' => '500! Error Occured'], 500);
    }
}
