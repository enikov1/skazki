const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const prefix=fs.readFileSync('src/js/app.js','utf8').split("document.addEventListener('click'")[0];
function context(fetch){const ctx={fetch,AbortController,setTimeout,clearTimeout};vm.createContext(ctx);vm.runInContext(prefix,ctx);return ctx}
test('network errors have an actionable Russian message',async()=>{const ctx=context(async()=>{throw new TypeError('Failed to fetch')});await assert.rejects(ctx.siteFetch('/test'),/Проверьте интернет/)});
test('superseded requests retain AbortError so they can be ignored',async()=>{const cancel=new AbortController();cancel.abort();const ctx=context(async(_,options)=>{options.signal.throwIfAborted()});await assert.rejects(ctx.siteFetch('/test',{signal:cancel.signal}),{name:'AbortError'})});
test('HTML error responses are not exposed as parser errors',async()=>{const ctx=context();await assert.rejects(ctx.readJsonResponse({status:419,headers:{get:()=> 'text/html'}}),/Сеанс завершился/)});
test('unexpected JSON shapes have a readable error',async()=>{const ctx=context();for(const data of [null,[],42])await assert.rejects(ctx.readJsonResponse({headers:{get:()=> 'application/json'},json:async()=>data}),/Не удалось прочитать ответ/)});
test('valid validation payloads remain available to field rendering',async()=>{const ctx=context();const data={errors:{email:['Введите почту']}};assert.equal(await ctx.readJsonResponse({headers:{get:()=> 'application/json'},json:async()=>data}),data)});
