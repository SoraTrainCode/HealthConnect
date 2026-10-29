<?php
require __DIR__.'/includes/bootstrap.php';require __DIR__.'/includes/layout.php';require __DIR__.'/includes/news_sync.php';
$q=mb_substr(trim((string)($_GET['q']??'')),0,200);$cat=max(0,(int)($_GET['category']??0));$page=max(1,(int)($_GET['page']??1));$source=($_GET['source']??'local')==='rss'?'rss':'local';$stale=false;
if($source==='rss') {
 $cache=news_sync();$stale=$cache['stale']??false;$all=$cache['items']??[];
 if($q!==''){$needle=mb_strtolower($q);$all=array_values(array_filter($all,fn($a)=>str_contains(mb_strtolower($a['title'].' '.$a['summary']),$needle)));}
 $count=count($all);$pages=max(1,(int)ceil($count/9));$page=min($page,$pages);$articles=array_slice($all,($page-1)*9,9);
} else {
 $where="a.status='published'";$params=[];
 if($q!==''){$where.=' AND (a.title LIKE ? OR a.content LIKE ?)';$params[]='%'.$q.'%';$params[]='%'.$q.'%';}
 if($cat){$where.=' AND a.category_id=?';$params[]=$cat;}
 $count=(int)query('SELECT COUNT(*) FROM articles a WHERE '.$where,$params)->fetchColumn();$pages=max(1,(int)ceil($count/9));$page=min($page,$pages);
 $articles=query('SELECT a.*,c.name category_name FROM articles a JOIN categories c ON c.id=a.category_id WHERE '.$where.' ORDER BY a.created_at DESC,a.id DESC LIMIT 9 OFFSET '.(($page-1)*9),$params)->fetchAll();
}
head('Góc kiến thức sức khỏe');
?>
<div class="d-flex gap-2 mb-4"><a class="btn <?php echo $source==='local'?'btn-brand':'btn-outline-brand'; ?>" href="?source=local">Bài viết HealthConnect</a><a class="btn <?php echo $source==='rss'?'btn-brand':'btn-outline-brand'; ?>" href="?source=rss">Tin từ nguồn RSS</a></div>
<?php if($source==='rss'): ?><p class="text-muted">Tin được lấy từ các nguồn cấu hình, có thể bằng tiếng Anh. Bấm tiêu đề để đọc tại website nguồn.</p><?php if($stale): ?><div class="alert alert-info">Chưa lấy được tin mới. Các tin đã lưu trước đó vẫn được hiển thị nếu có.</div><?php endif; endif; ?>
<form method="get" class="row g-2 mb-4"><input type="hidden" name="source" value="<?php echo e($source); ?>"><div class="col-md-6"><label for="q" class="form-label">Tìm bài viết</label><input class="form-control" name="q" id="q" value="<?php echo e($q); ?>" placeholder="Nhập từ khóa..."></div><?php if($source==='local'): ?><div class="col-md-4"><label for="category" class="form-label">Danh mục</label><select class="form-select" name="category" id="category"><option value="0">Tất cả danh mục</option><?php foreach(query('SELECT * FROM categories ORDER BY name') as $c): ?><option value="<?php echo (int)$c['id']; ?>" <?php echo $cat===(int)$c['id']?'selected':''; ?>><?php echo e($c['name']); ?></option><?php endforeach; ?></select></div><?php endif; ?><div class="col-md-2 d-flex align-items-center"><button class="btn btn-brand">Tìm kiếm</button></div></form><p class="small text-muted"><?php echo $count; ?> kết quả · Trang <?php echo $page.' / '.$pages; ?></p><?php cards($articles); ?>
<nav class="mt-4 d-flex gap-2 flex-wrap" aria-label="Trang tin tức"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a class="btn <?php echo $i===$page?'btn-brand':'btn-outline-brand'; ?>" href="?<?php echo e(http_build_query(['q'=>$q,'category'=>$cat,'source'=>$source,'page'=>$i])); ?>" <?php echo $i===$page?'aria-current="page"':''; ?>><?php echo $i; ?></a><?php endfor; ?></nav><?php foot(); ?>
