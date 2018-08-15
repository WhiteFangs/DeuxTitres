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

function getTopics () {
  $baseUrl = "http://news.google.fr";
  $topics = array();
  $homePage = getCURLOutput('https://news.google.com/?hl=fr&gl=FR&ceid=FR%3Afr'); // Google News FR Home page
  $homeXpath = getDOMXPath($homePage);
  $topicsNodes = $homeXpath->query('//a[@class="boy4he"]');
  foreach($topicsNodes as $key => $node) {
    $topic = (object) array('name' => $node->getAttribute("aria-label"), 'url' => $baseUrl . substr($node->getAttribute("href"), 1));
    $topics[] = $topic;
  }
  return $topics;
}

function getHeadline($topic){
  $headlines = array();
  $topicPage = getCURLOutput($topic->url);
  $topicPage = str_replace("<span>", "", $topicPage);
  $topicPage = str_replace("</span>", "", $topicPage);
  $topicXpath = getDOMXPath($topicPage);
  $headlinesNodes = $topicXpath->query('//a[@class="ipQwMb Q7tWef"]/text()');
  for($i = 0; $i < $headlinesNodes->length; $i++){
    $titletext = $headlinesNodes->item($i)->nodeValue;
    if(strstr($titletext, " ...") === false && strstr($titletext, $topic->name) !== false){
      $headlines[] = $titletext;
    }
  }
  $headline = $headlines[array_rand($headlines)];
  return $headline;
}

function tweet(){
  global $APIsettings;
  $topics = getTopics();
  $firstIdx = array_rand($topics);
  $firstTopic = $topics[$firstIdx];
  unset($topics[$firstIdx]);
  $topics = array_values($topics);
  $headline = getHeadline($firstTopic);
  if($headline != null && strstr($headline, $firstTopic->name) !== false){
    $secondTopic = $topics[array_rand($topics)];
    $newHeadline = str_replace($firstTopic->name, $secondTopic->name, $headline);
    if(strlen($newHeadline) < 281){
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
}

tweet();

?>
