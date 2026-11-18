(function(){
 var mq=window.matchMedia('(prefers-color-scheme: dark)'),root=document.documentElement;
 function eff(){return root.getAttribute('data-theme')||(mq.matches?'dark':'light')}
 function t(){root.setAttribute('data-bs-theme',eff())}
 t();mq.addEventListener&&mq.addEventListener('change',t);
 var tb=document.getElementById('themeBtn');
 if(tb)tb.addEventListener('click',function(){
  var n=eff()==='dark'?'light':'dark';
  root.setAttribute('data-theme',n);
  try{localStorage.setItem('adid_theme',n)}catch(e){}
  t();
 });

 /* Filtre des actualités (les cartes sont générées par PHP depuis la base) */
 var cards=document.querySelectorAll('#grid [data-type]'),empty=document.getElementById('empty');
 document.querySelectorAll('.filters .btn').forEach(function(b){
  b.addEventListener('click',function(){
   document.querySelectorAll('.filters .btn').forEach(function(x){x.classList.remove('active')});
   b.classList.add('active');
   var f=b.dataset.f,n=0;
   cards.forEach(function(c){var show=f==='all'||c.dataset.type===f;c.hidden=!show;if(show)n++});
   if(empty)empty.hidden=n>0;
  });
 });

 var up=document.getElementById('up');
 function vis(){up.classList.toggle('show',window.scrollY>300)}
 window.addEventListener('scroll',vis,{passive:true});vis();
 up.addEventListener('click',function(){window.scrollTo({top:0});});

 var cf=document.getElementById('cf');
 cf.addEventListener('submit',function(ev){
  ev.preventDefault();
  var n=document.getElementById('n').value.trim(),e=document.getElementById('e').value.trim(),m=document.getElementById('m').value.trim(),fb=document.getElementById('fb'),btn=cf.querySelector('button[type=submit]');
  function say(txt,ok){fb.textContent=txt;fb.style.color=ok?'#9fe3b5':'#ffb4a8'}
  if(!n||!/^\S+@\S+\.\S+$/.test(e)||!m){say('Renseignez votre nom, une adresse e-mail valide et un message.',false);return}
  var fd=new FormData();fd.append('name',n);fd.append('email',e);fd.append('message',m);fd.append('website',document.getElementById('website').value);
  btn.disabled=true;say('Envoi en cours…',true);
  fetch('contact.php',{method:'POST',body:fd})
   .then(function(r){return r.json()})
   .then(function(d){say(d.message,d.ok);if(d.ok)cf.reset()})
   .catch(function(){say('L’envoi a échoué. Vérifiez votre connexion et réessayez.',false)})
   .finally(function(){btn.disabled=false});
 });
})();
