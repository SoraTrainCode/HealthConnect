<?php
declare(strict_types=1);

function news_cache_path(): string
{
    $sources = $GLOBALS["config"]["news_sources"] ?? [];
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "healthconnect-rss-" . hash("sha256", dirname(__DIR__) . "\0" . json_encode($sources, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) . ".json";
}

function news_cache_read(): array
{
    $path = news_cache_path();
    if (!is_file($path) || filesize($path) > 2_000_000) return [];
    $raw = @file_get_contents($path);
    $cache = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($cache) && isset($cache["items"]) && is_array($cache["items"]) ? $cache : [];
}

function news_http_get(string $url): ?string
{
    if (!filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== "https") return null;
    if (!extension_loaded('curl')) return null;
    $body = '';
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>6, CURLOPT_USERAGENT=>"HealthConnect RSS reader/2.0", CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS, CURLOPT_HTTPHEADER=>["Accept: application/rss+xml, application/atom+xml, application/xml, text/xml"], CURLOPT_WRITEFUNCTION=>function($handle,$chunk) use (&$body) {if(strlen($body)+strlen($chunk)>2_000_000)return 0;$body.=$chunk;return strlen($chunk);}]);
    $ok=curl_exec($ch);
    $status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
    $type=strtolower((string)curl_getinfo($ch,CURLINFO_CONTENT_TYPE));
    curl_close($ch);
    if (!$ok || $status<200 || $status>=300 || (!str_contains($type,'xml')&&!str_contains($type,'rss'))) return null;
    return $body;
}

function news_text($value, int $limit=1200): string
{
    $text = trim(preg_replace("/\\s+/u", " ", html_entity_decode(strip_tags((string)$value), ENT_QUOTES|ENT_HTML5, "UTF-8")) ?? "");
    return mb_substr($text, 0, $limit, "UTF-8");
}

function news_valid_link(string $url): bool
{
    return (bool)filter_var($url,FILTER_VALIDATE_URL) && in_array(parse_url($url,PHP_URL_SCHEME),["http","https"],true);
}

function news_sync(): array
{
    $cache = news_cache_read();
    $ttl = max(1800,min(3600,(int)cfg("news_cache_ttl")));
    if ($cache && time()-(int)($cache["fetched_at"]??0)<$ttl) return $cache;
    $lock=@fopen(news_cache_path().'.lock','c');
    if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) {if($lock)fclose($lock);return $cache?:['items'=>[],'stale'=>true];}
    try {
    $cache=news_cache_read();
    if ($cache && time()-(int)($cache['fetched_at']??0)<$ttl) return $cache;
    $items=[];
    $sources = is_array($GLOBALS["config"]["news_sources"] ?? null) ? $GLOBALS["config"]["news_sources"] : [];
    foreach (array_slice($sources,0,3) as $source) {
        $xml=news_http_get((string)($source["url"]??""));
        if ($xml===null) continue;
        $items=array_replace($items,news_parse($xml,$source));
    }
    if ($items) {
        $items=array_values($items);usort($items,fn($a,$b)=>strcmp($b["created_at"],$a["created_at"]));
        $cache=["fetched_at"=>time(),"stale"=>false,"items"=>array_slice($items,0,100)];
        @file_put_contents(news_cache_path(),json_encode($cache,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);
    } else {
        // Preserve the last good items and back off for one TTL after a failed refresh.
        $cache=["fetched_at"=>time(),"stale"=>true,"items"=>$cache["items"]??[]];
        @file_put_contents(news_cache_path(),json_encode($cache,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);
    }
    return $cache;
    } finally {flock($lock,LOCK_UN);fclose($lock);}
}

function news_listing_items(): array
{
    try {return news_sync()["items"]??[];} catch(Throwable $error) {error_log("HealthConnect RSS sync: ".$error->getMessage());return news_cache_read()["items"]??[];}
}

function news_parse(string $xml, array $source): array
{
    if(!function_exists('simplexml_load_string')) return [];
    $items=[];
        $feed=@simplexml_load_string($xml,"SimpleXMLElement",LIBXML_NONET|LIBXML_NOCDATA);
        if (!$feed) return [];
        $entries=isset($feed->channel->item)?$feed->channel->item:($feed->entry??[]);
        foreach ($entries as $entry) {
            $title=news_text($entry->title??"",255);
            $summary=news_text($entry->description??$entry->summary??$entry->content??"",450);
            $link=trim((string)($entry->link??""));
            if ($link==="" && isset($entry->link["href"])) $link=(string)$entry->link["href"];
            if ($title==="" || !news_valid_link($link)) continue;
            $published=strtotime((string)($entry->pubDate??$entry->published??$entry->updated??""));
            $image="";
            $media=$entry->children('http://search.yahoo.com/mrss/');
            foreach ([$entry->enclosure,$media->content,$media->thumbnail] as $node) {
                $candidate=(string)($node->attributes()['url']??'');
                if ($candidate!=="" && news_valid_link($candidate) && preg_match('/\\.(jpg|jpeg|png|webp)(?:[?#]|$)/i',$candidate)) {$image=$candidate;break;}
            }
            $id=trim((string)($entry->guid??$entry->id??$link));
            $key=hash("sha256",$id!==""?$id:$link);
            $publishedAt = date("Y-m-d H:i:s", $published ?: time());
            $items[$key]=["id"=>$key,"title"=>$title,"summary"=>$summary,"thumbnail_url"=>$image,"category"=>"Sức khỏe cộng đồng","category_name"=>"Sức khỏe cộng đồng","published_at"=>$publishedAt,"created_at"=>$publishedAt,"source_name"=>news_text($source["name"]??"Nguồn tin",120),"source_url"=>$link];
        }
    return $items;
}
