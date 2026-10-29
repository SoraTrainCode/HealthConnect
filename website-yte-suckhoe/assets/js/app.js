(()=>{
 const toggle=document.getElementById('menu-toggle'),nav=document.getElementById('main-nav');
 toggle?.addEventListener('click',()=>{const open=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',String(open));});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'){nav?.classList.remove('open');toggle?.setAttribute('aria-expanded','false');}});
 const status=document.getElementById('connection-state');const update=()=>{if(status)status.hidden=navigator.onLine;};window.addEventListener('online',update);window.addEventListener('offline',update);update();
 const base=new URL(window.HC_BASE||'./',location.href);
 if('serviceWorker'in navigator&&window.isSecureContext){navigator.serviceWorker.register(new URL('sw.js',base)).catch(()=>{});}
 let installPrompt;const button=document.getElementById('install-app');
 window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();installPrompt=event;if(button)button.hidden=false;});
 button?.addEventListener('click',async()=>{if(!installPrompt)return;await installPrompt.prompt();await installPrompt.userChoice;installPrompt=null;button.hidden=true;});
 window.addEventListener('appinstalled',()=>{installPrompt=null;if(button)button.hidden=true;});
})();
