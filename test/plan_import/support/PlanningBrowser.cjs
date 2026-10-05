'use strict';
// Chrome DevTools Protocol: navegador real, sin dependencias npm ni mocks de API.
const {spawn}=require('node:child_process');
const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const delay=ms=>new Promise(resolve=>setTimeout(resolve,ms));
async function openBrowser(){
  const binary=process.env.CHROME_PATH||'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
  if(!fs.existsSync(binary))throw Error('No se encontró el navegador configurado.');
  const directory=fs.mkdtempSync(path.join(os.tmpdir(),'sisco-planning-browser-'));
  const cleanDirectory=()=>{
    const resolved=path.resolve(directory);
    if(path.dirname(resolved)!==path.resolve(os.tmpdir())||!path.basename(resolved).startsWith('sisco-planning-browser-'))throw Error('Perfil temporal fuera del alcance esperado.');
    fs.rmSync(resolved,{recursive:true,force:true,maxRetries:10,retryDelay:100});
  };
  const port=Number(process.env.SISCO_BROWSER_PORT||9338);
  const processBrowser=spawn(binary,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check',`--remote-debugging-port=${port}`,`--user-data-dir=${directory}`,'about:blank'],{stdio:'ignore',windowsHide:true});
  let socket;
  try{
    let target;
    for(let i=0;i<100;i++){
      try{const targets=await(await fetch(`http://127.0.0.1:${port}/json/list`)).json();target=targets.find(t=>t.type==='page');if(target)break;}catch{}
      await delay(100);
    }
    if(!target)throw Error('No inició el navegador.');
    socket=new WebSocket(target.webSocketDebuggerUrl);
    await new Promise((resolve,reject)=>{socket.addEventListener('open',resolve,{once:true});socket.addEventListener('error',reject,{once:true});});
    let sequence=0;const pending=new Map(),errors=[];
    socket.addEventListener('message',event=>{
      const response=JSON.parse(event.data);
      if(response.id){const entry=pending.get(response.id);if(!entry)return;pending.delete(response.id);clearTimeout(entry.timer);response.error?entry.reject(Error(response.error.message)):entry.resolve(response.result);}
      if(response.method==='Runtime.exceptionThrown')errors.push(response.params.exceptionDetails.text);
    });
    const command=(method,params={})=>new Promise((resolve,reject)=>{
      const id=++sequence;const timer=setTimeout(()=>{pending.delete(id);reject(Error(`Timeout CDP ${method}`));},20000);
      pending.set(id,{resolve,reject,timer});socket.send(JSON.stringify({id,method,params}));
    });
    const evaluate=async expression=>{
      const result=await command('Runtime.evaluate',{expression,awaitPromise:true,returnByValue:true,userGesture:true});
      if(result.exceptionDetails)throw Error(result.exceptionDetails.exception?.description||result.exceptionDetails.text);
      return result.result.value;
    };
    const wait=async expression=>{for(let i=0;i<150;i++){try{if(await evaluate(expression))return;}catch{}await delay(100);}throw Error('Timeout esperando: '+expression);};
    await command('Page.enable');await command('Runtime.enable');
    await command('Page.setInterceptFileChooserDialog',{enabled:false});
    socket.addEventListener('message',event=>{const response=JSON.parse(event.data);if(response.method==='Page.javascriptDialogOpening')command('Page.handleJavaScriptDialog',{accept:true}).catch(()=>{});});
    const navigate=async url=>{await command('Page.navigate',{url});await wait(`location.href===${JSON.stringify(url)} && document.readyState==='complete'`);};
    return {evaluate,wait,navigate,errors,async close(){socket.close();processBrowser.kill();await delay(300);cleanDirectory();}};
  }catch(error){socket?.close();processBrowser.kill();await delay(300);cleanDirectory();throw error;}
}
module.exports={openBrowser};
