<?php
require_once 'util/CacheQueueAPI.class.php';

    // Configure PHP
    set_time_limit(0);
    ini_set('display_errors', 'on');
    
    // Include essential files
    require_once 'lib/bot.php';
    
    // Create a new instance of the bot
    $bot = bot::getInstance();

    // Configure the bot
    $bot->setServer('irc.rizon.net');
    $bot->setPort(6667);
    
    // Bot identification vars
    $bot->setNick('calcTest');
    $bot->setName('calcTest');
    
    // Default chanels to join
    $bot->setChannel(array('#lother')); // or just a single channel : $bot->setChannel('#channel');
    
    // Load some plugins
    $bot->loadPlugin('base');
    #$bot->loadPlugin('move');
    $bot->loadPlugin('erep_user');
    
    // Establish a connection
    $bot->connect();
?>
