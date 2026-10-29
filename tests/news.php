<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/website-yte-suckhoe/includes/news_sync.php';
function check_news(bool $ok,string $label): void {if(!$ok)throw new RuntimeException($label);}
$rss='<rss xmlns:media="http://search.yahoo.com/mrss/"><channel><item><guid>one</guid><title>Demo &amp; news</title><link>https://example.org/news</link><description><![CDATA[<b>Summary</b>]]></description><media:thumbnail url="https://example.org/a.png"/><pubDate>Mon, 26 Oct 2026 10:00:00 GMT</pubDate></item><item><title>Unsafe URL</title><link>javascript:alert(1)</link></item></channel></rss>';
$items=array_values(news_parse($rss,['name'=>'Demo RSS']));
check_news(count($items)===1,'Unsafe item URL filtered');
check_news($items[0]['title']==='Demo & news','RSS title decoding');
check_news($items[0]['summary']==='Summary','Summary strips markup');
check_news($items[0]['thumbnail_url']==='https://example.org/a.png','Media namespace image');
$atom='<feed xmlns="http://www.w3.org/2005/Atom"><entry><id>two</id><title>Atom news</title><link href="https://example.org/atom"/><summary>Example</summary><updated>2026-10-26T10:00:00Z</updated></entry></feed>';
$items=array_values(news_parse($atom,['name'=>'Demo Atom']));
check_news(count($items)===1 && $items[0]['source_url']==='https://example.org/atom','Atom entry parsing');
check_news(news_parse('not XML',[])===[],'Malformed XML handled');
echo "6 RSS/Atom checks passed\n";
