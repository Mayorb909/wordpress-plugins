(()=>{'use strict';
  const align=(root)=>{
    root.style.setProperty('--krc-viewport-shift','0px');
    root.style.setProperty('--krc-viewport-width',document.documentElement.clientWidth+'px');
    root.style.setProperty('--krc-viewport-shift',-root.getBoundingClientRect().left+'px');
  };
  const start=()=>document.querySelectorAll('.krc-careers').forEach(root=>{
    align(root);
    window.addEventListener('resize',()=>align(root),{passive:true});
  });
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start,{once:true});else start();
})();
