<?php
/**
 * VK Wall to RSS Script
 * Author: kadukmm
 * ICQ: 46-466-46
 * Skype: kadukmm
 * Email: nikolay.kaduk@gmail.com
 */
$owner_id = $argv[1];


header("Content-type: text/plain");
include("FeedItem.php");
include("FeedWriter.php");
//error_reporting(0);
$feed = new FeedWriter(RSS2);
$feed->setTitle('VK.COM');
$feed->setLink('http://vk.com/');
$feed->setDescription('vk2rss');

$url = "http://api.vk.com/method/wall.get?owner_id=$owner_id&count=90";
$response = file_get_contents($url);
$wall = json_decode($response);
for ($i = 1; $i<=count($wall->response)-1; $i++) {
    $wall->response[$i]->text = preg_replace("#&mdash;#", '', $wall->response[$i]->text);
    $wall->response[$i]->text = html_entity_decode($wall->response[$i]->text, null, 'utf-8');
    $newItem = $feed->createNewItem();
    $title = explode('<br>',$wall->response[$i]->text);
    $title = $title[0];
    $title = (mb_strlen($title, 'utf-8')<=100) ? $title : mb_substr($title,0,100,'utf-8').'...';
    //echo $wall->response[$i]->id."\t".$title."\t".$wall->response[$i]->text."\n";
    $newItem->setTitle($title);
    $newItem->setLink("http://vk.com/wall{$owner_id}_{$wall->response[$i]->id}");
    $newItem->setDate($wall->response[$i]->date);
    $description = $wall->response[$i]->text;
    
    if (isset($wall->response[$i]->attachments)) {
        foreach ($wall->response[$i]->attachments as $attachment) {
            switch ($attachment->type) {
                case 'photo': {
                    $description .= "<br><img src='{$attachment->photo->src_big}'/>";
                    break;
                }
                case 'audio': {
                    $description .= "<br><a href='http://vk.com/wall{$owner_id}_{$wall->response[$i]->id}'>{$attachment->audio->performer} &ndash; {$attachment->audio->title}</a>";
                    break;    
                }
                case 'doc': {
                    $description .= "<br><a href='{$attachment->doc->url}'>{$attachment->doc->title}</a>";
                    break;
                }
                case 'link': {
                    $description .= "<br><a href='{$attachment->link->url}'>{$attachment->link->title}</a>";
                    break;
                }
                case 'video': {
                    $description .= "<br><a href='http://vk.com/video{$attachment->video->owner_id}_{$attachment->video->vid}'><img src='{$attachment->video->image_big}'/></a>";
                    break;
                }
            }
        }
    }
    
    $newItem->setDescription($description);
    $newItem->addElement('guid', $wall->response[$i]->id);
    $feed->addItem($newItem);
}
$feed->genarateFeed();
?>
