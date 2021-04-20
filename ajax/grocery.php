<?php

require './header.php';

$request = raw_input_stream();

switch ($request['action'] ?? '') {

    case 'get':
        echo get_grocery_shoppings($request);
        break;

    case 'store':
        echo add_grocery($request);
        break;

    case 'update':
        echo update_grocery($request);
        break;

    case 'delete':
        echo delete_member($request);
        break;

    default:
        echo response(['message' => 'Not found'], 404);
        break;
}

function get_grocery_shoppings($req)
{
    global $mm_db;

    $member_id = $req['member_id'];
    $year = $req['year'];
    $month = $req['month'];

    if (!$member_id || !$year || !$month) {
        echo response(['grocery_shoppings' => []]);
        return;
    }

    $query = "
        SELECT gsm.*, m.name as member_name, m.phone
        FROM `mm_grocery_shopping_master` AS gsm
        JOIN mm_members AS m
        ON m.id = gsm.member_id
        WHERE gsm.member_id = {$member_id}
        AND YEAR(gsm.date) = {$year}
        AND MONTH(gsm.date) = {$month}
        AND gsm.deleted_at IS NULL
        ORDER BY gsm.id DESC
    ";

    $result = $mm_db->query($query);

    $grocery_shoppings = [];

    if ($result->num_rows > 0) {
        
        while ($row = $result->fetch_assoc()) {

            $master_id = $row['id'];
            $gsd_query = "SELECT * FROM `mm_grocery_shopping_details` WHERE `master_id` = {$master_id}";
            $gsd_result = $mm_db->query($gsd_query);

            if ($gsd_result->num_rows > 0) {

                $grocery_shopping_details = [];

                while ($gsd_row = $gsd_result->fetch_assoc()) {
                    $grocery_shopping_details[] = $gsd_row;
                }

                $row['grocery_shopping_details'] = $grocery_shopping_details;
            }
            
            $row['date'] = date('D d M, Y', strtotime($row['date']));
            $grocery_shoppings[] = $row;
        }
    }

    echo response(['grocery_shoppings' => $grocery_shoppings]);
}

function add_grocery($req)
{
    global $mm_db;

    $member_id = $req['member_id'];
    $date = $req['date'] ? date('Y-m-d', strtotime($req['date'])) : date('Y-m-d');
    $grocery_items = $req['grocery_items'] ?? [];

    $total_amount = 0;

    if (is_array($grocery_items) && count($grocery_items)) {
        $total_amount = array_sum(array_column($grocery_items, 'amount'));
    }

    try {

        $mm_db->begin_transaction(); // Start transaction

        $mm_db->query("
            INSERT INTO mm_grocery_shopping_master (member_id, date, total_amount)
            VALUES ('$member_id', '$date', '$total_amount')
        ");

        $grocery_master_id = $mm_db->insert_id;

        if (is_array($grocery_items) && count($grocery_items)) {

            foreach ($grocery_items as $item) {

                $item_name = $item['item_name'];
                $quantity = $item['quantity'];
                $amount = $item['amount'];
                
                $mm_db->query("
                    INSERT INTO mm_grocery_shopping_details (master_id, item_name, quantity, amount)
                    VALUES ('$grocery_master_id', '$item_name', '$quantity', '$amount')
                ");
            }
        }

        /* If code reaches this point without errors then commit the data in the database */
        $mm_db->commit();

        echo response(['message' => 'Grocery shopping added successfully']);
        
    } catch (mysqli_sql_exception $e) {

        $mm_db->rollback(); // If fail to insert, it will rollback

        echo response(['message' => $e->getMessage()], 500);
    }
}

function update_grocery($req)
{
    global $mm_db;

    $grocery_id = $req['grocery_id'];
    $member_id = $req['member_id'];
    $date = $req['date'] ? date('Y-m-d', strtotime($req['date'])) : date('Y-m-d');
    $grocery_items = $req['grocery_items'] ?? [];

    $total_amount = 0;

    if (is_array($grocery_items) && count($grocery_items)) {
        $total_amount = array_sum(array_column($grocery_items, 'amount'));
    }

    try {

        $mm_db->begin_transaction(); // Start transaction

        $mm_db->query("
            UPDATE mm_grocery_shopping_master 
            SET 
                member_id = '$member_id', 
                date = '$date', 
                total_amount = '$total_amount'
            WHERE id = '$grocery_id'
        ");

        if (is_array($grocery_items) && count($grocery_items)) {

            $mm_db->query("DELETE FROM mm_grocery_shopping_details WHERE master_id = '$grocery_id'");

            foreach ($grocery_items as $item) {

                $item_name = $item['item_name'];
                $quantity = $item['quantity'];
                $amount = $item['amount'];
                
                $mm_db->query("
                    INSERT INTO mm_grocery_shopping_details (master_id, item_name, quantity, amount)
                    VALUES ('$grocery_id', '$item_name', '$quantity', '$amount')
                ");
            }
        }

        /* If code reaches this point without errors then commit the data in the database */
        $mm_db->commit();

        echo response(['message' => 'Grocery shopping updated successfully']);
        
    } catch (mysqli_sql_exception $e) {

        $mm_db->rollback(); // If fail to insert, it will rollback

        echo response(['message' => $e->getMessage()], 500);
    }
}

function delete_member($req)
{
    global $mm_db;
    
    try {

        $grocery_id = $req['grocery_id'];
        $dateTime = mm_date('Y-m-d H:i:s');

        $mm_db->query("
            UPDATE mm_grocery_shopping_master
            SET deleted_at = '$dateTime'
            WHERE id = '$grocery_id'
        ");

        echo response(['message' => 'Grocery deleted successfully']);

    } catch (mysqli_sql_exception $e) {

        echo response(['message' => $e->getMessage()], 500);
    }
}
