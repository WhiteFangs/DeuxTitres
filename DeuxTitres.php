<?php

include('twitterCredentials.php');
require_once('TwitterAPIExchange.php');
header('Content-Type: text/html; charset=utf-8');

/** Set access tokens here - see: https://apps.twitter.com/ **/
$APIsettings = array(
    'oauth_access_token' => $oauthToken,
    'oauth_access_token_secret' => $oauthTokenSecret,
    'consumer_key' => $consumerKey,
    'consumer_secret' => $consumerSecret
);

function getCURLOutput($url){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_AUTOREFERER, true);
  curl_setopt($ch, CURLOPT_VERBOSE, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');
  $output = curl_exec($ch);
  curl_close($ch);
  return $output;
}

function getDOMXPath($page){
  $dom = new DOMDocument;
  $page = mb_convert_encoding($page, 'HTML-ENTITIES', "UTF-8");
  @$dom->loadHTML($page);
  $xpath = new DOMXPath($dom);
  return $xpath;
}

function getTopics ($category) {
  $baseUrl = "http://news.google.fr";
  $topics = array();
  $categoryPage = getCURLOutput($baseUrl . '/news/headlines/section/topic/' . $category . '.fr_fr/?ned=fr&hl=fr');
  $categoryXpath = getDOMXPath($categoryPage);
  $topicsName = $categoryXpath->query('//*[@class="iuPoYd"]/c-wiz/a/*[@class="Q3vG6d kzAuJ"]/text()');
  $topicsNodes = $categoryXpath->query('//*[@class="iuPoYd"]/c-wiz/a[@class="J3nBBd ME7ew"]');
  foreach($topicsNodes as $key => $node) {
    $topic = (object) array('name' => $topicsName->item($key)->nodeValue, 'url' => $baseUrl . $node->getAttribute("href"));
    $topics[] = $topic;
  }
  return $topics;
}

function getHeadline($topic){
  $headlines = array();
  $topicPage = getCURLOutput($topic->url);
  $topicPage = str_replace("<b>", "", $topicPage);
  $topicPage = str_replace("</b>", "", $topicPage);
  $topicXpath = getDOMXPath($topicPage);
  $headlinesNodes = $topicXpath->query('//a[@class="nuEeue hzdq5d ME7ew"]/text()');
  for($i = 0; $i < $headlinesNodes->length; $i++){
    $titletext = $headlinesNodes->item($i)->nodeValue;
    if(strstr($titletext, " ...") === false && strstr($titletext, $topic->name) !== false){
      $headlines[] = $titletext;
    }
  }
  $index = array_rand($headlines);
  $headline = $headlines[$index];
  return $headline;
}

function tweet(){
  global $APIsettings;
  $categoryCodes = array('WORLD', 'NATION', 'BUSINESS', 'SCITECH', 'HEALTH', 'ENTERTAINMENT', 'SPORTS');
  $firstIdx = array_rand($categoryCodes);
  $firstCat = $categoryCodes[$firstIdx];
  unset($categoryCodes[$firstIdx]);
  $categoryCodes = array_values($categoryCodes);
  $topics = getTopics($firstCat);
  if(count($topics) > 0){
    $firstTopic = $topics[array_rand($topics)];
    $headline = getHeadline($firstTopic);
    if($headline != null && strstr($headline, $firstTopic->name) !== false){
      $secondCat = $categoryCodes[array_rand($categoryCodes)];
      $newTopics = getTopics($secondCat);
      if(count($newTopics) > 0){
        $secondTopic = $newTopics[array_rand($newTopics)];
        $newHeadline = str_replace($firstTopic->name, $secondTopic->name, $headline);
        if(strlen($newHeadline) < 141){

          // Post the tweet
          $postfields = array(
              'status' =>  $newHeadline);
          $url = "https://api.twitter.com/1.1/statuses/update.json";
          $requestMethod = "POST";
          $twitter = new TwitterAPIExchange($APIsettings);
          echo $twitter->buildOauth($url, $requestMethod)
                        ->setPostfields($postfields)
                        ->performRequest();
        }else{
          tweet();
        }
      }else{
        tweet();
      }
    }else{
      tweet();
    }
  }else{
    tweet();
  }
}

tweet();

?>
