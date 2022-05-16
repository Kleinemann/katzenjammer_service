<?php
require_once("config.php");
require_once("db_manager.php");

function createQuest($data)
{
    //print_r($data);
    $sql = "SELECT * FROM quests WHERE building_id = ".$data->building_id.";";
    $quests = DB_Manager::getTable($sql);

    $quest = $quests->data[mt_rand(0, $quests->count-1)];

    $quest['start'] = date_timestamp_get(date_create());
    $quest['position'] = Array();
    $quest['position']['lat'] = $data->position->lat - QUEST_RANGE + mt_rand(1,100) * QUEST_RANGE / 50;
    $quest['position']['lon'] = $data->position->lon - QUEST_RANGE + mt_rand(1,100) * QUEST_RANGE / 50;
    //print_r(($quest));

    $userBuildingId = $data->id;
    $questId = $quest['id'];
    $start = $quest['start'];
    $pos = json_encode($quest['position']);
    $sqlInsert = "INSERT INTO building_quest (user_building_id, quest_id, position, start) VALUES ($userBuildingId, $questId, '$pos', $start);";

    $insert = DB_Manager::executeSql($sqlInsert);
    //print_r($sqlInsert);
    return $quest;
}

?>