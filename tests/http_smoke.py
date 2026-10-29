"""Isolated HTTP smoke tests. Requires PHP + MySQL/MariaDB and Python requests.
Creates a random DB and temporary app copy; never reads app config.local.php.
"""
import os, re, shutil, subprocess, tempfile, time, socket, threading, json, secrets, datetime
from pathlib import Path
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from socketserver import StreamRequestHandler, ThreadingTCPServer
import requests
root=Path(__file__).resolve().parents[1]
php=[os.environ.get('HC_PHP_BIN','php')]
if os.environ.get('HC_PHP_INI'):php+=['-c',os.environ['HC_PHP_INI']]
env=os.environ.copy()
host=env.get('HC_TEST_DB_HOST','127.0.0.1');port=env.get('HC_TEST_DB_PORT','3306')
env.setdefault('HC_TEST_DB_USER','root');env.setdefault('HC_TEST_DB_PASS','')
name='healthconnect_http_'+secrets.token_hex(6)
env['HC_TEST_DSN']=f'mysql:host={host};port={port};dbname={name};charset=utf8mb4'
with socket.socket() as sock:sock.bind(('127.0.0.1',0));http_port=sock.getsockname()[1]
base_url=f'http://127.0.0.1:{http_port}/'
smtp_messages=[]
class SMTPHandler(StreamRequestHandler):
 def handle(self):
  self.wfile.write(b'220 local test SMTP\r\n')
  while True:
   line=self.rfile.readline()
   if not line:break
   cmd=line.upper()
   if cmd.startswith((b'EHLO',b'HELO')):self.wfile.write(b'250-localhost\r\n250 8BITMIME\r\n')
   elif cmd.startswith(b'DATA'):
    self.wfile.write(b'354 End with dot\r\n');parts=[]
    while True:
     item=self.rfile.readline()
     if item in (b'.\r\n',b''):break
     parts.append(item[1:] if item.startswith(b'..') else item)
    smtp_messages.append(b''.join(parts));self.wfile.write(b'250 Stored\r\n')
   elif cmd.startswith(b'QUIT'):self.wfile.write(b'221 Bye\r\n');break
   else:self.wfile.write(b'250 OK\r\n')
class OllamaHandler(BaseHTTPRequestHandler):
 def do_POST(self):
  payload=json.loads(self.rfile.read(int(self.headers['Content-Length'])))
  assert payload['stream'] is False and payload['messages'][0]['role']=='system'
  self.send_response(200);self.send_header('Content-Type','application/json');self.end_headers();self.wfile.write(b'{"message":{"content":"MOCK HEALTH RESPONSE"}}')
 def log_message(self,*args):pass
smtp=ThreadingTCPServer(('127.0.0.1',0),SMTPHandler);smtp.daemon_threads=True
ollama=ThreadingHTTPServer(('127.0.0.1',0),OllamaHandler)
for mock in [smtp,ollama]:threading.Thread(target=mock.serve_forever,daemon=True).start()
folder=tempfile.TemporaryDirectory(prefix='healthconnect-http-');web=Path(folder.name)/'site'
shutil.copytree(root/'website-yte-suckhoe',web,ignore=shutil.ignore_patterns('config.local.php'))
config={'db_host':host,'db_port':port,'db_name':name,'db_user':env['HC_TEST_DB_USER'],'db_pass':env['HC_TEST_DB_PASS'],'setup_key':'local-test-key-123456789','app_url':base_url.rstrip('/'),'news_sources':[], 'mail_transport':'smtp','smtp_host':'127.0.0.1','smtp_port':str(smtp.server_address[1]),'smtp_security':'none','smtp_auth':'0','mail_from':'test@example.test','ollama_url':f'http://127.0.0.1:{ollama.server_address[1]}/api/chat'}
(web/'config.local.php').write_text("<?php return json_decode("+json.dumps(json.dumps(config))+",true);",encoding='utf-8')
schema=(web/'database/schema.sql').read_text().replace('healthconnect_moi',name)
create_env=dict(env,HC_CREATE_DSN=f'mysql:host={host};port={port};charset=utf8mb4')
subprocess.run(php+['-r',"$p=new PDO(getenv('HC_CREATE_DSN'),getenv('HC_TEST_DB_USER'),getenv('HC_TEST_DB_PASS'));$p->exec(file_get_contents('php://stdin'));"],input=schema,text=True,env=create_env,check=True)
log=open(Path(folder.name)/'php.log','w')
server=subprocess.Popen(php+['-S',f'127.0.0.1:{http_port}','-t',str(web),str(web/'tools/router.php')],env=env,stdout=log,stderr=log)
checks=0
url=base_url
def ok(value,label):
 global checks
 assert value,label
 checks+=1
 print('PASS HTTP:',label)
def get(s,path):return s.get(url+path,timeout=10)
def post(s,path,data):
 page=get(s,path)
 token=re.search(r'name="csrf" value="([^"]+)"',page.text)
 assert token,(path,page.text[:200])
 return s.post(url+path,data=dict(data,csrf=token[1]),timeout=10,allow_redirects=False)
try:
 for i in range(60):
  try:requests.get(url,timeout=1);break
  except requests.RequestException:time.sleep(.1)
 admin=requests.Session(); a=requests.Session(); b=requests.Session()
 page=get(admin,'setup.php');ok(page.status_code==200 and 'name="setup_key"' in page.text,'setup form renders')
 result=post(admin,'setup.php',{'setup_key':'local-test-key-123456789','email':'admin@example.test','full_name':'Admin Test','password':'TestPass123!','seed':'on'})
 ok(result.status_code==302,'setup creates admin and seed')
 ok(get(admin,'setup.php').status_code==403,'setup locks after admin')
 ok(post(admin,'dang-nhap.php?role=admin',{'role':'admin','email':'admin@example.test','password':'TestPass123!'}).status_code==302,'admin login')
 for s,email in [(a,'a@example.test'),(b,'b@example.test')]:
  ok(post(s,'dang-ky.php',{'email':email,'full_name':'Demo User','password':'TestPass123!','confirm':'TestPass123!'}).status_code==302,'register '+email)
 ok(get(a,'admin/lich-kham.php').status_code==403,'user blocked from admin')
 day=datetime.datetime.now(datetime.timezone(datetime.timedelta(hours=7))).date()+datetime.timedelta(days=2)
 while day.weekday()==6:day+=datetime.timedelta(days=1)
 data={'doctor_id':'1','date':str(day),'time':'09:00','patient_name':'Demo Patient','phone':'0900000000','specialty':'Nội khoa','reason':'Demo'}
 ok(a.post(url+'dat-lich.php',data=data).status_code==403,'booking requires CSRF')
 ok(post(a,'dat-lich.php',data).status_code==302,'user books appointment')
 page=get(a,'dat-lich.php'); aid=re.search(r'name="id" value="(\d+)"',page.text)[1]
 ok(post(b,'dat-lich.php',{'act':'cancel','id':aid}).status_code==200,'other user cannot cancel')
 page=get(a,'dat-lich.php');ok('Chờ xác nhận' in page.text,'appointment remains pending')
 ok(post(admin,'admin/lich-kham.php?date='+str(day),{'act':'confirm:'+aid}).status_code==302,'admin confirms')
 ok('Đã xác nhận' in get(a,'dat-lich.php').text,'user sees confirmed status')
 ok(post(admin,'admin/lich-kham.php?date='+str(day),{'act':'done:'+aid}).status_code==200,'reject early completion HTTP')
 ok(post(a,'dat-lich.php',{'act':'cancel','id':aid}).status_code==302,'owner cancels')
 ok(post(admin,'admin/lich-kham.php?date='+str(day),{'act':'reopen:'+aid}).status_code==302,'admin reopens')
 ok(get(admin,'lich-kham.php').url.endswith('/admin/lich-kham.php'),'legacy calendar redirects correctly')
 ok(get(requests.Session(),'admin/dang-nhap.php').url.endswith('dang-nhap.php?role=admin'),'legacy login redirects correctly')
 for path in ['config.local.php','database/schema.sql','includes/bootstrap.php','tools/doctor.php']:
  ok(get(a,path).status_code==403,'protected '+path)
 # Health diary, export and cross-user boundaries.
 diary={'act':'diary','log_date':str(datetime.date.today()),'sleep_hours':'7.5','water_ml':'1800','exercise_minutes':'35','note':'=HYPERLINK("bad")'}
 ok(post(a,'suc-khoe.php',diary).status_code==302,'save health diary')
 ok('1800' in get(a,'suc-khoe.php').text,'diary visible to owner')
 ok('HYPERLINK' not in get(b,'suc-khoe.php').text,'diary not visible to another user')
 ok("'=HYPERLINK" in get(a,'xuat-du-lieu.php?type=diary').text,'CSV formula escaped')
 ok('HYPERLINK' not in get(b,'xuat-du-lieu.php?type=diary').text,'CSV scoped to owner')
 ok(post(a,'suc-khoe.php',{'act':'goal','weight_kg':'65.5'}).status_code==302,'save weight goal')
 ok(post(a,'bmi.php',{'height':'170','weight':'67.5','note':'test'}).status_code==302,'save decimal BMI')
 ok(get(b,'phieu-hen.php?id='+aid).status_code==404,'receipt private')
 ok('BEGIN:VCALENDAR' in get(a,'phieu-hen.php?id='+aid+'&calendar=1').text,'calendar export')
 ok(get(admin,'admin/bac-si.php').status_code==200,'doctor management renders')
 ok(post(admin,'admin/bac-si.php',{'act':'dayoff','id':'1','off_date':str(day)}).status_code==200,'cannot block a day with existing bookings')
 for path in ['index.php','suc-khoe.php','bac-si.php','tin-tuc.php?source=local','tin-tuc.php?source=rss','huong-dan-app.php','quyen-rieng-tu.php']:
  ok(get(a,path).status_code==200,'page '+path)
 manifest=get(a,'manifest.webmanifest').json();ok(manifest['display']=='standalone','PWA manifest')
 sw=get(a,'sw.js').text;ok('PHP, APIs, uploads and private data use network only' in sw,'SW privacy rule present')
 ok(get(a,'assets/vendor/bootstrap.min.css').status_code==200,'local Bootstrap available')
 # Existing administration workflows remain usable after the app-shell changes.
 import csv, io
 users=list(csv.DictReader(io.StringIO(get(admin,'admin/nguoi-dung.php?export=1').text.lstrip('\ufeff'))))
 aid_user=next(row['ID'] for row in users if row['Email']=='a@example.test')
 result=post(admin,'admin/nguoi-dung.php',{'id':aid_user,'do':'delete'})
 ok(result.status_code==200 and 'Tài khoản còn lịch khám' in result.text,'cannot delete account with appointment history')
 ok(get(a,'suc-khoe.php').url.endswith('suc-khoe.php'),'account retained after rejected delete')
 article={'title':'HTTP regression article','slug':'http-regression-article','summary':'Regression summary','content':'<p>Safe content</p><script>alert(1)</script>','category_id':'1','status':'draft','thumbnail_url':''}
 ok(post(admin,'admin/bai-viet.php',article).status_code==302,'admin creates draft article')
 page=get(admin,'admin/bai-viet.php?q=HTTP+regression+article');article_id=re.search(r'\?edit=(\d+)#editor',page.text)[1]
 ok(get(a,'chi-tiet-bai-viet.php?id='+article_id).status_code==404,'draft hidden from public')
 ok(post(admin,'admin/bai-viet.php',{'act':'toggle:'+article_id}).status_code==302,'publish article')
 page=get(a,'chi-tiet-bai-viet.php?id='+article_id)
 ok(page.status_code==200 and '<script>alert(1)</script>' not in page.text,'published article sanitized')
 ok('HTTP regression article' in get(a,'tin-tuc.php?q=HTTP+regression+article').text,'search finds published article')
 # Upload a valid image, then edit with its stored relative path.
 page=get(admin,'admin/bai-viet.php?edit='+article_id);csrf=re.search(r'name="csrf" value="([^"]+)"',page.text)[1]
 image=(root/'website-yte-suckhoe/assets/icons/icon-192.png').read_bytes()
 data=dict(article,id=article_id,status='published',csrf=csrf)
 result=admin.post(url+'admin/bai-viet.php',data=data,files={'thumbnail':('test.png',image,'image/png')},allow_redirects=False,timeout=10)
 ok(result.status_code==302,'article image upload')
 page=get(admin,'admin/bai-viet.php?edit='+article_id);stored=re.search(r'uploads/[a-f0-9]{32}\.png',page.text)[0]
 ok(post(admin,'admin/bai-viet.php',dict(article,id=article_id,status='published',thumbnail_url=stored)).status_code==302,'edit article retains uploaded relative image')
 result=post(admin,'admin/danh-muc.php',{'action':'delete','id':'1'})
 ok(result.status_code==200 and 'Không thể xóa danh mục đang có bài viết' in result.text,'category with articles cannot be deleted')
 # Mocked Ollama: production PHP request, real persistence, no external model call.
 page=get(a,'suc-khoe.php');csrf=re.search(r'name="csrf" value="([^"]+)"',page.text)[1]
 reply=a.post(url+'api/chat.php',json={'message':'Demo health question'},headers={'X-CSRF-Token':csrf},timeout=10)
 ok(reply.status_code==200 and 'MOCK HEALTH RESPONSE' in reply.json()['answer'],'Ollama API contract and chat persistence')
 sid=reply.json()['session_id'];ok('MOCK HEALTH RESPONSE' in get(a,'lich-su-chat.php?id='+str(sid)).text,'chat history saved')
 # Reset password through local SMTP capture; no real email leaves this test.
 ok(post(a,'quen-mat-khau.php',{'email':'a@example.test'}).status_code==200,'reset request HTTP')
 from email.parser import BytesParser
 from email.policy import default
 message=BytesParser(policy=default).parsebytes(smtp_messages[-1]);body_text=message.get_body(preferencelist=('plain',)).get_content() if message.is_multipart() else message.get_content()
 match=re.search(r'token=([a-f0-9]{64})',body_text);ok(bool(match),'SMTP reset link delivered to test capture')
 token=match[1];reset=post(a,'dat-lai-mat-khau.php?token='+token,{'token':token,'password':'ChangedPass123!','confirm':'ChangedPass123!'})
 ok(reset.status_code==302,'reset link changes password')
 ok(get(a,'suc-khoe.php').url.endswith('dang-nhap.php'),'password reset invalidates session')
 ok(post(a,'dat-lai-mat-khau.php?token='+token,{'token':token,'password':'ChangedPass123!','confirm':'ChangedPass123!'}).status_code==200,'reset token cannot be reused')
 ok(post(a,'dang-nhap.php',{'email':'a@example.test','password':'ChangedPass123!','role':'user'}).status_code==302,'login with changed password')
 # Restore fixture password for optional browser suite.
 restore_code="$p=new PDO(getenv('HC_TEST_DSN'),getenv('HC_TEST_DB_USER'),getenv('HC_TEST_DB_PASS'));$s=$p->prepare(\"UPDATE users SET password_hash=? WHERE email='a@example.test'\");$s->execute([password_hash('TestPass123!',PASSWORD_DEFAULT)]);"
 subprocess.run(php+['-r',restore_code],env=env,check=True)
 print(f'{checks} HTTP checks passed')
 # Optional browser smoke uses same process/network namespace as PHP server.
 if os.environ.get('HC_BROWSER_TEST')=='1':
  subprocess.run(['node',os.environ['HC_BROWSER_SCRIPT']],env=dict(env,HC_TEST_BASE_URL=base_url.rstrip('/')),check=True)

finally:
 server.terminate();server.wait(timeout=5)
 for mock in [smtp,ollama]:mock.shutdown();mock.server_close()
 log.close()
 subprocess.run(php+['-r',"$p=new PDO(getenv('HC_CREATE_DSN'),getenv('HC_TEST_DB_USER'),getenv('HC_TEST_DB_PASS'));$p->exec('DROP DATABASE `"+name+"`');"],env=create_env,check=True)
 folder.cleanup()
