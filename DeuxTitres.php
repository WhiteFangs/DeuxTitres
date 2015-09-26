<?php

require_once('./TwitterAPIExchange.php');
header('Content-Type: text/html; charset=utf-8');

function getCURLOutput($url, $withScript){
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
  if(!$withScript)
    $output = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $output);
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
  $categoryPage = getCURLOutput($baseUrl . '/news/section?ned=fr&topic=' . $category, true);
  $categoryXpath = getDOMXPath($categoryPage);
  $topicsName = $categoryXpath->query('//*[@class="topic"]/a/text()');
  $topicsNodes = $categoryXpath->query('//*[@class="topic"]/a');
  foreach($topicsNodes as $key => $node) {
    $topic = (object) array('name' => $topicsName->item($key)->nodeValue, 'url' => $baseUrl . $node->getAttribute("href"));
    $topics[] = $topic;
  }
  return $topics;
}

function getHeadline($topic){
  $headlines = array();
  $topicPage = getCURLOutput($topic->url, true);
  $topicPage = str_replace("<b>", "", $topicPage);
  $topicPage = str_replace("</b>", "", $topicPage);
  $topicXpath = getDOMXPath($topicPage);
  $headlinesNodes = $topicXpath->query('//*[@class="titletext"]/text()');
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
  $categoryCodes = array('w', 'n', 'b', 'tc', 'e', 's');
  $firstIdx = array_rand($categoryCodes);
  $firstCat = $categoryCodes[$firstIdx];
  unset($categoryCodes[$firstIdx]);
  $categoryCodes = array_values($categoryCodes);
  $topics = getTopics($firstCat);
  if(count($topics) > 0){
    $firstTopic = $topics[array_rand($topics)];
    $headline = getHeadline($firstTopic);
    echo $headline;
    echo '<br>';
    echo $firstTopic->name;
    echo '<br>';
    if($headline != null && strstr($headline, $firstTopic->name) !== false){
      $secondCat = $categoryCodes[array_rand($categoryCodes)];
      $newTopics = getTopics($secondCat);
      if(count($newTopics) > 0){
        $secondTopic = $newTopics[array_rand($newTopics)];
        echo $secondTopic->name;
        echo '<br>';
        $newHeadline = str_replace($firstTopic->name, $secondTopic->name, $headline);
        echo $newHeadline;
        if(strlen($newHeadline) < 141){

          /** Set access tokens here - see: https://apps.twitter.com/ **/
          $APIsettings = array(
              'oauth_access_token' => "3783512423-K58EYTbI9RxQZZxPLdYnTqVRaWgMkOFa2EZC9WB",
              'oauth_access_token_secret' => "V9kxGs4kaTjyJTomWbyOlPJraUKTHZkYIUzboe428HNK8",
              'consumer_key' => "btCcRgpJaWzZFbung2doBIzvl",
              'consumer_secret' => "zROhHpVoQHmsp9Os9I5t0DsD931IhhZFivV9wtiBwgvjUshqbs"
          );

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
